<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 

// Filter parameters
$from_date = isset($_REQUEST['from_date']) ? trim($_REQUEST['from_date']) : '';
$to_date = isset($_REQUEST['to_date']) ? trim($_REQUEST['to_date']) : '';
$filter_status = isset($_REQUEST['filter_status']) ? trim($_REQUEST['filter_status']) : 'pending_only';
$active_tab = isset($_REQUEST['tab']) ? trim($_REQUEST['tab']) : 'all';
$account_id = isset($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) : '';

// Optional single customer/supplier selection
$selected_account = null;
if (!empty($account_id)) {
    $selected_account = fetchRecord($dbc, 'customers', 'customer_id', $account_id);
}

// Date scope label
$date_scope_label = "Overall (All Time)";
if (!empty($from_date) && !empty($to_date)) {
    $date_scope_label = "From " . date('d-M-Y', strtotime($from_date)) . " To " . date('d-M-Y', strtotime($to_date));
} elseif (!empty($from_date)) {
    $date_scope_label = "From " . date('d-M-Y', strtotime($from_date)) . " Onwards";
} elseif (!empty($to_date)) {
    $date_scope_label = "Up to " . date('d-M-Y', strtotime($to_date));
}

// Setup Report Header variables for unified include
$report_title = "Customer & Supplier Pending Dues Report";
if (!empty($selected_account)) {
    $report_title = (($selected_account['customer_type'] ?? '') == 'supplier') ? "Supplier Pending Payables Report" : "Customer Pending Dues Report";
    $report_customer = $selected_account;
} else {
    $report_customer = null;
}
$report_duration = $date_scope_label;

// -------------------------------------------------------------
// 1. Fetch and Calculate Customer Dues (Accounts Receivable)
// -------------------------------------------------------------
$customers_data = [];
$total_customer_invoiced = 0;
$total_customer_received = 0;
$total_customer_pending_due = 0;
$total_customer_advance = 0;
$count_customers_with_due = 0;
$count_customers_cleared = 0;

$cust_sql = "SELECT * FROM customers WHERE customer_status = 1 AND customer_type = 'customer'";
if (!empty($selected_account)) {
    if (($selected_account['customer_type'] ?? '') == 'customer') {
        $cust_sql .= " AND customer_id = '$account_id'";
    } else {
        $cust_sql .= " AND 1 = 0"; // Exclude customers if a supplier was specifically selected
    }
}
$cust_sql .= " ORDER BY customer_name ASC";
$q_cust = mysqli_query($dbc, $cust_sql);

while ($c = mysqli_fetch_assoc($q_cust)) {
    $cid = $c['customer_id'];
    
    if (!empty($from_date) || !empty($to_date)) {
        // Opening balance prior to from_date
        $opening = 0;
        if (!empty($from_date)) {
            $q_op = mysqli_query($dbc, "SELECT SUM(CAST(credit AS DECIMAL(15,2))) AS op_cr, SUM(CAST(debit AS DECIMAL(15,2))) AS op_dr FROM transactions WHERE customer_id = '$cid' AND transaction_date < '$from_date'");
            $r_op = mysqli_fetch_assoc($q_op);
            $opening = floatval($r_op['op_cr'] ?? 0) - floatval($r_op['op_dr'] ?? 0);
        }
        
        // Date range condition
        $date_cond = "";
        if (!empty($from_date) && !empty($to_date)) {
            $date_cond = " AND transaction_date BETWEEN '$from_date' AND '$to_date'";
        } elseif (!empty($from_date)) {
            $date_cond = " AND transaction_date >= '$from_date'";
        } elseif (!empty($to_date)) {
            $date_cond = " AND transaction_date <= '$to_date'";
        }
        
        $q_period = mysqli_query($dbc, "SELECT SUM(CAST(credit AS DECIMAL(15,2))) AS p_cr, SUM(CAST(debit AS DECIMAL(15,2))) AS p_dr FROM transactions WHERE customer_id = '$cid' $date_cond");
        $r_period = mysqli_fetch_assoc($q_period);
        $invoiced = floatval($r_period['p_cr'] ?? 0);
        $received = floatval($r_period['p_dr'] ?? 0);
        $net_due = $opening + $invoiced - $received;
    } else {
        $opening = 0;
        $q_all = mysqli_query($dbc, "SELECT SUM(CAST(credit AS DECIMAL(15,2))) AS tot_cr, SUM(CAST(debit AS DECIMAL(15,2))) AS tot_dr FROM transactions WHERE customer_id = '$cid'");
        $r_all = mysqli_fetch_assoc($q_all);
        $invoiced = floatval($r_all['tot_cr'] ?? 0);
        $received = floatval($r_all['tot_dr'] ?? 0);
        $net_due = $invoiced - $received;
    }
    
    // Also check recent open invoices count for this customer
    $q_inv_cnt = mysqli_query($dbc, "SELECT COUNT(*) as inv_cnt FROM tax_sales WHERE customer_id = '$cid'");
    $r_inv_cnt = mysqli_fetch_assoc($q_inv_cnt);
    $invoices_count = intval($r_inv_cnt['inv_cnt'] ?? 0);
    
    $is_due = ($net_due > 0.01);
    $is_advance = ($net_due < -0.01);
    $is_cleared = (!$is_due && !$is_advance);
    
    if ($is_due) {
        $total_customer_pending_due += $net_due;
        $count_customers_with_due++;
    } elseif ($is_advance) {
        $total_customer_advance += abs($net_due);
    } else {
        $count_customers_cleared++;
    }
    
    $total_customer_invoiced += $invoiced;
    $total_customer_received += $received;
    
    // Filtering
    if ($filter_status == 'pending_only' && !$is_due) {
        continue;
    } elseif ($filter_status == 'cleared_only' && !$is_cleared) {
        continue;
    }
    
    $customers_data[] = [
        'customer_id' => $cid,
        'customer_name' => $c['customer_name'],
        'customer_phone' => $c['customer_phone'] ?? '',
        'customer_strn' => $c['customer_strn'] ?? '',
        'customer_ntn' => $c['customer_ntn'] ?? '',
        'customer_address' => $c['customer_address'] ?? '',
        'opening' => $opening,
        'invoiced' => $invoiced,
        'received' => $received,
        'net_due' => $net_due,
        'invoices_count' => $invoices_count,
        'status' => $is_due ? 'due' : ($is_advance ? 'advance' : 'cleared')
    ];
}

// -------------------------------------------------------------
// 2. Fetch and Calculate Supplier Dues (Accounts Payable)
// -------------------------------------------------------------
$suppliers_data = [];
$total_supplier_purchases = 0;
$total_supplier_paid = 0;
$total_supplier_pending_pay = 0;
$total_supplier_advance = 0;
$count_suppliers_with_pending = 0;
$count_suppliers_cleared = 0;

$supp_sql = "SELECT * FROM customers WHERE customer_status = 1 AND customer_type = 'supplier'";
if (!empty($selected_account)) {
    if (($selected_account['customer_type'] ?? '') == 'supplier') {
        $supp_sql .= " AND customer_id = '$account_id'";
    } else {
        $supp_sql .= " AND 1 = 0"; // Exclude suppliers if a customer was specifically selected
    }
}
$supp_sql .= " ORDER BY customer_name ASC";
$q_supp = mysqli_query($dbc, $supp_sql);

while ($s = mysqli_fetch_assoc($q_supp)) {
    $sid = $s['customer_id'];
    
    if (!empty($from_date) || !empty($to_date)) {
        $opening = 0;
        if (!empty($from_date)) {
            $q_op = mysqli_query($dbc, "SELECT SUM(CAST(debit AS DECIMAL(15,2))) AS op_dr, SUM(CAST(credit AS DECIMAL(15,2))) AS op_cr FROM transactions WHERE customer_id = '$sid' AND transaction_date < '$from_date'");
            $r_op = mysqli_fetch_assoc($q_op);
            $opening = floatval($r_op['op_dr'] ?? 0) - floatval($r_op['op_cr'] ?? 0);
        }
        
        $date_cond = "";
        if (!empty($from_date) && !empty($to_date)) {
            $date_cond = " AND transaction_date BETWEEN '$from_date' AND '$to_date'";
        } elseif (!empty($from_date)) {
            $date_cond = " AND transaction_date >= '$from_date'";
        } elseif (!empty($to_date)) {
            $date_cond = " AND transaction_date <= '$to_date'";
        }
        
        $q_period = mysqli_query($dbc, "SELECT SUM(CAST(debit AS DECIMAL(15,2))) AS p_dr, SUM(CAST(credit AS DECIMAL(15,2))) AS p_cr FROM transactions WHERE customer_id = '$sid' $date_cond");
        $r_period = mysqli_fetch_assoc($q_period);
        $purchases = floatval($r_period['p_dr'] ?? 0);
        $paid = floatval($r_period['p_cr'] ?? 0);
        $net_pending = $opening + $purchases - $paid;
    } else {
        $opening = 0;
        $q_all = mysqli_query($dbc, "SELECT SUM(CAST(debit AS DECIMAL(15,2))) AS tot_dr, SUM(CAST(credit AS DECIMAL(15,2))) AS tot_cr FROM transactions WHERE customer_id = '$sid'");
        $r_all = mysqli_fetch_assoc($q_all);
        $purchases = floatval($r_all['tot_dr'] ?? 0);
        $paid = floatval($r_all['tot_cr'] ?? 0);
        $net_pending = $purchases - $paid;
    }
    
    // Also check tax purchases count
    $q_tp_cnt = mysqli_query($dbc, "SELECT COUNT(*) as tp_cnt FROM tax_purchases WHERE supplier_id = '$sid'");
    $r_tp_cnt = mysqli_fetch_assoc($q_tp_cnt);
    $purchases_count = intval($r_tp_cnt['tp_cnt'] ?? 0);
    
    $is_pending = ($net_pending > 0.01);
    $is_advance = ($net_pending < -0.01);
    $is_cleared = (!$is_pending && !$is_advance);
    
    if ($is_pending) {
        $total_supplier_pending_pay += $net_pending;
        $count_suppliers_with_pending++;
    } elseif ($is_advance) {
        $total_supplier_advance += abs($net_pending);
    } else {
        $count_suppliers_cleared++;
    }
    
    $total_supplier_purchases += $purchases;
    $total_supplier_paid += $paid;
    
    if ($filter_status == 'pending_only' && !$is_pending) {
        continue;
    } elseif ($filter_status == 'cleared_only' && !$is_cleared) {
        continue;
    }
    
    $suppliers_data[] = [
        'supplier_id' => $sid,
        'supplier_name' => $s['customer_name'],
        'supplier_phone' => $s['customer_phone'] ?? '',
        'supplier_strn' => $s['customer_strn'] ?? '',
        'supplier_ntn' => $s['customer_ntn'] ?? '',
        'supplier_address' => $s['customer_address'] ?? '',
        'opening' => $opening,
        'purchases' => $purchases,
        'paid' => $paid,
        'net_pending' => $net_pending,
        'purchases_count' => $purchases_count,
        'status' => $is_pending ? 'pending' : ($is_advance ? 'advance' : 'cleared')
    ];
}

// Net Position (Receivables − Payables)
$net_working_balance = $total_customer_pending_due - $total_supplier_pending_pay;
?>
<style type="text/css">
  /* Generous Margins: Top, Left, Right */
  main.main-content {
    padding-top: 35px !important;
    padding-left: 45px !important;
    padding-right: 45px !important;
    padding-bottom: 50px !important;
  }
  @media (max-width: 992px) {
    main.main-content {
      padding-top: 15px !important;
      padding-left: 15px !important;
      padding-right: 15px !important;
    }
  }

  /* Remove Bottom Line under Header */
  .unified-report-header {
    border-bottom: none !important;
    margin-bottom: 10px !important;
    padding-bottom: 5px !important;
  }
  .report-header-box {
    border-bottom: none !important;
  }

  /* UI Styling */
  .summary-card {
    border-radius: 8px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: none;
    overflow: hidden;
  }
  .summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
  }
  .summary-card .card-body {
    padding: 1.25rem;
  }
  .stat-label {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
  }
  .stat-value {
    font-size: 26px;
    font-weight: 800;
    line-height: 1.2;
    margin-top: 5px;
    margin-bottom: 3px;
  }
  .stat-meta {
    font-size: 12px;
    color: #888;
  }
  .table-custom-header thead th {
    background-color: #2b3035 !important;
    color: #fff !important;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    vertical-align: middle;
    border: 1px solid #454d55;
    padding: 10px 8px;
  }
  .table-custom-header tbody td {
    font-size: 14px;
    vertical-align: middle;
    border: 1px solid #dee2e6;
    padding: 9px 10px;
  }
  .table-custom-header tbody tr:hover {
    background-color: #f8f9fa;
  }
  .table-custom-header tfoot th {
    background-color: #f1f3f5;
    font-size: 15px;
    font-weight: 800;
    border: 1px solid #dee2e6;
    padding: 10px;
  }
  .final-summary-card {
    background: linear-gradient(135deg, #ffffff 0%, #fdfdfd 100%);
    border: 1.5px solid #930027;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(147, 0, 39, 0.08);
  }
  .grand-number-box {
    padding: 20px 25px;
    border-radius: 8px;
    height: 100%;
  }
  .nav-pills .nav-link {
    font-weight: 600;
    font-size: 14px;
    color: #495057;
    border-radius: 20px;
    padding: 8px 18px;
    margin-right: 8px;
  }
  .nav-pills .nav-link.active {
    background-color: #930027;
    color: #fff;
  }
  .badge-due {
    background-color: #fff0f0;
    color: #d90429;
    border: 1px solid #ffccd5;
    font-weight: 700;
    font-size: 12px;
    padding: 5px 9px;
  }
  .badge-cleared {
    background-color: #eafbe7;
    color: #2b9348;
    border: 1px solid #b7efc5;
    font-weight: 700;
    font-size: 12px;
    padding: 5px 9px;
  }
  .badge-advance {
    background-color: #e7f5ff;
    color: #1971c2;
    border: 1px solid #a5d8ff;
    font-weight: 700;
    font-size: 12px;
    padding: 5px 9px;
  }

  /* Signature Styling */
  .print-signature-row {
    display: none;
  }

  /* Print Stylesheet Overrides */
  @page {
    size: auto;
    margin: 20mm 20mm 15mm 20mm !important;
  }
  @media print {
    .d-print-none, .navbar, .toggle-sidebar, .sidebar, #header, footer, .btn, .breadcrumb, .nav-pills, .form-row, .modal,
    .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate, .paging_simple_numbers {
      display: none !important;
    }
    body {
      background: #fff !important;
      color: #000 !important;
      font-size: 12px !important;
      margin: 0 !important;
      padding-top: 15px !important;
      padding-left: 20px !important;
      padding-right: 20px !important;
    }
    .wrapper, main, .main-content, .container-fluid, .card, .card-body {
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
      box-shadow: none !important;
      width: 100% !important;
    }
    .unified-report-header {
      border-bottom: none !important;
      padding-bottom: 5px !important;
      margin-bottom: 10px !important;
    }
    .summary-card {
      border: 1.5px solid #333 !important;
      box-shadow: none !important;
      margin-bottom: 12px !important;
    }
    .stat-value {
      font-size: 18px !important;
      font-weight: 800 !important;
      color: #000 !important;
    }
    .table-custom-header thead th {
      background-color: #f1f3f5 !important;
      color: #000 !important;
      font-size: 11.5px !important;
      font-weight: 800 !important;
      padding: 6px 5px !important;
      border: 1.5px solid #000 !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .table-custom-header tbody td {
      font-size: 11.5px !important;
      padding: 5px 6px !important;
      border: 1px solid #333 !important;
      color: #000 !important;
      font-weight: 500 !important;
    }
    .table-custom-header tfoot th {
      font-size: 12px !important;
      padding: 6px !important;
      border: 1.5px solid #000 !important;
      background-color: #e9ecef !important;
      color: #000 !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .final-summary-card {
      border: 1px solid #ccc !important;
      border-bottom: none !important;
      box-shadow: none !important;
      background: #fdfdfd !important;
      page-break-inside: avoid !important;
    }
    .print-signature-row {
      display: flex !important;
      justify-content: space-between !important;
      margin-top: 35px !important;
      padding-top: 10px !important;
      border-top: none !important;
      border-bottom: none !important;
      page-break-inside: avoid !important;
    }
    .print-sig-col {
      width: 28% !important;
      text-align: center !important;
    }
    .print-sig-line {
      display: none !important;
      border-top: none !important;
    }
    .print-sig-title {
      font-size: 12px !important;
      font-weight: 700 !important;
      color: #000 !important;
    }
    .print-sig-sub {
      font-size: 11px !important;
      color: #444 !important;
    }
    .page-break {
      page-break-before: always;
    }
  }
</style>

<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>
    
    <main role="main" class="main-content">
      <div class="container-fluid">

        <!-- Top Action Toolbar (Hidden on Print) -->
        <div class="d-flex justify-content-between align-items-center d-print-none mt-3 mb-2">
          <div>
            <span class="badge badge-primary px-3 py-2 font-weight-bold" style="font-size: 13px;">
              <i class="fe fe-activity mr-1"></i> Pending Dues Statement
            </span>
          </div>
          <div>
            <button onclick="window.print();" class="btn btn-primary font-weight-bold shadow-sm mr-1">
              <i class="fe fe-printer mr-1"></i> Print Report
            </button>
           </div>
        </div>

        <!-- Unified Report Header (Centered Logo, Address, Phone, Customer, Duration) -->
        <?php include 'includes/report_header.php'; ?>

        <!-- Filter Controls (d-print-none) -->
        <div class="card shadow-sm mb-4 d-print-none">
          <div class="card-body py-3">
            <form action="dues_report.php" method="GET" id="filterForm">
              <div class="row align-items-end">
                <div class="col-md-3 col-sm-6 mb-2">
                  <label class="font-weight-bold small text-muted">ACCOUNT (CUSTOMER / SUPPLIER)</label>
                  <select name="account_id" class="form-control form-control-sm select2 searchableSelect">
                    <option value="">-- All Accounts (Overall) --</option>
                    <optgroup label="Customers">
                      <?php 
                      $q_opt_c = mysqli_query($dbc, "SELECT customer_id, customer_name, customer_phone FROM customers WHERE customer_status = 1 AND customer_type = 'customer' ORDER BY customer_name ASC");
                      while ($rc = mysqli_fetch_assoc($q_opt_c)):
                        $sel = ($account_id == $rc['customer_id']) ? 'selected' : '';
                      ?>
                        <option value="<?=$rc['customer_id']?>" <?=$sel?>><?=htmlspecialchars(getCustomerDisplayName($rc['customer_name'], $rc['customer_phone']))?></option>
                      <?php endwhile; ?>
                    </optgroup>
                    <optgroup label="Suppliers">
                      <?php 
                      $q_opt_s = mysqli_query($dbc, "SELECT customer_id, customer_name, customer_phone FROM customers WHERE customer_status = 1 AND customer_type = 'supplier' ORDER BY customer_name ASC");
                      while ($rs = mysqli_fetch_assoc($q_opt_s)):
                        $sel = ($account_id == $rs['customer_id']) ? 'selected' : '';
                      ?>
                        <option value="<?=$rs['customer_id']?>" <?=$sel?>><?=htmlspecialchars(getCustomerDisplayName($rs['customer_name'], $rs['customer_phone']))?></option>
                      <?php endwhile; ?>
                    </optgroup>
                  </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                  <label class="font-weight-bold small text-muted">FROM DATE</label>
                  <input type="date" name="from_date" value="<?=htmlspecialchars($from_date)?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                  <label class="font-weight-bold small text-muted">TO DATE</label>
                  <input type="date" name="to_date" value="<?=htmlspecialchars($to_date)?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 col-sm-6 mb-2">
                  <label class="font-weight-bold small text-muted">STATUS FILTER</label>
                  <select name="filter_status" class="form-control form-control-sm searchableSelect">
                    <option value="pending_only" <?=$filter_status == 'pending_only' ? 'selected' : ''?>>Only Pending Accounts (Due > 0)</option>
                    <option value="all" <?=$filter_status == 'all' ? 'selected' : ''?>>All Accounts (Including Cleared)</option>
                    <option value="cleared_only" <?=$filter_status == 'cleared_only' ? 'selected' : ''?>>Only Cleared Accounts (Due = 0)</option>
                  </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2 text-right">
                  <button type="submit" class="btn btn-sm btn-admin mr-1">
                    <i class="fe fe-filter mr-1"></i> Filter
                  </button>
                  <a href="dues_report.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fe fe-refresh-cw mr-1"></i> Reset
                  </a>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- 4 KPI Summary Cards -->
        <div class="row mb-4">
          <!-- Card 1: Customer Dues -->
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card summary-card border-left-success h-100" style="border-left: 5px solid #28a745 !important;">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col mr-2">
                    <div class="stat-label text-success">
                      <i class="fe fe-arrow-down-left mr-1"></i> Customers Pending Due
                    </div>
                    <div class="stat-value text-success">
                      Rs. <?=number_format($total_customer_pending_due)?>
                    </div>
                    <div class="stat-meta">
                      <strong><?=$count_customers_with_due?></strong> customer(s) with pending balance
                    </div>
                  </div>
                  <div class="col-auto">
                    <span class="fe fe-users fe-32 text-success" style="opacity: 0.35;"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: Supplier Payables -->
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card summary-card border-left-danger h-100" style="border-left: 5px solid #dc3545 !important;">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col mr-2">
                    <div class="stat-label text-danger">
                      <i class="fe fe-arrow-up-right mr-1"></i> Suppliers Pending to Pay
                    </div>
                    <div class="stat-value text-danger">
                      Rs. <?=number_format($total_supplier_pending_pay)?>
                    </div>
                    <div class="stat-meta">
                      <strong><?=$count_suppliers_with_pending?></strong> supplier(s) awaiting payment
                    </div>
                  </div>
                  <div class="col-auto">
                    <span class="fe fe-truck fe-32 text-danger" style="opacity: 0.35;"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 3: Net Position -->
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card summary-card border-left-primary h-100" style="border-left: 5px solid #007bff !important;">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col mr-2">
                    <div class="stat-label text-primary">
                      <i class="fe fe-activity mr-1"></i> Net Working Balance
                    </div>
                    <div class="stat-value <?=$net_working_balance >= 0 ? 'text-primary' : 'text-danger'?>">
                      Rs. <?=number_format($net_working_balance)?>
                    </div>
                    <div class="stat-meta">
                      <?php if ($net_working_balance >= 0): ?>
                        <span class="badge badge-success">Net Receivable Surplus</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Net Payable Deficit</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <span class="fe fe-dollar-sign fe-32 text-primary" style="opacity: 0.35;"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 4: Accounts Scope Overview -->
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card summary-card border-left-dark h-100" style="border-left: 5px solid #343a40 !important;">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col mr-2">
                    <div class="stat-label text-dark">
                      <i class="fe fe-briefcase mr-1"></i> Accounts In Scope
                    </div>
                    <div class="stat-value text-dark">
                      <?=(count($customers_data) + count($suppliers_data))?>
                    </div>
                    <div class="stat-meta">
                      <?=count($customers_data)?> Customers &bull; <?=count($suppliers_data)?> Suppliers
                    </div>
                  </div>
                  <div class="col-auto">
                    <span class="fe fe-pie-chart fe-32 text-dark" style="opacity: 0.35;"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Navigation Pills (d-print-none) -->
        <div class="d-print-none mb-3">
          <ul class="nav nav-pills" id="reportTabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="tab-all-link" data-toggle="pill" href="#tab-all" role="tab">
                <i class="fe fe-layers mr-1"></i> View All (Both Customers &amp; Suppliers)
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="tab-customers-link" data-toggle="pill" href="#tab-customers" role="tab">
                <i class="fe fe-users mr-1"></i> Customers Due Only 
                <span class="badge badge-light ml-1"><?=$count_customers_with_due?></span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="tab-suppliers-link" data-toggle="pill" href="#tab-suppliers" role="tab">
                <i class="fe fe-truck mr-1"></i> Suppliers Pending Only 
                <span class="badge badge-light ml-1"><?=$count_suppliers_with_pending?></span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="tab-summary-link" data-toggle="pill" href="#tab-summary" role="tab">
                <i class="fe fe-check-circle mr-1"></i> Grand Totals Summary
              </a>
            </li>
          </ul>
        </div>

        <!-- Tab Contents Wrapper -->
        <div class="tab-content" id="reportTabContent">

          <!-- TAB 1: ALL (Both Stacked) -->
          <div class="tab-pane fade show active" id="tab-all" role="tabpanel">

            <!-- SECTION 1: CUSTOMERS DUE TABLE -->
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                <h5 class="mb-0 text-white font-weight-bold">
                  <i class="fe fe-users mr-2 text-success"></i> Customer Pending Dues (Receivables)
                </h5>
                <span class="badge badge-light font-weight-bold px-2 py-1">
                  Total Due: Rs. <?=number_format($total_customer_pending_due)?>
                </span>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-hover table-custom-header mb-0" id="customersTable">
                    <thead>
                      <tr class="text-center">
                        <th style="width: 50px;">Sr #</th>
                        <th style="text-align: left;">Customer Name</th>
                        <th style="width: 130px;">Phone / Contact</th>
                        <th style="width: 130px;">STRN / NTN</th>
                        <th style="width: 130px;" class="text-right">Total Invoiced (Rs)</th>
                        <th style="width: 130px;" class="text-right">Total Received (Rs)</th>
                        <th style="width: 150px;" class="text-right">Pending Due (Rs)</th>
                        <th style="width: 110px;" class="text-center">Status</th>
                        <th style="width: 140px;" class="text-center d-print-none">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      if (!empty($customers_data)):
                        $sr = 0;
                        foreach ($customers_data as $row):
                          $sr++;
                      ?>
                        <tr>
                          <td class="text-center font-weight-bold text-muted"><?=$sr?></td>
                          <td class="font-weight-bold text-dark">
                            <span class="text-uppercase"><?=$row['customer_name']?></span>
                            <?php if (!empty($row['customer_address'])): ?>
                              <br/><small class="text-muted font-weight-normal"><?=htmlspecialchars($row['customer_address'])?></small>
                            <?php endif; ?>
                          </td>
                          <td class="text-center"><?=$row['customer_phone'] ?: '<span class="text-muted">-</span>'?></td>
                          <td class="text-center">
                            <?php if (!empty($row['customer_strn'])): ?>
                              <span class="badge badge-light border">STRN: <?=htmlspecialchars($row['customer_strn'])?></span><br/>
                            <?php endif; ?>
                            <?php if (!empty($row['customer_ntn'])): ?>
                              <span class="badge badge-light border">NTN: <?=htmlspecialchars($row['customer_ntn'])?></span>
                            <?php endif; ?>
                            <?php if (empty($row['customer_strn']) && empty($row['customer_ntn'])): ?>
                              <span class="text-muted">-</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-right font-weight-bold text-secondary">
                            <?=number_format($row['invoiced'])?>
                          </td>
                          <td class="text-right font-weight-bold text-success">
                            <?=number_format($row['received'])?>
                          </td>
                          <td class="text-right font-weight-bold" style="font-size: 15px;">
                            <?php if ($row['net_due'] > 0.01): ?>
                              <span class="text-danger font-weight-bold">
                                <?=number_format($row['net_due'])?>
                              </span>
                            <?php elseif ($row['net_due'] < -0.01): ?>
                              <span class="text-info font-weight-bold" title="Advance balance paid by customer">
                                (<?=number_format(abs($row['net_due']))?>) <small>Adv</small>
                              </span>
                            <?php else: ?>
                              <span class="text-muted">0</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center">
                            <?php if ($row['status'] == 'due'): ?>
                              <span class="badge badge-due"><i class="fe fe-alert-circle mr-1"></i>Pending Due</span>
                            <?php elseif ($row['status'] == 'advance'): ?>
                              <span class="badge badge-advance"><i class="fe fe-info mr-1"></i>Advance</span>
                            <?php else: ?>
                              <span class="badge badge-cleared"><i class="fe fe-check mr-1"></i>Cleared</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center d-print-none">
                            <div class="btn-group btn-group-sm">
                              <a href="reports.php?customer_id=<?=$row['customer_id']?>&genealledger=1" target="_blank" class="btn btn-outline-primary" title="View Customer Ledger">
                                <i class="fe fe-file-text"></i> Ledger
                              </a>
                              <a href="print_balance.php?customer=<?=$row['customer_id']?>" target="_blank" class="btn btn-outline-secondary" title="Print Balance Slip">
                                <i class="fe fe-printer"></i> Slip
                              </a>
                            </div>
                          </td>
                        </tr>
                      <?php 
                        endforeach;
                      else:
                      ?>
                        <tr>
                          <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fe fe-check-circle fe-24 d-block mb-2 text-success"></i>
                            <strong>No customer dues found matching the criteria.</strong>
                          </td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                    <tfoot>
                      <tr class="bg-light">
                        <th colspan="4" class="text-right font-weight-bold">Total Customer Receivables:</th>
                        <th class="text-right font-weight-bold text-secondary"><?=number_format($total_customer_invoiced)?></th>
                        <th class="text-right font-weight-bold text-success"><?=number_format($total_customer_received)?></th>
                        <th class="text-right font-weight-bold text-danger h6 mb-0">
                          Rs. <?=number_format($total_customer_pending_due)?>
                        </th>
                        <th colspan="2" class="d-print-none"></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <!-- SECTION 2: SUPPLIERS PENDING TO PAY TABLE -->
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                <h5 class="mb-0 text-white font-weight-bold">
                  <i class="fe fe-truck mr-2 text-danger"></i> Supplier Pending Payables (Amount We Must Pay)
                </h5>
                <span class="badge badge-light font-weight-bold px-2 py-1">
                  Total Payable: Rs. <?=number_format($total_supplier_pending_pay)?>
                </span>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-hover table-custom-header mb-0" id="suppliersTable">
                    <thead>
                      <tr class="text-center">
                        <th style="width: 50px;">Sr #</th>
                        <th style="text-align: left;">Supplier Name</th>
                        <th style="width: 130px;">Phone / Contact</th>
                        <th style="width: 130px;">STRN / NTN</th>
                        <th style="width: 130px;" class="text-right">Total Purchases (Rs)</th>
                        <th style="width: 130px;" class="text-right">Total Paid (Rs)</th>
                        <th style="width: 150px;" class="text-right">Pending to Pay (Rs)</th>
                        <th style="width: 110px;" class="text-center">Status</th>
                        <th style="width: 140px;" class="text-center d-print-none">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      if (!empty($suppliers_data)):
                        $sr = 0;
                        foreach ($suppliers_data as $row):
                          $sr++;
                      ?>
                        <tr>
                          <td class="text-center font-weight-bold text-muted"><?=$sr?></td>
                          <td class="font-weight-bold text-dark">
                            <span class="text-uppercase"><?=$row['supplier_name']?></span>
                            <?php if (!empty($row['supplier_address'])): ?>
                              <br/><small class="text-muted font-weight-normal"><?=htmlspecialchars($row['supplier_address'])?></small>
                            <?php endif; ?>
                          </td>
                          <td class="text-center"><?=$row['supplier_phone'] ?: '<span class="text-muted">-</span>'?></td>
                          <td class="text-center">
                            <?php if (!empty($row['supplier_strn'])): ?>
                              <span class="badge badge-light border">STRN: <?=htmlspecialchars($row['supplier_strn'])?></span><br/>
                            <?php endif; ?>
                            <?php if (!empty($row['supplier_ntn'])): ?>
                              <span class="badge badge-light border">NTN: <?=htmlspecialchars($row['supplier_ntn'])?></span>
                            <?php endif; ?>
                            <?php if (empty($row['supplier_strn']) && empty($row['supplier_ntn'])): ?>
                              <span class="text-muted">-</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-right font-weight-bold text-secondary">
                            <?=number_format($row['purchases'])?>
                          </td>
                          <td class="text-right font-weight-bold text-success">
                            <?=number_format($row['paid'])?>
                          </td>
                          <td class="text-right font-weight-bold" style="font-size: 15px;">
                            <?php if ($row['net_pending'] > 0.01): ?>
                              <span class="text-danger font-weight-bold">
                                <?=number_format($row['net_pending'])?>
                              </span>
                            <?php elseif ($row['net_pending'] < -0.01): ?>
                              <span class="text-info font-weight-bold" title="Advance payment made to supplier">
                                (<?=number_format(abs($row['net_pending']))?>) <small>Adv</small>
                              </span>
                            <?php else: ?>
                              <span class="text-muted">0</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center">
                            <?php if ($row['status'] == 'pending'): ?>
                              <span class="badge badge-due"><i class="fe fe-clock mr-1"></i>Pending Pay</span>
                            <?php elseif ($row['status'] == 'advance'): ?>
                              <span class="badge badge-advance"><i class="fe fe-info mr-1"></i>Advance Paid</span>
                            <?php else: ?>
                              <span class="badge badge-cleared"><i class="fe fe-check mr-1"></i>Cleared</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center d-print-none">
                            <div class="btn-group btn-group-sm">
                              <a href="reports.php?customer_id=<?=$row['supplier_id']?>&genealledger=1" target="_blank" class="btn btn-outline-primary" title="View Supplier Ledger">
                                <i class="fe fe-file-text"></i> Ledger
                              </a>
                              <a href="print_balance.php?customer=<?=$row['supplier_id']?>" target="_blank" class="btn btn-outline-secondary" title="Print Balance Slip">
                                <i class="fe fe-printer"></i> Slip
                              </a>
                            </div>
                          </td>
                        </tr>
                      <?php 
                        endforeach;
                      else:
                      ?>
                        <tr>
                          <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fe fe-check-circle fe-24 d-block mb-2 text-success"></i>
                            <strong>No supplier pending payments found matching the criteria.</strong>
                          </td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                    <tfoot>
                      <tr class="bg-light">
                        <th colspan="4" class="text-right font-weight-bold">Total Supplier Payables:</th>
                        <th class="text-right font-weight-bold text-secondary"><?=number_format($total_supplier_purchases)?></th>
                        <th class="text-right font-weight-bold text-success"><?=number_format($total_supplier_paid)?></th>
                        <th class="text-right font-weight-bold text-danger h6 mb-0">
                          Rs. <?=number_format($total_supplier_pending_pay)?>
                        </th>
                        <th colspan="2" class="d-print-none"></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <!-- SECTION 3: GRAND TOTAL SUMMARY BOX AT LAST -->
            <div class="final-summary-card p-4 mb-4">
              <div class="row align-items-center">
                <div class="col-12 text-center mb-3">
                  <h4 class="font-weight-bold text-dark text-uppercase mb-1" style="letter-spacing: 1px;">
                    <i class="fe fe-award text-danger mr-2"></i>Executive Summary: Total Receivables &amp; Payables
                  </h4>
                  <p class="text-muted small mb-0">Overall Pending Settlement Status for <?=$get_company['name']?></p>
                </div>
              </div>
              <div class="row">
                <!-- Customer Total Box -->
                <div class="col-md-6 mb-3 mb-md-0">
                  <div class="grand-number-box bg-white border border-success">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="font-weight-bold text-success uppercase">
                        <i class="fe fe-arrow-down-left mr-1"></i> Total Pending Receiving from Customers
                      </span>
                      <span class="badge badge-success px-2 py-1"><?=$count_customers_with_due?> Customer(s)</span>
                    </div>
                    <div class="h2 font-weight-bold text-success mb-1">
                      Rs. <?=number_format($total_customer_pending_due)?>
                    </div>
                    <small class="text-muted">Total accounts receivable pending recovery from clients</small>
                  </div>
                </div>

                <!-- Supplier Total Box -->
                <div class="col-md-6">
                  <div class="grand-number-box bg-white border border-danger">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="font-weight-bold text-danger uppercase">
                        <i class="fe fe-arrow-up-right mr-1"></i> Total Pending Paying to Suppliers
                      </span>
                      <span class="badge badge-danger px-2 py-1"><?=$count_suppliers_with_pending?> Supplier(s)</span>
                    </div>
                    <div class="h2 font-weight-bold text-danger mb-1">
                      Rs. <?=number_format($total_supplier_pending_pay)?>
                    </div>
                    <small class="text-muted">Total accounts payable pending payment to vendors/suppliers</small>
                  </div>
                </div>
              </div>

              <!-- Net Balance Difference Strip -->
              <div class="row mt-3">
                <div class="col-12">
                  <div class="p-3 rounded text-center <?=$net_working_balance >= 0 ? 'bg-light border border-primary' : 'bg-light border border-warning'?>">
                    <div class="row align-items-center">
                      <div class="col-md-8 text-md-left text-center mb-2 mb-md-0">
                        <h5 class="mb-0 font-weight-bold text-dark">
                          Net Position (Receivables − Payables): 
                          <span class="<?=$net_working_balance >= 0 ? 'text-primary' : 'text-danger'?>">
                            Rs. <?=number_format($net_working_balance)?>
                          </span>
                        </h5>
                        <small class="text-muted">
                          <?php if ($net_working_balance > 0): ?>
                            <i class="fe fe-trending-up text-success mr-1"></i><strong>Favorable Cashflow:</strong> Expected customer inflows exceed supplier payables by Rs. <?=number_format($net_working_balance)?>.
                          <?php elseif ($net_working_balance < 0): ?>
                            <i class="fe fe-trending-down text-danger mr-1"></i><strong>Liability Deficit:</strong> Supplier payables exceed expected customer recoveries by Rs. <?=number_format(abs($net_working_balance))?>.
                          <?php else: ?>
                            <i class="fe fe-minus text-info mr-1"></i><strong>Equilibrium:</strong> Pending receivables are exactly equal to pending payables.
                          <?php endif; ?>
                        </small>
                      </div>
                      <div class="col-md-4 text-md-right text-center">
                        <span class="badge <?=$net_working_balance >= 0 ? 'badge-primary' : 'badge-danger'?> p-2 font-weight-bold" style="font-size: 13px;">
                          <?=$net_working_balance >= 0 ? 'NET RECEIVABLE (SURPLUS)' : 'NET PAYABLE (DEFICIT)'?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>

          </div> <!-- /tab-all -->

          <!-- TAB 2: CUSTOMERS ONLY -->
          <div class="tab-pane fade" id="tab-customers" role="tabpanel">
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white font-weight-bold"><i class="fe fe-users mr-2 text-success"></i> Customer Receivables</h5>
                <span class="badge badge-success px-2 py-1">Total Due: Rs. <?=number_format($total_customer_pending_due)?></span>
              </div>
              <div class="card-body p-0">
                <div class="p-3">
                  <div class="alert alert-info py-2 mb-3">
                    <strong>Total Pending from Customers:</strong> Rs. <?=number_format($total_customer_pending_due)?> across <strong><?=$count_customers_with_due?></strong> customer account(s).
                  </div>
                </div>
                <!-- Re-use customers table view in tab -->
                <div class="table-responsive px-3 pb-3">
                  <table class="table table-bordered table-striped table-hover table-custom-header mb-0" id="customersOnlyTable">
                    <thead>
                      <tr class="text-center">
                        <th style="width: 50px;">#</th>
                        <th style="text-align: left;">Customer Name</th>
                        <th>Phone</th>
                        <th>STRN / NTN</th>
                        <th class="text-right">Total Invoiced</th>
                        <th class="text-right">Total Received</th>
                        <th class="text-right">Pending Due (Rs)</th>
                        <th>Status</th>
                        <th class="d-print-none">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($customers_data)): $i=0; foreach ($customers_data as $row): $i++; ?>
                        <tr>
                          <td class="text-center"><?=$i?></td>
                          <td class="font-weight-bold text-uppercase"><?=$row['customer_name']?></td>
                          <td class="text-center"><?=$row['customer_phone'] ?: '-'?></td>
                          <td class="text-center"><?=$row['customer_strn'] ?: ($row['customer_ntn'] ?: '-')?></td>
                          <td class="text-right"><?=number_format($row['invoiced'])?></td>
                          <td class="text-right"><?=number_format($row['received'])?></td>
                          <td class="text-right font-weight-bold" style="font-size: 15px;">
                            <?php if ($row['net_due'] > 0.01): ?>
                              <span class="text-danger font-weight-bold"><?=number_format($row['net_due'])?></span>
                            <?php elseif ($row['net_due'] < -0.01): ?>
                              <span class="text-info font-weight-bold">(<?=number_format(abs($row['net_due']))?>) <small>Adv</small></span>
                            <?php else: ?>
                              <span class="text-muted font-weight-normal">0</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center">
                            <?php if ($row['status'] == 'due'): ?>
                              <span class="badge badge-due"><i class="fe fe-alert-circle mr-1"></i>Pending Due</span>
                            <?php elseif ($row['status'] == 'advance'): ?>
                              <span class="badge badge-advance"><i class="fe fe-info mr-1"></i>Advance</span>
                            <?php else: ?>
                              <span class="badge badge-cleared"><i class="fe fe-check mr-1"></i>Cleared</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center d-print-none">
                            <a href="reports.php?customer_id=<?=$row['customer_id']?>&genealledger=1" target="_blank" class="btn btn-sm btn-outline-primary">Ledger</a>
                            <a href="print_balance.php?customer=<?=$row['customer_id']?>" target="_blank" class="btn btn-sm btn-outline-secondary">Slip</a>
                          </td>
                        </tr>
                      <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center py-3 text-muted">No customer records found matching filter.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div> <!-- /tab-customers -->

          <!-- TAB 3: SUPPLIERS ONLY -->
          <div class="tab-pane fade" id="tab-suppliers" role="tabpanel">
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white font-weight-bold"><i class="fe fe-truck mr-2 text-danger"></i> Supplier Payables</h5>
                <span class="badge badge-danger px-2 py-1">Total Payable: Rs. <?=number_format($total_supplier_pending_pay)?></span>
              </div>
              <div class="card-body p-0">
                <div class="p-3">
                  <div class="alert alert-warning py-2 mb-3">
                    <strong>Total Pending Paying to Suppliers:</strong> Rs. <?=number_format($total_supplier_pending_pay)?> across <strong><?=$count_suppliers_with_pending?></strong> supplier account(s).
                  </div>
                </div>
                <div class="table-responsive px-3 pb-3">
                  <table class="table table-bordered table-striped table-hover table-custom-header mb-0" id="suppliersOnlyTable">
                    <thead>
                      <tr class="text-center">
                        <th style="width: 50px;">#</th>
                        <th style="text-align: left;">Supplier Name</th>
                        <th>Phone</th>
                        <th>STRN / NTN</th>
                        <th class="text-right">Total Purchases</th>
                        <th class="text-right">Total Paid</th>
                        <th class="text-right">Pending to Pay (Rs)</th>
                        <th>Status</th>
                        <th class="d-print-none">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($suppliers_data)): $i=0; foreach ($suppliers_data as $row): $i++; ?>
                        <tr>
                          <td class="text-center"><?=$i?></td>
                          <td class="font-weight-bold text-uppercase"><?=$row['supplier_name']?></td>
                          <td class="text-center"><?=$row['supplier_phone'] ?: '-'?></td>
                          <td class="text-center"><?=$row['supplier_strn'] ?: ($row['supplier_ntn'] ?: '-')?></td>
                          <td class="text-right"><?=number_format($row['purchases'])?></td>
                          <td class="text-right"><?=number_format($row['paid'])?></td>
                          <td class="text-right font-weight-bold" style="font-size: 15px;">
                            <?php if ($row['net_pending'] > 0.01): ?>
                              <span class="text-danger font-weight-bold"><?=number_format($row['net_pending'])?></span>
                            <?php elseif ($row['net_pending'] < -0.01): ?>
                              <span class="text-info font-weight-bold">(<?=number_format(abs($row['net_pending']))?>) <small>Adv</small></span>
                            <?php else: ?>
                              <span class="text-muted font-weight-normal">0</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center">
                            <?php if ($row['status'] == 'pending'): ?>
                              <span class="badge badge-due"><i class="fe fe-clock mr-1"></i>Pending Pay</span>
                            <?php elseif ($row['status'] == 'advance'): ?>
                              <span class="badge badge-advance"><i class="fe fe-info mr-1"></i>Advance Paid</span>
                            <?php else: ?>
                              <span class="badge badge-cleared"><i class="fe fe-check mr-1"></i>Cleared</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center d-print-none">
                            <a href="reports.php?customer_id=<?=$row['supplier_id']?>&genealledger=1" target="_blank" class="btn btn-sm btn-outline-primary">Ledger</a>
                            <a href="print_balance.php?customer=<?=$row['supplier_id']?>" target="_blank" class="btn btn-sm btn-outline-secondary">Slip</a>
                          </td>
                        </tr>
                      <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center py-3 text-muted">No supplier records found matching filter.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div> <!-- /tab-suppliers -->

          <!-- TAB 4: GRAND SUMMARY ONLY -->
          <div class="tab-pane fade" id="tab-summary" role="tabpanel">
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-dark text-white">
                <h5 class="mb-0 text-white font-weight-bold"><i class="fe fe-award mr-2 text-warning"></i> Complete Financial Position Summary</h5>
              </div>
              <div class="card-body p-4">
                <div class="table-responsive">
                  <table class="table table-bordered mb-0">
                    <thead class="thead-dark">
                      <tr>
                        <th>Metric / Classification</th>
                        <th class="text-center">Count of Accounts</th>
                        <th class="text-right">Total Billed / Purchased</th>
                        <th class="text-right">Total Settled / Paid</th>
                        <th class="text-right">Total Pending Balance (Rs)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="font-weight-bold text-success">
                          <i class="fe fe-arrow-down-left mr-1"></i> Customer Receivables (Dues to Collect)
                        </td>
                        <td class="text-center font-weight-bold"><?=$count_customers_with_due?> Accounts</td>
                        <td class="text-right"><?=number_format($total_customer_invoiced)?></td>
                        <td class="text-right"><?=number_format($total_customer_received)?></td>
                        <td class="text-right font-weight-bold text-success h5 mb-0">
                          Rs. <?=number_format($total_customer_pending_due)?>
                        </td>
                      </tr>
                      <tr>
                        <td class="font-weight-bold text-danger">
                          <i class="fe fe-arrow-up-right mr-1"></i> Supplier Payables (Dues We Must Pay)
                        </td>
                        <td class="text-center font-weight-bold"><?=$count_suppliers_with_pending?> Accounts</td>
                        <td class="text-right"><?=number_format($total_supplier_purchases)?></td>
                        <td class="text-right"><?=number_format($total_supplier_paid)?></td>
                        <td class="text-right font-weight-bold text-danger h5 mb-0">
                          Rs. <?=number_format($total_supplier_pending_pay)?>
                        </td>
                      </tr>
                      <tr class="table-warning font-weight-bold h5">
                        <td colspan="4" class="text-right font-weight-bold">
                          Net Working Balance (Receivables − Payables):
                        </td>
                        <td class="text-right font-weight-bold <?=$net_working_balance >= 0 ? 'text-primary' : 'text-danger'?>">
                          Rs. <?=number_format($net_working_balance)?>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div> <!-- /tab-summary -->

        </div> <!-- /tab-content -->

      </div> <!-- .container-fluid -->
    </main>
  </div> <!-- .wrapper -->

  <?php include_once 'includes/foot.php'; ?>

  <script>
    $(document).ready(function() {
      // Initialize DataTables if available
      if ($.fn.DataTable) {
        ['#customersTable', '#suppliersTable', '#customersOnlyTable', '#suppliersOnlyTable'].forEach(function(tblId) {
          if ($(tblId + ' tbody tr').length > 1 && !$(tblId + ' tbody tr td[colspan]').length) {
            $(tblId).DataTable({
              pageLength: 25,
              order: [[6, 'desc']] // Order by Pending Due/Pay descending
            });
          }
        });
      }
    });

    // Expand all rows before printing so no rows are clipped
    window.onbeforeprint = function() {
      if ($.fn.DataTable) {
        $('.dataTable').each(function() {
          var dt = $(this).DataTable();
          $(this).data('old-page-len', dt.page.len());
          dt.page.len(-1).draw();
        });
      }
    };
    window.onafterprint = function() {
      if ($.fn.DataTable) {
        $('.dataTable').each(function() {
          var dt = $(this).DataTable();
          var oldLen = $(this).data('old-page-len') || 25;
          dt.page.len(oldLen).draw();
        });
      }
    };

    // Clean CSV Export Function
    function exportAllToCSV() {
      var csv = [];
      csv.push(["CUSTOMER & SUPPLIER PENDING DUES REPORT"]);
      csv.push(["Generated on: " + new Date().toLocaleString()]);
      csv.push(["Scope: <?=addslashes($date_scope_label)?>"]);
      csv.push([]);

      csv.push(["--- 1. CUSTOMERS PENDING DUE (RECEIVABLES) ---"]);
      csv.push(["Sr#", "Customer Name", "Phone", "STRN", "NTN", "Total Invoiced", "Total Received", "Pending Due (Rs)", "Status"]);
      <?php foreach ($customers_data as $idx => $r): ?>
        csv.push([
          "<?=$idx + 1?>",
          "<?=addslashes($r['customer_name'])?>",
          "<?=addslashes($r['customer_phone'])?>",
          "<?=addslashes($r['customer_strn'])?>",
          "<?=addslashes($r['customer_ntn'])?>",
          "<?=$r['invoiced']?>",
          "<?=$r['received']?>",
          "<?=$r['net_due']?>",
          "<?=$r['status']?>"
        ]);
      <?php endforeach; ?>
      csv.push(["SUBTOTAL CUSTOMERS PENDING DUE", "", "", "", "", "<?=$total_customer_invoiced?>", "<?=$total_customer_received?>", "<?=$total_customer_pending_due?>", ""]);
      csv.push([]);

      csv.push(["--- 2. SUPPLIERS PENDING PAYABLES ---"]);
      csv.push(["Sr#", "Supplier Name", "Phone", "STRN", "NTN", "Total Purchases", "Total Paid", "Pending to Pay (Rs)", "Status"]);
      <?php foreach ($suppliers_data as $idx => $r): ?>
        csv.push([
          "<?=$idx + 1?>",
          "<?=addslashes($r['supplier_name'])?>",
          "<?=addslashes($r['supplier_phone'])?>",
          "<?=addslashes($r['supplier_strn'])?>",
          "<?=addslashes($r['supplier_ntn'])?>",
          "<?=$r['purchases']?>",
          "<?=$r['paid']?>",
          "<?=$r['net_pending']?>",
          "<?=$r['status']?>"
        ]);
      <?php endforeach; ?>
      csv.push(["SUBTOTAL SUPPLIERS PENDING PAYABLE", "", "", "", "", "<?=$total_supplier_purchases?>", "<?=$total_supplier_paid?>", "<?=$total_supplier_pending_pay?>", ""]);
      csv.push([]);

      csv.push(["--- 3. GRAND TOTAL SUMMARY ---"]);
      csv.push(["Total Pending Receiving from Customers", "<?=$total_customer_pending_due?>"]);
      csv.push(["Total Pending Paying to Suppliers", "<?=$total_supplier_pending_pay?>"]);
      csv.push(["Net Working Position (Receivables - Payables)", "<?=$net_working_balance?>"]);

      var csvContent = "data:text/csv;charset=utf-8," + csv.map(e => e.map(cell => '"' + ('' + cell).replace(/"/g, '""') + '"').join(",")).join("\n");
      var encodedUri = encodeURI(csvContent);
      var link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "pending_dues_report_" + new Date().toISOString().slice(0, 10) + ".csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  </script>
</body>
</html>
