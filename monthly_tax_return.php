<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 

// Fetch company details for header
$company_id = !empty($get_company['id']) ? intval($get_company['id']) : 5;
$comp_res = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company WHERE id = '$company_id'"));
if ($comp_res) {
    $get_company = $comp_res;
}
$comp_name = !empty($get_company['name']) ? $get_company['name'] : 'BA TRADERS';
$comp_strn = !empty($get_company['s_tax_no']) ? $get_company['s_tax_no'] : '32-77-8763-934-54';
$comp_ntn = !empty($get_company['ntn_no']) ? $get_company['ntn_no'] : 'A322716-3';
$comp_address = !empty($get_company['address']) ? $get_company['address'] : 'Shop # 621, AL Madina Commercial Market, Near Hamza Kanta, Oil Depot Road, Tata Market, Faisalabad';

// Selected Month and Year
$selected_year = isset($_REQUEST['year']) && is_numeric($_REQUEST['year']) ? intval($_REQUEST['year']) : 2026;
$selected_month = !empty($_REQUEST['month']) ? ucfirst(strtolower(trim($_REQUEST['month']))) : 'Oct';

// Output GST Warning Limit (default 10% / 0.10)
$warning_limit_pct = isset($_REQUEST['warning_limit']) && is_numeric($_REQUEST['warning_limit']) ? floatval($_REQUEST['warning_limit']) : 10.0;
$warning_limit_ratio = $warning_limit_pct / 100.0;
$allowed_multiplier_pct = 100 + $warning_limit_pct;
$example_allowed_sale = 100 + $warning_limit_pct;
$warning_limit_str = (floatval($warning_limit_pct) == intval($warning_limit_pct)) ? intval($warning_limit_pct) : $warning_limit_pct;
$allowed_multiplier_str = (floatval($allowed_multiplier_pct) == intval($allowed_multiplier_pct)) ? intval($allowed_multiplier_pct) : $allowed_multiplier_pct;
$example_sale_str = (floatval($example_allowed_sale) == intval($example_allowed_sale)) ? intval($example_allowed_sale) : $example_allowed_sale;

// Months array
$all_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
if (!in_array($selected_month, $all_months)) {
    $selected_month = 'Oct';
}

// -------------------------------------------------------------------------------------------------
// 1. Calculate Monthly Return Figures Directly from tax_purchases & tax_sales for all 12 Months
// -------------------------------------------------------------------------------------------------
$monthly_data = [];
$cum_stock = 0.0;
$cum_gst_cf = 0.0;

foreach ($all_months as $m_name) {
    // A. Purchases from tax_purchases & tax_purchase_items
    $q_p = mysqli_query($dbc, "SELECT 
        COALESCE(SUM(tpi.quantity), 0) AS pur_kg,
        COALESCE(SUM(tpi.value_excl_tax), 0) AS pur_val,
        COALESCE(SUM(tpi.tax_amount), 0) AS pur_gst
        FROM tax_purchases tp
        JOIN tax_purchase_items tpi ON tpi.tax_purchase_id = tp.tax_purchase_id
        WHERE (tp.tax_month = '$m_name' OR (COALESCE(tp.tax_month, '') = '' AND DATE_FORMAT(tp.invoice_date, '%b') = '$m_name'))
          AND YEAR(tp.invoice_date) = '$selected_year'");
    $r_p = mysqli_fetch_assoc($q_p);
    $pur_kg = floatval($r_p['pur_kg']);
    $pur_val = floatval($r_p['pur_val']);
    $input_gst = floatval($r_p['pur_gst']);

    // B. Sales from tax_sales & tax_sale_items
    $q_s = mysqli_query($dbc, "SELECT 
        COALESCE(SUM(tsi.quantity), 0) AS sal_kg,
        COALESCE(SUM(tsi.value_excl_tax), 0) AS sal_val,
        COALESCE(SUM(tsi.tax_amount), 0) AS sal_gst
        FROM tax_sales ts
        JOIN tax_sale_items tsi ON tsi.tax_sale_id = ts.tax_sale_id
        WHERE (ts.tax_month = '$m_name' OR (COALESCE(ts.tax_month, '') = '' AND DATE_FORMAT(ts.invoice_date, '%b') = '$m_name'))
          AND YEAR(ts.invoice_date) = '$selected_year'");
    $r_s = mysqli_fetch_assoc($q_s);
    $sal_kg = floatval($r_s['sal_kg']);
    $sal_val = floatval($r_s['sal_val']);
    $output_gst = floatval($r_s['sal_gst']);

    // Cumulative Stock
    $opening_stock = $cum_stock;
    $closing_stock = $opening_stock + $pur_kg - $sal_kg;
    $cum_stock = $closing_stock;

    // Cumulative Sales Tax
    $opening_gst_bf = $cum_gst_cf;
    $total_avail_input = $opening_gst_bf + $input_gst;
    $base_gst_for_limit = ($input_gst > 0) ? $input_gst : $total_avail_input;
    $allowed_output_gst = $base_gst_for_limit * (1 + $warning_limit_ratio);

    if ($output_gst > $total_avail_input) {
        $net_gst_payable = $output_gst - $total_avail_input;
        $closing_gst_cf = 0.0;
    } else {
        $net_gst_payable = 0.0;
        $closing_gst_cf = $total_avail_input - $output_gst;
    }
    $cum_gst_cf = $closing_gst_cf;

    // Warning Flags
    $gst_warning = ($output_gst > $allowed_output_gst && $output_gst > 0) ? 'WARNING' : 'OK';
    $stock_warning = ($closing_stock < 0) ? 'WARNING' : 'OK';

    $monthly_data[$m_name] = [
        'month' => $m_name,
        'purchase_kg' => $pur_kg,
        'sale_kg' => $sal_kg,
        'opening_stock' => $opening_stock,
        'closing_stock' => $closing_stock,
        'purchase_val' => $pur_val,
        'sale_val' => $sal_val,
        'opening_gst_bf' => $opening_gst_bf,
        'input_gst' => $input_gst,
        'total_avail_input' => $total_avail_input,
        'output_gst' => $output_gst,
        'allowed_output_gst' => $allowed_output_gst,
        'net_gst_payable' => $net_gst_payable,
        'closing_gst_cf' => $closing_gst_cf,
        'gst_warning' => $gst_warning,
        'stock_warning' => $stock_warning
    ];
}

// Active Dashboard Data for Selected Month
$curr = $monthly_data[$selected_month];

// -------------------------------------------------------------------------------------------------
// 2. Detailed Invoices & Items for Selected Month (Directly from tax_purchases & tax_sales)
// -------------------------------------------------------------------------------------------------
$q_pur_items = mysqli_query($dbc, "SELECT 
    'Purchase' AS transaction_type,
    tp.tax_purchase_id AS inv_id,
    tp.invoice_no,
    tp.invoice_date AS entry_date,
    IF(COALESCE(tp.tax_month, '') != '', tp.tax_month, DATE_FORMAT(tp.invoice_date, '%b')) AS tax_month,
    COALESCE(tp.gd_number, '') AS po_no,
    COALESCE(c.customer_name, 'Unknown Supplier') AS party_name,
    tpi.description_of_goods AS item_name,
    tpi.quantity AS kg,
    tpi.rate AS rate_excl_tax,
    tpi.value_excl_tax AS taxable_value,
    tpi.tax_rate AS gst_rate,
    tpi.tax_amount AS gst_amount,
    tpi.value_incl_tax AS invoice_total,
    tp.remarks AS notes,
    CONCAT('tax_purchase.php?edit_id=', tp.tax_purchase_id) AS edit_url,
    CONCAT('print_tax_purchase.php?id=', tp.tax_purchase_id) AS print_url,
    'tax_purchases' AS table_name,
    'tax_purchase_id' AS id_col
FROM tax_purchases tp
JOIN tax_purchase_items tpi ON tpi.tax_purchase_id = tp.tax_purchase_id
LEFT JOIN customers c ON c.customer_id = tp.supplier_id
WHERE (tp.tax_month = '$selected_month' OR (COALESCE(tp.tax_month, '') = '' AND DATE_FORMAT(tp.invoice_date, '%b') = '$selected_month'))
  AND YEAR(tp.invoice_date) = '$selected_year'");

$q_sal_items = mysqli_query($dbc, "SELECT 
    'Sale' AS transaction_type,
    ts.tax_sale_id AS inv_id,
    ts.invoice_no,
    ts.invoice_date AS entry_date,
    IF(COALESCE(ts.tax_month, '') != '', ts.tax_month, DATE_FORMAT(ts.invoice_date, '%b')) AS tax_month,
    COALESCE(ts.po_no, '') AS po_no,
    COALESCE(c.customer_name, 'Unknown Customer') AS party_name,
    tsi.description_of_goods AS item_name,
    tsi.quantity AS kg,
    tsi.rate AS rate_excl_tax,
    tsi.value_excl_tax AS taxable_value,
    tsi.tax_rate AS gst_rate,
    tsi.tax_amount AS gst_amount,
    tsi.value_incl_tax AS invoice_total,
    ts.remarks AS notes,
    CONCAT('tax_sale.php?edit_id=', ts.tax_sale_id) AS edit_url,
    CONCAT('print_tax_sale.php?id=', ts.tax_sale_id) AS print_url,
    'tax_sales' AS table_name,
    'tax_sale_id' AS id_col
FROM tax_sales ts
JOIN tax_sale_items tsi ON tsi.tax_sale_id = ts.tax_sale_id
LEFT JOIN customers c ON c.customer_id = ts.customer_id
WHERE (ts.tax_month = '$selected_month' OR (COALESCE(ts.tax_month, '') = '' AND DATE_FORMAT(ts.invoice_date, '%b') = '$selected_month'))
  AND YEAR(ts.invoice_date) = '$selected_year'");

$detailed_transactions = [];
while ($r = mysqli_fetch_assoc($q_pur_items)) $detailed_transactions[] = $r;
while ($r = mysqli_fetch_assoc($q_sal_items)) $detailed_transactions[] = $r;

// Sort transactions chronologically (Purchases then Sales if same date)
usort($detailed_transactions, function($a, $b) {
    $t1 = strtotime($a['entry_date']);
    $t2 = strtotime($b['entry_date']);
    if ($t1 == $t2) {
        if ($a['transaction_type'] == $b['transaction_type']) {
            return $a['inv_id'] - $b['inv_id'];
        }
        return ($a['transaction_type'] === 'Purchase') ? -1 : 1;
    }
    return $t1 - $t2;
});

// Compute running stock sequence and separate purchase vs sale totals
$run_stock = $curr['opening_stock'];
$tot_pur_kg = 0; $tot_sal_kg = 0;
$tot_pur_val = 0; $tot_sal_val = 0;
$tot_pur_gst = 0; $tot_sal_gst = 0;
$tot_pur_inv = 0; $tot_sal_inv = 0;

foreach ($detailed_transactions as $k => $item) {
    if ($item['transaction_type'] === 'Purchase') {
        $run_stock += floatval($item['kg']);
        $tot_pur_kg += floatval($item['kg']);
        $tot_pur_val += floatval($item['taxable_value']);
        $tot_pur_gst += floatval($item['gst_amount']);
        $tot_pur_inv += floatval($item['invoice_total']);
    } else {
        $run_stock -= floatval($item['kg']);
        $tot_sal_kg += floatval($item['kg']);
        $tot_sal_val += floatval($item['taxable_value']);
        $tot_sal_gst += floatval($item['gst_amount']);
        $tot_sal_inv += floatval($item['invoice_total']);
    }
    $detailed_transactions[$k]['running_stock'] = $run_stock;
}

// Net Total = Purchase - Sale
$net_kg = $tot_pur_kg - $tot_sal_kg;
$net_val = $tot_pur_val - $tot_sal_val;
$net_gst = $tot_pur_gst - $tot_sal_gst;
$net_inv = $tot_pur_inv - $tot_sal_inv;
?>

<style>
  .metric-card {
      border-radius: 8px;
      border: 1px solid #dee2e6;
      background: #ffffff;
      padding: 14px 18px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.04);
      height: 100%;
      position: relative;
      transition: all 0.2s ease-in-out;
    }
    .metric-card:hover {
      box-shadow: 0 4px 10px rgba(0,0,0,0.08);
      transform: translateY(-2px);
    }
    .metric-card .metric-label {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #6c757d;
      margin-bottom: 4px;
    }
    .metric-card .metric-value {
      font-size: 22px;
      font-weight: 800;
      color: #212529;
      line-height: 1.2;
    }
    .metric-card .metric-sub {
      font-size: 11px;
      color: #888;
      margin-top: 4px;
    }
    .metric-card.accent-stock { border-left: 4px solid #17a2b8; }
    .metric-card.accent-pur { border-left: 4px solid #28a745; }
    .metric-card.accent-sale { border-left: 4px solid #007bff; }
    .metric-card.accent-tax { border-left: 4px solid #930027; }
    .metric-card.accent-warn { border-left: 4px solid #ffc107; }

    /* Month Selector Pills */
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

    /* Tables */
    .custom-report-table thead th {
      background-color: #2b3035 !important;
      color: #ffffff !important;
      font-size: 12px;
      font-weight: 700;
      text-align: center;
      vertical-align: middle;
      border: 1px solid #454d55;
      padding: 8px 6px;
    }
    .custom-report-table tbody td {
      font-size: 12.5px;
      vertical-align: middle;
      padding: 7px 8px;
      border: 1px solid #dee2e6;
    }
    .custom-report-table tbody tr.row-active {
      background-color: #fff3cd !important;
      font-weight: bold;
    }
    .custom-report-table tbody tr.row-active td {
      border-top: 2px solid #ffc107 !important;
      border-bottom: 2px solid #ffc107 !important;
    }
    .custom-report-table tfoot td {
      font-size: 13px;
      font-weight: 800;
      background: #f8f9fa;
      border: 1px solid #ced4da;
      padding: 8px;
    }

    /* Print View */
    @page {
      size: A4 portrait;
      margin: 10mm 15mm;
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
      .month-pill-group {
        display: none !important;
      }

      html, body {
        background: #fff !important;
        color: #000 !important;
        font-size: 10px !important;
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

      #printReportContainer {
        padding-left: 15mm !important;
        padding-right: 15mm !important;
        padding-top: 5mm !important;
        padding-bottom: 12mm !important;
        margin-left: auto !important;
        margin-right: auto !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
      }

      #printReportContainer .row {
        margin-left: -5px !important;
        margin-right: -5px !important;
      }

      #printReportContainer .row > [class*="col"] {
        padding-left: 5px !important;
        padding-right: 5px !important;
      }

      #printReportContainer .card {
        border: none !important;
        box-shadow: none !important;
        background: #fff !important;
        margin: 0 0 12px 0 !important;
        width: 100% !important;
      }

      #printReportContainer .card-body {
        padding: 0 !important;
        margin: 0 !important;
      }

      #printReportContainer .card-header.card-bg {
        border-radius: 4px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      #printReportContainer .table-responsive {
        overflow: visible !important;
        width: 100% !important;
      }

      #printReportContainer .table,
      #printReportContainer .custom-report-table {
        width: 100% !important;
        max-width: 100% !important;
      }

      .custom-report-table thead th {
        background-color: #222 !important;
        color: #fff !important;
        border: 1px solid #000 !important;
        font-size: 9.5px !important;
        padding: 4px 5px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      .custom-report-table tbody td, 
      .custom-report-table tfoot td {
        border: 1px solid #000 !important;
        font-size: 9.5px !important;
        padding: 4px 5px !important;
        color: #000 !important;
      }

      .custom-report-table tr {
        page-break-inside: avoid;
        break-inside: avoid;
      }

      .print-header {
        margin-bottom: 12px !important;
        border-bottom: 2px solid #000;
        padding-bottom: 8px;
        page-break-after: avoid;
      }

      .metric-card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        padding: 6px !important;
        page-break-inside: avoid;
        break-inside: avoid;
      }

      .badge {
        border: 1px solid #000 !important;
        color: #000 !important;
        background: transparent !important;
      }
    }
  </style>

<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>

    <main role="main" class="main-content">
      <div class="container-fluid print-sheet" id="printReportContainer">

        <!-- Top Header & Direct Invoicing Links -->
        <div class="card shadow mb-4">
          <div class="card-header card-bg text-white py-3">
            <div class="row align-items-center">
              <div class="col-lg-6 col-md-12">
                <h4 class="mb-0 text-white font-weight-bold">
                  <i class="fe fe-pie-chart mr-2"></i>Monthly Sales Tax Return Dashboard
                </h4>
                <small class="text-light">
                  Direct Live Calculation from <strong>Tax Sale</strong> and <strong>Tax Purchase</strong> Invoices — <?=$comp_name?>
                </small>
              </div>
              <div class="col-lg-6 col-md-12 text-lg-right text-left mt-2 mt-lg-0 d-print-none">
                <!-- Direct Invoicing Shortcuts from Image Menu -->
                <a href="tax_sale.php" class="btn btn-primary btn-sm font-weight-bold shadow-sm mr-1">
                  <i class="fe fe-plus mr-1"></i>New Tax Sale
                </a>
                <a href="tax_purchase.php" class="btn btn-success btn-sm font-weight-bold shadow-sm mr-1">
                  <i class="fe fe-plus mr-1"></i>New Tax Purchase
                </a>
                <a href="tax_sale_list.php" class="btn btn-outline-light btn-sm font-weight-bold shadow-sm mr-1">
                  <i class="fe fe-list mr-1"></i>Sale List
                </a>
                <a href="tax_purchase_list.php" class="btn btn-outline-light btn-sm font-weight-bold shadow-sm mr-1">
                  <i class="fe fe-list mr-1"></i>Purchase List
                </a>
                <button type="button" class="btn btn-light btn-sm font-weight-bold shadow-sm mr-1" onclick="window.print()">
                  <i class="fe fe-printer mr-1"></i>Print
                </button>
              </div>
            </div>
          </div>

          <!-- Filter Toolbar -->
          <div class="card-body bg-light border-bottom py-3 d-print-none">
            <form method="GET" action="monthly_tax_return.php" id="filterForm">
              <div class="row align-items-center">
                <!-- Month Pills -->
                <div class="col-lg-7 col-md-12 mb-2 mb-lg-0">
                  <div class="d-flex align-items-center flex-wrap">
                    <span class="font-weight-bold text-dark mr-3"><i class="fe fe-calendar mr-1"></i>Select Month:</span>
                    <div class="month-pill-group">
                      <?php foreach ($all_months as $m): ?>
                        <a href="monthly_tax_return.php?year=<?=$selected_year?>&month=<?=$m?>&warning_limit=<?=$warning_limit_pct?>" 
                           class="month-pill <?=($m === $selected_month) ? 'active' : ''?>">
                          <?=$m?>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <!-- Select Dropdowns -->
                <div class="col-lg-5 col-md-12">
                  <div class="form-row align-items-center justify-content-lg-end">
                    <div class="col-auto">
                      <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                          <span class="input-group-text font-weight-bold bg-white">Month</span>
                        </div>
                        <select name="month" class="form-control form-control-sm" onchange="this.form.submit()">
                          <?php foreach ($all_months as $m): ?>
                            <option value="<?=$m?>" <?=($m === $selected_month) ? 'selected' : ''?>><?=$m?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                          <span class="input-group-text font-weight-bold bg-white">Year</span>
                        </div>
                        <select name="year" class="form-control form-control-sm" onchange="this.form.submit()">
                          <?php for ($y = 2024; $y <= 2028; $y++): ?>
                            <option value="<?=$y?>" <?=($y == $selected_year) ? 'selected' : ''?>><?=$y?></option>
                          <?php endfor; ?>
                        </select>
                      </div>
                    </div>

                    <div class="col-auto">
                      <div class="input-group input-group-sm" title="Warning if Output GST exceeds Input GST + X%">
                        <div class="input-group-prepend">
                          <span class="input-group-text font-weight-bold bg-white">Limit</span>
                        </div>
                        <input type="number" step="1" min="0" max="100" name="warning_limit" value="<?=$warning_limit_pct?>" class="form-control form-control-sm text-center" style="width: 55px;" onchange="this.form.submit()">
                        <div class="input-group-append">
                          <span class="input-group-text bg-white">%</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Unified Centered Header for Print -->
        <div class="d-none d-print-block">
          <?php
          $report_title = "MONTHLY SALES TAX RETURN - " . strtoupper($selected_month) . " " . $selected_year;
          $report_customer = null;
          $report_duration = "Tax Period: " . strtoupper($selected_month) . " " . $selected_year;
          include 'includes/report_header.php';
          ?>
        </div>
        <style>
          @media print {
            #printReportContainer {
              padding-left: 15mm !important;
              padding-right: 15mm !important;
              margin: 0 auto !important;
              width: 100% !important;
              max-width: 100% !important;
              box-sizing: border-box !important;
            }
          }
        </style>

        <!-- ========================================================= -->
        <!-- EXECUTIVE KPI CARDS / METRICS (The Dashboard Core)        -->
        <!-- ========================================================= -->
        <div class="mb-4">
          <!-- Month Banner & Status Badges -->
          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5 class="font-weight-bold text-dark mb-0">
              <i class="fe fe-activity text-danger mr-2"></i>Sales Tax Return Summary for <span class="badge badge-primary px-3 py-1 font-weight-bold"><?=strtoupper($selected_month)?> <?=$selected_year?></span>
              <small class="text-muted ml-2 font-weight-normal">(Calculated directly from Tax Purchases & Tax Sales)</small>
            </h5>
            <div class="mt-2 mt-sm-0">
              <span class="mr-3 font-weight-bold">
                Output GST Warning Limit (<?=$warning_limit_pct?>%): 
                <?php if ($curr['gst_warning'] === 'OK'): ?>
                  <span class="badge badge-success px-2 py-1" title="Actual Sale GST (Rs. <?=number_format($curr['output_gst'], 2)?>) is within Allowed Limit (Rs. <?=number_format($curr['allowed_output_gst'], 2)?>)">
                    <i class="fe fe-check-circle mr-1"></i>OK <small class="font-weight-normal">(Sale Rs. <?=number_format($curr['output_gst'], 2)?> &le; Allowed Rs. <?=number_format($curr['allowed_output_gst'], 2)?>)</small>
                  </span>
                <?php else: ?>
                  <span class="badge badge-danger px-2 py-1" title="Actual Sale GST (Rs. <?=number_format($curr['output_gst'], 2)?>) exceeds Allowed Limit (Rs. <?=number_format($curr['allowed_output_gst'], 2)?>)">
                    <i class="fe fe-alert-triangle mr-1"></i>WARNING <small class="font-weight-normal">(Sale Rs. <?=number_format($curr['output_gst'], 2)?> &gt; Allowed Rs. <?=number_format($curr['allowed_output_gst'], 2)?>)</small>
                  </span>
                <?php endif; ?>
              </span>
              <span class="font-weight-bold">
                Stock Status: 
                <?php if ($curr['stock_warning'] === 'OK'): ?>
                  <span class="badge badge-success px-2 py-1"><i class="fe fe-check-circle mr-1"></i>OK</span>
                <?php else: ?>
                  <span class="badge badge-danger px-2 py-1"><i class="fe fe-alert-triangle mr-1"></i>WARNING: Negative Stock</span>
                <?php endif; ?>
              </span>
            </div>
          </div>

          <!-- Row 1: Stock Status KG -->
          <div class="row">
            <div class="col-xl-3 col-md-6 col-12 mb-3">
              <div class="metric-card accent-stock">
                <div class="metric-label text-info"><i class="fe fe-corner-down-right mr-1"></i>Opening Stock KG</div>
                <div class="metric-value"><?=number_format($curr['opening_stock'], 2)?> <span class="h6 text-muted font-weight-normal">KG</span></div>
                <div class="metric-sub text-muted">Stock brought forward from previous month</div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12 mb-3">
              <div class="metric-card accent-pur">
                <div class="metric-label text-success"><i class="fe fe-plus-circle mr-1"></i>Purchase KG</div>
                <div class="metric-value text-success"><?=number_format($curr['purchase_kg'], 2)?> <span class="h6 text-muted font-weight-normal">KG</span></div>
                <div class="metric-sub text-muted">Total KG from Tax Purchase invoices in <?=strtoupper($selected_month)?></div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12 mb-3">
              <div class="metric-card accent-sale">
                <div class="metric-label text-primary"><i class="fe fe-minus-circle mr-1"></i>Sale KG</div>
                <div class="metric-value text-primary"><?=number_format($curr['sale_kg'], 2)?> <span class="h6 text-muted font-weight-normal">KG</span></div>
                <div class="metric-sub text-muted">Total KG from Tax Sale invoices in <?=strtoupper($selected_month)?></div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12 mb-3">
              <div class="metric-card accent-stock">
                <div class="metric-label text-dark"><i class="fe fe-box mr-1"></i>Closing Stock KG</div>
                <div class="metric-value text-dark font-weight-bold"><?=number_format($curr['closing_stock'], 2)?> <span class="h6 text-muted font-weight-normal">KG</span></div>
                <div class="metric-sub">Ending stock = Opening + Purchase - Sale</div>
              </div>
            </div>
          </div>

          <!-- Row 2: Taxable Values -->
          <div class="row">
            <div class="col-xl-3 col-md-6 col-12 mb-3">
              <div class="metric-card accent-pur">
                <div class="metric-label text-success">Purchase Taxable Value</div>
                <div class="metric-value text-dark">Rs. <?=number_format($curr['purchase_val'], 2)?></div>
                <div class="metric-sub text-muted">Total value excl. GST on purchases</div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12 mb-3">
              <div class="metric-card accent-sale">
                <div class="metric-label text-primary">Sale Taxable Value</div>
                <div class="metric-value text-dark">Rs. <?=number_format($curr['sale_val'], 2)?></div>
                <div class="metric-sub text-muted">Total value excl. GST on sales</div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12 mb-3">
              <div class="metric-card accent-tax">
                <div class="metric-label text-danger">Allowed Output GST (<?=$allowed_multiplier_str?>%)</div>
                <div class="metric-value text-danger">Rs. <?=number_format($curr['allowed_output_gst'], 2)?></div>
                <div class="metric-sub text-muted">Purchase GST (Rs. <?=number_format($curr['input_gst'], 2)?>) + <?=$warning_limit_str?>% (e.g. 100 &rarr; <?=$example_sale_str?>)</div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12 mb-3">
              <div class="metric-card accent-warn">
                <div class="metric-label text-warning font-weight-bold">Net GST Payable</div>
                <div class="metric-value <?=($curr['net_gst_payable'] > 0) ? 'text-danger' : 'text-success'?>">
                  Rs. <?=number_format($curr['net_gst_payable'], 2)?>
                </div>
                <div class="metric-sub text-muted">Payable to FBR (Output GST - Total Available)</div>
              </div>
            </div>
          </div>

          <!-- Row 3: Sales Tax Return Figures -->
          <div class="row">
            <div class="col-xl-4 col-md-6 col-12 mb-3">
              <div class="metric-card accent-tax">
                <div class="metric-label">Opening GST Carry Forward (B/F)</div>
                <div class="metric-value text-info">Rs. <?=number_format($curr['opening_gst_bf'], 2)?></div>
                <div class="metric-sub text-muted">GST brought forward from previous month</div>
              </div>
            </div>

            <div class="col-xl-4 col-md-6 col-12 mb-3">
              <div class="metric-card accent-pur">
                <div class="metric-label text-success">Input GST (Purchase Invoices)</div>
                <div class="metric-value text-success">Rs. <?=number_format($curr['input_gst'], 2)?></div>
                <div class="metric-sub text-muted">Sales tax paid on purchase invoices</div>
              </div>
            </div>

            <div class="col-xl-4 col-md-6 col-12 mb-3">
              <div class="metric-card accent-stock">
                <div class="metric-label text-primary">Total Available Input GST</div>
                <div class="metric-value text-primary font-weight-bold">Rs. <?=number_format($curr['total_avail_input'], 2)?></div>
                <div class="metric-sub text-muted">Opening GST Carry Forward + Input GST</div>
              </div>
            </div>

            <div class="col-xl-6 col-md-6 col-12 mb-3">
              <div class="metric-card accent-tax">
                <div class="metric-label text-danger">Output GST (Sales Invoices)</div>
                <div class="metric-value text-danger font-weight-bold">Rs. <?=number_format($curr['output_gst'], 2)?></div>
                <div class="metric-sub text-muted">Sales tax charged on sales tax invoices</div>
              </div>
            </div>

            <div class="col-xl-6 col-md-12 col-12 mb-3">
              <div class="metric-card accent-stock">
                <div class="metric-label text-success">Closing GST Carry Forward (C/F)</div>
                <div class="metric-value text-success font-weight-bold">Rs. <?=number_format($curr['closing_gst_cf'], 2)?></div>
                <div class="metric-sub text-muted">Total Available Input GST minus Output GST (Excess Input Tax)</div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================= -->
        <!-- EXECUTIVE FORMULA TABLE & GUIDANCE                        -->
        <!-- ========================================================= -->
        <div class="row mb-4">
          <!-- Excel Formula Table -->
          <div class="col-lg-8 col-12 mb-3">
            <div class="card shadow h-100">
              <div class="card-header bg-dark text-white font-weight-bold py-2">
                <i class="fe fe-list mr-2"></i>Sales Tax Return Metric Specification Table (<?=strtoupper($selected_month)?> <?=$selected_year?>)
              </div>
              <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                  <thead class="thead-light">
                    <tr>
                      <th style="width: 28%;">Metric</th>
                      <th style="width: 24%;" class="text-right">Formula Result</th>
                      <th>Meaning & Definition</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="font-weight-bold">Opening Stock KG</td>
                      <td class="text-right font-weight-bold"><?=rtrim(rtrim(number_format($curr['opening_stock'], 4), '0'), '.')?></td>
                      <td class="text-muted">Stock brought forward from previous month</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold text-success">Purchase KG</td>
                      <td class="text-right font-weight-bold text-success"><?=rtrim(rtrim(number_format($curr['purchase_kg'], 4), '0'), '.')?></td>
                      <td class="text-muted">Total KG purchased in selected month</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold text-primary">Sale KG</td>
                      <td class="text-right font-weight-bold text-primary"><?=rtrim(rtrim(number_format($curr['sale_kg'], 4), '0'), '.')?></td>
                      <td class="text-muted">Total KG sold in selected month</td>
                    </tr>
                    <tr class="table-info">
                      <td class="font-weight-bold">Closing Stock KG</td>
                      <td class="text-right font-weight-bold"><?=rtrim(rtrim(number_format($curr['closing_stock'], 4), '0'), '.')?></td>
                      <td class="text-muted font-weight-bold">Ending stock for selected month (Opening + Purchase - Sale)</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold">Purchase Value</td>
                      <td class="text-right font-weight-bold">Rs. <?=rtrim(rtrim(number_format($curr['purchase_val'], 4), '0'), '.')?></td>
                      <td class="text-muted">Taxable purchase value (Excl. GST)</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold">Sale Value</td>
                      <td class="text-right font-weight-bold">Rs. <?=rtrim(rtrim(number_format($curr['sale_val'], 4), '0'), '.')?></td>
                      <td class="text-muted">Taxable sale value (Excl. GST)</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold">Opening GST Carry Forward</td>
                      <td class="text-right font-weight-bold">Rs. <?=rtrim(rtrim(number_format($curr['opening_gst_bf'], 4), '0'), '.')?></td>
                      <td class="text-muted">GST brought forward from previous month</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold text-success">Input GST</td>
                      <td class="text-right font-weight-bold text-success">Rs. <?=rtrim(rtrim(number_format($curr['input_gst'], 4), '0'), '.')?></td>
                      <td class="text-muted">GST from purchase invoices</td>
                    </tr>
                    <tr class="table-primary">
                      <td class="font-weight-bold">Total Available Input GST</td>
                      <td class="text-right font-weight-bold">Rs. <?=rtrim(rtrim(number_format($curr['total_avail_input'], 4), '0'), '.')?></td>
                      <td class="text-muted font-weight-bold">Opening GST + Input GST</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold text-danger">Output GST</td>
                      <td class="text-right font-weight-bold text-danger">Rs. <?=rtrim(rtrim(number_format($curr['output_gst'], 4), '0'), '.')?></td>
                      <td class="text-muted">GST charged on sales invoices</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold">Allowed Output GST</td>
                      <td class="text-right font-weight-bold">Rs. <?=rtrim(rtrim(number_format($curr['allowed_output_gst'], 4), '0'), '.')?></td>
                      <td class="text-muted">Purchase GST + <?=$warning_limit_str?>% (e.g. Rs. 100 purchase GST &rarr; Rs. <?=$example_sale_str?> allowed sale GST)</td>
                    </tr>
                    <tr class="<?=($curr['net_gst_payable'] > 0) ? 'table-danger' : 'table-light'?>">
                      <td class="font-weight-bold">Net GST Payable</td>
                      <td class="text-right font-weight-bold text-danger">Rs. <?=rtrim(rtrim(number_format($curr['net_gst_payable'], 4), '0'), '.')?></td>
                      <td class="text-muted font-weight-bold">Output GST minus Total Available Input GST (if > 0)</td>
                    </tr>
                    <tr class="table-success">
                      <td class="font-weight-bold">Closing GST Carry Forward</td>
                      <td class="text-right font-weight-bold text-success">Rs. <?=rtrim(rtrim(number_format($curr['closing_gst_cf'], 4), '0'), '.')?></td>
                      <td class="text-muted font-weight-bold">Total Available Input GST minus Output GST (if > 0)</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold"><?=$warning_limit_str?>% GST Warning</td>
                      <td class="text-right">
                        <?php if ($curr['gst_warning'] === 'OK'): ?>
                          <span class="badge badge-success font-weight-bold px-2 py-1">OK</span>
                        <?php else: ?>
                          <span class="badge badge-danger font-weight-bold px-2 py-1">WARNING</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-muted">Warns if output GST exceeds allowed (<?=$warning_limit_str?>% limit)</td>
                    </tr>
                    <tr>
                      <td class="font-weight-bold">Stock Warning</td>
                      <td class="text-right">
                        <?php if ($curr['stock_warning'] === 'OK'): ?>
                          <span class="badge badge-success font-weight-bold px-2 py-1">OK</span>
                        <?php else: ?>
                          <span class="badge badge-danger font-weight-bold px-2 py-1">WARNING</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-muted">Warns if closing stock is negative</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Rules Card -->
          <div class="col-lg-4 col-12 mb-3">
            <div class="card shadow h-100 border-left-primary">
              <div class="card-header bg-primary text-white font-weight-bold py-2">
                <i class="fe fe-info mr-2"></i>How to Use & System Workflow
              </div>
              <div class="card-body">
                <ul class="list-group list-group-flush mb-0">
                  <li class="list-group-item px-0 py-2 d-flex">
                    <span class="badge badge-dark mr-2 align-self-start mt-1">1</span>
                    <div><strong>Data Source:</strong> Automatically calculated directly from <strong>Tax Sale</strong> and <strong>Tax Purchase</strong> invoices.</div>
                  </li>
                  <li class="list-group-item px-0 py-2 d-flex">
                    <span class="badge badge-success mr-2 align-self-start mt-1">2</span>
                    <div><strong>Tax Purchase:</strong> Use <code>Tax Purchase</code> for supplier/importer bills (Input GST).</div>
                  </li>
                  <li class="list-group-item px-0 py-2 d-flex">
                    <span class="badge badge-primary mr-2 align-self-start mt-1">3</span>
                    <div><strong>Tax Sale:</strong> Use <code>Tax Sale</code> for your sales tax customer deliveries (Output GST).</div>
                  </li>
                  <li class="list-group-item px-0 py-2 d-flex">
                    <span class="badge badge-warning text-dark mr-2 align-self-start mt-1">4</span>
                    <div><strong><?=$warning_limit_str?>% GST Warning:</strong> Warns if Sale Output GST &gt; Purchase Input GST + <?=$warning_limit_str?>% (e.g. Purchase GST = Rs. 100 &rarr; Allowed Sale GST = Rs. <?=$example_sale_str?>).</div>
                  </li>
                  <li class="list-group-item px-0 py-2 d-flex">
                    <span class="badge badge-danger mr-2 align-self-start mt-1">5</span>
                    <div><strong>Stock Warning:</strong> Warns if total sale KG exceeds total purchase KG (closing stock &lt; 0).</div>
                  </li>
                  <li class="list-group-item px-0 py-2 d-flex">
                    <span class="badge badge-info mr-2 align-self-start mt-1">6</span>
                    <div><strong>FBR Return Filing:</strong> For your monthly tax return, use <strong>Input GST</strong>, <strong>Output GST</strong> and <strong>Net Payable</strong> shown on this dashboard.</div>
                  </li>
                </ul>

                <hr>
                <div class="small text-muted bg-light p-2 rounded border">
                  <i class="fe fe-check-circle text-success mr-1"></i>
                  Any invoice created, edited, or deleted under <strong>Tax Invoices</strong> updates this return instantly.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ========================================================= -->
        <!-- 12-MONTH ANNUAL BREAKDOWN TABLE                            -->
        <!-- ========================================================= -->
        <div class="card shadow mb-4">
          <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
            <h5 class="mb-0 text-white font-weight-bold">
              <i class="fe fe-calendar mr-2"></i>12-Month Annual Sales Tax & Stock Breakdown (Year <?=$selected_year?>)
            </h5>
            <small class="text-light">Click on any month row to view its complete dashboard & data</small>
          </div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-bordered table-hover text-center custom-report-table mb-0">
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Purchase KG</th>
                  <th>Sale KG</th>
                  <th>Opening Stock KG</th>
                  <th>Closing Stock KG</th>
                  <th>Purchase Value</th>
                  <th>Sale Value</th>
                  <th>Opening GST (B/F)</th>
                  <th>Input GST</th>
                  <th>Total Avail Input</th>
                  <th>Output GST</th>
                  <th>Net GST Payable</th>
                  <th>Closing GST (C/F)</th>
                  <th class="d-print-none">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $tot_p_kg = 0; $tot_s_kg = 0; $tot_p_val = 0; $tot_s_val = 0;
                $tot_in_gst = 0; $tot_out_gst = 0; $tot_net_pay = 0;

                foreach ($all_months as $m_name):
                    $m = $monthly_data[$m_name];
                    $is_sel = ($m_name === $selected_month);
                    $tot_p_kg += $m['purchase_kg'];
                    $tot_s_kg += $m['sale_kg'];
                    $tot_p_val += $m['purchase_val'];
                    $tot_s_val += $m['sale_val'];
                    $tot_in_gst += $m['input_gst'];
                    $tot_out_gst += $m['output_gst'];
                    $tot_net_pay += $m['net_gst_payable'];
                ?>
                <tr class="<?=$is_sel ? 'row-active' : ''?>" style="cursor: pointer;" onclick="window.location.href='monthly_tax_return.php?year=<?=$selected_year?>&month=<?=$m_name?>&warning_limit=<?=$warning_limit_pct?>'">
                  <td class="font-weight-bold">
                    <?php if ($is_sel): ?>
                      <span class="badge badge-primary px-2 py-1"><?=$m['month']?> (Active)</span>
                    <?php else: ?>
                      <?=$m['month']?>
                    <?php endif; ?>
                  </td>
                  <td class="text-right text-success font-weight-bold"><?=$m['purchase_kg'] > 0 ? number_format($m['purchase_kg'], 2) : '0'?></td>
                  <td class="text-right text-primary font-weight-bold"><?=$m['sale_kg'] > 0 ? number_format($m['sale_kg'], 2) : '0'?></td>
                  <td class="text-right"><?=$m['opening_stock'] > 0 ? number_format($m['opening_stock'], 2) : '0'?></td>
                  <td class="text-right font-weight-bold text-dark"><?=$m['closing_stock'] > 0 ? number_format($m['closing_stock'], 2) : '0'?></td>
                  <td class="text-right"><?=$m['purchase_val'] > 0 ? number_format($m['purchase_val'], 2) : '0'?></td>
                  <td class="text-right"><?=$m['sale_val'] > 0 ? number_format($m['sale_val'], 2) : '0'?></td>
                  <td class="text-right"><?=$m['opening_gst_bf'] > 0 ? number_format($m['opening_gst_bf'], 2) : '0'?></td>
                  <td class="text-right text-success font-weight-bold"><?=$m['input_gst'] > 0 ? number_format($m['input_gst'], 2) : '0'?></td>
                  <td class="text-right font-weight-bold"><?=$m['total_avail_input'] > 0 ? number_format($m['total_avail_input'], 2) : '0'?></td>
                  <td class="text-right text-danger font-weight-bold"><?=$m['output_gst'] > 0 ? number_format($m['output_gst'], 2) : '0'?></td>
                  <td class="text-right <?=$m['net_gst_payable'] > 0 ? 'text-danger font-weight-bold' : ''?>"><?=$m['net_gst_payable'] > 0 ? number_format($m['net_gst_payable'], 2) : '0'?></td>
                  <td class="text-right text-success font-weight-bold"><?=$m['closing_gst_cf'] > 0 ? number_format($m['closing_gst_cf'], 2) : '0'?></td>
                  <td class="d-print-none text-center">
                    <a href="monthly_tax_return.php?year=<?=$selected_year?>&month=<?=$m_name?>&warning_limit=<?=$warning_limit_pct?>" class="btn btn-xs btn-outline-primary" title="View Month">
                      <i class="fe fe-eye"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="bg-light font-weight-bold">
                <tr>
                  <td class="text-center font-weight-bold">Full Year Total</td>
                  <td class="text-right text-success"><?=number_format($tot_p_kg, 2)?></td>
                  <td class="text-right text-primary"><?=number_format($tot_s_kg, 2)?></td>
                  <td class="text-right">-</td>
                  <td class="text-right font-weight-bold"><?=number_format($cum_stock, 2)?></td>
                  <td class="text-right"><?=number_format($tot_p_val, 2)?></td>
                  <td class="text-right"><?=number_format($tot_s_val, 2)?></td>
                  <td class="text-right">-</td>
                  <td class="text-right text-success"><?=number_format($tot_in_gst, 2)?></td>
                  <td class="text-right">-</td>
                  <td class="text-right text-danger"><?=number_format($tot_out_gst, 2)?></td>
                  <td class="text-right text-danger font-weight-bold"><?=number_format($tot_net_pay, 2)?></td>
                  <td class="text-right text-success font-weight-bold"><?=number_format($cum_gst_cf, 2)?></td>
                  <td class="d-print-none"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- ========================================================= -->
        <!-- DETAILED INVOICE TRANSACTIONS SHEET                        -->
        <!-- ========================================================= -->
        <div class="card shadow mb-4">
          <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
            <h5 class="mb-0 text-white font-weight-bold">
              <i class="fe fe-file-text mr-2"></i>Tax Purchase & Tax Sale Items — <?=strtoupper($selected_month)?> <?=$selected_year?>
            </h5>
            <div class="d-print-none">
              <a href="tax_sale.php" class="btn btn-sm btn-primary mr-1"><i class="fe fe-plus mr-1"></i>New Sale</a>
              <a href="tax_purchase.php" class="btn btn-sm btn-success"><i class="fe fe-plus mr-1"></i>New Purchase</a>
            </div>
          </div>
          <div class="card-body p-0 table-responsive">
            <table class="table table-bordered table-hover text-center custom-report-table mb-0" id="transactionsTable">
              <thead>
                <tr>
                  <th style="width: 35px;">#</th>
                  <th>Date</th>
                  <th>Month</th>
                  <th>Type</th>
                  <th>PO No</th>
                  <th>Party</th>
                  <th>Invoice #</th>
                  <th>Item</th>
                  <th>KG</th>
                  <th>Rate Excl. GST</th>
                  <th>Taxable Value</th>
                  <th>GST %</th>
                  <th>GST Amount</th>
                  <th>Invoice Total</th>
                  <th>Running Stock KG</th>
                  <th>Notes</th>
                  <th class="d-print-none" style="width: 100px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($detailed_transactions)): ?>
                  <tr>
                    <td colspan="17" class="text-center py-4 text-muted">
                      No tax invoices recorded for <?=strtoupper($selected_month)?> <?=$selected_year?>.
                    </td>
                  </tr>
                <?php else: 
                  foreach ($detailed_transactions as $idx => $t):
                    $is_pur = ($t['transaction_type'] === 'Purchase');
                ?>
                <tr>
                  <td><?=($idx + 1)?></td>
                  <td class="text-nowrap"><?=date('d-M-Y', strtotime($t['entry_date']))?></td>
                  <td class="font-weight-bold"><?=$t['tax_month']?></td>
                  <td>
                    <?php if ($is_pur): ?>
                      <span class="badge badge-success px-2 py-1"><i class="fe fe-arrow-down-left mr-1"></i>Purchase</span>
                    <?php else: ?>
                      <span class="badge badge-primary px-2 py-1"><i class="fe fe-arrow-up-right mr-1"></i>Sale</span>
                    <?php endif; ?>
                  </td>
                  <td class="font-weight-bold"><?=$t['po_no'] ?: '-'?></td>
                  <td class="text-left font-weight-bold text-dark"><?=$t['party_name']?></td>
                  <td class="font-weight-bold text-danger"><?=$t['invoice_no'] ?: '-'?></td>
                  <td class="font-weight-bold"><?=$t['item_name']?></td>
                  <td class="text-right font-weight-bold <?=$is_pur ? 'text-success' : 'text-primary'?>">
                    <?=number_format($t['kg'], 2)?>
                  </td>
                  <td class="text-right"><?=number_format($t['rate_excl_tax'], 2)?></td>
                  <td class="text-right font-weight-bold"><?=number_format($t['taxable_value'], 2)?></td>
                  <td class="text-center"><?=number_format($t['gst_rate'], 0)?>%</td>
                  <td class="text-right text-danger font-weight-bold"><?=number_format($t['gst_amount'], 2)?></td>
                  <td class="text-right text-success font-weight-bold"><?=number_format($t['invoice_total'], 2)?></td>
                  <td class="text-right font-weight-bold text-dark bg-light">
                    <?=number_format($t['running_stock'], 2)?>
                  </td>
                  <td class="text-left small text-muted"><?=$t['notes'] ?: '-'?></td>
                  <td class="d-print-none text-center">
                    <div class="btn-group btn-group-sm">
                      <a href="<?=$t['print_url']?>" target="_blank" class="btn btn-outline-primary" title="Print Tax Invoice">
                        <i class="fe fe-printer"></i>
                      </a>
                      <a href="<?=$t['edit_url']?>" target="_blank" class="btn btn-outline-info" title="Edit Tax Invoice">
                        <i class="fe fe-edit"></i>
                      </a>
                      <button type="button" class="btn btn-outline-danger" title="Delete Invoice" onclick="deleteTaxInvoice('<?=$t['table_name']?>', <?=$t['inv_id']?>, '<?=$t['invoice_no']?>')">
                        <i class="fe fe-trash-2"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <?php endif; ?>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>

  <?php include_once 'includes/foot.php'; ?>

  <!-- Scripts -->
  <script>
    function deleteTaxInvoice(table, id, invNo) {
      const typeLabel = (table === 'tax_purchases') ? 'Tax Purchase Invoice' : 'Tax Sale Invoice';
      Swal.fire({
        title: 'Delete ' + typeLabel + ' #' + invNo + '?',
        text: 'This will permanently remove the invoice, items, and recalculate this return.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: 'php_action/ajax_deleteData.php',
            type: 'POST',
            data: {
              delete_bymanually: id,
              table: table,
              row: (table === 'tax_purchases' ? 'tax_purchase_id' : 'tax_sale_id')
            },
            dataType: 'json',
            success: function(res) {
              if (res.sts === 'success') {
                Swal.fire({
                  icon: 'success',
                  title: 'Deleted!',
                  text: res.msg,
                  timer: 1200,
                  showConfirmButton: false
                }).then(() => {
                  window.location.reload();
                });
              } else {
                Swal.fire('Error', res.msg, 'error');
              }
            },
            error: function() {
              Swal.fire('Error', 'Server communication error', 'error');
            }
          });
        }
      });
    }

    function exportToCSV() {
      const rows = [];
      rows.push(['Date', 'Month', 'Type', 'PO No', 'Party', 'Invoice No', 'Item', 'KG', 'Rate Excl. GST', 'Taxable Value', 'GST %', 'GST Amount', 'Invoice Total', 'Running Stock KG', 'Notes']);
      
      const tableRows = document.querySelectorAll('#transactionsTable tbody tr');
      tableRows.forEach(tr => {
        const cols = tr.querySelectorAll('td');
        if (cols.length >= 16) {
          rows.push([
            cols[1].innerText.trim(),
            cols[2].innerText.trim(),
            cols[3].innerText.trim(),
            cols[4].innerText.trim() === '-' ? '' : cols[4].innerText.trim(),
            cols[5].innerText.trim(),
            cols[6].innerText.trim() === '-' ? '' : cols[6].innerText.trim(),
            cols[7].innerText.trim(),
            cols[8].innerText.trim().replace(/,/g, ''),
            cols[9].innerText.trim().replace(/,/g, ''),
            cols[10].innerText.trim().replace(/,/g, ''),
            cols[11].innerText.trim().replace(/%/g, ''),
            cols[12].innerText.trim().replace(/,/g, ''),
            cols[13].innerText.trim().replace(/,/g, ''),
            cols[14].innerText.trim().replace(/,/g, ''),
            cols[15].innerText.trim() === '-' ? '' : cols[15].innerText.trim()
          ]);
        }
      });

      let csvContent = "data:text/csv;charset=utf-8," 
        + rows.map(e => e.map(item => `"${(item + '').replace(/"/g, '""')}"`).join(",")).join("\n");

      const encodedUri = encodeURI(csvContent);
      const link = document.createElement("a");
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", `Monthly_Tax_Return_${'<?=$selected_month?>'}_${'<?=$selected_year?>'}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    $(document).ready(function() {
      if ($.fn.DataTable.isDataTable('#transactionsTable')) {
        $('#transactionsTable').DataTable().destroy();
      }
      $('#transactionsTable').DataTable({
        pageLength: 50,
        ordering: false,
        responsive: true
      });
    });
  </script>
</body>
</html>
