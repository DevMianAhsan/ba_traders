<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 

$edit_id = !empty($_REQUEST['edit_id']) ? intval($_REQUEST['edit_id']) : 0;
$tax_purchase = null;
$tax_purchase_items = [];

if ($edit_id > 0) {
    $tax_purchase = fetchRecord($dbc, "tax_purchases", "tax_purchase_id", $edit_id);
    if ($tax_purchase) {
        $q_items = mysqli_query($dbc, "SELECT * FROM tax_purchase_items WHERE tax_purchase_id = '$edit_id' ORDER BY item_id ASC");
        while ($row = mysqli_fetch_assoc($q_items)) {
            $tax_purchase_items[] = $row;
        }
    }
}

// Next invoice number
if (empty($tax_purchase)) {
    $last_inv = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT invoice_no FROM tax_purchases ORDER BY tax_purchase_id DESC LIMIT 1"));
    if ($last_inv && is_numeric($last_inv['invoice_no'])) {
        $next_invoice_no = intval($last_inv['invoice_no']) + 1;
    } else {
        $res_status = mysqli_fetch_assoc(mysqli_query($dbc, "SHOW TABLE STATUS LIKE 'tax_purchases'"));
        $next_id = !empty($res_status['Auto_increment']) ? $res_status['Auto_increment'] : 1374;
        $next_invoice_no = $next_id;
    }
} else {
    $next_invoice_no = $tax_purchase['invoice_no'];
}

// Buyer Details (BA TRADERS from company table)
$company_id = !empty($tax_purchase['company_id']) ? intval($tax_purchase['company_id']) : (!empty($get_company['id']) ? intval($get_company['id']) : 5);
if ($company_id > 0) {
    $comp_res = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company WHERE id = '$company_id'"));
    if ($comp_res) {
        $get_company = $comp_res;
    }
}
$buyer_name = !empty($get_company['name']) ? $get_company['name'] : 'BA TRADERS';
$buyer_address = !empty($get_company['address']) ? $get_company['address'] : 'Shop # 621, AL Madina Commercial Market, Near Hamza Kanta, Oil Depot Road, Tata Market, Faisalabad';
$buyer_strn = !empty($get_company['s_tax_no']) ? $get_company['s_tax_no'] : '32-77-8763-934-54';
$buyer_ntn = !empty($get_company['ntn_no']) ? $get_company['ntn_no'] : 'A322716-3';
$buyer_cnic = !empty($get_company['cnic_no']) ? $get_company['cnic_no'] : '';
$buyer_phone = !empty($get_company['company_phone']) ? $get_company['company_phone'] : '';

// Supplier Details from customers table if editing
$edit_supplier = null;
if (!empty($tax_purchase['supplier_id'])) {
    $edit_supplier = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM customers WHERE customer_id = '{$tax_purchase['supplier_id']}'"));
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
                  <i class="fe fe-shopping-cart mr-2"></i><?=!empty($tax_purchase) ? "Edit Tax Purchase Invoice" : "Purchase Tax Invoice Form"?>
                </h4>
                <small class="text-light">BA TRADERS &bull; Purchase Tax Invoice Document</small>
              </div>
              <div class="col-sm-7 text-right">
                <a href="tax_purchase_list.php" class="btn btn-sm btn-light font-weight-bold mr-1">
                  <i class="fe fe-list mr-1"></i>Invoices List
                </a>
                <a href="tax_purchase.php" class="btn btn-sm btn-outline-light font-weight-bold mr-1">
                  <i class="fe fe-refresh-cw mr-1"></i>New
                </a>
                <!-- <button type="button" class="btn btn-sm btn-warning font-weight-bold mr-1" id="btn_save_purchase_top" onclick="triggerSubmit('save')">
                  <i class="fe fe-save mr-1"></i>Save
                </button>
                <button type="button" class="btn btn-sm btn-success font-weight-bold" id="btn_save_and_print_purchase_top" onclick="triggerSubmit('save_and_print')">
                  <i class="fe fe-printer mr-1"></i>Save & Print
                </button> -->
              </div>
            </div>
          </div>
          <div class="card-body p-3" style="background: #eef2f5;">
            <form id="tax_purchase_form" method="POST" action="php_action/custom_action.php">
              <input type="hidden" name="tax_purchase_action" value="save_tax_purchase">
              <input type="hidden" name="tax_purchase_id" value="<?=@$tax_purchase['tax_purchase_id']?>">
              <input type="hidden" name="company_id" id="company_id" value="<?=$company_id?>">

              <!-- The Exact Invoice Sheet Document -->
        <div class="invoice-sheet">
          <!-- Document Title -->
          <div class="invoice-title">
            <h1>PURCHASE TAX INVOICE</h1>
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
                <input type="date" name="invoice_date" id="invoice_date" class="inv-input" style="width: 140px;" required value="<?=@$tax_purchase['invoice_date'] ?: date('Y-m-d')?>">
              </div>
            </div>

            <div class="meta-right">
              <div class="meta-field-right">
                <span class="lbl">Time of Supply :</span>
                <input type="text" name="time_of_supply" id="time_of_supply" class="inv-input-clean" style="width: 150px;" placeholder="Time / Terms" value="<?=@$tax_purchase['time_of_supply']?>">
              </div>
              <div class="meta-field-right">
                <span class="lbl">Payment Terms :</span>
                <input type="text" readonly name="payment_terms" id="payment_terms" class="inv-input-clean font-weight-bold" style="width: 100px; text-align: left;" value="<?=@$tax_purchase['payment_terms'] ?: 'CREDIT'?>">
              </div>
              <!-- <div class="meta-field-right">
                <select name="copy_type" class="inv-input-clean font-weight-bold" style="letter-spacing: 2px;">
                  <option <?=(@$tax_purchase['copy_type'] == 'ORIGINAL' || empty($tax_purchase)) ? 'selected' : ''?> value="ORIGINAL">ORIGINAL</option>
                  <option <?=(@$tax_purchase['copy_type'] == 'DUPLICATE') ? 'selected' : ''?> value="DUPLICATE">DUPLICATE</option>
                  <option <?=(@$tax_purchase['copy_type'] == 'TRIPLICATE') ? 'selected' : ''?> value="TRIPLICATE">TRIPLICATE</option>
                </select>
              </div> -->
            </div>
          </div>

          <!-- Two-Column Rounded Parties Box -->
          <div class="parties-card">
            <!-- Left Party: Supplier -->
            <div class="party-col left-col">
              <div class="party-row">
                <div class="party-lbl">Supplier Name :</div>
                <div class="party-val">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small text-muted font-weight-bold">Select Supplier:</span>
                    <span class="badge badge-info px-2 py-1" id="supplier_balance_badge" style="<?=(!empty($tax_purchase['supplier_id']) ? '' : 'display:none;')?> font-size:11px;">
                      Balance: <span id="supplier_balance_val"><?php
                        if (!empty($tax_purchase['supplier_id'])) {
                            $b_row = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(credit-debit) AS bal FROM transactions WHERE customer_id = '{$tax_purchase['supplier_id']}'"));
                            echo number_format($b_row['bal'] ?? 0, 2);
                        } else {
                            echo "0.00";
                        }
                      ?></span>
                    </span>
                  </div>
                  <select class="form-control form-control-sm select2 mb-1 searchableSelect" id="supplier_select">
                    <option value="">-- Select Supplier --</option>
                    <?php
                    $qs = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_status = 1 AND customer_type = 'supplier' ORDER BY customer_name ASC");
                    while ($rs = mysqli_fetch_assoc($qs)) {
                        $sel = (!empty($tax_purchase['supplier_id']) && $tax_purchase['supplier_id'] == $rs['customer_id']) ? "selected" : "";
                    ?>
                      <option value="<?=$rs['customer_id']?>" <?=$sel?>
                        data-id="<?=$rs['customer_id']?>"
                        data-name="<?=htmlspecialchars($rs['customer_name'])?>"
                        data-phone="<?=htmlspecialchars($rs['customer_phone'])?>"
                        data-address="<?=htmlspecialchars($rs['customer_address'])?>"
                        data-strn="<?=htmlspecialchars($rs['customer_strn'] ?? '')?>"
                        data-ntn="<?=htmlspecialchars($rs['customer_ntn'] ?? '')?>"
                      >
                        <?=htmlspecialchars(getCustomerDisplayName($rs['customer_name'], $rs['customer_phone']))?>
                      </option>
                    <?php } ?>
                  </select>
                  <input type="text" name="supplier_name" id="supplier_name" class="inv-input-clean party-name-val w-100" required placeholder="Supplier Name" value="<?=htmlspecialchars(@$edit_supplier['customer_name'] ?: @$tax_purchase['supplier_name'])?>">
                  <input type="hidden" name="supplier_id" id="supplier_id" value="<?=@$tax_purchase['supplier_id']?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">Address :</div>
                <div class="party-val">
                  <textarea name="supplier_address" id="supplier_address" rows="2" class="inv-input w-100" placeholder="Address..."><?=htmlspecialchars(@$edit_supplier['customer_address'] ?: @$tax_purchase['supplier_address'])?></textarea>
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">Contact No. :</div>
                <div class="party-val">
                  <input type="text" name="supplier_contact" id="supplier_contact" class="inv-input-clean w-100" placeholder="Contact number" value="<?=htmlspecialchars(@$edit_supplier['customer_phone'] ?: @$tax_purchase['supplier_contact'])?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">S.Tax Reg No. :</div>
                <div class="party-val">
                  <input type="text" name="supplier_strn" id="supplier_strn" class="inv-input font-weight-bold text-primary w-100" placeholder="e.g. 32-77-8761-660-49" value="<?=htmlspecialchars(@$edit_supplier['customer_strn'] ?: @$tax_purchase['supplier_strn'])?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">NTN No. :</div>
                <div class="party-val">
                  <input type="text" name="supplier_ntn" id="supplier_ntn" class="inv-input font-weight-bold text-dark w-100" placeholder="e.g. 5368642-7" value="<?=htmlspecialchars(@$edit_supplier['customer_ntn'] ?: @$tax_purchase['supplier_ntn'])?>">
                </div>
              </div>
            </div>

            <!-- Right Party: Buyer (BA TRADERS from company table) -->
            <div class="party-col">
              <div class="party-row">
                <div class="party-lbl">Buyer Name :</div>
                <div class="party-val">
                  <input type="text" name="buyer_name" id="buyer_name" class="inv-input-clean party-name-val w-100" value="<?=htmlspecialchars($buyer_name)?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">Address :</div>
                <div class="party-val">
                  <textarea name="buyer_address" id="buyer_address" rows="2" class="inv-input w-100"><?=htmlspecialchars($buyer_address)?></textarea>
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">Contact No. :</div>
                <div class="party-val">
                  <input type="text" name="buyer_contact" id="buyer_contact" class="inv-input-clean w-100" value="<?=htmlspecialchars($buyer_phone)?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">S.Tax Reg No. :</div>
                <div class="party-val">
                  <input type="text" name="buyer_strn" id="buyer_strn" class="inv-input font-weight-bold text-danger w-100" value="<?=htmlspecialchars($buyer_strn)?>">
                </div>
              </div>

              <div class="party-row">
                <div class="party-lbl">NTN No. :</div>
                <div class="party-val d-flex align-items-center">
                  <input type="text" name="buyer_ntn" id="buyer_ntn" class="inv-input font-weight-bold text-dark mr-2" style="width: 100px;" value="<?=htmlspecialchars($buyer_ntn)?>">
                  <!-- <strong class="mr-1">CNIC No. :</strong>
                  <input type="text" name="buyer_cnic" id="buyer_cnic" class="inv-input flex-grow-1" placeholder="CNIC" value="<?=htmlspecialchars($buyer_cnic)?>"> -->
                </div>
              </div>
            </div>
          </div>

          <!-- Description Row -->
          <div class="desc-line d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center flex-grow-1 mr-3">
              <strong>Description :</strong> &nbsp;
              <input type="text" name="header_description" id="header_description" class="inv-input flex-grow-1 font-weight-bold" placeholder="GST Invoice Number : ... / BA TRADERS / GD Number : ... / H.S.Code # ..." value="<?=@$tax_purchase['header_description']?>">
              <input type="text" name="gd_number" id="gd_number" class="inv-input ml-2" style="width: 140px;" placeholder="GD Number" value="<?=@$tax_purchase['gd_number']?>" oninput="autoUpdateHeaderDesc()">
            </div>
            <button type="button" class="btn btn-sm btn-primary no-print shadow-sm" onclick="addItemRow()" style="white-space: nowrap; font-weight: bold; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
              <i class="fe fe-plus mr-1"></i>Add Item Row
            </button>
          </div>

          <!-- Main 7-Column Table with rounded corners -->
          <div class="invoice-table-wrapper">
            <table class="inv-table">
              <thead>
                <tr>
                  <th style="width: 12%;">Quantity</th>
                  <th style="width: 32%;">Description<br>Of<br>Goods</th>
                  <th style="width: 10%;">Rate</th>
                  <th style="width: 14%;">Value<br>Excl<br>Taxes</th>
                  <th style="width: 8%;">Rate<br>Of<br>Sales<br>Tax<br>%</th>
                  <th style="width: 11%;">Amount<br>Of<br>Sales Tax</th>
                  <th style="width: 13%;">Value<br>Including<br>Sales Tax</th>
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
                    <span id="lbl_total_qty">0.000</span>
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
                  </td>
                  <td class="align-right">
                    <span id="lbl_total_val_incl">0.00</span>
                    <input type="hidden" name="total_value_incl_tax" id="total_value_incl_tax" value="0">
                  </td>
                  <td class="no-print"></td>
                </tr>

                <!-- I.Tax 236G -->
                <!-- <tr>
                  <td colspan="4" style="border: none;"></td>
                  <td colspan="2" class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
                    I.Tax 236G &nbsp;&nbsp;
                    <input type="number" step="0.01" min="0" name="itax_236g_rate" id="itax_236g_rate" class="inv-input" style="width: 50px; text-align: center;" value="<?=@$tax_purchase['itax_236g_rate'] ?: '0.10'?>" oninput="calcPurchaseTotals()">%
                  </td>
                  <td class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
                    <input type="number" step="0.01" name="itax_236g_amount" id="itax_236g_amount" class="tbl-input tbl-input-readonly align-right" value="<?=@$tax_purchase['itax_236g_amount'] ?: '0.00'?>" oninput="calcNetOnly()">
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
            <textarea readonly name="amount_in_words" id="amount_in_words" rows="2" class="inv-input-clean w-100 font-weight-bold" style="font-size: 12px; color: #000;"><?=@$tax_purchase['amount_in_words']?></textarea>
          </div>

          <!-- Signature Line -->
          <div class="signature-area">
            <div class="signature-line">
              Authorised Signature
            </div>
          </div>
        </div>

              <!-- Bottom Action Bar -->
              <div class="row mt-3 mb-2">
                <div class="col-12 text-right">
                  <!-- <a href="tax_purchase_list.php" class="btn btn-secondary mr-2">
                    <i class="fe fe-arrow-left mr-1"></i>Invoices List
                  </a>
                  <button type="button" class="btn btn-info mr-2" onclick="addItemRow()">
                    <i class="fe fe-plus mr-1"></i>Add Item Row
                  </button> -->
                  <!-- <button type="button" class="btn btn-admin mr-2" id="btn_save_purchase" onclick="triggerSubmit('save')">
                    <i class="fe fe-save mr-1"></i>Save Invoice
                  </button> -->
                  <button type="button" class="btn btn-success" id="btn_save_and_print_purchase" onclick="triggerSubmit('save_and_print')">
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
        data-rate="<?=floatval($rp['purchase_rate'] ?: $rp['current_rate'])?>"
        data-desc="<?=htmlspecialchars($rp['product_description'] ?: $rp['product_name'])?>"
      >
        <?=$rp['product_name']?> (HS: <?=$rp['hs_code'] ?: 'N/A'?>)
      </option>
    <?php } ?>
  </div>

  <script>
    var rowIndex = 0;
    var productOptionsHtml = $('#product_options_source').html();
    var existingItems = <?=json_encode($tax_purchase_items)?>;

    $(document).ready(function() {
      // Initialize select2 on supplier selector
      if ($.fn.select2) {
        $('.select2').select2({ width: '100%' });
      }
      // Supplier selection change handler
      $('#supplier_select').on('change select2:select', function() {
        var opt = $(this).find(':selected');
        if (opt.val()) {
          var sid = opt.data('id') || opt.val();
          $('#supplier_id').val(sid);
          $('#supplier_name').val(opt.data('name') || '');
          $('#supplier_contact').val(opt.data('phone') || '');
          $('#supplier_address').val(opt.data('address') || '');
          $('#supplier_strn').val(opt.data('strn') || '');
          $('#supplier_ntn').val(opt.data('ntn') || '');
          autoUpdateHeaderDesc();

          $.ajax({
            type: 'POST',
            url: 'php_action/custom_action.php',
            data: { getBalance: sid },
            dataType: 'json',
            success: function(resp) {
              if (resp && resp.blnc !== undefined) {
                $('#supplier_balance_val').text(resp.blnc);
                $('#supplier_balance_badge').show();
              }
            }
          });
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
    });

    function autoUpdateHeaderDesc() {
      var invNo = $('#invoice_no').val() || '';
      var gdNo = $('#gd_number').val() || '';
      var hsFirst = '';
      $('input[name="item_hscode[]"]').each(function() {
        var v = $.trim($(this).val());
        if (v && !hsFirst) hsFirst = v;
      });
      var desc = 'GST Invoice Number : ' + invNo + ' / BA TRADERS';
      if (gdNo) desc += ' / GD Number : ' + gdNo;
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
      var unit = item ? (item.unit || 'KG') : 'KG';
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
              <input type="text" name="item_unit[]" id="item_unit_${currentIdx}" class="tbl-input align-center" style="font-size: 9.5px; height: 22px;" value="${unit || 'KG'}">
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

      calcPurchaseTotals();
    }

    function removeRow(idx) {
      if ($('#items_tbody tr').length <= 1) {
        Swal.fire('Warning', 'At least one item row is required!', 'warning');
        return;
      }
      $(`#row_${idx}`).remove();
      calcPurchaseTotals();
    }

    function calcPurchaseTotals() {
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
      $('#total_value_incl_tax').val(totalValIncl.toFixed(2));

      // Calculate Income Tax 236G (e.g. 0.10% of Value Including Sales Tax)
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
      $('#invoice_amount').val(invoiceAmount.toFixed(2));

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
      $('#tax_purchase_form').submit();
    }

    $('#tax_purchase_form').on('submit', function(e) {
      e.preventDefault();

      if ($('#items_tbody tr').length === 0) {
        Swal.fire('Error', 'Please add at least one item row', 'error');
        return false;
      }

      var form = $(this);
      var btn = (submitButtonType === 'save') ? $('#btn_save_purchase, #btn_save_purchase_top') : $('#btn_save_and_print_purchase, #btn_save_and_print_purchase_top');
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
                window.open('print_tax_purchase.php?id=' + resp.order_id, '_blank');
                window.location.href = 'tax_purchase.php';
              } else if (result.isDenied) {
                window.location.href = 'tax_purchase.php';
              } else {
                window.location.href = 'tax_purchase_list.php';
              }
            });
          } else {
            Swal.fire('Error', resp.msg || 'Could not save purchase invoice', 'error');
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
