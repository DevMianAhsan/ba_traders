<?php
include_once 'php_action/core.php';

// Company Details
$company_id = !empty($get_company['id']) ? intval($get_company['id']) : 5;
$comp_res = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company WHERE id = '$company_id'"));
if ($comp_res) {
    $get_company = $comp_res;
}
$comp_name = !empty($get_company['name']) ? $get_company['name'] : 'BA TRADERS';
$comp_strn = !empty($get_company['s_tax_no']) ? $get_company['s_tax_no'] : '32-77-8763-934-54';
$comp_ntn = !empty($get_company['ntn_no']) ? $get_company['ntn_no'] : 'A322716-3';
$comp_address = !empty($get_company['address']) ? $get_company['address'] : 'Shop # 621, AL Madina Commercial Market, Near Hamza Kanta, Oil Depot Road, Tata Market, Faisalabad';
$comp_phone = !empty($get_company['company_phone']) ? $get_company['company_phone'] : '';

// -----------------------------------------------------------------------------
// Selected Month, Year, and Customer Handling
// -----------------------------------------------------------------------------
$selected_ym = date('Y-m');
if (!empty($_REQUEST['month_year']) && preg_match('/^\d{4}-\d{2}$/', trim($_REQUEST['month_year']))) {
    $selected_ym = trim($_REQUEST['month_year']);
} elseif (!empty($_REQUEST['year']) && !empty($_REQUEST['month'])) {
    $y = intval($_REQUEST['year']);
    $m = trim($_REQUEST['month']);
    if (is_numeric($m)) {
        $m_num = str_pad(intval($m), 2, '0', STR_PAD_LEFT);
    } else {
        $m_num = date('m', strtotime("$m 1, 2000"));
    }
    $selected_ym = sprintf('%04d-%02d', $y, $m_num);
}

list($selected_year, $selected_month_num) = explode('-', $selected_ym);
$start_date = "$selected_year-$selected_month_num-01";
$end_date = date("Y-m-t", strtotime($start_date));
$month_name_short = date("M", strtotime($start_date));
$month_name_full = date("F", strtotime($start_date));

$prev_ym = date('Y-m', strtotime("$start_date -1 month"));
$next_ym = date('Y-m', strtotime("$start_date +1 month"));

// Customer filter
$customer_id = (isset($_REQUEST['customer_id']) && $_REQUEST['customer_id'] !== '' && $_REQUEST['customer_id'] !== 'all') ? mysqli_real_escape_string($dbc, $_REQUEST['customer_id']) : '';

$fetchCustomer = null;
if (!empty($customer_id)) {
    $fetchCustomer = fetchRecord($dbc, 'customers', 'customer_id', $customer_id);
}

// -----------------------------------------------------------------------------
// CSV Export Handler
// -----------------------------------------------------------------------------
if (isset($_REQUEST['export']) && $_REQUEST['export'] === 'csv') {
    $filename = "WHT_Report_" . $selected_ym . (!empty($customer_id) ? "_Cust_" . $customer_id : "_Overall") . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');

    // Header metadata
    fputcsv($out, [$comp_name]);
    fputcsv($out, ["MONTHLY WITHHOLDING TAX (WHT) REPORT - " . strtoupper($month_name_full) . " " . $selected_year]);
    fputcsv($out, ["Scope:", !empty($fetchCustomer) ? $fetchCustomer['customer_name'] . " (NTN: " . ($fetchCustomer['customer_ntn'] ?: 'N/A') . ")" : "All Customers (Overall)"]);
    fputcsv($out, ["Period:", $start_date . " to " . $end_date]);
    fputcsv($out, ["Generated On:", date('d-M-Y h:i A')]);
    fputcsv($out, []); // blank line

    // Column headers
    fputcsv($out, ['Sr #', 'Voucher Date', 'Voucher #', 'Customer Name', 'Customer NTN', 'Invoice #', 'Party PO', 'Gross Amount (Rs)', 'WHT Rate (%)', 'WHT Amount (Rs)', 'Net Received (Rs)', 'Cheque #', 'Bank Name', 'Remarks']);

    $where = "CAST(REPLACE(COALESCE(vi.tax_amount, '0'), ',', '') AS DECIMAL(15,2)) > 0
      AND (
        (COALESCE(v.voucher_date, '') != '' AND v.voucher_date >= '$start_date' AND v.voucher_date <= '$end_date')
        OR
        ((v.voucher_date IS NULL OR v.voucher_date = '') AND DATE(v.timestamp) >= '$start_date' AND DATE(v.timestamp) <= '$end_date')
      )";
    if (!empty($customer_id)) {
        $where .= " AND (v.customer_id1 = '$customer_id' OR ts.customer_id = '$customer_id' OR inv.customer_id = '$customer_id')";
    }

    $csv_sql = "SELECT vi.*, v.voucher_date, v.voucher_hint, v.td_check_no, v.voucher_bank_name, v.check_type, v.timestamp,
             COALESCE(NULLIF(v.customer_id1, ''), ts.customer_id, inv.customer_id) AS matched_customer_id,
             COALESCE(cv.customer_name, cts.customer_name, cinv.customer_name, 'Unknown Customer') AS customer_name,
             COALESCE(cv.customer_ntn, cts.customer_ntn, cinv.customer_ntn, '') AS customer_ntn
      FROM voucher_invoices vi
      JOIN vouchers v ON vi.voucher_id = v.voucher_id
      LEFT JOIN customers cv ON cv.customer_id = v.customer_id1
      LEFT JOIN tax_sales ts ON (vi.invoice_id = ts.invoice_no OR vi.invoice_id = ts.tax_sale_id)
      LEFT JOIN customers cts ON cts.customer_id = ts.customer_id
      LEFT JOIN invoices inv ON inv.invoice_id = vi.invoice_id
      LEFT JOIN customers cinv ON cinv.customer_id = inv.customer_id
      WHERE $where
      ORDER BY v.voucher_date ASC, vi.id ASC";

    $csv_q = mysqli_query($dbc, $csv_sql);
    $sr = 0;
    $sum_gross = 0;
    $sum_wht = 0;
    $sum_net = 0;

    while ($r = mysqli_fetch_assoc($csv_q)) {
        $sr++;
        $g = floatval(str_replace(',', '', $r['invoice_amount'] ?? 0));
        $w = floatval(str_replace(',', '', $r['tax_amount'] ?? 0));
        $n = floatval(str_replace(',', '', $r['received_amount'] ?? 0));
        $sum_gross += $g;
        $sum_wht += $w;
        $sum_net += $n;
        $v_date = !empty($r['voucher_date']) ? date('d-M-Y', strtotime($r['voucher_date'])) : date('d-M-Y', strtotime($r['timestamp']));

        fputcsv($out, [
            $sr,
            $v_date,
            $r['voucher_id'],
            $r['customer_name'],
            $r['customer_ntn'],
            $r['invoice_id'],
            $r['party_po'] ?: '',
            $g,
            $r['tax_percent'] ?: '0',
            $w,
            $n,
            $r['td_check_no'] ?: '',
            $r['voucher_bank_name'] ?: '',
            $r['voucher_hint'] ?: ''
        ]);
    }

    // Grand totals
    fputcsv($out, []);
    fputcsv($out, ['TOTALS', '', '', '', '', '', '', $sum_gross, '', $sum_wht, $sum_net, '', '', '']);
    fclose($out);
    exit;
}

// -----------------------------------------------------------------------------
// Fetch Detailed WHT Records for Selected Month & Customer
// -----------------------------------------------------------------------------
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
        $t_pct = floatval(str_replace(',', '', $row['tax_percent'] ?? 0));

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

// -----------------------------------------------------------------------------
// 12 Months Summary for Selected Year (Trend comparison)
// -----------------------------------------------------------------------------
$all_months = [
    '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
    '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
    '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
];

$annual_monthly_data = [];
foreach ($all_months as $m_num => $m_name) {
    $annual_monthly_data[$m_num] = [
        'name' => $m_name,
        'ym' => "$selected_year-$m_num",
        'gross' => 0.0,
        'wht' => 0.0,
        'net' => 0.0,
        'count' => 0
    ];
}

$ann_where = "CAST(REPLACE(COALESCE(vi.tax_amount, '0'), ',', '') AS DECIMAL(15,2)) > 0
  AND YEAR(COALESCE(NULLIF(v.voucher_date, ''), v.timestamp)) = '$selected_year'";
if (!empty($customer_id)) {
    $ann_where .= " AND (v.customer_id1 = '$customer_id' OR ts.customer_id = '$customer_id' OR inv.customer_id = '$customer_id')";
}

$ann_sql = "SELECT 
    DATE_FORMAT(COALESCE(NULLIF(v.voucher_date, ''), v.timestamp), '%m') AS m_num,
    COUNT(vi.id) AS inv_count,
    SUM(CAST(REPLACE(COALESCE(vi.invoice_amount, '0'), ',', '') AS DECIMAL(15,2))) AS sum_gross,
    SUM(CAST(REPLACE(COALESCE(vi.tax_amount, '0'), ',', '') AS DECIMAL(15,2))) AS sum_wht,
    SUM(CAST(REPLACE(COALESCE(vi.received_amount, '0'), ',', '') AS DECIMAL(15,2))) AS sum_net
  FROM voucher_invoices vi
  JOIN vouchers v ON vi.voucher_id = v.voucher_id
  LEFT JOIN tax_sales ts ON (vi.invoice_id = ts.invoice_no OR vi.invoice_id = ts.tax_sale_id)
  LEFT JOIN invoices inv ON inv.invoice_id = vi.invoice_id
  WHERE $ann_where
  GROUP BY m_num";

$q_ann = mysqli_query($dbc, $ann_sql);
if ($q_ann) {
    while ($ar = mysqli_fetch_assoc($q_ann)) {
        $m_k = $ar['m_num'];
        if (isset($annual_monthly_data[$m_k])) {
            $annual_monthly_data[$m_k]['gross'] = floatval($ar['sum_gross']);
            $annual_monthly_data[$m_k]['wht'] = floatval($ar['sum_wht']);
            $annual_monthly_data[$m_k]['net'] = floatval($ar['sum_net']);
            $annual_monthly_data[$m_k]['count'] = intval($ar['inv_count']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  /* KPI Metric Cards */
  .kpi-card {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    background: #ffffff;
    padding: 16px 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    height: 100%;
    position: relative;
    transition: all 0.2s ease-in-out;
  }
  .kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
  }
  .kpi-card .kpi-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #6c757d;
    margin-bottom: 4px;
  }
  .kpi-card .kpi-value {
    font-size: 26px;
    font-weight: 800;
    line-height: 1.2;
  }
  .kpi-card .kpi-sub {
    font-size: 11.5px;
    color: #6c757d;
    margin-top: 6px;
  }
  .kpi-accent-wht { border-left: 5px solid #dc3545; }
  .kpi-accent-gross { border-left: 5px solid #007bff; }
  .kpi-accent-net { border-left: 5px solid #28a745; }
  .kpi-accent-rate { border-left: 5px solid #6f42c1; }
  .kpi-accent-count { border-left: 5px solid #fd7e14; }

  /* Month Pills Group */
  .month-pill-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  .month-pill {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    color: #495057;
    background: #e9ecef;
    text-decoration: none !important;
    border: 1px solid #ced4da;
    transition: all 0.15s ease;
  }
  .month-pill:hover {
    background: #dee2e6;
    color: #212529;
  }
  .month-pill.active {
    background: #930027 !important;
    color: #ffffff !important;
    border-color: #930027 !important;
    box-shadow: 0 2px 6px rgba(147, 0, 39, 0.35);
  }

  /* Table styling */
  .report-table thead th {
    background-color: #2b3035 !important;
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #454d55;
    padding: 9px 8px;
  }
  .report-table tbody td {
    font-size: 13px;
    vertical-align: middle;
    border: 1px solid #dee2e6;
    padding: 8px 10px;
    color: #212529;
  }
  .report-table tbody tr:nth-of-type(even) {
    background-color: #fafbfc;
  }
  .report-table tbody tr:hover {
    background-color: #f1f4f8;
  }
  .report-table tfoot td {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    font-weight: 700;
    padding: 10px 10px;
  }

  /* Print Margins & Styles */
  @page {
    size: auto;
    margin: 10mm 18mm 10mm 18mm !important;
  }

  @media print {
    .d-print-none, 
    .navbar, 
    .navbar-slide, 
    .toggle-sidebar, 
    .sidebar, 
    #header, 
    footer, 
    .btn, 
    .breadcrumb, 
    form, 
    .month-pills-row {
      display: none !important;
    }

    html, body {
      background: #fff !important;
      color: #000 !important;
      font-size: 11px !important;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
    }

    .wrapper, 
    main, 
    .main-content {
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
      box-shadow: none !important;
      background: #fff !important;
      width: 100% !important;
    }

    /* Print Page Container with Left and Right Margins */
    .container-fluid {
      padding-left: 15mm !important;
      padding-right: 15mm !important;
      padding-top: 5mm !important;
      padding-bottom: 5mm !important;
      margin: 0 auto !important;
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
    }

    .card, 
    .card-body {
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
      box-shadow: none !important;
      background: transparent !important;
      width: 100% !important;
    }

    /* Neutralize Bootstrap row negative margins in print */
    .row {
      margin-left: 0 !important;
      margin-right: 0 !important;
    }

    .row > [class*="col"] {
      padding-left: 4px !important;
      padding-right: 4px !important;
    }

    .print-header {
      display: block !important;
      margin-bottom: 15px !important;
      width: 100% !important;
    }

    .report-table {
      width: 100% !important;
      border-collapse: collapse !important;
      margin-top: 10px !important;
    }

    .report-table thead th {
      background-color: #222 !important;
      color: #fff !important;
      border: 1px solid #000 !important;
      font-size: 11px !important;
      padding: 5px 6px !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .report-table tbody td, 
    .report-table tfoot td {
      border: 1px solid #000 !important;
      font-size: 11px !important;
      padding: 5px 6px !important;
      color: #000 !important;
    }

    .kpi-card {
      border: 1px solid #aaa !important;
      box-shadow: none !important;
      padding: 8px 10px !important;
      break-inside: avoid;
    }

    .kpi-card .kpi-value {
      font-size: 18px !important;
    }

    .page-break {
      page-break-before: always;
    }

    .signature-section {
      display: block !important;
      margin-top: 40px !important;
      width: 100% !important;
    }
  }
  .print-only {
    display: none;
  }
  @media print {
    .print-only {
      display: block !important;
    }
  }
</style>

<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>
    <main role="main" class="main-content">
      <div class="container-fluid">

        <!-- Top Header & Action Controls -->
        <div class="card shadow mb-3 d-print-none">
          <div class="card-header card-bg py-2">
            <div class="row align-items-center">
              <div class="col-md-7 col-12">
                <h4 class="mb-0 text-white font-weight-bold">
                  <i class="fe fe-percent mr-2"></i>Monthly Withholding Tax (WHT) Report
                </h4>
                <small class="text-white-50">
                  Withholding Tax (Section 153) deductions on customer invoices & vouchers
                </small>
              </div>
              <div class="col-md-5 col-12 text-md-right mt-2 mt-md-0">
                <a href="print_wht_report.php?month_year=<?=$selected_ym?><?=!empty($customer_id) ? '&customer_id=' . $customer_id : ''?>" target="_blank" class="btn btn-sm btn-light text-dark font-weight-bold ml-1">
                  <i class="fe fe-printer mr-1"></i> Dedicated Print View
                </a>
                <button type="button" onclick="window.print();" class="btn btn-sm btn-light text-dark font-weight-bold ml-1">
                  <i class="fe fe-printer mr-1"></i> Quick Print
                </button>
              </div>
            </div>
          </div>

          <!-- Filter Toolbar -->
          <div class="card-body pb-2 pt-3">
            <form action="wht_report.php" method="GET" id="whtFilterForm">
              <div class="row align-items-end">

                <!-- Month Picker with Navigation Buttons -->
                <div class="form-group col-lg-4 col-md-5 col-sm-12 mb-2">
                  <label class="font-weight-bold mb-1">
                    <i class="fe fe-calendar text-danger mr-1"></i> Select Month <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <a href="wht_report.php?month_year=<?=$prev_ym?><?=!empty($customer_id) ? '&customer_id=' . $customer_id : ''?>" class="btn btn-outline-secondary" title="Previous Month (<?=date('M Y', strtotime($start_date . ' -1 month'))?>)">
                        <i class="fe fe-chevron-left"></i>
                      </a>
                    </div>
                    <input type="month" name="month_year" id="month_year" value="<?=$selected_ym?>" class="form-control text-center font-weight-bold" onchange="$('#whtFilterForm').submit();">
                    <div class="input-group-append">
                      <a href="wht_report.php?month_year=<?=$next_ym?><?=!empty($customer_id) ? '&customer_id=' . $customer_id : ''?>" class="btn btn-outline-secondary" title="Next Month (<?=date('M Y', strtotime($start_date . ' +1 month'))?>)">
                        <i class="fe fe-chevron-right"></i>
                      </a>
                      <a href="wht_report.php?month_year=<?=date('Y-m')?><?=!empty($customer_id) ? '&customer_id=' . $customer_id : ''?>" class="btn btn-outline-dark" title="Current Month (<?=date('M Y')?>)">
                        Current
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Customer Dropdown (Select specific or Overall) -->
                <div class="form-group col-lg-5 col-md-5 col-sm-12 mb-2">
                  <label class="font-weight-bold mb-1">
                    <i class="fe fe-user text-primary mr-1"></i> Customer Account
                    <small class="text-muted font-weight-normal">(Leave blank for Overall)</small>
                  </label>
                  <select name="customer_id" id="wht_customer_id" class="form-control select2 searchableSelect" onchange="$('#whtFilterForm').submit();">
                    <option value="">-- All Customers (Overall) --</option>
                    <?php
                    $q_cust = mysqli_query($dbc, "SELECT customer_id, customer_name, customer_phone, customer_ntn FROM customers WHERE customer_status = 1 AND customer_type = 'customer' ORDER BY customer_name ASC");
                    while ($rc = mysqli_fetch_assoc($q_cust)) {
                      $sel = ($customer_id == $rc['customer_id']) ? 'selected' : '';
                      $phone_str = !empty($rc['customer_phone']) ? " - " . htmlspecialchars($rc['customer_phone']) : "";
                      $ntn_str = !empty($rc['customer_ntn']) ? " [NTN: " . htmlspecialchars($rc['customer_ntn']) . "]" : "";
                      echo "<option value='{$rc['customer_id']}' {$sel}>" . htmlspecialchars($rc['customer_name']) . $phone_str . $ntn_str . "</option>";
                    }
                    ?>
                  </select>
                </div>

                <!-- Submit & Clear Buttons -->
                <div class="form-group col-lg-3 col-md-2 col-sm-12 mb-2 text-md-right">
                  <button type="submit" class="btn btn-admin mr-1">
                    <i class="fe fe-filter mr-1"></i> Filter
                  </button>
                  <a href="wht_report.php?month_year=<?=$selected_ym?>" class="btn btn-outline-secondary" title="Clear Customer Filter to view Overall">
                    <i class="fe fe-refresh-cw mr-1"></i> Reset
                  </a>
                </div>
              </div>

              <!-- Month Pills Navigation for Selected Year -->
              <div class="row mt-2 pt-2 border-top month-pills-row align-items-center">
                <div class="col-auto">
                  <span class="font-weight-bold text-muted small mr-2">
                    <i class="fe fe-calendar"></i> <?=$selected_year?> Months:
                  </span>
                </div>
                <div class="col">
                  <div class="month-pill-group">
                    <?php
                    foreach ($all_months as $m_code => $m_lbl):
                      $pill_ym = "$selected_year-$m_code";
                      $is_active = ($pill_ym === $selected_ym);
                      $has_data = isset($annual_monthly_data[$m_code]) && $annual_monthly_data[$m_code]['wht'] > 0;
                    ?>
                      <a href="wht_report.php?month_year=<?=$pill_ym?><?=!empty($customer_id) ? '&customer_id=' . $customer_id : ''?>" 
                         class="month-pill <?=$is_active ? 'active' : ''?>"
                         title="<?=date('F Y', strtotime("$selected_year-$m_code-01"))?> <?=$has_data ? '(WHT: Rs. ' . number_format($annual_monthly_data[$m_code]['wht']) . ')' : ''?>">
                        <?=$m_lbl?>
                        <?php if ($has_data && !$is_active): ?>
                          <span class="badge badge-danger p-1" style="font-size: 8px; vertical-align: middle; border-radius: 50%;">●</span>
                        <?php endif; ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Unified Centered Report Header -->
        <?php
        $report_title = "MONTHLY WITHHOLDING TAX (WHT) REPORT";
        $report_customer = $fetchCustomer;
        $report_duration = strtoupper($month_name_full) . " " . $selected_year . " (" . date('d-M-Y', strtotime($start_date)) . " to " . date('d-M-Y', strtotime($end_date)) . ")";
        include 'includes/report_header.php';
        ?>

        <!-- Customer Profile Info Box (Only shown if a customer is selected) -->
        <?php if (!empty($fetchCustomer)): ?>
          <div class="card shadow-sm border-left-danger mb-3 d-print-none" style="border-left: 5px solid #930027; background: #fffdfd;">
            <div class="card-body py-2 px-3">
              <div class="row align-items-center">
                <div class="col-md-8 col-12">
                  <span class="badge badge-danger text-uppercase font-weight-bold px-2 py-1 mr-2">Filtered Customer</span>
                  <span class="h5 font-weight-bold text-dark mb-0 align-middle"><?=htmlspecialchars($fetchCustomer['customer_name'])?></span>
                  <div class="text-muted small mt-1">
                    <?php if (!empty($fetchCustomer['customer_phone'])): ?>
                      <span class="mr-3"><i class="fe fe-phone"></i> <?=htmlspecialchars($fetchCustomer['customer_phone'])?></span>
                    <?php endif; ?>
                    <?php if (!empty($fetchCustomer['customer_ntn'])): ?>
                      <span class="mr-3"><i class="fe fe-file-text"></i> <strong>NTN:</strong> <?=htmlspecialchars($fetchCustomer['customer_ntn'])?></span>
                    <?php endif; ?>
                    <?php if (!empty($fetchCustomer['customer_strn'])): ?>
                      <span class="mr-3"><i class="fe fe-hash"></i> <strong>STRN:</strong> <?=htmlspecialchars($fetchCustomer['customer_strn'])?></span>
                    <?php endif; ?>
                    <?php if (!empty($fetchCustomer['customer_address'])): ?>
                      <span><i class="fe fe-map-pin"></i> <?=htmlspecialchars($fetchCustomer['customer_address'])?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="col-md-4 col-12 text-md-right mt-2 mt-md-0">
                  <a href="wht_report.php?month_year=<?=$selected_ym?>" class="btn btn-sm btn-outline-danger">
                    <i class="fe fe-x mr-1"></i> Switch to Overall View
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Executive KPI Cards -->
        <div class="row mb-3">
          <!-- Total WHT Deducted (Main Highlight) -->
          <div class="col-xl-3 col-md-6 col-12 mb-2">
            <div class="kpi-card kpi-accent-wht">
              <div class="kpi-label text-danger font-weight-bold">
                <i class="fe fe-shield mr-1"></i> Total WHT Deducted
              </div>
              <div class="kpi-value text-danger">
                Rs. <?=number_format($total_wht, 2)?>
              </div>
              <div class="kpi-sub">
                In <?=strtoupper($month_name_short)?> <?=$selected_year?> &bull; <?=count($transactions)?> invoice deduction(s)
              </div>
            </div>
          </div>

          <!-- Total Gross Settled Amount -->
          <div class="col-xl-3 col-md-6 col-12 mb-2">
            <div class="kpi-card kpi-accent-gross">
              <div class="kpi-label text-primary font-weight-bold">
                <i class="fe fe-file-text mr-1"></i> Gross Invoiced Amount
              </div>
              <div class="kpi-value text-primary">
                Rs. <?=number_format($total_gross, 2)?>
              </div>
              <div class="kpi-sub">
                Total settled gross billing value
              </div>
            </div>
          </div>

          <!-- Net Received Amount -->
          <div class="col-xl-3 col-md-6 col-12 mb-2">
            <div class="kpi-card kpi-accent-net">
              <div class="kpi-label text-success font-weight-bold">
                <i class="fe fe-check-circle mr-1"></i> Net Amount Received
              </div>
              <div class="kpi-value text-success">
                Rs. <?=number_format($total_net, 2)?>
              </div>
              <div class="kpi-sub">
                Actual funds realized after WHT deduction
              </div>
            </div>
          </div>

          <!-- Effective Rate & Scope -->
          <div class="col-xl-3 col-md-6 col-12 mb-2">
            <div class="kpi-card kpi-accent-rate">
              <div class="kpi-label text-purple font-weight-bold" style="color: #6f42c1;">
                <i class="fe fe-pie-chart mr-1"></i> Effective WHT Rate
              </div>
              <div class="kpi-value" style="color: #6f42c1;">
                <?=number_format($effective_wht_rate, 2)?>%
              </div>
              <div class="kpi-sub">
                <?php if (!empty($fetchCustomer)): ?>
                  Customer: <strong><?=htmlspecialchars($fetchCustomer['customer_name'])?></strong>
                <?php else: ?>
                  Scope: <strong>All Customers (Overall)</strong> &bull; <?=count($customer_summary)?> Customer(s)
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 1: Customer-Wise Summary Table (Only shown when viewing OVERALL) -->
        <?php if (empty($customer_id)): ?>
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-light py-2">
              <div class="row align-items-center">
                <div class="col">
                  <h6 class="mb-0 font-weight-bold text-dark">
                    <i class="fe fe-users mr-1 text-primary"></i> Customer-Wise WHT Summary Breakdown &bull; <?=strtoupper($month_name_full)?> <?=$selected_year?>
                  </h6>
                </div>
                <div class="col-auto text-muted small">
                  <?=count($customer_summary)?> Customer(s) with WHT Deductions
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-bordered table-hover report-table mb-0">
                  <thead>
                    <tr>
                      <th style="width: 4%;">#</th>
                      <th style="width: 25%; text-align: left;">Customer Name</th>
                      <th style="width: 11%;">NTN / STRN</th>
                      <th style="width: 9%;">Vouchers</th>
                      <th style="width: 9%;">Invoices</th>
                      <th style="width: 14%; text-align: right;">Gross Amount (Rs)</th>
                      <th style="width: 8%;">WHT %</th>
                      <th style="width: 14%; text-align: right;">WHT Deducted (Rs)</th>
                      <th style="width: 14%; text-align: right;">Net Received (Rs)</th>
                      <th style="width: 7%;" class="d-print-none">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (!empty($customer_summary)):
                      $c_sr = 0;
                      foreach ($customer_summary as $c_id => $cs):
                        $c_sr++;
                        $c_rate = ($cs['total_gross'] > 0) ? (($cs['total_wht'] / $cs['total_gross']) * 100) : 0;
                    ?>
                      <tr>
                        <td class="text-center font-weight-bold"><?=$c_sr?></td>
                        <td>
                          <strong><?=htmlspecialchars($cs['customer_name'])?></strong>
                          <?php if (!empty($cs['customer_phone'])): ?>
                            <br><small class="text-muted"><i class="fe fe-phone"></i> <?=htmlspecialchars($cs['customer_phone'])?></small>
                          <?php endif; ?>
                        </td>
                        <td class="text-center">
                          <?php if (!empty($cs['customer_ntn'])): ?>
                            <span class="badge badge-light border font-weight-bold"><?=htmlspecialchars($cs['customer_ntn'])?></span>
                          <?php else: ?>
                            <span class="text-muted">-</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center font-weight-bold"><?=count($cs['vouchers'])?></td>
                        <td class="text-center font-weight-bold"><?=$cs['invoices_count']?></td>
                        <td class="text-right font-weight-bold text-primary">
                          <?=number_format($cs['total_gross'], 2)?>
                        </td>
                        <td class="text-center font-weight-bold text-secondary">
                          <?=number_format($c_rate, 2)?>%
                        </td>
                        <td class="text-right font-weight-bold text-danger" style="font-size: 14px;">
                          <?=number_format($cs['total_wht'], 2)?>
                        </td>
                        <td class="text-right font-weight-bold text-success">
                          <?=number_format($cs['total_net'], 2)?>
                        </td>
                        <td class="text-center d-print-none">
                          <a href="wht_report.php?month_year=<?=$selected_ym?>&customer_id=<?=$c_id?>" class="btn btn-xs btn-outline-admin py-0 px-2" title="Filter this report for <?=htmlspecialchars($cs['customer_name'])?>">
                            <i class="fe fe-eye"></i> View
                          </a>
                        </td>
                      </tr>
                    <?php
                      endforeach;
                    else:
                    ?>
                      <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                          <i class="fe fe-info fe-24 d-block mb-2"></i>
                          No customer WHT deductions found for <strong><?=strtoupper($month_name_full)?> <?=$selected_year?></strong>.
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                  <?php if (!empty($customer_summary)): ?>
                    <tfoot>
                      <tr>
                        <td colspan="5" class="text-right font-weight-bold">OVERALL CUSTOMER TOTALS:</td>
                        <td class="text-right text-primary font-weight-bold"><?=number_format($total_gross, 2)?></td>
                        <td class="text-center text-secondary font-weight-bold"><?=number_format($effective_wht_rate, 2)?>%</td>
                        <td class="text-right text-danger font-weight-bold" style="font-size: 15px;"><?=number_format($total_wht, 2)?></td>
                        <td class="text-right text-success font-weight-bold"><?=number_format($total_net, 2)?></td>
                        <td class="d-print-none"></td>
                      </tr>
                    </tfoot>
                  <?php endif; ?>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Section 2: Detailed Transactions Breakdown Table -->
        <div class="card shadow mb-4">
          <div class="card-header bg-light py-2">
            <div class="row align-items-center">
              <div class="col">
                <h6 class="mb-0 font-weight-bold text-dark">
                  <i class="fe fe-list mr-1 text-danger"></i> Detailed WHT Invoice Settlements Breakdown
                  <?php if (!empty($fetchCustomer)): ?>
                    &bull; For <span class="text-danger"><?=htmlspecialchars($fetchCustomer['customer_name'])?></span>
                  <?php else: ?>
                    &bull; All Customers (Overall)
                  <?php endif; ?>
                </h6>
              </div>
              <div class="col-auto text-muted small">
                Showing <?=count($transactions)?> Record(s) for <?=strtoupper($month_name_full)?> <?=$selected_year?>
              </div>
            </div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered table-hover report-table mb-0">
                <thead>
                  <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 9%;">Date</th>
                    <th style="width: 8%;">Voucher #</th>
                    <th style="width: 17%; text-align: left;">Customer Name</th>
                    <th style="width: 9%;">Invoice #</th>
                    <th style="width: 9%;">Party PO</th>
                    <th style="width: 12%; text-align: right;">Gross Amount (Rs)</th>
                    <th style="width: 7%;">WHT %</th>
                    <th style="width: 12%; text-align: right;">WHT Deducted (Rs)</th>
                    <th style="width: 13%; text-align: right;">Net Received (Rs)</th>
                    <th style="width: 15%; text-align: left;">Payment Details</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (!empty($transactions)):
                    $t_sr = 0;
                    foreach ($transactions as $t):
                      $t_sr++;
                      $v_date = !empty($t['voucher_date']) ? date('d-M-Y', strtotime($t['voucher_date'])) : date('d-M-Y', strtotime($t['timestamp']));
                      $g = floatval(str_replace(',', '', $t['invoice_amount'] ?? 0));
                      $w = floatval(str_replace(',', '', $t['tax_amount'] ?? 0));
                      $n = floatval(str_replace(',', '', $t['received_amount'] ?? 0));
                      $p = floatval(str_replace(',', '', $t['tax_percent'] ?? 0));
                  ?>
                    <tr>
                      <td class="text-center font-weight-bold"><?=$t_sr?></td>
                      <td class="text-center font-weight-bold"><?=$v_date?></td>
                      <td class="text-center">
                        <a href="print_voucher.php?id=<?=base64_encode($t['voucher_id'])?>" target="_blank" class="badge badge-info font-weight-bold p-1" title="View / Print Voucher #<?=$t['voucher_id']?>">
                          <i class="fe fe-file-text"></i> VOU #<?=$t['voucher_id']?>
                        </a>
                      </td>
                      <td>
                        <strong><?=htmlspecialchars($t['customer_name'])?></strong>
                        <?php if (!empty($t['customer_ntn'])): ?>
                          <br><small class="text-muted">NTN: <?=htmlspecialchars($t['customer_ntn'])?></small>
                        <?php endif; ?>
                      </td>
                      <td class="text-center font-weight-bold">
                        <span class="badge badge-light border text-dark font-weight-bold">
                          #<?=htmlspecialchars($t['invoice_id'])?>
                        </span>
                      </td>
                      <td class="text-center font-weight-bold">
                        <?=htmlspecialchars($t['party_po'] ?: '-')?>
                      </td>
                      <td class="text-right font-weight-bold text-primary">
                        <?=number_format($g, 2)?>
                      </td>
                      <td class="text-center font-weight-bold text-secondary">
                        <?=number_format($p, 2)?>%
                      </td>
                      <td class="text-right font-weight-bold text-danger" style="font-size: 14px;">
                        <?=number_format($w, 2)?>
                      </td>
                      <td class="text-right font-weight-bold text-success">
                        <?=number_format($n, 2)?>
                      </td>
                      <td style="font-size: 12px;">
                        <?php if (!empty($t['td_check_no'])): ?>
                          <div><strong>Chq #:</strong> <?=htmlspecialchars($t['td_check_no'])?></div>
                        <?php endif; ?>
                        <?php if (!empty($t['voucher_bank_name'])): ?>
                          <div><strong>Bank:</strong> <?=htmlspecialchars($t['voucher_bank_name'])?></div>
                        <?php endif; ?>
                        <?php if (!empty($t['voucher_hint'])): ?>
                          <div class="text-muted"><small><?=htmlspecialchars($t['voucher_hint'])?></small></div>
                        <?php endif; ?>
                        <?php if (empty($t['td_check_no']) && empty($t['voucher_bank_name']) && empty($t['voucher_hint'])): ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php
                    endforeach;
                  else:
                  ?>
                    <tr>
                      <td colspan="11" class="text-center py-4 text-muted">
                        <i class="fe fe-alert-circle fe-24 d-block mb-2"></i>
                        No WHT deduction records found for <strong><?=strtoupper($month_name_full)?> <?=$selected_year?></strong>
                        <?php if (!empty($fetchCustomer)): ?>
                          for customer <strong><?=htmlspecialchars($fetchCustomer['customer_name'])?></strong>
                        <?php endif; ?>.
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
                <?php if (!empty($transactions)): ?>
                  <tfoot>
                    <tr style="background: #f1f3f5; font-size: 14px;">
                      <td colspan="6" class="text-right font-weight-bold">GRAND TOTALS (<?=count($transactions)?> INVOICES / <?=count($distinct_vouchers)?> VOUCHERS):</td>
                      <td class="text-right font-weight-bold text-primary" style="font-size: 15px;"><?=number_format($total_gross, 2)?></td>
                      <td class="text-center font-weight-bold text-secondary"><?=number_format($effective_wht_rate, 2)?>%</td>
                      <td class="text-right font-weight-bold text-danger" style="font-size: 16px;"><?=number_format($total_wht, 2)?></td>
                      <td class="text-right font-weight-bold text-success" style="font-size: 15px;"><?=number_format($total_net, 2)?></td>
                      <td></td>
                    </tr>
                  </tfoot>
                <?php endif; ?>
              </table>
            </div>
          </div>
        </div>

        <!-- Section 3: 12-Month Annual Trend / Comparison Overview for Selected Year -->
        <div class="card shadow-sm mb-4 d-print-none">
          <div class="card-header bg-light py-2">
            <div class="row align-items-center">
              <div class="col">
                <h6 class="mb-0 font-weight-bold text-dark">
                  <i class="fe fe-bar-chart-2 mr-1 text-info"></i> <?=$selected_year?> Annual WHT Monthly Comparison
                  <small class="text-muted font-weight-normal ml-2">Click any month to quickly switch the report</small>
                </h6>
              </div>
              <div class="col-auto">
                <span class="badge badge-light border">Year <?=$selected_year?></span>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-hover text-center mb-0" style="font-size: 12px;">
                <thead class="thead-light">
                  <tr>
                    <?php foreach ($all_months as $m_c => $m_n): ?>
                      <th class="<?=$selected_month_num === $m_c ? 'bg-danger text-white' : ''?>">
                        <?=$m_n?>
                      </th>
                    <?php endforeach; ?>
                    <th class="bg-dark text-white font-weight-bold">Annual Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <?php
                    $year_sum_wht = 0;
                    foreach ($all_months as $m_c => $m_n):
                      $m_wht = $annual_monthly_data[$m_c]['wht'];
                      $year_sum_wht += $m_wht;
                      $is_sel = ($selected_month_num === $m_c);
                    ?>
                      <td class="<?=$is_sel ? 'table-warning font-weight-bold' : ''?>">
                        <a href="wht_report.php?month_year=<?=$selected_year?>-<?=$m_c?><?=!empty($customer_id) ? '&customer_id=' . $customer_id : ''?>" style="color: inherit; text-decoration: none; display: block;">
                          <div class="<?=$m_wht > 0 ? 'text-danger font-weight-bold' : 'text-muted'?>">
                            <?=number_format($m_wht)?>
                          </div>
                          <?php if ($annual_monthly_data[$m_c]['count'] > 0): ?>
                            <small class="text-muted"><?=$annual_monthly_data[$m_c]['count']?> inv</small>
                          <?php else: ?>
                            <small class="text-muted">-</small>
                          <?php endif; ?>
                        </a>
                      </td>
                    <?php endforeach; ?>
                    <td class="font-weight-bold text-danger table-secondary" style="font-size: 13px;">
                      Rs. <?=number_format($year_sum_wht)?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        </div>

      </div> <!-- .container-fluid -->
    </main> <!-- main -->
  </div> <!-- .wrapper -->
</body>
</html>
<?php include_once 'includes/foot.php'; ?>
<script type="text/javascript">
$(document).ready(function() {
  if ($.fn.select2) {
    $('#wht_customer_id').select2({
      placeholder: "-- All Customers (Overall) --",
      allowClear: true,
      width: '100%'
    });
  }
});
</script>
