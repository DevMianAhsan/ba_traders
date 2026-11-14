<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 

// =============================================================================
// Filters & Parameters
// =============================================================================
$customer_id   = (isset($_REQUEST['customer_id']) && $_REQUEST['customer_id'] !== '' && $_REQUEST['customer_id'] !== 'all') ? mysqli_real_escape_string($dbc, $_REQUEST['customer_id']) : '';
$from_date     = isset($_REQUEST['from_date']) ? trim($_REQUEST['from_date']) : '';
$to_date       = isset($_REQUEST['to_date']) ? trim($_REQUEST['to_date']) : '';
$status_filter = isset($_REQUEST['status']) ? trim($_REQUEST['status']) : 'pending_only'; // pending_only, all, not_invoiced, partially_invoiced, fully_invoiced, over_invoiced
$search_query  = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
$active_tab    = isset($_REQUEST['tab']) ? trim($_REQUEST['tab']) : 'po_view'; // po_view, dc_view

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

// Unified Report Header setup
$report_title    = "PO SALES TAX (FBR) INVOICING & PENDING REPORT";
$report_customer = $selected_customer;
$report_duration = $date_scope_label;

// =============================================================================
// Fetch Purchase Orders with Delivery Challans (DC) and Sales Tax Invoices
// =============================================================================
$where_clauses = ["1=1"];

if (!empty($customer_id)) {
    $where_clauses[] = "p.po_customer = '$customer_id'";
}
if (!empty($from_date)) {
    $from_escaped = mysqli_real_escape_string($dbc, $from_date);
    $where_clauses[] = "p.po_date >= '$from_escaped'";
}
if (!empty($to_date)) {
    $to_escaped = mysqli_real_escape_string($dbc, $to_date);
    $where_clauses[] = "p.po_date <= '$to_escaped'";
}
if (!empty($search_query)) {
    $search_escaped = mysqli_real_escape_string($dbc, $search_query);
    $where_clauses[] = "(p.po_partypo LIKE '%$search_escaped%' OR p.po_customer_name LIKE '%$search_escaped%' OR c.customer_name LIKE '%$search_escaped%' OR p.po_remarks LIKE '%$search_escaped%')";
}

$where_sql = implode(' AND ', $where_clauses);
$sql_pos = "SELECT p.*, c.customer_name, c.customer_phone, c.customer_strn, c.customer_ntn, c.customer_address
            FROM purchase_order p
            LEFT JOIN customers c ON c.customer_id = p.po_customer
            WHERE $where_sql
            ORDER BY p.po_date DESC, p.po_id DESC";

$q_pos = mysqli_query($dbc, $sql_pos);

$all_records        = [];
$filtered_records   = [];
$dc_records         = [];

// Summary Totals for ALL matching POs (before status filter)
$kpi_total_pos         = 0;
$kpi_total_po_amount   = 0;
$kpi_total_dc_amount   = 0;
$kpi_total_invoiced    = 0;
$kpi_total_pending     = 0;
$kpi_count_not_invoiced = 0;
$kpi_count_partial     = 0;
$kpi_count_completed   = 0;
$kpi_count_over        = 0;

if ($q_pos) {
    while ($p = mysqli_fetch_assoc($q_pos)) {
        $po_id       = (int)$p['po_id'];
        $po_no_clean = trim($p['po_partypo'] ?? '');
        $cust_id     = (int)($p['po_customer'] ?? 0);
        $po_escaped  = mysqli_real_escape_string($dbc, $po_no_clean);

        // 1. PO Amount
        $po_amount = floatval($p['grand_total'] ?? 0);
        if ($po_amount <= 0) {
            $q_sum = mysqli_query($dbc, "SELECT SUM(total) as s FROM purchase_order_item WHERE po_id = '$po_id'");
            $r_sum = mysqli_fetch_assoc($q_sum);
            $po_amount = floatval($r_sum['s'] ?? 0);
        }

        // 2. PO Items
        $po_items = [];
        $q_items = mysqli_query($dbc, "SELECT poi.*, prod.product_name, prod.product_code 
                                       FROM purchase_order_item poi 
                                       LEFT JOIN product prod ON prod.product_id = poi.product_id 
                                       WHERE poi.po_id = '$po_id' 
                                       ORDER BY poi.po_item_id ASC");
        while ($ritem = mysqli_fetch_assoc($q_items)) {
            $po_items[] = $ritem;
        }

        // 3. Orders (Delivery Challans / DC)
        $dcs = [];
        $dc_tot = 0;
        if ($po_no_clean !== '') {
            $dc_cust_cond = ($cust_id > 0) ? " AND (customer_account = '$cust_id' OR customer_account IS NULL OR customer_account = 0)" : "";
            $q_dcs = mysqli_query($dbc, "SELECT order_id, order_date, client_name, grand_total, order_status, credit_sale_type 
                                         FROM orders 
                                         WHERE party_po = '$po_escaped' $dc_cust_cond 
                                         ORDER BY order_id ASC");
            while ($rdc = mysqli_fetch_assoc($q_dcs)) {
                $dcs[] = $rdc;
                $dc_tot += floatval($rdc['grand_total'] ?? 0);

                // Populate DC-wise list
                $dc_records[] = [
                    'dc_id'        => $rdc['order_id'],
                    'dc_date'      => $rdc['order_date'],
                    'dc_amount'    => floatval($rdc['grand_total'] ?? 0),
                    'po_id'        => $po_id,
                    'po_no'        => $po_no_clean,
                    'po_date'      => $p['po_date'],
                    'po_amount'    => $po_amount,
                    'customer_id'  => $cust_id,
                    'customer_name'=> $p['customer_name'] ?: $p['po_customer_name'],
                    'customer_phone'=> $p['customer_phone'] ?? '',
                    'credit_type'  => $rdc['credit_sale_type'] ?? ''
                ];
            }
        }

        // 4. Sales Tax Invoices (FBR Invoices)
        $tax_invoices = [];
        $inv_tot = 0;
        $inv_tax_tot = 0;
        $inv_excl_tot = 0;
        if ($po_no_clean !== '') {
            $tax_cust_cond = ($cust_id > 0) ? " AND (customer_id = '$cust_id' OR customer_id IS NULL OR customer_id = 0)" : "";
            $q_taxes = mysqli_query($dbc, "SELECT tax_sale_id, invoice_no, fbr_invoice_no, invoice_date, total_value_excl_tax, total_sales_tax, grand_total, remarks 
                                           FROM tax_sales 
                                           WHERE TRIM(po_no) = '$po_escaped' $tax_cust_cond 
                                           ORDER BY tax_sale_id ASC");
            while ($rtax = mysqli_fetch_assoc($q_taxes)) {
                $tax_invoices[] = $rtax;
                $inv_tot      += floatval($rtax['grand_total'] ?? 0);
                $inv_tax_tot  += floatval($rtax['total_sales_tax'] ?? 0);
                $inv_excl_tot += floatval($rtax['total_value_excl_tax'] ?? 0);
            }
        }

        // 5. Remaining (Bakaya) & Invoicing Status
        $remaining = $po_amount - $inv_tot;
        if ($inv_tot <= 0) {
            $status = 'not_invoiced';
            $status_label = 'Not Invoiced';
            $badge_class = 'badge-danger';
            $kpi_count_not_invoiced++;
        } elseif ($remaining > 0.01) {
            $status = 'partially_invoiced';
            $pct = ($po_amount > 0) ? round(($inv_tot / $po_amount) * 100) : 0;
            $status_label = "Partial ($pct%)";
            $badge_class = 'badge-warning text-dark font-weight-bold';
            $kpi_count_partial++;
        } elseif ($remaining < -0.01) {
            $status = 'over_invoiced';
            $status_label = 'Over Invoiced';
            $badge_class = 'badge-info';
            $kpi_count_over++;
        } else {
            $status = 'fully_invoiced';
            $status_label = 'Fully Invoiced';
            $badge_class = 'badge-success';
            $kpi_count_completed++;
        }

        $kpi_total_pos++;
        $kpi_total_po_amount += $po_amount;
        $kpi_total_dc_amount += $dc_tot;
        $kpi_total_invoiced  += $inv_tot;
        if ($remaining > 0) {
            $kpi_total_pending += $remaining;
        }

        $item_data = [
            'po'              => $p,
            'po_id'           => $po_id,
            'po_no'           => $po_no_clean,
            'po_date'         => $p['po_date'],
            'customer_id'     => $cust_id,
            'customer_name'   => $p['customer_name'] ?: $p['po_customer_name'],
            'customer_phone'  => $p['customer_phone'] ?? '',
            'customer_ntn'    => $p['customer_ntn'] ?? '',
            'customer_strn'   => $p['customer_strn'] ?? '',
            'customer_address'=> $p['customer_address'] ?? '',
            'po_amount'       => $po_amount,
            'po_items'        => $po_items,
            'dcs'             => $dcs,
            'dc_tot'          => $dc_tot,
            'tax_invoices'    => $tax_invoices,
            'inv_tot'         => $inv_tot,
            'inv_tax_tot'     => $inv_tax_tot,
            'inv_excl_tot'    => $inv_excl_tot,
            'remaining'       => $remaining,
            'status'          => $status,
            'status_label'    => $status_label,
            'badge_class'     => $badge_class
        ];

        $all_records[] = $item_data;

        // Apply Status Filter
        $include_row = false;
        if ($status_filter === 'all') {
            $include_row = true;
        } elseif ($status_filter === 'pending_only') {
            // Default: Show Uninvoiced + Partially Invoiced
            if ($status === 'not_invoiced' || $status === 'partially_invoiced') {
                $include_row = true;
            }
        } elseif ($status_filter === $status) {
            $include_row = true;
        }

        if ($include_row) {
            $filtered_records[] = $item_data;
        }
    }
}

// Table filtered totals
$table_po_total       = 0;
$table_dc_total       = 0;
$table_invoiced_total = 0;
$table_remaining_total = 0;

foreach ($filtered_records as $rec) {
    $table_po_total       += $rec['po_amount'];
    $table_dc_total       += $rec['dc_tot'];
    $table_invoiced_total += $rec['inv_tot'];
    if ($rec['remaining'] > 0) {
        $table_remaining_total += $rec['remaining'];
    }
}

$kpi_pct_invoiced = ($kpi_total_po_amount > 0) ? round(($kpi_total_invoiced / $kpi_total_po_amount) * 100, 1) : 0;
?>

<style type="text/css">
  /* Modern clean styling */
  .kpi-card {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    overflow: hidden;
  }
  .kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .kpi-card .card-body {
    padding: 1.1rem 1.25rem;
  }
  .kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
  }
  
  /* Report Table Styling */
  .po-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
  }
  .po-table thead th {
    background: #1c2d42 !important;
    color: #fff !important;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 10px 8px;
    border: none;
    vertical-align: middle;
    white-space: nowrap;
  }
  .po-table tbody td {
    font-size: 12.5px;
    padding: 8px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
  }
  .po-table tbody tr.main-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .po-table tbody tr.main-row:hover {
    background-color: #f1f5f9 !important;
  }
  .po-table tbody tr.row-pending {
    background-color: #fff;
  }
  .po-table tbody tr.row-partial {
    background-color: #fffdf5;
  }
  .po-table tbody tr.row-completed {
    background-color: #f7faf7;
  }
  .po-table tfoot td {
    background: #f8f9fa;
    font-weight: 800;
    font-size: 13px;
    border-top: 2px solid #222 !important;
    border-bottom: 2px solid #222 !important;
    padding: 10px 8px;
  }

  /* Column specific widths & single-line button */
  .col-customer {
    width: 200px !important;
    max-width: 220px !important;
    min-width: 170px !important;
    word-break: break-word !important;
  }
  .col-action {
    width: 175px !important;
    min-width: 175px !important;
    white-space: nowrap !important;
  }
  .btn-create-tax {
    white-space: nowrap !important;
    font-size: 11.5px !important;
    padding: 4px 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    line-height: 1.4 !important;
  }
  .btn-create-tax i {
    margin-right: 4px !important;
  }

  /* Expandable detail card styling */
  .details-row td {
    background-color: #f8fafc !important;
    padding: 0 !important;
    border-top: none !important;
  }
  .details-container {
    padding: 14px 18px;
    border-left: 4px solid #1a73e8;
    background: #ffffff;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);
    margin: 4px 8px 12px 8px;
    border-radius: 4px;
  }
  .sub-table th {
    background-color: #eaeff5 !important;
    color: #334155 !important;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 8px;
    border: 1px solid #cbd5e1 !important;
  }
  .sub-table td {
    font-size: 11.5px;
    padding: 5px 8px;
    border: 1px solid #e2e8f0 !important;
  }

  .po-tag {
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.3px;
    color: #0d47a1;
  }
  .inv-pill {
    display: inline-block;
    padding: 2px 6px;
    font-size: 10.5px;
    font-weight: 700;
    border-radius: 4px;
    background-color: #e3f2fd;
    color: #0b2265;
    border: 1px solid #bbdefb;
    margin: 1px 2px;
    text-decoration: none !important;
  }
  .inv-pill:hover {
    background-color: #bbdefb;
    color: #0b2265;
  }
  .fbr-badge {
    font-size: 9.5px;
    background: #2e7d32;
    color: #fff;
    padding: 1px 4px;
    border-radius: 3px;
    margin-left: 2px;
  }
  .badge-bakaya {
    font-size: 12.5px;
    font-weight: 800;
    color: #d32f2f;
  }

  /* Print Styles */
  @media print {
    .d-print-none, .navbar, .navbar-slide, .toggle-sidebar, .sidebar, #header, footer, .btn, .breadcrumb, .nav-tabs, .pagination, .dataTables_filter, .dataTables_length, .dataTables_info, .dataTables_paginate {
      display: none !important;
    }
    body, .wrapper, main, .main-content, .container-fluid, .card, .card-body {
      background: #fff !important;
      color: #000 !important;
      padding: 0 !important;
      margin: 0 !important;
      box-shadow: none !important;
      border: none !important;
      width: 100% !important;
    }
    .po-table {
      width: 100% !important;
      border-collapse: collapse !important;
    }
    .po-table thead th {
      background: #222 !important;
      color: #fff !important;
      border: 1px solid #000 !important;
      padding: 5px 3px !important;
      font-size: 10px !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .po-table tbody td, .po-table tfoot td {
      border: 1px solid #000 !important;
      padding: 4px 3px !important;
      font-size: 10px !important;
      color: #000 !important;
    }
    .print-danger {
      color: #b71c1c !important;
      font-weight: 800 !important;
    }
    .inv-pill {
      border: none !important;
      background: none !important;
      padding: 0 !important;
      color: #000 !important;
    }
    .badge {
      border: 1px solid #555 !important;
      color: #000 !important;
      background: transparent !important;
      padding: 1px 3px !important;
      font-size: 9px !important;
    }
    .details-row {
      display: none !important; /* Hide expanded accordions on clean statement print unless printed specifically */
    }
    .print-show-block {
      display: block !important;
    }
  }
</style>

<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>

    <main role="main" class="main-content">
      <div class="container-fluid">

        <!-- Top Header & Actions -->
        <div class="card shadow-sm mb-3 d-print-none">
          <div class="card-header card-bg d-flex justify-content-between align-items-center flex-wrap py-2">
            <div>
              <h5 class="card-text mb-0 text-dark">
                <i class="fe fe-file-text mr-2 text-primary"></i>
                <b>PO Sales Tax (FBR) Invoicing & Pending Report</b>
                <span class="badge badge-warning text-dark ml-2" style="font-size: 11px;">FBR Invoicing Tracker</span>
              </h5>
              <small class="text-muted">Track which Purchase Orders have pending FBR Sales Tax invoices or partial balances</small>
            </div>
            <div class="d-flex align-items-center flex-wrap mt-2 mt-md-0">
              <button type="button" class="btn btn-sm btn-secondary mr-2" onclick="toggleAllDetails()" id="btn-toggle-all">
                <i class="fe fe-maximize-2"></i> <span id="toggle-all-text">Expand All Details</span>
              </button>
              <button type="button" class="btn btn-sm btn-primary mr-2" onclick="window.print()">
                <i class="fe fe-printer"></i> Print Report
              </button>
              <!-- <button type="button" class="btn btn-sm btn-success mr-2" onclick="exportReportToCSV()">
                <i class="fe fe-download"></i> Export Excel/CSV
              </button> -->
              <a href="tax_sale.php" class="btn btn-sm btn-admin">
                <i class="fe fe-plus"></i> New Tax Invoice
              </a>
            </div>
          </div>
        </div>

        <!-- Filter Card -->
        <div class="card shadow-sm mb-3 d-print-none">
          <div class="card-body py-3">
            <form action="po_tax_report.php" method="GET" id="filterForm">
              <div class="row align-items-end">

                <!-- Customer Filter -->
                <div class="col-lg-3 col-md-6 col-sm-12 mb-2">
                  <label class="small font-weight-bold mb-1">Customer / Buyer:</label>
                  <select name="customer_id" id="customer_id" class="form-control form-control-sm select2">
                    <option value="all">-- All Customers --</option>
                    <?php
                    $q_cust_opt = mysqli_query($dbc, "SELECT customer_id, customer_name, customer_phone FROM customers WHERE customer_status = 1 AND customer_type = 'customer' ORDER BY customer_name ASC");
                    while ($rc = mysqli_fetch_assoc($q_cust_opt)) {
                        $sel = ($customer_id == $rc['customer_id']) ? 'selected' : '';
                        $dname = getCustomerDisplayName($rc['customer_name'], $rc['customer_phone']);
                    ?>
                      <option value="<?=$rc['customer_id']?>" <?=$sel?>><?=htmlspecialchars($dname)?></option>
                    <?php } ?>
                  </select>
                </div>

                <!-- Status Filter -->
                <div class="col-lg-3 col-md-6 col-sm-12 mb-2">
                  <label class="small font-weight-bold mb-1">Invoicing Status:</label>
                  <select name="status" id="status" class="form-control form-control-sm">
                    <option value="pending_only" <?=$status_filter === 'pending_only' ? 'selected' : ''?>>
                      ⚠️ Pending & Partial Only (Action Required)
                    </option>
                    <option value="all" <?=$status_filter === 'all' ? 'selected' : ''?>>
                      📁 All POs (Complete History)
                    </option>
                    <option value="not_invoiced" <?=$status_filter === 'not_invoiced' ? 'selected' : ''?>>
                      🔴 Not Invoiced (0% Invoiced)
                    </option>
                    <option value="partially_invoiced" <?=$status_filter === 'partially_invoiced' ? 'selected' : ''?>>
                      🟡 Partially Invoiced (Pending Balance)
                    </option>
                    <option value="fully_invoiced" <?=$status_filter === 'fully_invoiced' ? 'selected' : ''?>>
                      🟢 Fully Invoiced (Completed)
                    </option>
                    <option value="over_invoiced" <?=$status_filter === 'over_invoiced' ? 'selected' : ''?>>
                      🔵 Over Invoiced
                    </option>
                  </select>
                </div>

                <!-- From Date -->
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                  <label class="small font-weight-bold mb-1">From PO Date:</label>
                  <input type="date" name="from_date" id="from_date" class="form-control form-control-sm" value="<?=htmlspecialchars($from_date)?>">
                </div>

                <!-- To Date -->
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                  <label class="small font-weight-bold mb-1">To PO Date:</label>
                  <input type="date" name="to_date" id="to_date" class="form-control form-control-sm" value="<?=htmlspecialchars($to_date)?>">
                </div>

                <!-- Submit & Reset Buttons -->
                <div class="col-lg-2 col-md-12 col-sm-12 mb-2 d-flex">
                  <button type="submit" class="btn btn-sm btn-primary flex-fill mr-1">
                    <i class="fe fe-filter"></i> Filter
                  </button>
                  <a href="po_tax_report.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                    <i class="fe fe-refresh-cw"></i>
                  </a>
                </div>

              </div>

              <!-- Quick Presets & Search -->
              <div class="row align-items-center mt-1 pt-2 border-top">
                <div class="col-md-6 col-sm-12 d-flex align-items-center flex-wrap mb-1">
                  <span class="small font-weight-bold mr-2 text-muted">Quick Date:</span>
                  <button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1" onclick="setQuickDate('today')">Today</button>
                  <button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1" onclick="setQuickDate('this_month')">This Month</button>
                  <button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1" onclick="setQuickDate('last_month')">Last Month</button>
                  <button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1" onclick="setQuickDate('this_year')">This Year</button>
                  <button type="button" class="btn btn-xs btn-outline-secondary mb-1" onclick="setQuickDate('all')">All Time</button>
                </div>
                <div class="col-md-6 col-sm-12 mb-1">
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fe fe-search"></i></span>
                    </div>
                    <input type="text" id="tableSearchInput" class="form-control" placeholder="Quick search PO #, Customer Name, FBR Inv #..." onkeyup="filterTableLive()">
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="row mb-3 d-print-none">

          <!-- Total POs -->
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card bg-white h-100">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-muted small text-uppercase font-weight-bold">Total Purchase Orders</span>
                  <div class="kpi-icon bg-light-primary text-primary" style="background-color: #e8f0fe;">
                    <i class="fe fe-shopping-cart"></i>
                  </div>
                </div>
                <h4 class="mb-0 font-weight-bold text-dark">Rs. <?=number_format($kpi_total_po_amount, 2)?></h4>
                <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                  <span><b><?=$kpi_total_pos?></b> Total POs</span>
                  <span>Delivered: <b>Rs. <?=number_format($kpi_total_dc_amount, 0)?></b></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Pending Sales Tax Balance (BAKAYA) - MAIN HIGHLIGHT -->
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card bg-white h-100 border-left border-danger" style="border-left-width: 4px !important;">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-danger small text-uppercase font-weight-bold">Pending Tax Invoices (Bakaya)</span>
                  <div class="kpi-icon bg-light-danger text-danger" style="background-color: #fee8e8;">
                    <i class="fe fe-alert-triangle"></i>
                  </div>
                </div>
                <h4 class="mb-0 font-weight-bold text-danger">Rs. <?=number_format($kpi_total_pending, 2)?></h4>
                <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                  <span><b><?=($kpi_count_not_invoiced + $kpi_count_partial)?></b> POs Awaiting Invoicing</span>
                  <span class="badge badge-danger">Needs Action</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Total FBR Tax Invoiced -->
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card bg-white h-100 border-left border-success" style="border-left-width: 4px !important;">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="text-success small text-uppercase font-weight-bold">Sales Tax Invoiced (FBR)</span>
                  <div class="kpi-icon bg-light-success text-success" style="background-color: #e8f8ed;">
                    <i class="fe fe-check-circle"></i>
                  </div>
                </div>
                <h4 class="mb-0 font-weight-bold text-success">Rs. <?=number_format($kpi_total_invoiced, 2)?></h4>
                <div class="progress mt-2" style="height: 5px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: <?=$kpi_pct_invoiced?>%;" aria-valuenow="<?=$kpi_pct_invoiced?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1 small text-muted">
                  <span><b><?=$kpi_pct_invoiced?>%</b> Completed</span>
                  <span><b><?=$kpi_count_completed?></b> Fully Invoiced</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Breakdown -->
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card kpi-card bg-white h-100">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="text-muted small text-uppercase font-weight-bold">Status Breakdown</span>
                  <div class="kpi-icon bg-light-warning text-warning" style="background-color: #fff8e1;">
                    <i class="fe fe-pie-chart"></i>
                  </div>
                </div>
                <div class="mt-2 small">
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>🔴 Not Invoiced (0%):</span>
                    <strong class="text-danger"><?=$kpi_count_not_invoiced?> POs</strong>
                  </div>
                  <div class="d-flex justify-content-between py-1 border-bottom">
                    <span>🟡 Partially Invoiced:</span>
                    <strong class="text-warning text-dark"><?=$kpi_count_partial?> POs</strong>
                  </div>
                  <div class="d-flex justify-content-between py-1">
                    <span>🟢 Fully Invoiced:</span>
                    <strong class="text-success"><?=$kpi_count_completed?> POs</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Main Content Card -->
        <div class="card shadow">
          
          <!-- Unified Report Header for Print and Top Display -->
          <div class="px-3 pt-3">
            <?php include_once 'includes/report_header.php'; ?>
          </div>

          <!-- Tabs: PO Wise (Main) vs DC Wise -->
          <div class="card-header bg-white border-bottom d-print-none pb-0 pt-2">
            <ul class="nav nav-tabs card-header-tabs" id="reportTabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link <?=($active_tab !== 'dc_view') ? 'active font-weight-bold text-primary' : 'text-muted'?>" id="po-tab" data-toggle="tab" href="#poTabPane" role="tab">
                  <i class="fe fe-layers mr-1"></i> PO-Wise Invoicing Status (Main) 
                  <span class="badge badge-primary ml-1"><?=count($filtered_records)?></span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?=($active_tab === 'dc_view') ? 'active font-weight-bold text-primary' : 'text-muted'?>" id="dc-tab" data-toggle="tab" href="#dcTabPane" role="tab">
                  <i class="fe fe-truck mr-1"></i> Delivery Challan (DC) Wise View
                  <span class="badge badge-secondary ml-1"><?=count($dc_records)?></span>
                </a>
              </li>
            </ul>
          </div>

          <div class="card-body p-0">
            <div class="tab-content" id="reportTabsContent">

              <!-- ======================================================= -->
              <!-- TAB 1: PO WISE INVOICING REPORT (MAIN FOCUS)            -->
              <!-- ======================================================= -->
              <div class="tab-pane fade <?=($active_tab !== 'dc_view') ? 'show active' : ''?>" id="poTabPane" role="tabpanel">

                <?php if ($status_filter === 'pending_only'): ?>
                  <div class="alert alert-warning border-0 mb-0 rounded-0 d-flex align-items-center justify-content-between py-2 px-3 d-print-none">
                    <span class="small font-weight-bold">
                      <i class="fe fe-info mr-1"></i> Showing only POs that need Sales Tax Invoices (Not Invoiced + Partially Invoiced).
                    </span>
                    <a href="po_tax_report.php?status=all<?=!empty($customer_id) ? '&customer_id='.$customer_id : ''?>" class="btn btn-xs btn-outline-dark">
                      Show All (Complete History)
                    </a>
                  </div>
                <?php endif; ?>

                <div class="table-responsive">
                  <table class="table po-table mb-0" id="mainPoTable">
                    <thead>
                      <tr>
                        <th style="width: 35px;" class="text-center">#</th>
                        <th style="width: 85px;">PO Date</th>
                        <th style="width: 90px;">PO #</th>
                        <th class="col-customer">Customer / Buyer</th>
                        <th class="text-right" style="width: 110px;">PO Total</th>
                        <th class="text-right" style="width: 115px;">Delivered (DC)</th>
                        <th class="text-right" style="width: 115px;">Tax Invoiced</th>
                        <th style="width: 140px;">FBR & Tax Inv #</th>
                        <th class="text-right" style="width: 125px;">Remaining (Bakaya)</th>
                        <th class="text-center" style="width: 105px;">Status</th>
                        <th class="text-center d-print-none col-action">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($filtered_records)): ?>
                        <tr>
                          <td colspan="11" class="text-center py-5 text-muted">
                            <i class="fe fe-check-circle fe-32 text-success d-block mb-2"></i>
                            <h5>No Pending POs Found!</h5>
                            <p class="small mb-0">All Purchase Orders matching your filter criteria are fully invoiced, or no records exist.</p>
                            <a href="po_tax_report.php?status=all" class="btn btn-sm btn-outline-primary mt-3">View All POs</a>
                          </td>
                        </tr>
                      <?php else: ?>
                        <?php 
                        $sr = 0;
                        foreach ($filtered_records as $rec): 
                            $sr++;
                            $row_class = 'row-pending';
                            if ($rec['status'] === 'partially_invoiced') $row_class = 'row-partial';
                            if ($rec['status'] === 'fully_invoiced') $row_class = 'row-completed';
                            $collapse_id = "po_collapse_" . $rec['po_id'];
                        ?>
                          <!-- Main PO Row -->
                          <tr class="main-row <?=$row_class?>" data-target="#<?=$collapse_id?>" data-search-text="<?=htmlspecialchars(strtolower($rec['po_no'] . ' ' . $rec['customer_name'] . ' ' . $rec['po_date']))?>">
                            
                            <!-- Index with collapse toggle -->
                            <td class="text-center font-weight-bold text-muted">
                              <span class="d-print-none toggle-icon mr-1 text-primary" id="icon_<?=$collapse_id?>" onclick="event.stopPropagation(); toggleRowDetails('<?=$collapse_id?>');" style="cursor: pointer;" title="Click to view details">
                                <i class="fe fe-plus-circle"></i>
                              </span>
                              <?=$sr?>
                            </td>

                            <!-- Date -->
                            <td style="white-space: nowrap;">
                              <?=date('d-M-Y', strtotime($rec['po_date']))?>
                            </td>

                            <!-- PO Number -->
                            <td>
                              <span class="po-tag"><?=htmlspecialchars($rec['po_no'])?></span>
                              <?php if (!empty($rec['po']['po_targetdate'])): ?>
                                <small class="text-muted d-block" style="font-size: 10px;">Target: <?=htmlspecialchars($rec['po']['po_targetdate'])?></small>
                              <?php endif; ?>
                            </td>

                            <!-- Customer Details -->
                            <td class="col-customer">
                              <strong class="text-dark d-block"><?=htmlspecialchars($rec['customer_name'])?></strong>
                              <div class="small text-muted" style="font-size: 10.5px; line-height: 1.3;">
                                <!-- <?php if (!empty($rec['customer_phone'])): ?>
                                  <span><i class="fe fe-phone fe-12"></i> <?=htmlspecialchars($rec['customer_phone'])?></span>
                                <?php endif; ?>
                                <?php if (!empty($rec['customer_strn'])): ?>
                                  <span class="ml-1"><b>STRN:</b> <?=htmlspecialchars($rec['customer_strn'])?></span>
                                <?php endif; ?> -->
                                <?php if (!empty($rec['customer_ntn'])): ?>
                                  <span class="ml-1"><b>NTN:</b> <?=htmlspecialchars($rec['customer_ntn'])?></span>
                                <?php endif; ?>
                              </div>
                            </td>

                            <!-- PO Total Amount -->
                            <td class="text-right font-weight-bold">
                              Rs. <?=number_format($rec['po_amount'], 2)?>
                            </td>

                            <!-- Delivered DC Amount -->
                            <td class="text-right">
                              <?php if ($rec['dc_tot'] > 0): ?>
                                <strong>Rs. <?=number_format($rec['dc_tot'], 2)?></strong>
                                <span class="badge badge-light border d-block ml-auto mt-1" style="width: fit-content; font-size: 10px;">
                                  <?=count($rec['dcs'])?> DC(s)
                                </span>
                              <?php else: ?>
                                <span class="text-muted">Rs. 0.00</span>
                                <small class="text-muted d-block" style="font-size: 10px;">0 DCs</small>
                              <?php endif; ?>
                            </td>

                            <!-- Tax Invoiced Amount -->
                            <td class="text-right">
                              <?php if ($rec['inv_tot'] > 0): ?>
                                <strong class="text-success">Rs. <?=number_format($rec['inv_tot'], 2)?></strong>
                                <span class="badge badge-light border d-block ml-auto mt-1" style="width: fit-content; font-size: 10px;">
                                  <?=count($rec['tax_invoices'])?> Invoice(s)
                                </span>
                              <?php else: ?>
                                <span class="text-muted">Rs. 0.00</span>
                                <small class="text-danger d-block font-weight-bold" style="font-size: 10px;">0 Invoices</small>
                              <?php endif; ?>
                            </td>

                            <!-- FBR & Internal Tax Invoice Pills -->
                            <td>
                              <?php if (!empty($rec['tax_invoices'])): ?>
                                <div class="d-flex flex-wrap">
                                  <?php foreach ($rec['tax_invoices'] as $tin): ?>
                                    <a href="print_tax_sale.php?id=<?=$tin['tax_sale_id']?>" target="_blank" class="inv-pill" title="Print/View Tax Invoice #<?=$tin['invoice_no']?>">
                                      Inv #<?=$tin['invoice_no']?>
                                      <?php if (!empty($tin['fbr_invoice_no'])): ?>
                                        <span class="fbr-badge">FBR: <?=htmlspecialchars($tin['fbr_invoice_no'])?></span>
                                      <?php endif; ?>
                                    </a>
                                  <?php endforeach; ?>
                                </div>
                              <?php else: ?>
                                <span class="text-muted font-italic small">None</span>
                              <?php endif; ?>
                            </td>

                            <!-- Remaining (Bakaya) -->
                            <td class="text-right">
                              <?php if ($rec['remaining'] > 0.01): ?>
                                <span class="badge-bakaya print-danger">Rs. <?=number_format($rec['remaining'], 2)?></span>
                              <?php elseif ($rec['remaining'] < -0.01): ?>
                                <span class="text-info font-weight-bold">Over: Rs. <?=number_format(abs($rec['remaining']), 2)?></span>
                              <?php else: ?>
                                <span class="text-success font-weight-bold"><i class="fe fe-check"></i> Rs. 0.00</span>
                              <?php endif; ?>
                            </td>

                            <!-- Status Badge -->
                            <td class="text-center">
                              <span class="badge <?=$rec['badge_class']?> px-2 py-1" style="font-size: 11px;">
                                <?=$rec['status_label']?>
                              </span>
                            </td>

                            <!-- Action Column -->
                            <td class="text-center d-print-none col-action">
                              <div class="btn-group btn-group-sm" style="white-space: nowrap; display: inline-flex;">
                                <?php if ($rec['remaining'] > 0.01 || $rec['inv_tot'] <= 0): ?>
                                  <a href="tax_sale.php?customer_id=<?=$rec['customer_id']?>&po_no=<?=urlencode($rec['po_no'])?>" class="btn btn-sm btn-primary btn-create-tax" title="Create Sales Tax (FBR) Invoice for this PO">
                                    <i class="fe fe-plus-circle"></i> Create Tax Invoice
                                  </a>
                                  <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding-left: 6px; padding-right: 6px;">
                                    <span class="sr-only">Toggle Dropdown</span>
                                  </button>
                                <?php else: ?>
                                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleRowDetails('<?=$collapse_id?>')" title="View Linked Invoices" style="white-space: nowrap; font-size: 11.5px; padding: 4px 8px;">
                                    <i class="fe fe-eye mr-1"></i> Details
                                  </button>
                                  <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="padding-left: 6px; padding-right: 6px;">
                                    <span class="sr-only">Toggle Dropdown</span>
                                  </button>
                                <?php endif; ?>
                                <div class="dropdown-menu dropdown-menu-right">
                                  <a class="dropdown-item" href="tax_sale.php?customer_id=<?=$rec['customer_id']?>&po_no=<?=urlencode($rec['po_no'])?>">
                                    <i class="fe fe-file-plus mr-1 text-primary"></i> Create Tax Invoice
                                  </a>
                                  <a class="dropdown-item" href="#" onclick="toggleRowDetails('<?=$collapse_id?>'); return false;">
                                    <i class="fe fe-layers mr-1 text-info"></i> View Breakdown & DCs
                                  </a>
                                  <div class="dropdown-divider"></div>
                                  <a class="dropdown-item" href="print_po.php?id=<?=$rec['po_id']?>" target="_blank">
                                    <i class="fe fe-printer mr-1 text-secondary"></i> Print Purchase Order
                                  </a>
                                  <a class="dropdown-item" href="purchase_order.php" onclick="event.preventDefault(); document.getElementById('edit_po_form_<?=$rec['po_id']?>').submit();">
                                    <i class="fe fe-edit mr-1 text-secondary"></i> Edit PO
                                  </a>
                                </div>
                              </div>

                              <!-- Hidden Form to Edit PO -->
                              <form action="purchase_order.php" method="POST" id="edit_po_form_<?=$rec['po_id']?>" style="display:none;">
                                <input type="hidden" name="edit_order_id" value="<?=base64_encode($rec['po_id'])?>">
                              </form>
                            </td>

                          </tr>

                          <!-- Expandable Details Row -->
                          <tr class="details-row collapse" id="<?=$collapse_id?>">
                            <td colspan="11">
                              <div class="details-container">
                                
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                  <div>
                                    <h6 class="mb-0 font-weight-bold text-dark">
                                      <i class="fe fe-file-text mr-1 text-primary"></i> Purchase Order #<?=htmlspecialchars($rec['po_no'])?> Details
                                    </h6>
                                    <span class="small text-muted">Dated: <?=date('d-M-Y', strtotime($rec['po_date']))?> | Customer: <b><?=htmlspecialchars($rec['customer_name'])?></b></span>
                                  </div>
                                  <div>
                                    <a href="tax_sale.php?customer_id=<?=$rec['customer_id']?>&po_no=<?=urlencode($rec['po_no'])?>" class="btn btn-sm btn-admin">
                                      <i class="fe fe-plus"></i> Create Tax Invoice for this PO
                                    </a>
                                    <a href="print_po.php?id=<?=$rec['po_id']?>" target="_blank" class="btn btn-sm btn-outline-secondary ml-1">
                                      <i class="fe fe-printer"></i> Print PO
                                    </a>
                                  </div>
                                </div>

                                <div class="row">

                                  <!-- 1. PO Ordered Items -->
                                  <div class="col-lg-5 col-md-12 mb-3">
                                    <div class="card h-100 border">
                                      <div class="card-header bg-light py-2">
                                        <b class="small text-uppercase"><i class="fe fe-box mr-1"></i> Ordered PO Items</b>
                                      </div>
                                      <div class="card-body p-0">
                                        <div class="table-responsive">
                                          <table class="table sub-table mb-0">
                                            <thead>
                                              <tr>
                                                <th>Product</th>
                                                <th class="text-right">Qty</th>
                                                <th class="text-right">Rate</th>
                                                <th class="text-right">Total</th>
                                              </tr>
                                            </thead>
                                            <tbody>
                                              <?php if (empty($rec['po_items'])): ?>
                                                <tr><td colspan="4" class="text-center text-muted py-2">No item lines found</td></tr>
                                              <?php else: ?>
                                                <?php foreach ($rec['po_items'] as $it): ?>
                                                  <tr>
                                                    <td><?=htmlspecialchars($it['product_name'] ?? ('Product #' . $it['product_id']))?></td>
                                                    <td class="text-right"><?=number_format($it['quantity'], 2)?></td>
                                                    <td class="text-right"><?=number_format($it['rate'], 2)?></td>
                                                    <td class="text-right font-weight-bold"><?=number_format($it['total'], 2)?></td>
                                                  </tr>
                                                <?php endforeach; ?>
                                              <?php endif; ?>
                                            </tbody>
                                          </table>
                                        </div>
                                      </div>
                                    </div>
                                  </div>

                                  <!-- 2. Delivery Challans (DCs) -->
                                  <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="card h-100 border">
                                      <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                        <b class="small text-uppercase"><i class="fe fe-truck mr-1"></i> Delivery Challans</b>
                                        <span class="badge badge-primary"><?=count($rec['dcs'])?></span>
                                      </div>
                                      <div class="card-body p-0">
                                        <div class="table-responsive">
                                          <table class="table sub-table mb-0">
                                            <thead>
                                              <tr>
                                                <th>DC #</th>
                                                <th>Date</th>
                                                <th class="text-right">Amount</th>
                                                <th class="text-center">Print</th>
                                              </tr>
                                            </thead>
                                            <tbody>
                                              <?php if (empty($rec['dcs'])): ?>
                                                <tr><td colspan="4" class="text-center text-muted py-3">No Delivery Challan created yet</td></tr>
                                              <?php else: ?>
                                                <?php foreach ($rec['dcs'] as $dc): ?>
                                                  <tr>
                                                    <td><b>DC-<?=$dc['order_id']?></b></td>
                                                    <td><?=date('d/m/y', strtotime($dc['order_date']))?></td>
                                                    <td class="text-right font-weight-bold"><?=number_format($dc['grand_total'], 2)?></td>
                                                    <td class="text-center">
                                                      <a href="print_sale.php?id=<?=$dc['order_id']?>" target="_blank" class="btn btn-xs btn-outline-secondary" title="Print DC">
                                                        <i class="fe fe-printer"></i>
                                                      </a>
                                                    </td>
                                                  </tr>
                                                <?php endforeach; ?>
                                              <?php endif; ?>
                                            </tbody>
                                          </table>
                                        </div>
                                      </div>
                                    </div>
                                  </div>

                                  <!-- 3. Sales Tax Invoices (FBR) -->
                                  <div class="col-lg-4 col-md-6 mb-3">
                                    <div class="card h-100 border">
                                      <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                        <b class="small text-uppercase"><i class="fe fe-file-text mr-1"></i> Sales Tax (FBR) Invoices</b>
                                        <span class="badge badge-success"><?=count($rec['tax_invoices'])?></span>
                                      </div>
                                      <div class="card-body p-0">
                                        <div class="table-responsive">
                                          <table class="table sub-table mb-0">
                                            <thead>
                                              <tr>
                                                <th>Inv #</th>
                                                <th>FBR Inv #</th>
                                                <th>Date</th>
                                                <th class="text-right">Total</th>
                                                <th class="text-center">Action</th>
                                              </tr>
                                            </thead>
                                            <tbody>
                                              <?php if (empty($rec['tax_invoices'])): ?>
                                                <tr>
                                                  <td colspan="5" class="text-center text-danger py-3">
                                                    <b><i class="fe fe-alert-circle mr-1"></i> No Tax Invoice Created!</b>
                                                    <div class="mt-1">
                                                      <a href="tax_sale.php?customer_id=<?=$rec['customer_id']?>&po_no=<?=urlencode($rec['po_no'])?>" class="btn btn-xs btn-primary">
                                                        + Create Tax Invoice
                                                      </a>
                                                    </div>
                                                  </td>
                                                </tr>
                                              <?php else: ?>
                                                <?php foreach ($rec['tax_invoices'] as $tin): ?>
                                                  <tr>
                                                    <td><b>Inv #<?=$tin['invoice_no']?></b></td>
                                                    <td>
                                                      <?php if (!empty($tin['fbr_invoice_no'])): ?>
                                                        <span class="badge badge-success font-weight-bold"><?=htmlspecialchars($tin['fbr_invoice_no'])?></span>
                                                      <?php else: ?>
                                                        <span class="text-muted font-italic">-</span>
                                                      <?php endif; ?>
                                                    </td>
                                                    <td><?=date('d/m/y', strtotime($tin['invoice_date']))?></td>
                                                    <td class="text-right font-weight-bold text-success"><?=number_format($tin['grand_total'], 2)?></td>
                                                    <td class="text-center">
                                                      <a href="print_tax_sale.php?id=<?=$tin['tax_sale_id']?>" target="_blank" class="btn btn-xs btn-outline-primary" title="Print Tax Invoice">
                                                        <i class="fe fe-printer"></i>
                                                      </a>
                                                      <a href="tax_sale.php?edit_id=<?=$tin['tax_sale_id']?>" class="btn btn-xs btn-outline-secondary ml-1" title="Edit Tax Invoice">
                                                        <i class="fe fe-edit"></i>
                                                      </a>
                                                    </td>
                                                  </tr>
                                                <?php endforeach; ?>
                                              <?php endif; ?>
                                            </tbody>
                                          </table>
                                        </div>
                                      </div>
                                    </div>
                                  </div>

                                </div>

                                <?php if (!empty($rec['po']['po_remarks'])): ?>
                                  <div class="mt-1 small text-muted">
                                    <b>Remarks:</b> <?=nl2br(htmlspecialchars($rec['po']['po_remarks']))?>
                                  </div>
                                <?php endif; ?>

                              </div>
                            </td>
                          </tr>

                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>

                    <!-- Table Footer Totals -->
                    <tfoot>
                      <tr>
                        <td colspan="4" class="text-right text-uppercase">Page / Filtered Totals:</td>
                        <td class="text-right font-weight-bold">Rs. <?=number_format($table_po_total, 2)?></td>
                        <td class="text-right font-weight-bold">Rs. <?=number_format($table_dc_total, 2)?></td>
                        <td class="text-right font-weight-bold text-success">Rs. <?=number_format($table_invoiced_total, 2)?></td>
                        <td></td>
                        <td class="text-right font-weight-bold text-danger" style="font-size: 14px;">Rs. <?=number_format($table_remaining_total, 2)?></td>
                        <td colspan="2" class="text-center small text-muted">
                          <b><?=count($filtered_records)?></b> POs Listed
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>

              </div>

              <!-- ======================================================= -->
              <!-- TAB 2: DELIVERY CHALLAN (DC) WISE VIEW                  -->
              <!-- ======================================================= -->
              <div class="tab-pane fade <?=($active_tab === 'dc_view') ? 'show active' : ''?>" id="dcTabPane" role="tabpanel">
                <div class="p-3 bg-light border-bottom d-print-none">
                  <span class="small font-weight-bold text-muted">
                    <i class="fe fe-info mr-1"></i> This view lists all Delivery Challans (DCs) generated against Purchase Orders, helping cross-verify dispatched goods against Sales Tax Invoices.
                  </span>
                </div>
                <div class="table-responsive">
                  <table class="table po-table mb-0" id="dcTable">
                    <thead>
                      <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="width: 85px;">DC Date</th>
                        <th style="width: 85px;">DC #</th>
                        <th style="width: 90px;">PO #</th>
                        <th class="col-customer">Customer / Buyer</th>
                        <th class="text-right" style="width: 120px;">DC Amount</th>
                        <th class="text-right" style="width: 120px;">PO Amount</th>
                        <th class="text-center" style="width: 110px;">Print DC</th>
                        <th class="text-center d-print-none col-action">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($dc_records)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">No Delivery Challans found for the selected filter.</td></tr>
                      <?php else: ?>
                        <?php 
                        $dc_sr = 0;
                        $dc_sum = 0;
                        foreach ($dc_records as $dcr): 
                            $dc_sr++;
                            $dc_sum += $dcr['dc_amount'];
                        ?>
                          <tr>
                            <td class="text-center"><?=$dc_sr?></td>
                            <td><?=date('d-M-Y', strtotime($dcr['dc_date']))?></td>
                            <td><strong class="text-primary">DC-<?=$dcr['dc_id']?></strong></td>
                            <td><span class="po-tag"><?=htmlspecialchars($dcr['po_no'])?></span></td>
                            <td class="col-customer"><strong class="text-dark d-block"><?=htmlspecialchars($dcr['customer_name'])?></strong></td>
                            <td class="text-right font-weight-bold">Rs. <?=number_format($dcr['dc_amount'], 2)?></td>
                            <td class="text-right text-muted">Rs. <?=number_format($dcr['po_amount'], 2)?></td>
                            <td class="text-center">
                              <a href="print_sale.php?id=<?=$dcr['dc_id']?>" target="_blank" class="btn btn-xs btn-outline-secondary">
                                <i class="fe fe-printer mr-1"></i> Print DC
                              </a>
                            </td>
                            <td class="text-center d-print-none col-action">
                              <a href="tax_sale.php?customer_id=<?=$dcr['customer_id']?>&po_no=<?=urlencode($dcr['po_no'])?>" class="btn btn-sm btn-primary btn-create-tax">
                                <i class="fe fe-plus-circle"></i> Create Tax Invoice
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="5" class="text-right text-uppercase font-weight-bold">Total DC Amount:</td>
                        <td class="text-right font-weight-bold">Rs. <?=number_format($dc_sum ?? 0, 2)?></td>
                        <td colspan="3"></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

            </div>
          </div>

          <!-- Print Signatures Section -->
          <div class="card-footer bg-white border-top py-4 mt-3" style="border-top: 1.5px solid #222 !important;">
            <!-- <div class="row text-center">
              <div class="col-4">
                <div style="border-top: 1.5px dashed #666; margin-top: 40px; padding-top: 5px; font-weight: 700; font-size: 12px;">
                  Prepared By (Accounts)
                </div>
              </div>
              <div class="col-4">
                <div style="border-top: 1.5px dashed #666; margin-top: 40px; padding-top: 5px; font-weight: 700; font-size: 12px;">
                  Checked / Tax Consultant
                </div>
              </div>
              <div class="col-4">
                <div style="border-top: 1.5px dashed #666; margin-top: 40px; padding-top: 5px; font-weight: 700; font-size: 12px;">
                  Authorized Signature
                </div>
              </div>
            </div> -->
            <div class="text-center mt-3 text-muted small">
              Report Generated on <?=date('d-M-Y h:i A')?> | System: <b><?=htmlspecialchars($comp_name)?></b>
            </div>
          </div>

        </div>

      </div>
    </main>
  </div>

  <?php include_once 'includes/foot.php'; ?>

  <script>
    // Live Search in Main PO Table
    function filterTableLive() {
      var query = $('#tableSearchInput').val().toLowerCase().trim();
      $('#mainPoTable tbody tr.main-row').each(function() {
        var rowText = $(this).attr('data-search-text') || $(this).text().toLowerCase();
        var targetId = $(this).data('target');
        if (query === '' || rowText.indexOf(query) !== -1) {
          $(this).show();
        } else {
          $(this).hide();
          if (targetId) $(targetId).collapse('hide');
        }
      });
    }

    // Toggle individual row details
    function toggleRowDetails(collapseId) {
      var el = $('#' + collapseId);
      var icon = $('#icon_' + collapseId + ' i');
      if (el.hasClass('show')) {
        el.collapse('hide');
        icon.removeClass('fe-minus-circle').addClass('fe-plus-circle');
      } else {
        el.collapse('show');
        icon.removeClass('fe-plus-circle').addClass('fe-minus-circle');
      }
    }

    // Toggle All Details
    var allExpanded = false;
    function toggleAllDetails() {
      if (!allExpanded) {
        $('.details-row').collapse('show');
        $('.toggle-icon i').removeClass('fe-plus-circle').addClass('fe-minus-circle');
        $('#toggle-all-text').text('Collapse All Details');
        $('#btn-toggle-all i').removeClass('fe-maximize-2').addClass('fe-minimize-2');
        allExpanded = true;
      } else {
        $('.details-row').collapse('hide');
        $('.toggle-icon i').removeClass('fe-minus-circle').addClass('fe-plus-circle');
        $('#toggle-all-text').text('Expand All Details');
        $('#btn-toggle-all i').removeClass('fe-minimize-2').addClass('fe-maximize-2');
        allExpanded = false;
      }
    }

    // Clicking anywhere on main-row toggles details
    $(document).on('click', '.po-table tbody tr.main-row', function(e) {
      if ($(e.target).closest('a, button, select, input, .dropdown-menu').length === 0) {
        var targetId = $(this).data('target');
        if (targetId) {
          toggleRowDetails(targetId.replace('#', ''));
        }
      }
    });

    // Quick Date filter presets
    function setQuickDate(type) {
      var today = new Date();
      var from = '';
      var to = '';

      var formatDate = function(d) {
        var month = '' + (d.getMonth() + 1);
        var day = '' + d.getDate();
        var year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
      };

      if (type === 'today') {
        from = formatDate(today);
        to = formatDate(today);
      } else if (type === 'this_month') {
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        from = formatDate(firstDay);
        to = formatDate(lastDay);
      } else if (type === 'last_month') {
        var firstDayPrev = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        var lastDayPrev = new Date(today.getFullYear(), today.getMonth(), 0);
        from = formatDate(firstDayPrev);
        to = formatDate(lastDayPrev);
      } else if (type === 'this_year') {
        var firstDayYear = new Date(today.getFullYear(), 0, 1);
        var lastDayYear = new Date(today.getFullYear(), 11, 31);
        from = formatDate(firstDayYear);
        to = formatDate(lastDayYear);
      } else if (type === 'all') {
        from = '';
        to = '';
      }

      $('#from_date').val(from);
      $('#to_date').val(to);
      $('#filterForm').submit();
    }

    // Export Table to CSV
    function exportReportToCSV() {
      var csv = [];
      csv.push(['"PO SALES TAX INVOICING & PENDING REPORT"']);
      csv.push(['"Scope: ' + '<?=addslashes($date_scope_label)?>' + '"']);
      csv.push(['"Generated: ' + '<?=date('d-M-Y H:i')?>' + '"']);
      csv.push([]);
      csv.push(['"Sr #"', '"PO Date"', '"PO Number"', '"Customer Name"', '"PO Amount (PKR)"', '"Delivered DC Amount"', '"Tax Invoiced Amount"', '"Remaining Bakaya (PKR)"', '"Status"', '"Tax Invoice Details"']);

      var rows = document.querySelectorAll('#mainPoTable tbody tr.main-row');
      rows.forEach(function(row) {
        if (row.style.display !== 'none') {
          var cols = row.querySelectorAll('td');
          var sr = cols[0].innerText.replace(/\s+/g, ' ').trim();
          var date = cols[1].innerText.trim();
          var po = cols[2].innerText.replace(/\s+/g, ' ').trim();
          var cust = cols[3].querySelector('strong') ? cols[3].querySelector('strong').innerText.trim() : cols[3].innerText.trim();
          var poAmt = cols[4].innerText.replace(/[^0-9.-]/g, '');
          var dcAmt = cols[5].innerText.replace(/[^0-9.-]/g, '');
          var invAmt = cols[6].innerText.replace(/[^0-9.-]/g, '');
          var invDetails = cols[7].innerText.replace(/\s+/g, ' ').trim();
          var remAmt = cols[8].innerText.replace(/[^0-9.-]/g, '');
          var status = cols[9].innerText.replace(/\s+/g, ' ').trim();

          csv.push([
            '"' + sr + '"',
            '"' + date + '"',
            '"' + po + '"',
            '"' + cust.replace(/"/g, '""') + '"',
            '"' + poAmt + '"',
            '"' + dcAmt + '"',
            '"' + invAmt + '"',
            '"' + remAmt + '"',
            '"' + status + '"',
            '"' + invDetails.replace(/"/g, '""') + '"'
          ]);
        }
      });

      // Add Total Row
      csv.push([]);
      csv.push([
        '""', '""', '""', '"TOTALS"',
        '"' + '<?=$table_po_total?>' + '"',
        '"' + '<?=$table_dc_total?>' + '"',
        '"' + '<?=$table_invoiced_total?>' + '"',
        '"' + '<?=$table_remaining_total?>' + '"',
        '""', '""'
      ]);

      var csvContent = "data:text/csv;charset=utf-8,\uFEFF" + csv.map(e => e.join(",")).join("\n");
      var encodedUri = encodeURI(csvContent);
      var link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", "PO_Sales_Tax_Report_" + '<?=date('Ymd')?>' + ".csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  </script>
</body>
</html>
