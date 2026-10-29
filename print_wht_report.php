<?php
include_once 'php_action/db_connect.php';
include_once 'includes/functions.php';

// Company Details
$get_company = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company ORDER BY id DESC LIMIT 1"));
$comp_name = !empty($get_company['name']) ? $get_company['name'] : 'BA TRADERS';
$comp_strn = !empty($get_company['s_tax_no']) ? $get_company['s_tax_no'] : '32-77-8763-934-54';
$comp_ntn = !empty($get_company['ntn_no']) ? $get_company['ntn_no'] : 'A322716-3';
$comp_address = !empty($get_company['address']) ? $get_company['address'] : 'Shop # 621, AL Madina Commercial Market, Near Hamza Kanta, Oil Depot Road, Tata Market, Faisalabad';
$comp_phone = !empty($get_company['company_phone']) ? $get_company['company_phone'] : '';

// Selected Month, Year, and Customer Handling
$selected_ym = date('Y-m');
if (!empty($_REQUEST['month_year']) && preg_match('/^\d{4}-\d{2}$/', trim($_REQUEST['month_year']))) {
    $selected_ym = trim($_REQUEST['month_year']);
} elseif (!empty($_REQUEST['year']) && !empty($_REQUEST['month'])) {
    $y = intval($_REQUEST['year']);
    $m = trim($_REQUEST['month']);
    $m_num = is_numeric($m) ? str_pad(intval($m), 2, '0', STR_PAD_LEFT) : date('m', strtotime("$m 1, 2000"));
    $selected_ym = sprintf('%04d-%02d', $y, $m_num);
}

list($selected_year, $selected_month_num) = explode('-', $selected_ym);
$start_date = "$selected_year-$selected_month_num-01";
$end_date = date("Y-m-t", strtotime($start_date));
$month_name_short = date("M", strtotime($start_date));
$month_name_full = date("F", strtotime($start_date));

// Customer filter
$customer_id = (isset($_REQUEST['customer_id']) && $_REQUEST['customer_id'] !== '' && $_REQUEST['customer_id'] !== 'all') ? mysqli_real_escape_string($dbc, $_REQUEST['customer_id']) : '';

$fetchCustomer = null;
if (!empty($customer_id)) {
    $fetchCustomer = fetchRecord($dbc, 'customers', 'customer_id', $customer_id);
}

// Fetch Detailed WHT Records
$where = "CAST(REPLACE(COALESCE(vi.tax_amount, '0'), ',', '') AS DECIMAL(15,2)) > 0
  AND (
    (COALESCE(v.voucher_date, '') != '' AND v.voucher_date >= '$start_date' AND v.voucher_date <= '$end_date')
    OR
    ((v.voucher_date IS NULL OR v.voucher_date = '') AND DATE(v.timestamp) >= '$start_date' AND DATE(v.timestamp) <= '$end_date')
  )";
if (!empty($customer_id)) {
    $where .= " AND (v.customer_id1 = '$customer_id' OR ts.customer_id = '$customer_id' OR inv.customer_id = '$customer_id')";
}

$sql = "SELECT vi.*, v.voucher_date, v.voucher_hint, v.td_check_no, v.voucher_bank_name, v.check_type, v.timestamp,
         COALESCE(NULLIF(v.customer_id1, ''), ts.customer_id, inv.customer_id) AS matched_customer_id,
         COALESCE(cv.customer_name, cts.customer_name, cinv.customer_name, 'Unknown Customer') AS customer_name,
         COALESCE(cv.customer_phone, cts.customer_phone, cinv.customer_phone, '') AS customer_phone,
         COALESCE(cv.customer_ntn, cts.customer_ntn, cinv.customer_ntn, '') AS customer_ntn,
         COALESCE(cv.customer_strn, cts.customer_strn, cinv.customer_strn, '') AS customer_strn
  FROM voucher_invoices vi
  JOIN vouchers v ON vi.voucher_id = v.voucher_id
  LEFT JOIN customers cv ON cv.customer_id = v.customer_id1
  LEFT JOIN tax_sales ts ON (vi.invoice_id = ts.invoice_no OR vi.invoice_id = ts.tax_sale_id)
  LEFT JOIN customers cts ON cts.customer_id = ts.customer_id
  LEFT JOIN invoices inv ON inv.invoice_id = vi.invoice_id
  LEFT JOIN customers cinv ON cinv.customer_id = inv.customer_id
  WHERE $where
  ORDER BY v.voucher_date ASC, vi.id ASC";

$result = mysqli_query($dbc, $sql);

$transactions = [];
$customer_summary = [];
$total_wht = 0.0;
$total_gross = 0.0;
$total_net = 0.0;
$distinct_vouchers = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $g_amt = floatval(str_replace(',', '', $row['invoice_amount'] ?? 0));
        $w_amt = floatval(str_replace(',', '', $row['tax_amount'] ?? 0));
        $n_amt = floatval(str_replace(',', '', $row['received_amount'] ?? 0));

        $total_gross += $g_amt;
        $total_wht += $w_amt;
        $total_net += $n_amt;
        $distinct_vouchers[$row['voucher_id']] = true;

        $c_id = $row['matched_customer_id'] ?: '0';
        if (!isset($customer_summary[$c_id])) {
            $customer_summary[$c_id] = [
                'customer_id' => $c_id,
                'customer_name' => $row['customer_name'],
                'customer_phone' => $row['customer_phone'],
                'customer_ntn' => $row['customer_ntn'],
                'customer_strn' => $row['customer_strn'],
                'vouchers' => [],
                'invoices_count' => 0,
                'total_gross' => 0.0,
                'total_wht' => 0.0,
                'total_net' => 0.0,
            ];
        }
        $customer_summary[$c_id]['vouchers'][$row['voucher_id']] = true;
        $customer_summary[$c_id]['invoices_count']++;
        $customer_summary[$c_id]['total_gross'] += $g_amt;
        $customer_summary[$c_id]['total_wht'] += $w_amt;
        $customer_summary[$c_id]['total_net'] += $n_amt;

        $transactions[] = $row;
    }
}

$effective_wht_rate = ($total_gross > 0) ? (($total_wht / $total_gross) * 100) : 0.0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>WHT Report - <?=htmlspecialchars($month_name_full)?> <?=$selected_year?> - <?=htmlspecialchars($comp_name)?></title>
  <link rel="icon" href="img/logo/<?=$get_company['logo']?>" type="image/gif" sizes="16x16">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/fontawesome.min.css">
  <link rel="stylesheet" href="css/feather.css">
  <style>
    body {
      background-color: #f7f9fa;
      color: #111;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      font-size: 12px;
      margin: 0;
      padding: 20px;
    }
    .print-container {
      background: #ffffff;
      max-width: 1050px;
      margin: 0 auto;
      padding: 30px 35px;
      border: 1px solid #dee2e6;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .company-name {
      color: #930027;
      font-weight: 800;
      font-size: 26px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      margin-bottom: 2px;
    }
    .report-title-badge {
      background: #930027;
      color: #fff;
      padding: 6px 14px;
      font-size: 14px;
      font-weight: 800;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      display: inline-block;
      margin-top: 5px;
    }
    .summary-box {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 12px 18px;
      margin-bottom: 15px;
    }
    .summary-kpi {
      text-align: center;
      padding: 6px 10px;
      border-right: 1px solid #e1e4e8;
    }
    .summary-kpi:last-child {
      border-right: none;
    }
    .summary-kpi .kpi-label {
      font-size: 10.5px;
      font-weight: 700;
      color: #6c757d;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .summary-kpi .kpi-num {
      font-size: 18px;
      font-weight: 800;
      line-height: 1.2;
      margin-top: 2px;
    }
    .print-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .print-table th {
      background: #2b3035 !important;
      color: #ffffff !important;
      font-weight: 700;
      font-size: 11px;
      border: 1px solid #454d55;
      padding: 6px 6px;
      text-align: center;
      vertical-align: middle;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .print-table td {
      border: 1px solid #dee2e6;
      padding: 5px 6px;
      font-size: 11px;
      vertical-align: middle;
      color: #111;
    }
    .print-table tfoot td {
      background: #f1f3f5 !important;
      font-weight: 700;
      border: 1px solid #ced4da;
      padding: 6px 6px;
      font-size: 11.5px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .btn-admin {
      background-color: #930027;
      color: #ffffff;
      border: none;
      font-weight: 700;
    }
    .btn-admin:hover {
      background-color: #75001f;
      color: #ffffff;
    }

    @page {
      size: auto;
      margin: 10mm 18mm 10mm 18mm !important;
    }

    @media print {
      body {
        background: #ffffff;
        padding: 0 15mm !important;
        margin: 0 !important;
        font-size: 11px;
        box-sizing: border-box !important;
      }
      .print-container {
        border: none;
        box-shadow: none;
        padding: 5mm 0 !important;
        max-width: 100%;
        width: 100%;
        box-sizing: border-box !important;
      }
      .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
      }
      .row > [class*="col"] {
        padding-left: 4px !important;
        padding-right: 4px !important;
      }
      .d-print-none {
        display: none !important;
      }
      .print-table th {
        background: #222 !important;
        color: #fff !important;
        border: 1px solid #000 !important;
      }
      .print-table td, .print-table tfoot td {
        border: 1px solid #000 !important;
      }
      .summary-box {
        border: 1px solid #000 !important;
        background: #fff !important;
      }
      .summary-kpi {
        border-right: 1px solid #999;
      }
    }
  </style>
</head>
<body>

  <!-- Screen Action Bar (Hidden on Print) -->
  <div class="d-print-none mb-3 text-center">
    <div class="btn-group shadow-sm">
      <button onclick="window.print();" class="btn btn-admin px-4 font-weight-bold">
        <i class="fe fe-printer mr-1"></i> Print / Save as PDF
      </button>
      <a href="wht_report.php?month_year=<?=$selected_ym?><?=!empty($customer_id) ? '&customer_id=' . $customer_id : ''?>" class="btn btn-dark">
        <i class="fe fe-arrow-left mr-1"></i> Back to Interactive Report
      </a>
      <a href="wht_report.php?month_year=<?=$selected_ym?><?=!empty($customer_id) ? '&customer_id=' . $customer_id : ''?>&export=csv" class="btn btn-success">
        <i class="fe fe-download mr-1"></i> Export CSV
      </a>
      <button onclick="window.close();" class="btn btn-secondary">
        <i class="fe fe-x mr-1"></i> Close
      </button>
    </div>
  </div>

  <div class="print-container">

    <!-- Unified Centered Header -->
    <?php
    $report_title = "MONTHLY WITHHOLDING TAX (WHT) REPORT";
    $report_customer = $fetchCustomer;
    $report_duration = strtoupper($month_name_full) . " " . $selected_year . " (" . date('d-M-Y', strtotime($start_date)) . " to " . date('d-M-Y', strtotime($end_date)) . ")";
    include 'includes/report_header.php';
    ?>

    <!-- Executive Summary KPI Strip -->
    <div class="summary-box">
      <div class="row">
        <div class="col-3 summary-kpi">
          <div class="kpi-label">Total WHT Deducted</div>
          <div class="kpi-num text-danger">Rs. <?=number_format($total_wht, 2)?></div>
        </div>
        <div class="col-3 summary-kpi">
          <div class="kpi-label">Gross Invoiced Settled</div>
          <div class="kpi-num text-primary">Rs. <?=number_format($total_gross, 2)?></div>
        </div>
        <div class="col-3 summary-kpi">
          <div class="kpi-label">Net Received (After WHT)</div>
          <div class="kpi-num text-success">Rs. <?=number_format($total_net, 2)?></div>
        </div>
        <div class="col-3 summary-kpi">
          <div class="kpi-label">Effective WHT Rate</div>
          <div class="kpi-num text-dark"><?=number_format($effective_wht_rate, 2)?>%</div>
        </div>
      </div>
    </div>

    <!-- Customer-Wise Breakdown (When Overall) -->
    <?php if (empty($customer_id) && !empty($customer_summary)): ?>
      <div style="font-weight: 700; font-size: 12px; margin-bottom: 4px; text-transform: uppercase; color: #333;">
        1. Customer-Wise WHT Summary
      </div>
      <table class="print-table">
        <thead>
          <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 28%; text-align: left;">Customer Name</th>
            <th style="width: 14%;">NTN</th>
            <th style="width: 8%;">Vouchers</th>
            <th style="width: 8%;">Invoices</th>
            <th style="width: 13%; text-align: right;">Gross (Rs)</th>
            <th style="width: 8%;">WHT %</th>
            <th style="width: 15%; text-align: right;">WHT Deducted (Rs)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $csr = 0;
          foreach ($customer_summary as $cid => $cs):
            $csr++;
            $crate = ($cs['total_gross'] > 0) ? (($cs['total_wht'] / $cs['total_gross']) * 100) : 0;
          ?>
            <tr>
              <td style="text-align: center; font-weight: 700;"><?=$csr?></td>
              <td><strong><?=htmlspecialchars($cs['customer_name'])?></strong></td>
              <td style="text-align: center;"><?=htmlspecialchars($cs['customer_ntn'] ?: '-')?></td>
              <td style="text-align: center;"><?=count($cs['vouchers'])?></td>
              <td style="text-align: center;"><?=count($cs['vouchers'])?></td>
              <td style="text-align: right;"><?=number_format($cs['total_gross'], 2)?></td>
              <td style="text-align: center;"><?=number_format($crate, 2)?>%</td>
              <td style="text-align: right; font-weight: 700; color: #930027;"><?=number_format($cs['total_wht'], 2)?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="5" style="text-align: right; font-weight: 700;">TOTALS:</td>
            <td style="text-align: right; font-weight: 700;"><?=number_format($total_gross, 2)?></td>
            <td style="text-align: center; font-weight: 700;"><?=number_format($effective_wht_rate, 2)?>%</td>
            <td style="text-align: right; font-weight: 700; color: #930027;"><?=number_format($total_wht, 2)?></td>
          </tr>
        </tfoot>
      </table>
    <?php endif; ?>

    <!-- Detailed Invoices / Vouchers WHT Transactions Table -->
    <div style="font-weight: 700; font-size: 12px; margin-bottom: 4px; margin-top: 10px; text-transform: uppercase; color: #333;">
      <?=empty($customer_id) ? '2. Detailed Invoice Settlements Breakdown' : 'Detailed Invoice Settlements Breakdown'?>
    </div>
    <table class="print-table">
      <thead>
        <tr>
          <th style="width: 4%;">#</th>
          <th style="width: 10%;">Date</th>
          <th style="width: 8%;">VOU #</th>
          <th style="width: 20%; text-align: left;">Customer Name</th>
          <th style="width: 9%;">Invoice #</th>
          <th style="width: 9%;">Party PO</th>
          <th style="width: 13%; text-align: right;">Gross (Rs)</th>
          <th style="width: 7%;">WHT %</th>
          <th style="width: 13%; text-align: right;">WHT (Rs)</th>
          <th style="width: 7%;">Net Rec (Rs)</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (!empty($transactions)):
          $tsr = 0;
          foreach ($transactions as $t):
            $tsr++;
            $v_date = !empty($t['voucher_date']) ? date('d-M-Y', strtotime($t['voucher_date'])) : date('d-M-Y', strtotime($t['timestamp']));
            $g = floatval(str_replace(',', '', $t['invoice_amount'] ?? 0));
            $w = floatval(str_replace(',', '', $t['tax_amount'] ?? 0));
            $n = floatval(str_replace(',', '', $t['received_amount'] ?? 0));
            $p = floatval(str_replace(',', '', $t['tax_percent'] ?? 0));
        ?>
          <tr>
            <td style="text-align: center; font-weight: 700;"><?=$tsr?></td>
            <td style="text-align: center;"><?=$v_date?></td>
            <td style="text-align: center; font-weight: 700;">#<?=$t['voucher_id']?></td>
            <td>
              <strong><?=htmlspecialchars($t['customer_name'])?></strong>
              <?php if (!empty($t['customer_ntn'])): ?>
                <span class="text-muted" style="font-size: 10px;">(NTN: <?=htmlspecialchars($t['customer_ntn'])?>)</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;">#<?=htmlspecialchars($t['invoice_id'])?></td>
            <td style="text-align: center;"><?=htmlspecialchars($t['party_po'] ?: '-')?></td>
            <td style="text-align: right;"><?=number_format($g, 2)?></td>
            <td style="text-align: center;"><?=number_format($p, 2)?>%</td>
            <td style="text-align: right; font-weight: 700; color: #930027;"><?=number_format($w, 2)?></td>
            <td style="text-align: right;"><?=number_format($n, 2)?></td>
          </tr>
        <?php
          endforeach;
        else:
        ?>
          <tr>
            <td colspan="10" style="text-align: center; padding: 25px; color: #777;">
              No Withholding Tax (WHT) records found for the selected period.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($transactions)): ?>
        <tfoot>
          <tr>
            <td colspan="6" style="text-align: right; font-weight: 700;">GRAND TOTALS:</td>
            <td style="text-align: right; font-weight: 700;"><?=number_format($total_gross, 2)?></td>
            <td style="text-align: center; font-weight: 700;"><?=number_format($effective_wht_rate, 2)?>%</td>
            <td style="text-align: right; font-weight: 700; color: #930027;"><?=number_format($total_wht, 2)?></td>
            <td style="text-align: right; font-weight: 700;"><?=number_format($total_net, 2)?></td>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>

    <!-- Signature Block -->
    <div style="margin-top: 60px; padding-top: 20px;">
      <div class="row text-center">
        <div class="col-4">
          <div style="border-top: 1px solid #000; margin: 0 30px; padding-top: 6px; font-weight: 700;">
            Prepared By
          </div>
        </div>
        <div class="col-4">
          <div style="border-top: 1px solid #000; margin: 0 30px; padding-top: 6px; font-weight: 700;">
            Verified / Checked By
          </div>
        </div>
        <div class="col-4">
          <div style="border-top: 1px solid #000; margin: 0 30px; padding-top: 6px; font-weight: 700;">
            Authorized Signature
          </div>
        </div>
      </div>
    </div>

  </div>

</body>
</html>
