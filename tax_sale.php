<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 

$edit_id = !empty($_REQUEST['edit_id']) ? intval($_REQUEST['edit_id']) : 0;
$tax_sale = null;
$tax_sale_items = [];

if ($edit_id > 0) {
    $tax_sale = fetchRecord($dbc, "tax_sales", "tax_sale_id", $edit_id);
    if ($tax_sale) {
        $q_items = mysqli_query($dbc, "SELECT * FROM tax_sale_items WHERE tax_sale_id = '$edit_id' ORDER BY item_id ASC");
        while ($row = mysqli_fetch_assoc($q_items)) {
            $tax_sale_items[] = $row;
        }
    }
}

// Next invoice number
if (empty($tax_sale)) {
    $last_inv = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT invoice_no FROM tax_sales ORDER BY tax_sale_id DESC LIMIT 1"));
    if ($last_inv && is_numeric($last_inv['invoice_no'])) {
        $next_invoice_no = intval($last_inv['invoice_no']) + 1;
    } else {
        $res_status = mysqli_fetch_assoc(mysqli_query($dbc, "SHOW TABLE STATUS LIKE 'tax_sales'"));
        $next_id = !empty($res_status['Auto_increment']) ? $res_status['Auto_increment'] : 1;
        $next_invoice_no = $next_id;
    }
} else {
    $next_invoice_no = $tax_sale['invoice_no'];
}

// Company Details (BA TRADERS from company table)
$company_id = !empty($tax_sale['company_id']) ? intval($tax_sale['company_id']) : (!empty($get_company['id']) ? intval($get_company['id']) : 5);
if ($company_id > 0) {
    $comp_res = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company WHERE id = '$company_id'"));
    if ($comp_res) {
        $get_company = $comp_res;
    }
}
$company_name = !empty($get_company['name']) ? $get_company['name'] : 'BA TRADERS';
$company_address = !empty($get_company['address']) ? $get_company['address'] : 'Shop # 621, AL Madina Commercial Market, Near Hamza Kanta, Oil Depot Road, Tata Market, Faisalabad';
$company_strn = !empty($get_company['s_tax_no']) ? $get_company['s_tax_no'] : '32-77-8763-934-54';
$company_ntn = !empty($get_company['ntn_no']) ? $get_company['ntn_no'] : 'A322716-3';
$company_phone = !empty($get_company['company_phone']) ? $get_company['company_phone'] : '';

// Customer Details from customers table if editing
$edit_customer = null;
$customer_pos = [];
if (!empty($tax_sale['customer_id'])) {
    $edit_customer = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM customers WHERE customer_id = '{$tax_sale['customer_id']}'"));
    $q_pos = mysqli_query($dbc, "SELECT DISTINCT po_partypo FROM purchase_order WHERE po_customer = '{$tax_sale['customer_id']}' ORDER BY po_id DESC");
    if ($q_pos) {
        while ($r_po = mysqli_fetch_assoc($q_pos)) {
            $c_po_clean = trim($r_po['po_partypo'] ?? '');
            if ($c_po_clean !== '' && !in_array($c_po_clean, $customer_pos)) {
                $customer_pos[] = $c_po_clean;
            }
        }
    }
}
?>
<head>
  <style>
    /* Project Theme Integrated */
    .invoice-sheet {
      width: 98%;
      max-width: 1700px;
      min-height: auto;
      margin: 0 auto 30px auto;
      background: #fff;
      padding: 25px 35px 30px 35px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.35);
      font-family: "Times New Roman", Times, Georgia, serif;
      color: #000;
      box-sizing: border-box;
      border-radius: 6px;
      position: relative;
    }
    .invoice-title {
      text-align: center;
      margin-bottom: 12px;
    }
    .invoice-title h1 {
      font-family: "Times New Roman", Times, Georgia, serif;
      font-size: 26px;
      font-weight: bold;
      letter-spacing: 1.5px;
      text-decoration: underline;
      display: inline-block;
      margin: 0;
    }

    /* Top Metadata */
    .meta-row-top {
      display: flex;
      justify-content: space-between;
      font-size: 13.5px;
      margin-bottom: 12px;
      line-height: 1.6;
    }
    .meta-left { width: 45%; }
    .meta-right { width: 48%; text-align: right; }
    .meta-field {
      display: flex;
      align-items: center;
      margin-bottom: 3px;
    }
    .meta-field .lbl {
      font-weight: bold;
      min-width: 95px;
    }
    .meta-field-right {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      margin-bottom: 3px;
    }
    .meta-field-right .lbl {
      font-weight: bold;
      margin-right: 8px;
    }

    /* Input styling inside invoice */
    .inv-input {
      border: 1px solid #ced4da;
      border-radius: 3px;
      padding: 2px 6px;
      font-family: "Times New Roman", Times, serif;
      font-size: 13px;
      background: #fafafa;
    }
    .inv-input:focus {
      background: #fff;
      border-color: #80bdff;
      outline: none;
      box-shadow: 0 0 4px rgba(0,123,255,0.3);
    }
    .inv-input-clean {
      border: none;
      border-bottom: 1px dashed #aaa;
      border-radius: 0;
      padding: 1px 4px;
      background: transparent;
      font-family: "Times New Roman", Times, serif;
      font-size: 13px;
    }
    .inv-input-clean:focus {
      border-bottom: 1px solid #930027;
      background: #fff;
      outline: none;
    }

    /* Parties Box */
    .parties-card {
      border: 2px solid #000;
      border-radius: 14px;
      display: flex;
      margin-bottom: 10px;
      overflow: hidden;
      font-size: 12.5px;
      line-height: 1.5;
    }
    .party-col {
      width: 50%;
      padding: 8px 14px 10px 14px;
    }
    .party-col.left-col {
      border-right: 2px solid #000;
    }
    .party-row {
      display: flex;
      align-items: flex-start;
      margin-bottom: 3px;
    }
    .party-lbl {
      font-weight: bold;
      min-width: 110px;
      padding-top: 2px;
    }
    .party-val {
      flex: 1;
    }
    .party-name-val {
      font-weight: bold;
      font-size: 14px;
      text-decoration: underline;
    }

    /* Description */
    .desc-line {
      font-size: 12.5px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
    }
    .desc-line strong {
      min-width: 90px;
      font-size: 13px;
    }
    .desc-line strong.po-lbl {
      min-width: auto;
      margin-left: 14px;
      margin-right: 6px;
      white-space: nowrap;
    }
    .po-input-group {
      width: 250px !important;
      min-width: 220px !important;
      max-width: 260px !important;
      flex: 0 0 250px !important;
    }
    .po-input-group .select2-container,
    .po-input-group .select2-container--bootstrap4 {
      flex: 1 1 auto !important;
      width: 1% !important;
      min-width: 0 !important;
    }
    .po-input-group .select2-selection--single {
      height: 31px !important;
      min-height: 31px !important;
      max-height: 31px !important;
      font-size: 12.5px !important;
      display: flex !important;
      align-items: center !important;
      border: 1px solid #ced4da !important;
    }
    .po-input-group .select2-selection__rendered {
      line-height: 29px !important;
      padding-left: 8px !important;
      padding-right: 20px !important;
      font-size: 12.5px !important;
      font-weight: bold;
      color: #000 !important;
    }
    .po-input-group .select2-selection__arrow {
      height: 29px !important;
      top: 0 !important;
    }
    .po-input-group .input-group-append button,
    .po-input-group .input-group-append .btn-admin {
      height: 31px !important;
      min-height: 31px !important;
      max-height: 31px !important;
      line-height: 29px !important;
      padding: 0 10px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
    }

    /* Table */
    .invoice-table-wrapper {
      border: 2px solid #000;
      border-top-left-radius: 14px;
      border-top-right-radius: 14px;
      overflow: hidden;
      margin-bottom: 0;
    }
    .inv-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 10.5px;
    }
    .inv-table th {
      border-right: 1.5px solid #000;
      border-bottom: 2px solid #000;
      padding: 6px 2px;
      font-size: 9.5px;
      font-weight: bold;
      text-align: center;
      line-height: 1.25;
      background: #fff;
    }
    .inv-table th:last-child {
      border-right: none;
    }
    .inv-table td {
      border-right: 1.5px solid #000;
      padding: 4px 3px;
      vertical-align: top;
      line-height: 1.35;
    }
    .inv-table td:last-child {
      border-right: none;
    }
    .item-dotted-row td {
      border-bottom: 1.5px dotted #777;
    }
    .totals-divider-row td {
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      font-weight: bold;
      padding: 6px 4px;
      font-size: 11px;
    }
    .align-right { text-align: right; }
    .align-center { text-align: center; }
    .align-left { text-align: left; }

    .tbl-input {
      width: 100%;
      border: 1px solid #ddd;
      border-radius: 2px;
      padding: 2px 4px;
      font-size: 11px;
      font-family: "Times New Roman", Times, serif;
      background: #fbfbfb;
      box-sizing: border-box;
    }
    .tbl-input:focus {
      border-color: #80bdff;
      background: #fff;
      outline: none;
    }
    .tbl-input-readonly {
      background: #f4f4f4;
      border: 1px solid #e0e0e0;
      font-weight: bold;
    }

    .rupees-words-box {
      margin-top: 14px;
      font-size: 12px;
      font-weight: bold;
      letter-spacing: 0.5px;
      line-height: 1.4;
    }

    .footer-dual-box {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-top: 35px;
    }
    .fbr-box-inner {
      font-size: 11.5px;
    }

    .signature-area {
      margin-top: 60px;
      text-align: right;
    }
    .signature-line {
      display: inline-block;
      width: 220px;
      border-top: 1.5px solid #000;
      text-align: center;
      padding-top: 5px;
      font-size: 12.5px;
      font-weight: bold;
    }
  </style>
</head>

<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>

    <main role="main" class="main-content">
      <div class="container-fluid">
        <div class="card shadow mb-4">
          <div class="card-header card-bg text-white">
            <div class="row align-items-center">
              <div class="col-sm-5">
                <h4 class="mb-0 text-white font-weight-bold">
                  <i class="fe fe-file-text mr-2"></i><?=!empty($tax_sale) ? "Edit Tax Sale Invoice" : "Sales Tax Invoice Form (Sale)"?>
                </h4>
                <small class="text-light">BA TRADERS &bull; Sales Tax Invoice Document</small>
              </div>
              <div class="col-sm-7 text-right">
                <a href="tax_sale_list.php" class="btn btn-sm btn-light font-weight-bold mr-1">
                  <i class="fe fe-list mr-1"></i>Invoices List
                </a>
                <a href="tax_sale.php" class="btn btn-sm btn-outline-light font-weight-bold mr-1">
                  <i class="fe fe-refresh-cw mr-1"></i>New
                </a>
                <!-- <button type="button" class="btn btn-sm btn-warning font-weight-bold mr-1" id="btn_save_sale_top" onclick="triggerSubmit('save')">
                  <i class="fe fe-save mr-1"></i>Save
                </button>
                <button type="button" class="btn btn-sm btn-success font-weight-bold" id="btn_save_and_print_sale_top" onclick="triggerSubmit('save_and_print')">
                  <i class="fe fe-printer mr-1"></i>Save & Print
                </button> -->
              </div>
            </div>
          </div>
          <div class="card-body p-3" style="background: #eef2f5;">
            <form id="tax_sale_form" method="POST" action="php_action/custom_action.php">
              <input type="hidden" name="tax_sale_action" value="save_tax_sale">
              <input type="hidden" name="tax_sale_id" value="<?=@$tax_sale['tax_sale_id']?>">
              <input type="hidden" name="company_id" id="company_id" value="<?=$company_id?>">

              <!-- The Exact Invoice Sheet Document -->
        <div class="invoice-sheet">
          <!-- Document Title -->
          <div class="invoice-title">
            <h1>SALES TAX INVOICE</h1>
          </div>

          <!-- Top Metadata Row -->
          <div class="meta-row-top">
            <div class="meta-left">
              <div class="meta-field">
                <span class="lbl">Invoice No. :</span>
                <input type="text" name="invoice_no" id="invoice_no" readonly class="inv-input font-weight-bold text-danger" style="width: 120px;" required value="<?=$next_invoice_no?>">
              </div>
              <div class="meta-field">
                <span class="lbl">Date :</span>
                <input type="date" name="invoice_date" id="invoice_date" class="inv-input" style="width: 140px;" required value="<?=@$tax_sale['invoice_date'] ?: date('Y-m-d')?>">
              </div>
            </div>

            <div class="meta-right">
              <div class="meta-field-right">
                <span class="lbl">Time of Supply :</span>
                <input type="text" name="time_of_supply" id="time_of_supply" class="inv-input-clean" style="width: 150px;" placeholder="Time / Terms" value="<?=@$tax_sale['time_of_supply']?>">
              </div>
              <div class="meta-field-right">
                <span class="lbl">Payment Terms :</span>
                <input type="text" readonly name="payment_terms" id="payment_terms" class="inv-input-clean font-weight-bold" style="width: 100px; text-align: left;" value="<?=@$tax_sale['payment_terms'] ?: 'CREDIT'?>">
              </div>
              <!-- <div class="meta-field-right">
                <select name="copy_type" class="inv-input-clean font-weight-bold" style="letter-spacing: 2px;">
                  <option <?=(@$tax_sale['copy_type'] == 'ORIGINAL' || empty($tax_sale)) ? 'selected' : ''?> value="ORIGINAL">ORIGINAL</option>
                  <option <?=(@$tax_sale['copy_type'] == 'DUPLICATE') ? 'selected' : ''?> value="DUPLICATE">DUPLICATE</option>
                  <option <?=(@$tax_sale['copy_type'] == 'TRIPLICATE') ? 'selected' : ''?> value="TRIPLICATE">TRIPLICATE</option>
                </select>
              </div> -->
            </div>
          </div>

          <!-- Two-Column Rounded Parties Box -->
          <div class="parties-card">
            <!-- Left Party: Supplier (BA TRADERS from company table) -->
            <div class="party-col left-col">
              <div class="party-row">
                <div class="party-lbl">Supplier Name :</div>
                <div class="party-val">
                  <input type="text" name="company_name" id="company_name" class="inv-input-clean party-name-val w-100" value="<?=$company_name?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">Address :</div>
                <div class="party-val">
                  <textarea name="company_address" id="company_address" rows="2" class="inv-input w-100"><?=$company_address?></textarea>
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">Contact No. :</div>
                <div class="party-val">
                  <input type="text" name="company_phone" id="company_phone" class="inv-input-clean w-100" value="<?=$company_phone?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">S.Tax Reg No. :</div>
                <div class="party-val">
                  <input type="text" name="company_strn" id="company_strn" class="inv-input font-weight-bold text-danger w-100" value="<?=$company_strn?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">NTN No. :</div>
                <div class="party-val">
                  <input type="text" name="company_ntn" id="company_ntn" class="inv-input font-weight-bold text-dark w-100" value="<?=$company_ntn?>">
                </div>
              </div>
            </div>

            <!-- Right Party: Buyer (Customer / Messers) -->
            <div class="party-col">
              <div class="party-row">
                <div class="party-lbl">Buyer Name :</div>
                <div class="party-val">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted font-weight-bold">Select Customer:</span>
                    <span class="badge badge-info px-2 py-1" id="customer_balance_badge" style="<?=(!empty($tax_sale['customer_id']) ? '' : 'display:none;')?> font-size:11px;">
                      Balance: <span id="customer_balance_val"><?php
                        if (!empty($tax_sale['customer_id'])) {
                            $b_row = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(credit-debit) AS bal FROM transactions WHERE customer_id = '{$tax_sale['customer_id']}'"));
                            echo number_format($b_row['bal'] ?? 0, 2);
                        } else {
                            echo "0.00";
                        }
                      ?></span>
                    </span>
                  </div>
                  <select class="form-control form-control-sm select2 mb-1 searchableSelect" id="customer_select">
                    <option value="">-- Select Customer --</option>
                    <?php
                    $qc = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_status = 1 AND customer_type = 'customer' ORDER BY customer_name ASC");
                    while ($rc = mysqli_fetch_assoc($qc)) {
                        $sel = (!empty($tax_sale['customer_id']) && $tax_sale['customer_id'] == $rc['customer_id']) ? "selected" : "";
                    ?>
                      <option value="<?=$rc['customer_id']?>" <?=$sel?>
                        data-id="<?=$rc['customer_id']?>"
                        data-name="<?=htmlspecialchars($rc['customer_name'])?>"
                        data-phone="<?=htmlspecialchars($rc['customer_phone'])?>"
                        data-address="<?=htmlspecialchars($rc['customer_address'])?>"
                        data-strn="<?=htmlspecialchars($rc['customer_strn'] ?? '')?>"
                        data-ntn="<?=htmlspecialchars($rc['customer_ntn'] ?? '')?>"
                        data-province="<?=htmlspecialchars($rc['customer_province'] ?? '')?>"
                        data-cnic="<?=htmlspecialchars($rc['customer_cnic'] ?? '')?>"
                      >
                        <?=htmlspecialchars(getCustomerDisplayName($rc['customer_name'], $rc['customer_phone']))?>
                      </option>
                    <?php } ?>
                  </select>
                  <input type="text" name="customer_name" id="customer_name" class="inv-input-clean party-name-val w-100" required placeholder="Buyer / Messers Name" value="<?=htmlspecialchars(@$edit_customer['customer_name'] ?: @$tax_sale['customer_name'])?>">
                  <input type="hidden" name="customer_id" id="customer_id" value="<?=@$tax_sale['customer_id']?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">Address :</div>
                <div class="party-val">
                  <textarea name="customer_address" id="customer_address" rows="2" class="inv-input w-100" placeholder="Customer Address..."><?=htmlspecialchars(@$edit_customer['customer_address'] ?: @$tax_sale['customer_address'])?></textarea>
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">Contact No. :</div>
                <div class="party-val d-flex align-items-center">
                  <input type="text" name="customer_phone" id="customer_phone" class="inv-input-clean flex-grow-1 mr-2" placeholder="Phone" value="<?=htmlspecialchars(@$edit_customer['customer_phone'] ?: @$tax_sale['customer_phone'])?>">
                  <strong class="mr-1">Province :</strong>
                  <input type="text" name="customer_province" id="customer_province" class="inv-input" style="width: 90px;" placeholder="Province" value="<?=htmlspecialchars(@$edit_customer['customer_province'] ?: @$tax_sale['customer_province'])?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">S.Tax Reg No. :</div>
                <div class="party-val">
                  <input type="text" name="customer_strn" id="customer_strn" class="inv-input font-weight-bold text-primary w-100" placeholder="e.g. 02-01-8708-018-73" value="<?=htmlspecialchars(@$edit_customer['customer_strn'] ?: @$tax_sale['customer_strn'])?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">NTN No. :</div>
                <div class="party-val d-flex align-items-center">
                  <input type="text" name="customer_ntn" id="customer_ntn" class="inv-input font-weight-bold text-dark mr-2" style="width: 100px;" placeholder="e.g. 0711733-7" value="<?=htmlspecialchars(@$edit_customer['customer_ntn'] ?: @$tax_sale['customer_ntn'])?>">
                  <!-- <strong class="mr-1">CNIC No. :</strong>
                  <input type="text" name="customer_cnic" id="customer_cnic" class="inv-input flex-grow-1" placeholder="CNIC" value="<?=@$tax_sale['customer_cnic']?>"> -->
                </div>
              </div>
            </div>
          </div>

          <!-- Description Row -->
          <div class="desc-line d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center flex-grow-1 mr-3">
              <strong style="white-space: nowrap;">Description :</strong> &nbsp;
              <input type="text" name="header_description" id="header_description" class="inv-input flex-grow-1 font-weight-bold" placeholder="GST Invoice Number : ... / BA TRADERS / P.O # : ... / H.S.Code # ..." value="<?=@$tax_sale['header_description']?>">
              
              <strong class="po-lbl">PO # :</strong>
              <div class="input-group po-input-group">
                <select name="po_no" id="po_no" data-placeholder="-- Select PO --" class="inv-input font-weight-bold searchableSelect" style="height: 31px;" onchange="onPoChange(this.value)">
                  <?php if (!empty($customer_pos)): ?>
                    <option value="">-- Select PO --</option>
                    <?php 
                    $found_sel = false;
                    foreach ($customer_pos as $cpo): 
                        $is_sel = (!empty($tax_sale['po_no']) && $tax_sale['po_no'] == $cpo);
                        if ($is_sel) $found_sel = true;
                    ?>
                      <option value="<?=htmlspecialchars($cpo)?>" <?=$is_sel ? 'selected' : ''?>><?=htmlspecialchars($cpo)?></option>
                    <?php endforeach; ?>
                    <?php if (!empty($tax_sale['po_no']) && !$found_sel): ?>
                      <option value="<?=htmlspecialchars($tax_sale['po_no'])?>" selected><?=htmlspecialchars($tax_sale['po_no'])?></option>
                    <?php endif; ?>
                    <option value="__custom__">+ Enter Custom PO...</option>
                  <?php elseif (!empty($tax_sale['po_no'])): ?>
                    <option value="">-- Select PO --</option>
                    <option value="<?=htmlspecialchars($tax_sale['po_no'])?>" selected><?=htmlspecialchars($tax_sale['po_no'])?></option>
                    <option value="__custom__">+ Enter Custom PO...</option>
                  <?php else: ?>
                    <option value="">Select Customer first</option>
                  <?php endif; ?>
                </select>
                <div class="input-group-append">
                  <button type="button" class="btn btn-sm btn-admin" onclick="getpoDetails()" title="View PO Details" style="height: 31px; padding: 2px 10px;">
                    <span class="fa fa-eye"></span>
                  </button>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-primary no-print shadow-sm" onclick="addItemRow()" style="white-space: nowrap; font-weight: bold; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
              <i class="fe fe-plus mr-1"></i>Add Item Row
            </button>
          </div>

          <!-- Main 10-Column Table with rounded corners -->
          <div class="invoice-table-wrapper">
            <table class="inv-table">
              <thead>
                <tr>
                  <th style="width: 12%;">Quantity</th>
                  <th style="width: 31%;">Description<br>Of<br>Goods</th>
                  <th style="width: 10%;">Rate</th>
                  <th style="width: 14%;">Value<br>Excl<br>Taxes</th>
                  <th style="width: 8%;">Rate<br>Of<br>Sales<br>Tax<br>%</th>
                  <th style="width: 11%;">Amount<br>Of<br>Sales Tax</th>
                  <th style="width: 11%;">Value<br>Including<br>Sales Tax</th>
                  <th style="width: 3%;" class="no-print text-center">
                    <button type="button" class="btn btn-sm btn-success p-0" onclick="addItemRow()" title="Add Item Row" style="width: 22px; height: 22px; line-height: 20px; font-weight: bold; font-size: 13px;">
                      +
                    </button>
                  </th>
                </tr>
              </thead>
              <tbody id="items_tbody">
                <!-- Dynamically generated rows -->
              </tbody>
              <tfoot>
                <!-- Totals Row -->
                <tr class="totals-divider-row">
                  <td class="align-right">
                    <span id="lbl_total_qty">0.000</span> KG
                    <input type="hidden" name="total_qty" id="total_qty" value="0">
                  </td>
                  <td></td>
                  <td></td>
                  <td class="align-right">
                    <span id="lbl_total_val_excl">0.00</span>
                    <input type="hidden" name="total_value_excl_tax" id="total_value_excl_tax" value="0">
                  </td>
                  <td></td>
                  <td class="align-right">
                    <span id="lbl_total_sales_tax">0.00</span>
                    <input type="hidden" name="total_sales_tax" id="total_sales_tax" value="0">
                    <input type="hidden" name="total_further_tax" id="total_further_tax" value="0">
                    <input type="hidden" name="total_tax_payable" id="total_tax_payable" value="0">
                    <input type="hidden" name="total_tax_amount" id="total_tax_amount" value="0">
                  </td>
                  <td class="align-right">
                    <span id="lbl_total_val_incl">0.00</span>
                    <input type="hidden" name="total_value_incl_tax" id="total_value_incl_tax" value="0">
                    <input type="hidden" name="grand_total" id="grand_total" value="0">
                  </td>
                  <td class="no-print"></td>
                </tr>

                <!-- I.Tax 236G -->
                <!-- <tr>
                  <td colspan="4" style="border: none;"></td>
                  <td colspan="2" class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
                    I.Tax 236G &nbsp;&nbsp;
                    <input type="number" step="0.01" min="0" name="itax_236g_rate" id="itax_236g_rate" class="inv-input" style="width: 50px; text-align: center;" value="<?=@$tax_sale['itax_236g_rate'] ?: '0.00'?>" oninput="calcSaleTotals()">%
                  </td>
                  <td class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
                    <input type="number" step="0.01" name="itax_236g_amount" id="itax_236g_amount" class="tbl-input tbl-input-readonly align-right" value="<?=@$tax_sale['itax_236g_amount'] ?: '0.00'?>" oninput="calcNetOnly()">
                  </td>
                  <td class="no-print" style="border: none;"></td>
                </tr> -->

                <!-- Inv. Amt -->
                <tr>
                  <td colspan="4" style="border: none;"></td>
                  <td colspan="2" class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
                    Total Amount:
                  </td>
                  <td class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
                    <span id="lbl_invoice_amount" style="font-size: 12px; font-weight: bold;">0.00</span>
                    <input type="hidden" name="invoice_amount" id="invoice_amount" value="0">
                  </td>
                  <td class="no-print" style="border: none;"></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Rupees in Words -->
          <div class="rupees-words-box">
            <textarea readonly name="amount_in_words" id="amount_in_words" rows="2" class="inv-input-clean w-100 font-weight-bold" style="font-size: 12px; color: #000;"><?=@$tax_sale['amount_in_words']?></textarea>
          </div>

          <!-- Bottom Section: FBR and Signature -->
          
            <!-- <div class="fbr-box-inner">
              <div class="d-flex align-items-center mb-1">
                <strong class="mr-2">FBR INVOICE NO. :</strong>
                <input type="text" name="fbr_invoice_no" id="fbr_invoice_no" class="inv-input" style="width: 250px;" placeholder="e.g. 3310004541981DI15EZNB651146" value="<?=@$tax_sale['fbr_invoice_no']?>">
                <button type="button" class="btn btn-sm btn-outline-secondary ml-1" onclick="generateFBRNumber()" title="Generate Random FBR No">
                  <i class="fe fe-refresh-cw"></i>
                </button>
              </div>
              <small class="text-muted">THIS IS SYSTEM GENERATED SALESTAX INVOICE</small>
            </div> -->

            <div class="signature-area">
              <div class="signature-line">
                Authorised Signature
              </div>
            </div>
          
        </div>

              <!-- Bottom Action Bar -->
              <div class="row mt-3 mb-2">
                <div class="col-12 text-right">
                  <!-- <a href="tax_sale_list.php" class="btn btn-secondary mr-2">
                    <i class="fe fe-arrow-left mr-1"></i>Invoices List
                  </a>
                  <button type="button" class="btn btn-info mr-2" onclick="addItemRow()">
                    <i class="fe fe-plus mr-1"></i>Add Item Row
                  </button> -->
                  <!-- <button type="button" class="btn btn-admin mr-2" id="btn_save_sale" onclick="triggerSubmit('save')">
                    <i class="fe fe-save mr-1"></i>Save Invoice
                  </button> -->
                  <button type="button" class="btn btn-success" id="btn_save_and_print_sale" onclick="triggerSubmit('save_and_print')">
                    <i class="fe fe-printer mr-1"></i>Save & Print
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>

  <?php include_once 'includes/foot.php'; ?>

  <!-- Party PO Details Modal -->
  <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title font-weight-bold">Party PO Details</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-3">
          <table class="table table-bordered table-striped mb-0" id="TablicaRacuni">
            <thead class="thead-dark">
              <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Required Quantity</th>
                <th>Delivered Quantity</th>
                <th>Remaining Quantity</th>
              </tr>
            </thead>
            <tbody id="party_poDetails">
              <tr><td colspan="5" class="text-center text-muted">No PO Selected</td></tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Template for Products Dropdown -->
  <div id="product_options_source" style="display: none;">
    <option value="">-- Choose Product --</option>
    <?php
    $qp = mysqli_query($dbc, "SELECT * FROM product WHERE status = 1 ORDER BY product_name ASC");
    while ($rp = mysqli_fetch_assoc($qp)) {
    ?>
      <option value="<?=$rp['product_id']?>"
        data-id="<?=$rp['product_id']?>"
        data-name="<?=htmlspecialchars($rp['product_name'])?>"
        data-hscode="<?=htmlspecialchars($rp['hs_code'] ?? '')?>"
        data-unit="KG"
        data-rate="<?=floatval($rp['current_rate'])?>"
        data-desc="<?=htmlspecialchars($rp['product_description'] ?: $rp['product_name'])?>"
      >
        <?=$rp['product_name']?> (HS: <?=$rp['hs_code'] ?: 'N/A'?>)
      </option>
    <?php } ?>
  </div>

  <script>
    var rowIndex = 0;
    var fullProductOptionsHtml = $('#product_options_source').html();
    var productOptionsHtml = fullProductOptionsHtml;
    var existingItems = <?=json_encode($tax_sale_items)?>;

    function escapeHtml(text) {
      if (!text) return '';
      return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    $(document).ready(function() {
      // Initialize select2 on customer selector
      if ($.fn.select2) {
        $('.select2').select2({ width: '100%' });
      }

      // Customer selection change handler
      $('#customer_select').on('change', function() {
        var opt = $(this).find(':selected');
        if (opt.val()) {
          var cid = opt.data('id') || opt.val();
          $('#customer_id').val(cid);
          $('#customer_name').val(opt.data('name') || '');
          $('#customer_phone').val(opt.data('phone') || '');
          $('#customer_address').val(opt.data('address') || '');
          $('#customer_strn').val(opt.data('strn') || '');
          $('#customer_ntn').val(opt.data('ntn') || '');
          $('#customer_province').val(opt.data('province') || '');
          autoUpdateHeaderDesc();

          $.ajax({
            url: 'php_action/custom_action.php',
            type: 'POST',
            data: { getBalance: cid },
            dataType: 'json',
            success: function(resp) {
              if (resp && resp.blnc !== undefined) {
                $('#customer_balance_val').text(parseFloat(resp.blnc).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#customer_balance_badge').show();
              }
            }
          });

          // Fetch POs for customer
          loadCustomerPOs(cid);
        } else {
          $('#customer_id').val('');
          $('#customer_balance_badge').hide();
          loadCustomerPOs('');
        }
      });

      // Populate existing items or add default blank row
      if (existingItems && existingItems.length > 0) {
        existingItems.forEach(function(item) {
          addItemRow(item);
        });
      } else {
        addItemRow();
      }

      // If initial PO exists (edit mode), filter products accordingly
      var initialPo = $('#po_no').val();
      if (initialPo && initialPo !== '__custom__') {
        onPoChange(initialPo, true);
      }
    });

    function updatePoDropdown(html, valToSelect) {
      if ($.fn.select2) {
        try {
          if ($('#po_no').hasClass('select2-hidden-accessible')) {
            $('#po_no').select2('destroy');
          }
        } catch (e) {}
      }
      $('.po-input-group').find('.select2-container').remove();

      $('#po_no').html(html);

      if (valToSelect) {
        $('#po_no').val(valToSelect);
      } else {
        $('#po_no').val('');
      }

      var firstOptText = $('#po_no option:first').text() || '-- Select PO --';

      if ($.fn.select2) {
        $('#po_no').select2({
          theme: 'bootstrap4',
          width: 'resolve',
          placeholder: {
            id: '',
            text: firstOptText
          }
        });
      }
    }

    function loadCustomerPOs(cid, selectedPo) {
      if (!cid) {
        updatePoDropdown('<option value="">Select Customer first</option>', '');
        resetProductDropdown();
        autoUpdateHeaderDesc();
        return;
      }

      updatePoDropdown('<option value="">Loading POs...</option>', '');

      $.ajax({
        type: 'POST',
        url: 'php_action/custom_action.php',
        data: {
          get_party_po: 'get_party_po',
          customer_id: cid,
          page_type: 'tax_sale'
        },
        dataType: 'text',
        success: function(msg) {
          var res = (msg || '').trim();
          var hasPOs = (res && res.indexOf('<option') !== -1 && res.indexOf('Not Found') === -1);
          var html = '';

          if (hasPOs) {
            html += '<option value="">-- Select PO --</option>';
            html += res;
            html += '<option value="__custom__">+ Enter Custom PO...</option>';

            var poToSelect = '';
            if (selectedPo) {
              poToSelect = selectedPo;
            } else {
              // Extract the first PO number from res
              var match = res.match(/value="([^"]+)"/);
              if (match && match[1]) {
                poToSelect = match[1];
              }
            }

            updatePoDropdown(html, poToSelect);

            if (poToSelect) {
              onPoChange(poToSelect);
            } else {
              resetProductDropdown();
              autoUpdateHeaderDesc();
            }
          } else {
            html += '<option value="">-- No PO Found --</option>';
            html += '<option value="__custom__">+ Enter Custom PO...</option>';
            updatePoDropdown(html, '');
            resetProductDropdown();
            autoUpdateHeaderDesc();
          }
        },
        error: function() {
          updatePoDropdown('<option value="">-- No PO Found --</option><option value="__custom__">+ Enter Custom PO...</option>', '');
          resetProductDropdown();
          autoUpdateHeaderDesc();
        }
      });
    }

    var isHandlingCustomPo = false;
    function onPoChange(poVal, isInit) {
      if (isHandlingCustomPo) return;
      if (poVal === '__custom__') {
        var custom = prompt('Enter Custom P.O Number:');
        if (custom && custom.trim() !== '') {
          custom = custom.trim();
          if ($('#po_no option[value="' + custom + '"]').length === 0) {
            $('#po_no option[value="__custom__"]').before('<option value="' + custom + '" selected>' + custom + '</option>');
          }
          isHandlingCustomPo = true;
          updatePoDropdown($('#po_no').html(), custom);
          isHandlingCustomPo = false;
          poVal = custom;
        } else {
          isHandlingCustomPo = true;
          updatePoDropdown($('#po_no').html(), '');
          isHandlingCustomPo = false;
          poVal = '';
        }
      }

      autoUpdateHeaderDesc();

      if (!poVal) {
        resetProductDropdown();
        return;
      }

      var cid = $('#customer_id').val() || '';

      $.ajax({
        url: 'php_action/custom_action.php',
        type: 'POST',
        data: {
          get_tax_sale_po_products: 'get_tax_sale_po_products',
          party_po: poVal,
          customer_id: cid
        },
        dataType: 'json',
        success: function(resp) {
          if (resp && resp.sts === 'success' && resp.data && resp.data.length > 0) {
            var html = '<option value="">-- Choose Product --</option>';
            resp.data.forEach(function(p) {
              var hsText = p.hs_code ? p.hs_code : 'N/A';
              html += '<option value="' + p.product_id + '"' +
                ' data-id="' + p.product_id + '"' +
                ' data-name="' + escapeHtml(p.product_name) + '"' +
                ' data-hscode="' + escapeHtml(p.hs_code) + '"' +
                ' data-unit="KG"' +
                ' data-rate="' + p.rate + '"' +
                ' data-qty="' + (p.ordered_qty || '') + '"' +
                ' data-desc="' + escapeHtml(p.description) + '">' +
                escapeHtml(p.product_name) + ' (HS: ' + escapeHtml(hsText) + ')' +
                '</option>';
            });

            // If initializing in edit mode, ensure existing items remain present
            if (isInit) {
              $('.product_selector').each(function() {
                var currVal = $(this).val();
                if (currVal && html.indexOf('value="' + currVal + '"') === -1) {
                  var currOpt = $('#product_options_source option[value="' + currVal + '"]');
                  if (currOpt.length > 0) {
                    html += currOpt[0].outerHTML;
                  }
                }
              });
            }

            productOptionsHtml = html;

            // Update all existing item rows
            $('.product_selector').each(function() {
              var currentVal = $(this).val();
              var rowId = $(this).closest('tr').attr('id').replace('row_', '');
              $(this).html(productOptionsHtml);

              if (currentVal && $(this).find('option[value="' + currentVal + '"]').length > 0) {
                $(this).val(currentVal);
              } else {
                if (!isInit) {
                  $(this).val('');
                  onProductSelect(rowId, this);
                }
              }
            });

            // Auto-select if only 1 product in PO and first row is blank
            if (!isInit && resp.data.length === 1 && $('#items_tbody tr').length === 1) {
              var firstRowSelector = $('#items_tbody tr:first .product_selector');
              if (!firstRowSelector.val()) {
                firstRowSelector.val(resp.data[0].product_id);
                var firstRowId = $('#items_tbody tr:first').attr('id').replace('row_', '');
                onProductSelect(firstRowId, firstRowSelector[0]);
              }
            }
          } else {
            resetProductDropdown();
          }
        },
        error: function() {
          resetProductDropdown();
        }
      });
    }

    function resetProductDropdown() {
      productOptionsHtml = fullProductOptionsHtml;
      $('.product_selector').each(function() {
        var currentVal = $(this).val();
        $(this).html(productOptionsHtml);
        if (currentVal) $(this).val(currentVal);
      });
    }

    function getpoDetails() {
      var party_po = $('#po_no :selected').val() || $('#po_no').val();
      if (!party_po || party_po === '__custom__') {
        Swal.fire('Notice', 'Please select a Party PO first', 'info');
        return;
      }
      $('#myModal').modal('show');
      $('#party_poDetails').html('<tr><td colspan="5" class="text-center">Loading PO details...</td></tr>');
      var customer_id = $('#customer_id').val() || '';
      $.ajax({
        url: 'php_action/custom_action.php',
        type: 'POST',
        data: { getpoDetails: 'getpoDetails', party_po: party_po, customer_id: customer_id },
        dataType: 'text',
        success: function(msg) {
          var res = (msg || '').trim();
          $('#party_poDetails').html(res);
        },
        error: function() {
          $('#party_poDetails').html('<tr><td colspan="5" class="text-center text-danger">Failed to load PO details</td></tr>');
        }
      });
    }

    function generateFBRNumber() {
      var rand = Math.floor(100000000000 + Math.random() * 900000000000);
      var code = '331000454' + rand.toString().substring(0, 4) + 'DI15EZNB' + Math.floor(100000 + Math.random() * 900000);
      $('#fbr_invoice_no').val(code);
    }

    function autoUpdateHeaderDesc() {
      var invNo = $('#invoice_no').val() || '';
      var poNo = $('#po_no').val() || '';
      if (poNo === '__custom__') poNo = '';
      var hsFirst = '';
      $('input[name="item_hscode[]"]').each(function() {
        var v = $.trim($(this).val());
        if (v && !hsFirst) hsFirst = v;
      });
      var desc = 'GST Invoice Number : ' + invNo + ' / BA TRADERS';
      if (poNo) desc += ' / P.O # : ' + poNo;
      if (hsFirst) desc += ' / H.S.Code # ' + hsFirst;
      if (!$('#header_description').val() || $('#header_description').val().indexOf('GST Invoice Number') === 0) {
        $('#header_description').val(desc);
      }
    }

    function addItemRow(item) {
      rowIndex++;
      var currentIdx = rowIndex;

      var pid = item ? (item.product_id || '') : '';
      var desc = item ? (item.description_of_goods || '') : '';
      var hscode = item ? (item.hs_code || '') : '';
      var origin = item ? (item.origin || '') : '';
      var unit = 'KG';
      var qty = item && item.quantity ? parseFloat(item.quantity) : '';
      var rate = item && item.rate ? parseFloat(item.rate) : '';
      var taxRate = item && item.tax_rate !== undefined ? parseFloat(item.tax_rate) : 18.00;
      var valExcl = item && item.value_excl_tax ? parseFloat(item.value_excl_tax) : 0;
      var taxAmt = item && item.tax_amount ? parseFloat(item.tax_amount) : 0;
      var valIncl = item && item.value_incl_tax ? parseFloat(item.value_incl_tax) : 0;

      var rowHtml = `
        <tr id="row_${currentIdx}" class="item-dotted-row">
          <td>
            <input type="number" step="0.001" min="0" name="item_qty[]" id="item_qty_${currentIdx}" class="tbl-input align-right" placeholder="0.000" value="${qty}" oninput="calcRow(${currentIdx})">
            <div class="d-flex align-items-center mt-1">
              <input type="text" readonly name="item_unit[]" id="item_unit_${currentIdx}" class="tbl-input align-center tbl-input-readonly" style="font-size: 9.5px; height: 22px; font-weight: bold;" value="KG">
            </div>
          </td>
          <td>
            <select class="tbl-input product_selector mb-1" onchange="onProductSelect(${currentIdx}, this)">
              ${productOptionsHtml}
            </select>
            <input type="hidden" name="item_product_id[]" id="item_product_id_${currentIdx}" value="${pid}">
            <input type="hidden" name="item_origin[]" id="item_origin_${currentIdx}" value="">
            <input type="text" name="item_description[]" id="item_description_${currentIdx}" required class="tbl-input font-weight-bold" placeholder="Description of goods" value="${desc}">
            <div class="d-flex align-items-center mt-1">
              <span style="font-size: 9px; min-width: 55px;">H.S. Code:</span>
              <input type="text" name="item_hscode[]" id="item_hscode_${currentIdx}" class="tbl-input align-center" style="font-size: 9.5px; height: 20px;" placeholder="HS Code" value="${hscode}" onchange="autoUpdateHeaderDesc()">
            </div>
          </td>
          <td>
            <input type="number" step="0.0001" min="0" name="item_rate[]" id="item_rate_${currentIdx}" class="tbl-input align-right" placeholder="0.0000" value="${rate}" oninput="calcRow(${currentIdx})">
          </td>
          <td>
            <input type="number" step="0.01" readonly name="item_val_excl[]" id="item_val_excl_${currentIdx}" class="tbl-input tbl-input-readonly align-right" value="${valExcl > 0 ? valExcl.toFixed(2) : '0.00'}">
          </td>
          <td>
            <input type="number" step="0.01" min="0" name="item_tax_rate[]" id="item_tax_rate_${currentIdx}" class="tbl-input align-center" value="${taxRate}" oninput="calcRow(${currentIdx})">
          </td>
          <td>
            <input type="number" step="0.01" readonly name="item_tax_amount[]" id="item_tax_amount_${currentIdx}" class="tbl-input tbl-input-readonly align-right text-danger" value="${taxAmt > 0 ? taxAmt.toFixed(2) : '0.00'}">
            <input type="hidden" name="item_further_tax_rate[]" id="item_further_tax_rate_${currentIdx}" value="0">
            <input type="hidden" name="item_further_tax_amount[]" id="item_further_tax_amount_${currentIdx}" value="0">
            <input type="hidden" name="item_total_tax[]" id="item_total_tax_${currentIdx}" value="${taxAmt > 0 ? taxAmt.toFixed(2) : '0.00'}">
          </td>
          <td>
            <input type="number" step="0.01" readonly name="item_val_incl[]" id="item_val_incl_${currentIdx}" class="tbl-input tbl-input-readonly align-right text-success" style="font-weight: bold;" value="${valIncl > 0 ? valIncl.toFixed(2) : '0.00'}">
          </td>
          <td class="align-center no-print">
            <button type="button" class="btn btn-sm btn-outline-danger p-0" style="width: 22px; height: 22px; line-height: 20px;" onclick="removeRow(${currentIdx})">
              &times;
            </button>
          </td>
        </tr>
      `;

      $('#items_tbody').append(rowHtml);

      if (pid) {
        $(`#row_${currentIdx} .product_selector`).val(pid);
      }

      calcRow(currentIdx);
    }

    function onProductSelect(idx, elem) {
      var opt = $(elem).find(':selected');
      if (opt.val()) {
        $(`#item_product_id_${idx}`).val(opt.data('id') || opt.val());
        $(`#item_description_${idx}`).val(opt.data('name') || '');
        $(`#item_hscode_${idx}`).val(opt.data('hscode') || '');
        $(`#item_unit_${idx}`).val('KG');
        
        var defaultRate = parseFloat(opt.data('rate') || 0);
        if (defaultRate > 0) {
          $(`#item_rate_${idx}`).val(defaultRate);
        } else {
          $(`#item_rate_${idx}`).val('');
        }
        var defaultQty = parseFloat(opt.data('qty') || 0);
        if (defaultQty > 0 && (!$(`#item_qty_${idx}`).val() || parseFloat($(`#item_qty_${idx}`).val()) === 0)) {
          $(`#item_qty_${idx}`).val(defaultQty);
        }
      } else {
        $(`#item_product_id_${idx}`).val('');
        $(`#item_description_${idx}`).val('');
        $(`#item_hscode_${idx}`).val('');
        $(`#item_rate_${idx}`).val('');
      }
      calcRow(idx);
      autoUpdateHeaderDesc();
    }

    function calcRow(idx) {
      var qty = parseFloat($(`#item_qty_${idx}`).val()) || 0;
      var rate = parseFloat($(`#item_rate_${idx}`).val()) || 0;
      var taxRate = parseFloat($(`#item_tax_rate_${idx}`).val()) || 0;

      var valExcl = (qty > 0 && rate > 0) ? (qty * rate) : 0;
      var taxAmt = (valExcl > 0) ? (valExcl * (taxRate / 100)) : 0;
      var valIncl = valExcl + taxAmt;

      $(`#item_val_excl_${idx}`).val(valExcl.toFixed(2));
      $(`#item_tax_amount_${idx}`).val(taxAmt.toFixed(2));
      $(`#item_further_tax_rate_${idx}`).val('0');
      $(`#item_further_tax_amount_${idx}`).val('0.00');
      $(`#item_total_tax_${idx}`).val(taxAmt.toFixed(2));
      $(`#item_val_incl_${idx}`).val(valIncl.toFixed(2));

      calcSaleTotals();
    }

    function removeRow(idx) {
      if ($('#items_tbody tr').length <= 1) {
        Swal.fire('Warning', 'At least one item row is required!', 'warning');
        return;
      }
      $(`#row_${idx}`).remove();
      calcSaleTotals();
    }

    function calcSaleTotals() {
      var totalQty = 0;
      var totalValExcl = 0;
      var totalSalesTax = 0;
      var totalValIncl = 0;

      $('input[name="item_qty[]"]').each(function() { totalQty += parseFloat($(this).val()) || 0; });
      $('input[name="item_val_excl[]"]').each(function() { totalValExcl += parseFloat($(this).val()) || 0; });
      $('input[name="item_tax_amount[]"]').each(function() { totalSalesTax += parseFloat($(this).val()) || 0; });
      $('input[name="item_val_incl[]"]').each(function() { totalValIncl += parseFloat($(this).val()) || 0; });

      $('#lbl_total_qty').text(totalQty.toFixed(3));
      $('#lbl_total_val_excl').text(totalValExcl.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      $('#lbl_total_sales_tax').text(totalSalesTax.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      $('#lbl_total_val_incl').text(totalValIncl.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

      $('#total_qty').val(totalQty.toFixed(3));
      $('#total_value_excl_tax').val(totalValExcl.toFixed(2));
      $('#total_sales_tax').val(totalSalesTax.toFixed(2));
      $('#total_further_tax').val('0.00');
      $('#total_tax_payable').val(totalSalesTax.toFixed(2));
      $('#total_tax_amount').val(totalSalesTax.toFixed(2));
      $('#total_value_incl_tax').val(totalValIncl.toFixed(2));
      $('#grand_total').val(totalValIncl.toFixed(2));

      // Calculate Income Tax 236G
      var itaxRate = parseFloat($('#itax_236g_rate').val()) || 0;
      var itaxAmount = Math.round(totalValIncl * (itaxRate / 100));
      $('#itax_236g_amount').val(itaxAmount.toFixed(2));

      var invoiceAmount = totalValIncl + itaxAmount;
      $('#lbl_invoice_amount').text(invoiceAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      $('#invoice_amount').val(invoiceAmount.toFixed(2));

      // Live amount in words
      updateNumberInWords(invoiceAmount);
    }

    function calcNetOnly() {
      var totalValIncl = parseFloat($('#total_value_incl_tax').val()) || 0;
      var itaxAmount = parseFloat($('#itax_236g_amount').val()) || 0;
      var invoiceAmount = totalValIncl + itaxAmount;

      $('#lbl_invoice_amount').text(invoiceAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      updateNumberInWords(invoiceAmount);
    }

    function updateNumberInWords(num) {
      if (parseFloat(num) <= 0) {
        $('#amount_in_words').val('');
      } else {
        $('#amount_in_words').val(numberToWordsPKR(num));
      }
    }

    function numberToWordsPKR(amount) {
      amount = parseFloat(amount) || 0;
      var intPart = Math.floor(amount);
      var decPart = Math.round((amount - intPart) * 100);

      var a = ['', 'ONE ', 'TWO ', 'THREE ', 'FOUR ', 'FIVE ', 'SIX ', 'SEVEN ', 'EIGHT ', 'NINE ', 'TEN ', 'ELEVEN ', 'TWELVE ', 'THIRTEEN ', 'FOURTEEN ', 'FIFTEEN ', 'SIXTEEN ', 'SEVENTEEN ', 'EIGHTEEN ', 'NINETEEN '];
      var b = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];

      function convertBelowThousand(n) {
        var str = '';
        if (n >= 100) {
          str += a[Math.floor(n / 100)] + 'HUNDRED ';
          n %= 100;
        }
        if (n > 0) {
          if (n < 20) {
            str += a[n];
          } else {
            str += b[Math.floor(n / 10)] + (n % 10 > 0 ? '-' + a[n % 10].trim() : '') + ' ';
          }
        }
        return str;
      }

      if (intPart === 0) {
        var words = 'ZERO';
      } else {
        var chunks = [];
        var units = ['', 'THOUSAND', 'MILLION', 'BILLION'];
        var uIdx = 0;
        var temp = intPart;
        while (temp > 0) {
          var rem = temp % 1000;
          if (rem > 0) {
            var chunkText = convertBelowThousand(rem).trim();
            if (uIdx > 0 && units[uIdx]) {
              chunkText += ' ' + units[uIdx];
            }
            chunks.unshift(chunkText);
          }
          temp = Math.floor(temp / 1000);
          uIdx++;
        }
        var words = chunks.join(' ');
      }

      var res = 'RUPEES: ' + words.trim();
      if (decPart > 0) {
        var paisa = (decPart < 10 ? '0' : '') + decPart;
        res += ' AND ' + paisa + ' / 100 ONLY.';
      } else {
        res += ' ONLY.';
      }
      return res;
    }

    // Handle AJAX Form Submission
    var submitButtonType = 'save';

    function triggerSubmit(type) {
      submitButtonType = type;
      $('#tax_sale_form').submit();
    }

    $('#tax_sale_form').on('submit', function(e) {
      e.preventDefault();

      if ($('#items_tbody tr').length === 0) {
        Swal.fire('Error', 'Please add at least one item row', 'error');
        return false;
      }

      if ($('#po_no').val() === '__custom__') {
        $('#po_no').val('');
      }

      var form = $(this);
      var btn = (submitButtonType === 'save') ? $('#btn_save_sale, #btn_save_sale_top') : $('#btn_save_and_print_sale, #btn_save_and_print_sale_top');
      btn.prop('disabled', true);

      $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(resp) {
          btn.prop('disabled', false);

          if (resp.sts === 'success') {
            Swal.fire({
              title: resp.msg,
              text: 'Invoice #: ' + ($('#invoice_no').val() || ''),
              icon: 'success',
              showDenyButton: true,
              showCancelButton: true,
              confirmButtonText: 'Print Invoice',
              denyButtonText: 'Add New',
              cancelButtonText: 'Invoices List'
            }).then(function(result) {
              if (result.isConfirmed) {
                window.open('print_tax_sale.php?id=' + resp.order_id, '_blank');
                window.location.href = 'tax_sale.php';
              } else if (result.isDenied) {
                window.location.href = 'tax_sale.php';
              } else {
                window.location.href = 'tax_sale_list.php';
              }
            });
          } else {
            Swal.fire('Error', resp.msg || 'Could not save sale invoice', 'error');
          }
        },
        error: function(xhr, status, err) {
          btn.prop('disabled', false);
          Swal.fire('Error', 'Server error: ' + err, 'error');
        }
      });
    });
  </script>
</body>
</html>
