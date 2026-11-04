<?php 
include_once 'php_action/core.php';

// Company Details
$company_id = !empty($get_company['id']) ? intval($get_company['id']) : 5;
$comp_res = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company WHERE id = '$company_id'"));
if ($comp_res) {
    $get_company = $comp_res;
}
$comp_name    = !empty($get_company['name']) ? $get_company['name'] : 'BA TRADERS';
$comp_strn    = !empty($get_company['s_tax_no']) ? $get_company['s_tax_no'] : '32-77-8763-934-54';
$comp_ntn     = !empty($get_company['ntn_no']) ? $get_company['ntn_no'] : 'A322716-3';
$comp_address = !empty($get_company['address']) ? $get_company['address'] : 'Shop # 621, AL Madina Commercial Market, Near Hamza Kanta, Oil Depot Road, Tata Market, Faisalabad';
$comp_phone   = !empty($get_company['company_phone']) ? $get_company['company_phone'] : '';

// -----------------------------------------------------------------------------
// Filters & Parameters
// -----------------------------------------------------------------------------
$customer_id   = (isset($_REQUEST['customer_id']) && $_REQUEST['customer_id'] !== '' && $_REQUEST['customer_id'] !== 'all') ? mysqli_real_escape_string($dbc, $_REQUEST['customer_id']) : '';
$from_date     = isset($_REQUEST['from_date']) ? trim($_REQUEST['from_date']) : '';
$to_date       = isset($_REQUEST['to_date']) ? trim($_REQUEST['to_date']) : '';
$status_filter = isset($_REQUEST['status']) ? trim($_REQUEST['status']) : 'all'; // all, pending, paid, partial, overpaid
$search_query  = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';

// Selected Customer info
$selected_customer = null;
if (!empty($customer_id)) {
    $selected_customer = fetchRecord($dbc, 'customers', 'customer_id', $customer_id);
}

// Date Scope Label
$date_scope_label = "Overall (All Time)";
if (!empty($from_date) && !empty($to_date)) {
    $date_scope_label = "From " . date('d-M-Y', strtotime($from_date)) . " To " . date('d-M-Y', strtotime($to_date));
} elseif (!empty($from_date)) {
    $date_scope_label = "From " . date('d-M-Y', strtotime($from_date)) . " Onwards";
} elseif (!empty($to_date)) {
    $date_scope_label = "Up to " . date('d-M-Y', strtotime($to_date));
}

// Variables for unified report_header.php
$report_title    = "TAX SALE INVOICE PAYMENT & BALANCE DUE REPORT";
$report_customer = $selected_customer;
$report_duration = $date_scope_label;

// -----------------------------------------------------------------------------
// Build Query to Fetch Invoices
// -----------------------------------------------------------------------------
$where_clauses = ["1=1"];

if (!empty($customer_id)) {
    $where_clauses[] = "ts.customer_id = '$customer_id'";
}
if (!empty($from_date)) {
    $from_escaped = mysqli_real_escape_string($dbc, $from_date);
    $where_clauses[] = "ts.invoice_date >= '$from_escaped'";
}
if (!empty($to_date)) {
    $to_escaped = mysqli_real_escape_string($dbc, $to_date);
    $where_clauses[] = "ts.invoice_date <= '$to_escaped'";
}
if (!empty($search_query)) {
    $search_escaped = mysqli_real_escape_string($dbc, $search_query);
    $where_clauses[] = "(ts.invoice_no LIKE '%$search_escaped%' OR ts.fbr_invoice_no LIKE '%$search_escaped%' OR ts.po_no LIKE '%$search_escaped%' OR c.customer_name LIKE '%$search_escaped%')";
}

$where_sql = implode(' AND ', $where_clauses);
$sql = "SELECT ts.*, c.customer_name, c.customer_phone, c.customer_strn, c.customer_ntn, c.customer_address
        FROM tax_sales ts
        LEFT JOIN customers c ON c.customer_id = ts.customer_id
        WHERE $where_sql
        ORDER BY ts.invoice_date DESC, ts.tax_sale_id DESC";

$q_invoices = mysqli_query($dbc, $sql);

$report_data = [];
$total_invoiced = 0;
$total_paid = 0;
$total_left = 0;
$total_overpaid = 0;
$total_wht = 0;
$count_paid = 0;
$count_partial = 0;
$count_pending = 0;
$count_overpaid = 0;

if ($q_invoices) {
    while ($r = mysqli_fetch_assoc($q_invoices)) {
        $ts_id = $r['tax_sale_id'];
        $inv_no = trim($r['invoice_no'] ?? '');
        $fbr_no = trim($r['fbr_invoice_no'] ?? '');
        $po = trim($r['po_no'] ?? '');
        $cust_id = $r['customer_id'];

        $ts_id_escaped = mysqli_real_escape_string($dbc, $ts_id);
        $inv_no_escaped = mysqli_real_escape_string($dbc, $inv_no);
        $fbr_no_escaped = mysqli_real_escape_string($dbc, $fbr_no);
        $po_escaped = mysqli_real_escape_string($dbc, $po);

        $inv_clause = "(vi.invoice_id = '$ts_id_escaped'";
        if (!empty($inv_no)) $inv_clause .= " OR vi.invoice_id = '$inv_no_escaped'";
        if (!empty($fbr_no)) $inv_clause .= " OR vi.invoice_id = '$fbr_no_escaped'";
        $inv_clause .= ")";

        $where_vi = "$inv_clause";
        if (!empty($po) && !empty($cust_id)) {
            $where_vi = "($inv_clause OR ((vi.invoice_id IS NULL OR vi.invoice_id = '' OR vi.invoice_id = '0') AND vi.party_po = '$po_escaped' AND (v.customer_id1 = '$cust_id' OR v.customer_id1 IS NULL OR v.customer_id1 = '' OR v.customer_id1 = '0')))";
        }

        $q_vi = mysqli_query($dbc, "
            SELECT vi.*, v.voucher_date, v.voucher_hint, v.td_check_no, v.voucher_bank_name, v.td_check_date, v.check_type, v.timestamp
            FROM voucher_invoices vi
            JOIN vouchers v ON vi.voucher_id = v.voucher_id
            WHERE $where_vi
            ORDER BY v.voucher_date ASC, vi.id ASC
        ");

        $paid_amt = 0;
        $wht_amt = 0;
        $vouchers_list = [];

        if ($q_vi && mysqli_num_rows($q_vi) > 0) {
            while ($v = mysqli_fetch_assoc($q_vi)) {
                $v_amt = floatval(str_replace(',', '', $v['invoice_amount'] ?? 0));
                $v_tax = floatval(str_replace(',', '', $v['tax_amount'] ?? 0));
                $paid_amt += $v_amt;
                $wht_amt += $v_tax;
                $vouchers_list[] = [
                    'voucher_id' => $v['voucher_id'],
                    'voucher_date' => !empty($v['voucher_date']) ? $v['voucher_date'] : date('Y-m-d', strtotime($v['timestamp'])),
                    'amount' => $v_amt,
                    'tax_percent' => floatval($v['tax_percent'] ?? 0),
                    'tax_amount' => $v_tax,
                    'check_no' => $v['td_check_no'] ?? '',
                    'bank_name' => $v['voucher_bank_name'] ?? '',
                    'check_date' => $v['td_check_date'] ?? '',
                    'hint' => $v['voucher_hint'] ?? '',
                ];
            }
        }

        $grand_total = floatval($r['grand_total']);
        $total_settled = $paid_amt + $wht_amt;
        $diff = $grand_total - $total_settled;
        $left_amt = 0;
        $overpaid_amt = 0;

        if ($diff < -0.01) {
            $left_amt = 0;
            $overpaid_amt = abs($diff);
            $status = 'overpaid';
        } elseif ($diff <= 0.01) {
            $left_amt = 0;
            $status = 'paid';
        } elseif ($total_settled > 0) {
            $left_amt = $diff;
            $status = 'partial';
        } else {
            $left_amt = $grand_total;
            $status = 'unpaid';
        }

        // Filter by Status if specified
        if ($status_filter === 'pending' && $left_amt <= 0) {
            continue;
        } elseif ($status_filter === 'paid' && ($left_amt > 0 || $status === 'overpaid')) {
            continue;
        } elseif ($status_filter === 'partial' && $status !== 'partial') {
            continue;
        } elseif ($status_filter === 'overpaid' && $status !== 'overpaid') {
            continue;
        }

        // Aggregate statistics
        $total_invoiced += $grand_total;
        $total_paid += $paid_amt;
        $total_left += $left_amt;
        $total_overpaid += $overpaid_amt;
        $total_wht += $wht_amt;

        if ($status === 'paid') $count_paid++;
        elseif ($status === 'partial') $count_partial++;
        elseif ($status === 'unpaid') $count_pending++;
        elseif ($status === 'overpaid') $count_overpaid++;

        $report_data[] = [
            'invoice' => $r,
            'grand_total' => $grand_total,
            'paid_amount' => $paid_amt,
            'left_amount' => $left_amt,
            'overpaid_amount' => $overpaid_amt,
            'diff' => $diff,
            'wht_amount' => $wht_amt,
            'status' => $status,
            'vouchers' => $vouchers_list,
        ];
    }
}

// -----------------------------------------------------------------------------
// CSV Export Handler
// -----------------------------------------------------------------------------
if (isset($_REQUEST['export']) && $_REQUEST['export'] === 'csv') {
    if (ob_get_level()) {
        ob_end_clean();
    }
    $filename = "Tax_Sale_Invoice_Payment_Report_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');

    fputcsv($out, [$comp_name]);
    fputcsv($out, ["TAX SALE INVOICE PAYMENT & BALANCE DUE REPORT"]);
    fputcsv($out, ["Scope:", !empty($selected_customer) ? $selected_customer['customer_name'] . " (NTN: " . ($selected_customer['customer_ntn'] ?: 'N/A') . ")" : "All Customers (Overall)"]);
    fputcsv($out, ["Period:", $date_scope_label]);
    fputcsv($out, ["Status Filter:", ucfirst($status_filter)]);
    fputcsv($out, ["Generated On:", date('d-M-Y h:i A')]);
    fputcsv($out, []); // blank line

    fputcsv($out, [
        'Sr #',
        'Invoice Date',
        'Invoice #',
        'FBR Invoice #',
        'Customer Name',
        'Customer STRN',
        'Customer NTN',
        'Party PO #',
        'Grand Total (Rs)',
        'Amount Paid (Rs)',
        'WHT Deducted (Rs)',
        'Balance Due (Rs)',
        'Overpaid (Rs)',
        'Status',
        'Vouchers Count'
    ]);

    $sr = 0;
    foreach ($report_data as $row) {
        $sr++;
        $inv = $row['invoice'];
        fputcsv($out, [
            $sr,
            date('d-M-Y', strtotime($inv['invoice_date'])),
            $inv['invoice_no'] ?: $inv['tax_sale_id'],
            $inv['fbr_invoice_no'] ?: 'N/A',
            $inv['customer_name'],
            $inv['customer_strn'] ?: '',
            $inv['customer_ntn'] ?: '',
            $inv['po_no'] ?: '',
            $row['grand_total'],
            $row['paid_amount'],
            $row['wht_amount'],
            $row['left_amount'],
            $row['overpaid_amount'],
            strtoupper($row['status']),
            count($row['vouchers'])
        ]);
    }

    fputcsv($out, []);
    fputcsv($out, [
        'TOTALS',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        $total_invoiced,
        $total_paid,
        $total_wht,
        $total_left,
        $total_overpaid,
        '',
        ''
    ]);

    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
include_once 'includes/head.php';
?>
<style type="text/css">
  .report-table thead th {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    background-color: #2b3035;
    vertical-align: middle;
    border: 1px solid #454d55;
    padding: 8px 6px;
    text-align: center;
  }
  .report-table tbody td {
    font-size: 13px;
    vertical-align: middle;
    border: 1px solid #dee2e6;
    padding: 8px 8px;
    color: #212529;
  }
  .report-table tfoot th, .report-table tfoot td {
    font-size: 13px;
    font-weight: 700;
    background-color: #f1f3f5;
    border: 1px solid #dee2e6;
    padding: 8px 8px;
    vertical-align: middle;
  }
  .report-table tbody tr:nth-of-type(even) {
    background-color: #fbfbfb;
  }
  .report-table tbody tr:hover {
    background-color: #f4f6f9;
  }
  .fbr-badge {
    background-color: #e8f4fd;
    color: #0b5ed7;
    border: 1px solid #b6d4fe;
    font-size: 12px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
    letter-spacing: 0.3px;
    display: inline-block;
  }
  .fbr-empty {
    background-color: #f8f9fa;
    color: #6c757d;
    border: 1px dashed #ced4da;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 3px;
    display: inline-block;
  }
  .summary-card {
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 12px 16px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }
  .summary-title {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
  }
  .summary-value {
    font-size: 20px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.2;
  }
  .summary-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 3px;
  }

  @page {
    size: A4 landscape;
    margin: 8mm 6mm !important;
  }

  @media print {
    .d-print-none, .navbar, .navbar-slide, .toggle-sidebar, .sidebar, #header, footer, .btn, .breadcrumb, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length, .modal {
      display: none !important;
    }
    .wrapper, main, .main-content, .container, .container-fluid, .card, .card-body {
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
      box-shadow: none !important;
      background: #fff !important;
      width: 100% !important;
      max-width: 100% !important;
    }
    body {
      background: #fff !important;
      color: #000 !important;
      font-size: 11px !important;
    }
    .report-table {
      width: 100% !important;
      border-collapse: collapse !important;
    }
    .report-table thead th {
      background-color: #222 !important;
      color: #fff !important;
      border: 1px solid #000 !important;
      font-size: 10.5px !important;
      padding: 5px 4px !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .report-table tbody td, .report-table tfoot td, .report-table tfoot th {
      border: 1px solid #000 !important;
      font-size: 10px !important;
      padding: 5px 4px !important;
      color: #000 !important;
    }
    .fbr-badge {
      background: transparent !important;
      color: #000 !important;
      border: 1px solid #000 !important;
      font-weight: bold !important;
    }
    .print-only {
      display: block !important;
    }
  }

  .print-only {
    display: none;
  }
</style>

<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>

    <main role="main" class="main-content">
      <div class="container-fluid">

        <!-- Top Header & Action Buttons -->
        <div class="card shadow mb-3 d-print-none">
          <div class="card-header card-bg py-2">
            <div class="row align-items-center">
              <div class="col-md-7 col-sm-12">
                <h4 class="mb-0 text-white font-weight-bold">
                  <i class="fe fe-file-text mr-1"></i> Tax Invoice Wise Ledger
                </h4>
                <small class="text-white-50">
                  Track invoiced amounts, voucher payments, remaining balance due, and FBR invoice numbers
                </small>
              </div>
              <div class="col-md-5 col-sm-12 text-md-right text-left mt-2 mt-md-0">
                <button type="button" onclick="window.print()" class="btn btn-sm btn-primary ml-1 shadow-sm">
                  <i class="fe fe-printer mr-1"></i> Print Report
                </button>
                <?php
                $csv_params = $_GET;
                $csv_params['export'] = 'csv';
                $csv_url = 'tax_sale_report.php?' . http_build_query($csv_params);
                ?>
                <a href="tax_sale_list.php" class="btn btn-sm btn-outline-light ml-1">
                  <i class="fe fe-list mr-1"></i> Tax Invoice List
                </a>
                <a href="tax_sale.php" class="btn btn-sm btn-admin2 ml-1">
                  <i class="fe fe-plus mr-1"></i> New Invoice
                </a>
              </div>
            </div>
          </div>

          <!-- Filter Form -->
          <div class="card-body bg-light border-bottom py-3">
            <form action="tax_sale_report.php" method="GET" class="form-row align-items-end">
              <!-- Customer Filter -->
              <div class="form-group col-lg-3 col-md-6 col-sm-12 mb-2">
                <label class="font-weight-bold small mb-1">Customer Account</label>
                <select class="form-control form-control-sm searchableSelect" name="customer_id" id="customer_id">
                  <option value="all">-- All Customers (Overall) --</option>
                  <?php
                  $q_c = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_status = 1 AND customer_type = 'customer' ORDER BY customer_name ASC");
                  while ($rc = mysqli_fetch_assoc($q_c)) {
                    $sel = ($customer_id == $rc['customer_id']) ? 'selected' : '';
                    $ntn_info = !empty($rc['customer_ntn']) ? ' (NTN: ' . $rc['customer_ntn'] . ')' : '';
                    echo "<option value='{$rc['customer_id']}' {$sel}>" . htmlspecialchars(getCustomerDisplayName($rc['customer_name'], $rc['customer_phone'] ?? '')) . $ntn_info . "</option>";
                  }
                  ?>
                </select>
              </div>

              <!-- From Date -->
              <div class="form-group col-lg-2 col-md-3 col-sm-6 mb-2">
                <label class="font-weight-bold small mb-1">From Date</label>
                <input type="date" name="from_date" value="<?=htmlspecialchars($from_date)?>" class="form-control form-control-sm">
              </div>

              <!-- To Date -->
              <div class="form-group col-lg-2 col-md-3 col-sm-6 mb-2">
                <label class="font-weight-bold small mb-1">To Date</label>
                <input type="date" name="to_date" value="<?=htmlspecialchars($to_date)?>" class="form-control form-control-sm">
              </div>

              <!-- Payment Status -->
              <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-2">
                <label class="font-weight-bold small mb-1">Payment Status</label>
                <select name="status" class="form-control form-control-sm">
                  <option value="all" <?=$status_filter === 'all' ? 'selected' : ''?>>All Invoices</option>
                  <option value="pending" <?=$status_filter === 'pending' ? 'selected' : ''?>>Pending Due (Left > 0)</option>
                  <option value="paid" <?=$status_filter === 'paid' ? 'selected' : ''?>>Fully Paid (Left = 0)</option>
                  <option value="partial" <?=$status_filter === 'partial' ? 'selected' : ''?>>Partially Paid</option>
                  <option value="overpaid" <?=$status_filter === 'overpaid' ? 'selected' : ''?>>Overpaid</option>
                </select>
              </div>

              <!-- Keyword / FBR Search -->
              <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-2">
                <label class="font-weight-bold small mb-1">Search FBR / Inv / PO</label>
                <input type="text" name="q" value="<?=htmlspecialchars($search_query)?>" placeholder="FBR #, Inv #, PO..." class="form-control form-control-sm">
              </div>

              <!-- Buttons -->
              <div class="form-group col-lg-1 col-md-4 col-sm-12 mb-2 text-right">
                <button type="submit" class="btn btn-sm btn-admin btn-block">
                  <i class="fe fe-filter"></i> Filter
                </button>
              </div>
            </form>

            <!-- Quick Date Presets -->
            <div class="pt-2 border-top mt-1 d-flex flex-wrap align-items-center">
              <span class="small font-weight-bold text-muted mr-2">Quick Dates:</span>
              <a href="tax_sale_report.php?<?=http_build_query(array_merge($_GET, ['from_date' => date('Y-m-d'), 'to_date' => date('Y-m-d')]))?>" class="badge badge-light border mr-1 py-1 px-2 text-secondary">Today</a>
              <a href="tax_sale_report.php?<?=http_build_query(array_merge($_GET, ['from_date' => date('Y-m-01'), 'to_date' => date('Y-m-t')]))?>" class="badge badge-light border mr-1 py-1 px-2 text-secondary">This Month (<?=date('M Y')?>)</a>
              <a href="tax_sale_report.php?<?=http_build_query(array_merge($_GET, ['from_date' => date('Y-m-01', strtotime('first day of last month')), 'to_date' => date('Y-m-t', strtotime('last day of last month'))]))?>" class="badge badge-light border mr-1 py-1 px-2 text-secondary">Last Month</a>
              <a href="tax_sale_report.php?<?=http_build_query(array_merge($_GET, ['from_date' => date('Y-01-01'), 'to_date' => date('Y-12-31')]))?>" class="badge badge-light border mr-1 py-1 px-2 text-secondary">This Year (<?=date('Y')?>)</a>
              <a href="tax_sale_report.php?<?=http_build_query(array_merge($_GET, ['from_date' => '', 'to_date' => '']))?>" class="badge badge-light border mr-1 py-1 px-2 text-secondary">All Time (Overall)</a>
              <?php if (!empty($customer_id) || !empty($from_date) || !empty($to_date) || $status_filter !== 'all' || !empty($search_query)): ?>
                <a href="tax_sale_report.php" class="badge badge-danger text-white ml-auto py-1 px-2"><i class="fe fe-x mr-1"></i>Reset All Filters</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Summary KPI Cards (Screen) -->
        <div class="row d-print-none mb-2">
          <div class="col-xl-3 col-md-6 col-sm-12">
            <div class="summary-card border-left border-dark" style="border-left-width: 4px !important;">
              <div class="summary-title">Total Invoices</div>
              <div class="summary-value"><?=number_format(count($report_data))?></div>
              <div class="summary-sub">
                <span class="text-success font-weight-bold"><?=$count_paid?> Paid</span> &bull; 
                <span class="text-warning font-weight-bold"><?=$count_partial?> Partial</span> &bull; 
                <span class="text-danger font-weight-bold"><?=$count_pending?> Due</span>
                <?php if ($count_overpaid > 0): ?>
                  &bull; <span class="text-info font-weight-bold"><?=$count_overpaid?> Overpaid</span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 col-sm-12">
            <div class="summary-card border-left border-primary" style="border-left-width: 4px !important;">
              <div class="summary-title">Total Invoiced (Grand Total)</div>
              <div class="summary-value text-primary">Rs <?=number_format($total_invoiced)?></div>
              <div class="summary-sub">Sum of all tax sale invoices</div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 col-sm-12">
            <div class="summary-card border-left border-success" style="border-left-width: 4px !important;">
              <div class="summary-title">Total Amount Paid</div>
              <div class="summary-value text-success">Rs <?=number_format($total_paid)?></div>
              <div class="summary-sub">
                <?php 
                $settled_pct = $total_invoiced > 0 ? round((($total_paid + $total_wht) / $total_invoiced) * 100, 1) : 0;
                ?>
                WHT Tax: <strong style="color: #6f42c1;">Rs <?=number_format($total_wht)?></strong> &bull; Settled: <strong><?=$settled_pct?>%</strong>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 col-sm-12">
            <div class="summary-card border-left border-danger" style="border-left-width: 4px !important;">
              <div class="summary-title">Total Amount Left (Due)</div>
              <div class="summary-value text-danger">Rs <?=number_format($total_left)?></div>
              <div class="summary-sub">
                <?php if ($total_overpaid > 0): ?>
                  Net Due: <strong class="text-dark">Rs <?=number_format(max(0, $total_invoiced - ($total_paid + $total_wht)))?></strong> (Overpaid: Rs <?=number_format($total_overpaid)?>)
                <?php else: ?>
                  Outstanding balance after Paid + WHT Tax
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Printable Report Header -->
        <div class="print-only">
          <?php include 'includes/report_header.php'; ?>
        </div>

        <!-- Main Report Table Card -->
        <div class="card shadow">
          <div class="card-body p-2 p-md-3">
            <div class="table-responsive">
              <table class="table table-bordered table-striped table-hover report-table mb-0 dataTableAsc" id="tax_sale_report_tb">
                <thead>
                  <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 8%;">Date</th>
                    <th style="width: 7%;">Inv #</th>
                    <th style="width: 12%;">FBR Invoice #</th>
                    <th style="width: 20%;">Customer Details</th>
                    <th style="width: 8%;">PO #</th>
                    <th style="width: 11%; text-align: right;">Grand Total (Rs)</th>
                    <th style="width: 11%; text-align: right;">Amount Paid (Rs)</th>
                    <th style="width: 9%; text-align: right;">WHT Tax (Rs)</th>
                    <th style="width: 11%; text-align: right;">Amount Left / Due (Rs)</th>
                    <th style="width: 8%; text-align: center;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $sr = 0;
                  if (!empty($report_data)):
                    foreach ($report_data as $row):
                      $sr++;
                      $inv = $row['invoice'];
                      $inv_display = $inv['invoice_no'] ?: $inv['tax_sale_id'];
                      $fbr_display = trim($inv['fbr_invoice_no'] ?? '');
                  ?>
                    <tr>
                      <td class="text-center font-weight-bold"><?=$sr?></td>
                      <td class="text-center text-nowrap"><?=date('d-M-Y', strtotime($inv['invoice_date']))?></td>
                      <td class="text-center font-weight-bold">
                        <a href="print_tax_sale.php?id=<?=$inv['tax_sale_id']?>" target="_blank" title="View / Print Tax Invoice" class="text-dark font-weight-bold">
                          #<?=$inv_display?>
                        </a>
                      </td>
                      <!-- FBR INVOICE NUMBER -->
                      <td>
                        <?php if (!empty($fbr_display)): ?>
                          <span class="fbr-badge" title="FBR Invoice Number">
                            <i class="fe fe-shield mr-1"></i><?=htmlspecialchars($fbr_display)?>
                          </span>
                        <?php else: ?>
                          <span class="fbr-empty" title="FBR Number not assigned">N/A</span>
                        <?php endif; ?>
                      </td>
                      <!-- CUSTOMER DETAILS -->
                      <td>
                        <strong class="text-dark"><?=htmlspecialchars($inv['customer_name'] ?? 'Unknown Customer')?></strong>
                        <div class="small text-muted mt-1" style="font-size: 11px;">
                          <?php if (!empty($inv['customer_strn'])): ?>
                            <span>STRN: <strong><?=htmlspecialchars($inv['customer_strn'])?></strong></span>
                          <?php endif; ?>
                          <?php if (!empty($inv['customer_ntn'])): ?>
                            <span class="ml-1">NTN: <strong><?=htmlspecialchars($inv['customer_ntn'])?></strong></span>
                          <?php endif; ?>
                          <?php if (!empty($inv['customer_phone'])): ?>
                            <span class="ml-1"><i class="fe fe-phone"></i> <?=htmlspecialchars($inv['customer_phone'])?></span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <!-- PARTY PO -->
                      <td class="text-center font-weight-bold"><?=htmlspecialchars($inv['po_no'] ?: '—')?></td>
                      <!-- GRAND TOTAL -->
                      <td class="text-right font-weight-bold text-nowrap">
                        <?=number_format($row['grand_total'], 2)?>
                      </td>
                      <!-- AMOUNT PAID -->
                      <td class="text-right text-nowrap">
                        <span class="text-success font-weight-bold"><?=number_format($row['paid_amount'], 2)?></span>
                        <?php if (!empty($row['vouchers'])): ?>
                          <div class="d-print-none mt-1">
                            <button type="button" class="btn btn-xs btn-outline-info py-0 px-2" data-toggle="modal" data-target="#vModal_<?=$inv['tax_sale_id']?>" title="Click to view voucher breakdown" style="font-size:10px; font-weight:600;">
                              <i class="fe fe-eye mr-1"></i><?=count($row['vouchers'])?> Voucher(s)
                            </button>
                          </div>
                        <?php endif; ?>
                      </td>
                      <!-- WHT TAX -->
                      <td class="text-right text-nowrap">
                        <?php if ($row['wht_amount'] > 0): ?>
                          <span class="font-weight-bold" style="color: #6f42c1;"><?=number_format($row['wht_amount'], 2)?></span>
                        <?php else: ?>
                          <span class="text-muted">0.00</span>
                        <?php endif; ?>
                      </td>
                      <!-- AMOUNT LEFT (DUE) -->
                      <td class="text-right text-nowrap">
                        <?php if ($row['status'] === 'overpaid'): ?>
                          <span class="badge badge-info px-2 py-1 font-weight-bold" title="Payment & WHT of Rs <?=number_format($row['paid_amount'] + $row['wht_amount'])?> exceeds invoice total of Rs <?=number_format($row['grand_total'])?>">
                            Overpaid (+<?=number_format($row['overpaid_amount'], 2)?>)
                          </span>
                        <?php elseif ($row['left_amount'] > 0): ?>
                          <span class="text-danger font-weight-bold" style="font-size: 13.5px;"><?=number_format($row['left_amount'], 2)?></span>
                        <?php else: ?>
                          <span class="badge badge-success px-2 py-1 font-weight-bold"><i class="fe fe-check mr-1"></i>Paid (0.00)</span>
                        <?php endif; ?>
                      </td>
                      <!-- STATUS -->
                      <td class="text-center text-nowrap">
                        <?php if ($row['status'] === 'paid'): ?>
                          <span class="badge badge-success px-2 py-1 font-weight-bold">PAID</span>
                        <?php elseif ($row['status'] === 'partial'): ?>
                          <span class="badge badge-warning px-2 py-1 font-weight-bold text-dark">PARTIAL</span>
                        <?php elseif ($row['status'] === 'overpaid'): ?>
                          <span class="badge badge-info px-2 py-1 font-weight-bold">OVERPAID</span>
                        <?php else: ?>
                          <span class="badge badge-danger px-2 py-1 font-weight-bold">UNPAID</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php 
                    endforeach;
                  else: 
                  ?>
                    <tr>
                      <td colspan="11" class="text-center py-4 text-muted">
                        <i class="fe fe-inbox fe-32 d-block mb-2"></i>
                        <strong>No tax sale invoices found matching the selected criteria.</strong>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>

                <!-- Summary Footer Row -->
                <tfoot>
                  <tr style="background:#e9ecef; font-weight:700;">
                    <td colspan="6" class="text-right">
                      <strong>SUMMARY TOTALS (<?=count($report_data)?> Invoices):</strong>
                    </td>
                    <td class="text-right font-weight-bold text-primary">
                      <?=number_format($total_invoiced, 2)?>
                    </td>
                    <td class="text-right font-weight-bold text-success">
                      <?=number_format($total_paid, 2)?>
                    </td>
                    <td class="text-right font-weight-bold" style="color: #6f42c1;">
                      <?=number_format($total_wht, 2)?>
                    </td>
                    <td class="text-right font-weight-bold text-danger">
                      <?=number_format($total_left, 2)?>
                    </td>
                    <td class="text-center">
                      <span class="small font-weight-bold text-muted">
                        <?=$total_invoiced > 0 ? round((($total_paid + $total_wht) / $total_invoiced) * 100, 1) : 0?>% Settled
                      </span>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div> <!-- /.table-responsive -->
          </div> <!-- /.card-body -->
        </div> <!-- /.card -->

        <!-- MODALS (Rendered outside table to preserve valid HTML and DataTable) -->
        <?php 
        if (!empty($report_data)):
          foreach ($report_data as $row):
            $inv = $row['invoice'];
            $inv_display = $inv['invoice_no'] ?: $inv['tax_sale_id'];
            $fbr_display = trim($inv['fbr_invoice_no'] ?? '');
            if (!empty($row['vouchers'])):
        ?>
        <div class="modal fade d-print-none" id="vModal_<?=$inv['tax_sale_id']?>" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header bg-dark text-white py-2">
                <h5 class="modal-title font-weight-bold" style="font-size:15px;">
                  <i class="fe fe-credit-card mr-1"></i> Payment Vouchers for Invoice #<?=$inv_display?>
                  <?php if (!empty($fbr_display)): ?>
                    <span class="badge badge-primary ml-2">FBR: <?=htmlspecialchars($fbr_display)?></span>
                  <?php endif; ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body p-3">
                <div class="row mb-3 bg-light p-2 rounded mx-0">
                  <div class="col-sm-3">
                    <small class="text-muted d-block">Customer</small>
                    <strong class="text-truncate d-block" title="<?=htmlspecialchars($inv['customer_name'])?>"><?=htmlspecialchars($inv['customer_name'])?></strong>
                  </div>
                  <div class="col-sm-3">
                    <small class="text-muted d-block">Invoice Grand Total</small>
                    <strong class="text-primary">Rs <?=number_format($row['grand_total'], 2)?></strong>
                  </div>
                  <div class="col-sm-2">
                    <small class="text-muted d-block">Total Paid</small>
                    <strong class="text-success">Rs <?=number_format($row['paid_amount'], 2)?></strong>
                  </div>
                  <div class="col-sm-2">
                    <small class="text-muted d-block">WHT Tax</small>
                    <strong style="color: #6f42c1;">Rs <?=number_format($row['wht_amount'], 2)?></strong>
                  </div>
                  <div class="col-sm-2">
                    <small class="text-muted d-block">Balance Left</small>
                    <strong class="<?=$row['left_amount'] > 0 ? 'text-danger' : 'text-success'?>">
                      <?php if ($row['status'] === 'overpaid'): ?>
                        Overpaid (+Rs <?=number_format($row['overpaid_amount'], 2)?>)
                      <?php else: ?>
                        Rs <?=number_format($row['left_amount'], 2)?>
                      <?php endif; ?>
                    </strong>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm table-bordered table-striped mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Voucher #</th>
                        <th>Voucher Date</th>
                        <th>Cheque #</th>
                        <th>Bank</th>
                        <th class="text-right">Amount Paid</th>
                        <th class="text-right">WHT Tax</th>
                        <th>Remarks</th>
                        <th class="text-center">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $v_sr = 0;
                      foreach ($row['vouchers'] as $vi): 
                        $v_sr++;
                      ?>
                      <tr>
                        <td class="text-center"><?=$v_sr?></td>
                        <td class="text-center"><strong>Voucher #<?=$vi['voucher_id']?></strong></td>
                        <td class="text-center"><?=date('d-M-Y', strtotime($vi['voucher_date']))?></td>
                        <td><?=htmlspecialchars($vi['check_no'] ?: '—')?></td>
                        <td><?=htmlspecialchars($vi['bank_name'] ?: '—')?></td>
                        <td class="text-right font-weight-bold text-success">
                          Rs <?=number_format($vi['amount'], 2)?>
                        </td>
                        <td class="text-right text-muted">
                          <?php if ($vi['tax_amount'] > 0): ?>
                            Rs <?=number_format($vi['tax_amount'], 2)?> (<?=$vi['tax_percent']?>%)
                          <?php else: ?>
                            —
                          <?php endif; ?>
                        </td>
                        <td class="small"><?=htmlspecialchars($vi['hint'] ?: '—')?></td>
                        <td class="text-center">
                          <a href="print_voucher.php?id=<?=$vi['voucher_id']?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2" title="Print Voucher">
                            <i class="fe fe-printer"></i>
                          </a>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        <?php 
            endif;
          endforeach;
        endif;
        ?>


      </div> <!-- /.container-fluid -->
    </main> <!-- /main -->
  </div> <!-- /.wrapper -->

<?php include_once 'includes/foot.php'; ?>
<script type="text/javascript" src="js/panelV2.js"></script>
<script>
$(document).ready(function() {
  if ($.fn.select2) {
    $('.searchableSelect').select2({
      width: '100%'
    });
  }
});
</script>
</body>
</html>
