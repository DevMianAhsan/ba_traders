<?php
// =====================================================================
// UNIFIED REPORT HEADER FOR ALL SYSTEM REPORTS
// Displays Centered Company Logo, Address, Phone, Report Title,
// Customer Details (if customer report/filtered), and Report Duration.
// =====================================================================

// Load company details if not already loaded
if (!isset($dbc) && file_exists(__DIR__ . '/../php_action/db_connect.php')) {
    include_once __DIR__ . '/../php_action/db_connect.php';
}

if (empty($get_company) && isset($dbc)) {
    $get_company = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company ORDER BY id DESC LIMIT 1"));
}

$comp_name    = !empty($get_company['name']) ? $get_company['name'] : 'BA traders';
$comp_logo    = !empty($get_company['logo']) ? $get_company['logo'] : '1802050328651838e477e47.png';
$comp_address = !empty($get_company['address']) ? $get_company['address'] : 'Shop # 621, Near Hamza Kanta, Oil Depot Road Tata Market Faisalabad';
$comp_phone   = !empty($get_company['company_phone']) ? $get_company['company_phone'] : '123456789,312456789';

// Report Title
$header_title = !empty($report_title) ? $report_title : '';

// Customer / Account Info
$header_cust = null;
if (!empty($report_customer) && is_array($report_customer)) {
    $header_cust = $report_customer;
} elseif (!empty($fetchCustomer) && is_array($fetchCustomer)) {
    $header_cust = $fetchCustomer;
} elseif (!empty($customers) && is_array($customers)) {
    $header_cust = $customers;
}

$cust_name    = !empty($header_cust['customer_name']) ? trim($header_cust['customer_name']) : '';
$cust_address = !empty($header_cust['customer_address']) ? trim($header_cust['customer_address']) : '';
$cust_phone   = !empty($header_cust['customer_phone']) ? trim($header_cust['customer_phone']) : '';
$cust_ntn     = !empty($header_cust['customer_ntn']) ? trim($header_cust['customer_ntn']) : '';
$cust_strn    = !empty($header_cust['customer_strn']) ? trim($header_cust['customer_strn']) : '';

// Duration / Date Scope
$duration_str = !empty($report_duration) ? $report_duration : (!empty($date_scope_label) ? $date_scope_label : 'Overall (All Time)');
?>

<!-- Unified Print & Display Styles for Reports -->
<style type="text/css">
  .unified-report-header {
    background: transparent;
    padding: 10px 0 15px 0;
    margin-bottom: 15px;
    border-bottom: 2px solid #222;
  }
  .unified-report-header .comp-logo-img {
    max-height: 80px;
    width: auto;
    object-fit: contain;
    display: inline-block;
  }
  .unified-report-header .comp-address-line {
    font-size: 15px;
    font-weight: 700;
    color: #0b2265;
    line-height: 1.4;
    margin-top: 4px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  }
  .unified-report-header .comp-phone-line {
    font-size: 14px;
    font-weight: 700;
    color: #0b2265;
    margin-top: 2px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  }
  .unified-report-header .report-title-strip {
    margin: 8px 0;
  }
  .unified-report-header .report-title-text {
    font-size: 17px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #111;
    border-top: 1.5px solid #222;
    border-bottom: 2px solid #222;
    padding: 3px 20px;
    display: inline-block;
  }
  .unified-report-header .report-customer-meta {
    margin-top: 8px;
    font-size: 14px;
    line-height: 1.5;
  }
  .unified-report-header .cust-name-row {
    font-size: 15px;
    font-weight: 700;
    color: #111;
  }
  .unified-report-header .cust-name-val {
    font-size: 16px;
    font-weight: 800;
    text-transform: uppercase;
    color: #000;
  }
  .unified-report-header .cust-address-row {
    font-size: 13.5px;
    font-weight: 600;
    color: #222;
    margin-top: 2px;
  }
  .unified-report-header .cust-duration-row,
  .unified-report-header .report-duration-meta {
    font-size: 13.5px;
    font-weight: 700;
    color: #111;
    margin-top: 2px;
  }

  @page {
    size: auto;
    margin: 8mm 0mm;
  }

  @media print {
    /* Hide non-printable web elements */
    .d-print-none, .print_hide, .navbar, .navbar-slide, .toggle-sidebar, .sidebar, 
    #header, footer, .btn, .breadcrumb, .nav-pills, .form-row, .modal, 
    .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate,
    .paging_simple_numbers, .dataTables_wrapper .row:first-child, .dataTables_wrapper .row:last-child {
      display: none !important;
    }

    html, body {
      background: #ffffff !important;
      color: #000000 !important;
      font-size: 12px !important;
      width: 100% !important;
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      box-sizing: border-box !important;
    }

    .wrapper, main, .main-content {
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
      box-shadow: none !important;
      width: 100% !important;
      max-width: 100% !important;
      background: transparent !important;
      box-sizing: border-box !important;
    }

    .container, .container-fluid {
      padding-left: 20mm !important;
      padding-right: 20mm !important;
      padding-top: 0 !important;
      padding-bottom: 0 !important;
      margin-left: auto !important;
      margin-right: auto !important;
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      border: none !important;
      box-shadow: none !important;
      background: transparent !important;
    }

    .card, .card-body {
      padding-left: 0 !important;
      padding-right: 0 !important;
      margin-left: 0 !important;
      margin-right: 0 !important;
      border: none !important;
      box-shadow: none !important;
      width: 100% !important;
      max-width: 100% !important;
      background: transparent !important;
      box-sizing: border-box !important;
    }

    .unified-report-header {
      padding-top: 0 !important;
      margin-bottom: 12px !important;
      border-bottom: 2px solid #000 !important;
    }

    .unified-report-header .comp-address-line,
    .unified-report-header .comp-phone-line {
      color: #000000 !important;
      font-size: 14px !important;
    }

    .table-responsive {
      overflow: visible !important;
      display: block !important;
      width: 100% !important;
    }

    .row {
      margin-left: 0 !important;
      margin-right: 0 !important;
    }

    .col-12 {
      padding-left: 0 !important;
      padding-right: 0 !important;
    }

    /* Crystal-clear printable tables */
    table, .table, .table-custom-header, .ledger-table, .print-table {
      width: 100% !important;
      border-collapse: collapse !important;
      margin-bottom: 12px !important;
      page-break-inside: auto;
    }

    tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }

    thead {
      display: table-header-group;
    }

    tfoot {
      display: table-footer-group;
    }

    th, .table th, .table-custom-header thead th, .ledger-table thead th, .print-table th {
      background-color: #f1f3f5 !important;
      color: #000000 !important;
      border: 1.5px solid #000000 !important;
      font-size: 11.5px !important;
      font-weight: 800 !important;
      padding: 6px 5px !important;
      text-align: center !important;
      vertical-align: middle !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    td, .table td, .table-custom-header tbody td, .ledger-table tbody td, .print-table td {
      border: 1px solid #333333 !important;
      font-size: 11.5px !important;
      color: #000000 !important;
      padding: 5px 6px !important;
      font-weight: 500 !important;
      vertical-align: middle !important;
    }

    tfoot th, tfoot td, .table tfoot th, .table-custom-header tfoot th {
      border: 1.5px solid #000000 !important;
      background-color: #e9ecef !important;
      color: #000000 !important;
      font-size: 12px !important;
      font-weight: 800 !important;
      padding: 6px !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    .badge {
      border: 1px solid #000000 !important;
      color: #000000 !important;
      background: transparent !important;
      font-weight: 700 !important;
      padding: 2px 5px !important;
      font-size: 10.5px !important;
    }
    .badge-due, .badge-danger {
      border: 1.5px solid #b30000 !important;
      color: #b30000 !important;
      font-weight: 800 !important;
    }
    .badge-cleared, .badge-success {
      border: 1.5px solid #1b6330 !important;
      color: #1b6330 !important;
      font-weight: 800 !important;
    }

    .summary-card {
      border: 1px solid #333 !important;
      box-shadow: none !important;
      background: #fff !important;
      margin-bottom: 12px !important;
    }
    .stat-value {
      font-size: 18px !important;
      color: #000 !important;
    }

    .final-summary-card {
      border: 2px solid #000 !important;
      box-shadow: none !important;
      background: #fff !important;
      page-break-inside: avoid !important;
    }

    .print-signature-row {
      display: flex !important;
      justify-content: space-between !important;
      margin-top: 40px !important;
      padding-top: 20px !important;
      page-break-inside: avoid !important;
    }
    .print-sig-col {
      width: 28% !important;
      text-align: center !important;
    }
    .print-sig-line {
      border-top: 1.5px solid #000000 !important;
      margin-bottom: 5px !important;
    }
    .print-sig-title {
      font-size: 12px !important;
      font-weight: 700 !important;
      color: #000000 !important;
    }
    .print-sig-sub {
      font-size: 10px !important;
      color: #555555 !important;
    }
  }

  .print-signature-row {
    display: none;
  }
</style>

<!-- Render Unified Centered Header -->
<div class="unified-report-header text-center">
  <!-- Centered Logo -->
  <div class="comp-logo-wrap text-center">
    <img src="img/logo/<?=htmlspecialchars($comp_logo)?>" alt="<?=htmlspecialchars($comp_name)?>" class="comp-logo-img">
  </div>

  <!-- Centered Address -->
  <div class="comp-address-line text-center">
    <?=htmlspecialchars($comp_address)?>
  </div>

  <!-- Centered Phone -->
  <div class="comp-phone-line text-center">
    Phone: <?=htmlspecialchars($comp_phone)?>
  </div>

  <!-- Centered Report Title -->
  <?php if (!empty($header_title)): ?>
    <div class="report-title-strip text-center">
      <span class="report-title-text">
        <?=htmlspecialchars($header_title)?>
      </span>
    </div>
  <?php endif; ?>

  <!-- Customer Metadata (if Customer Report or Filtered) -->
  <?php if (!empty($cust_name)): ?>
    <div class="report-customer-meta text-center">
      <div class="cust-name-row">
        <span>Customer Name:</span>
        <span class="cust-name-val"><?=htmlspecialchars($cust_name)?></span>
        <?php if (!empty($cust_phone)): ?>
          <span style="font-size: 13px; font-weight: 600; color: #333;">(Phone: <?=htmlspecialchars($cust_phone)?>)</span>
        <?php endif; ?>
        <?php if (!empty($cust_ntn)): ?>
          <span style="font-size: 12px; font-weight: 600; color: #555;">&bull; NTN: <?=htmlspecialchars($cust_ntn)?></span>
        <?php endif; ?>
      </div>
      <?php if (!empty($cust_address)): ?>
        <div class="cust-address-row">
          <span style="font-weight: 700;">Address:</span> <?=htmlspecialchars($cust_address)?>
        </div>
      <?php endif; ?>
      <div class="cust-duration-row">
        <span>Duration:</span> <span><?=htmlspecialchars($duration_str)?></span>
      </div>
    </div>
  <?php else: ?>
    <!-- If not a single customer, display Duration of Report -->
    <div class="report-duration-meta text-center">
      <span>Duration:</span> <span><?=htmlspecialchars($duration_str)?></span>
    </div>
  <?php endif; ?>
</div>
