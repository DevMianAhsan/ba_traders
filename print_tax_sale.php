<?php
include_once 'php_action/db_connect.php';
include_once 'includes/functions.php';

$id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$sale = fetchRecord($dbc, 'tax_sales', 'tax_sale_id', $id);

if (!$sale) {
    die("Tax Sale Invoice not found.");
}

$items = [];
$q = mysqli_query($dbc, "SELECT * FROM tax_sale_items WHERE tax_sale_id = '$id' ORDER BY item_id ASC");
while ($r = mysqli_fetch_assoc($q)) {
    $items[] = $r;
}

$company_id = !empty($sale['company_id']) ? intval($sale['company_id']) : 0;
if ($company_id > 0) {
    $get_company = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company WHERE id = '$company_id'"));
}
if (empty($get_company)) {
    $get_company = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company ORDER BY id DESC LIMIT 1"));
}
$company_name = $get_company['name'] ?? 'BA TRADERS';
$company_address = $get_company['address'] ?? '';
$company_strn = $get_company['s_tax_no'] ?? '';
$company_ntn = $get_company['ntn_no'] ?? '';
$company_phone = $get_company['company_phone'] ?? '';

// Customer / Buyer details loaded dynamically from customers table
$customer_id = !empty($sale['customer_id']) ? intval($sale['customer_id']) : 0;
$get_customer = [];
if ($customer_id > 0) {
    $get_customer = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM customers WHERE customer_id = '$customer_id'"));
}
$customer_name = $get_customer['customer_name'] ?? '';
$customer_address = $get_customer['customer_address'] ?? '';
$customer_province = $get_customer['customer_province'] ?? '';
$customer_phone = $get_customer['customer_phone'] ?? '';
$customer_strn = $get_customer['customer_strn'] ?? '';
$customer_ntn = $get_customer['customer_ntn'] ?? '';
$customer_cnic = $get_customer['customer_cnic'] ?? '';

// Calculate total sales tax, further tax, etc.
$total_sales_tax = !empty($sale['total_sales_tax']) ? floatval($sale['total_sales_tax']) : floatval($sale['total_tax_amount']);
$total_further_tax = !empty($sale['total_further_tax']) ? floatval($sale['total_further_tax']) : 0;
$total_tax_payable = !empty($sale['total_tax_payable']) ? floatval($sale['total_tax_payable']) : $total_sales_tax + $total_further_tax;
$total_val_incl = !empty($sale['total_value_incl_tax']) ? floatval($sale['total_value_incl_tax']) : floatval($sale['grand_total']);
$itax_rate = isset($sale['itax_236g_rate']) ? floatval($sale['itax_236g_rate']) : 0;
$itax_amount = !empty($sale['itax_236g_amount']) ? floatval($sale['itax_236g_amount']) : 0;
$inv_net_amount = $total_val_incl + $itax_amount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sales Tax Invoice - <?=$sale['invoice_no']?></title>
  <link rel="icon" type="image/x-icon" href="favicon.ico?v=2">
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico?v=2">
  <?php if (!empty($get_company['logo'])) : ?>
    <link rel="icon" type="image/gif" sizes="16x16" href="img/logo/<?=$get_company['logo']?>?v=2">
    <link rel="icon" type="image/png" sizes="32x32 16x16" href="img/logo/<?=$get_company['logo']?>?v=2">
  <?php endif; ?>
  <style>
    @page {
      size: A4 portrait;
      margin: 0;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: "Overpass", Tahoma, Arial, sans-serif;
    }
    body {
      background-color: #fff;
      padding: 0;
      color: #000;
    }
    .invoice-sheet {
      width: 100%;
      max-width: 1200px;
      min-height: auto;
      margin: 0 auto;
      background: #fff;
      padding: 12mm 15mm 12mm 15mm;
      box-shadow: none;
      box-sizing: border-box;
      position: relative;
    }
    

    /* Title */
    .invoice-title {
      text-align: center;
      margin-bottom: 14px;
    }
    .invoice-title h1 {
      font-size: 26px;
      font-weight: bold;
      letter-spacing: 1.5px;
      text-decoration: underline;
      display: inline-block;
    }

    /* Top Metadata */
    .meta-row-top {
      display: flex;
      justify-content: space-between;
      font-size: 13.5px;
      margin-bottom: 14px;
      line-height: 1.5;
    }
    .meta-left {
      width: 45%;
    }
    .meta-right {
      width: 45%;
      text-align: right;
    }
    .meta-item {
      margin-bottom: 3px;
    }
    .meta-item .lbl {
      font-weight: bold;
      display: inline-block;
      min-width: 95px;
    }
    .meta-right .lbl {
      min-width: 120px;
      text-align: left;
    }
    .copy-tag {
      font-size: 14px;
      font-weight: bold;
      letter-spacing: 2px;
      margin-top: 4px;
      text-align: right;
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
    .party-field {
      display: flex;
      margin-bottom: 3px;
    }
    .party-lbl {
      font-weight: bold;
      min-width: 110px;
    }
    .party-val {
      flex: 1;
    }
    .party-val.name-val {
      font-weight: bold;
      font-size: 13.5px;
      text-decoration: underline;
      letter-spacing: 0.5px;
    }

    /* Description */
    .desc-line {
      font-size: 12.5px;
      margin-bottom: 10px;
      padding-left: 2px;
    }
    .desc-line strong {
      font-size: 13px;
    }

    /* Main 10-Column Table */
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
      padding: 6px 4px;
      vertical-align: top;
      line-height: 1.35;
    }
    .inv-table td:last-child {
      border-right: none;
    }
    .item-dotted-row td {
      border-bottom: 1.5px dotted #777;
    }
    .empty-spacer-cell {
      height: 380px;
    }

    /* Subtotal & Totals row */
    .totals-divider-row td {
      border-top: 2px solid #000;
      border-bottom: 2px solid #000;
      font-weight: bold;
      padding: 6px 4px;
      font-size: 10.5px;
    }

    .align-right { text-align: right; }
    .align-center { text-align: center; }
    .align-left { text-align: left; }

    .rupees-words {
      margin-top: 14px;
      font-size: 12px;
      font-weight: bold;
      letter-spacing: 0.5px;
      line-height: 1.4;
    }

    .footer-dual {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-top: 40px;
    }
    .fbr-qr-box {
      font-size: 11px;
      font-weight: bold;
    }
    .fbr-qr-box img {
      width: 75px;
      height: 75px;
      border: 1px solid #000;
      margin-top: 4px;
    }

    .signature-area {
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

    @media print {
      html, body {
        background: #fff;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }
      
      .invoice-sheet {
        box-shadow: none !important;
        padding: 12mm 15mm 12mm 15mm !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        min-height: auto !important;
      }

      .empty-spacer-cell {
        height: 320px !important;
      }
    }
  </style>
</head>
<body>

  

  <div class="invoice-sheet">
    <!-- Header Title -->
    <div class="invoice-title">
      <h1>SALES TAX INVOICE</h1>
    </div>

    <!-- Metadata Row -->
    <div class="meta-row-top">
      <div class="meta-left">
        <div class="meta-item">
          <span class="lbl">Invoice No. :</span>
          <strong><?=$sale['invoice_no']?></strong>
        </div>
        <div class="meta-item">
          <span class="lbl">Date :</span>
          <strong><?=date('d/m/Y', strtotime($sale['invoice_date']))?></strong>
        </div>
      </div>
      <div class="meta-right">
        <div class="meta-item">
          <span class="lbl">Time of Supply :</span>
          <span><?=$sale['time_of_supply'] ?: '_____________________'?></span>
        </div>
        <div class="meta-item">
          <span class="lbl">Payment Terms :</span>
          <strong><?=$sale['payment_terms'] ?: 'CREDIT'?></strong>
        </div>
        <div class="copy-tag"><?=$sale['copy_type'] ?: 'ORIGINAL'?></div>
      </div>
    </div>

    <!-- Rounded Parties Box -->
    <div class="parties-card">
      <!-- Left Column: Supplier (BA TRADERS) -->
      <div class="party-col left-col">
        <div class="party-field">
          <div class="party-lbl">Supplier Name :</div>
          <div class="party-val name-val"><?=strtoupper($company_name)?></div>
        </div>
        <div class="party-field" style="min-height: 48px;">
          <div class="party-lbl">Address :</div>
          <div class="party-val"><?=nl2br(htmlspecialchars($company_address))?></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">Contact No. :</div>
          <div class="party-val"><?=$company_phone?></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">S.Tax Reg No. :</div>
          <div class="party-val"><strong><?=$company_strn?></strong></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">NTN No. :</div>
          <div class="party-val"><strong><?=$company_ntn?></strong></div>
        </div>
      </div>

      <!-- Right Column: Buyer (Customer / Messers) -->
      <div class="party-col">
        <div class="party-field">
          <div class="party-lbl">Buyer Name :</div>
          <div class="party-val name-val"><?=strtoupper($customer_name)?></div>
        </div>
        <div class="party-field" style="min-height: 48px;">
          <div class="party-lbl">Address :</div>
          <div class="party-val">
            <?=nl2br(htmlspecialchars($customer_address))?>
            <?php if (!empty($customer_province)): ?>
              <br><small><strong>Province :</strong> <?=strtoupper($customer_province)?></small>
            <?php endif; ?>
          </div>
        </div>
        <div class="party-field">
          <div class="party-lbl">Contact No. :</div>
          <div class="party-val"><?=$customer_phone?></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">S.Tax Reg No. :</div>
          <div class="party-val"><strong><?=$customer_strn?></strong></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">NTN No. :</div>
          <div class="party-val">
            <strong><?=$customer_ntn?></strong>
            <?php if (!empty($customer_cnic)): ?>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              <strong>CNIC No. :</strong> <?=$customer_cnic?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Description Row -->
    <div class="desc-line">
      <strong>Description :</strong> &nbsp; 
      <?php if (!empty($sale['header_description'])): ?>
        <?=$sale['header_description']?>
      <?php else: ?>
        GST Invoice Number : <?=$sale['invoice_no']?> / <?=$company_name?> 
        <?php if (!empty($sale['po_no'])): ?>/ P.O # : <?=$sale['po_no']?> <?php endif; ?>
        <?php if (!empty($items[0]['hs_code'])): ?>/ H.S.Code # <?=$items[0]['hs_code']?><?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Main Table with rounded top corners -->
    <div class="invoice-table-wrapper">
      <table class="inv-table">
        <thead>
          <tr>
            <th style="width: 12%;">Quantity</th>
            <th style="width: 32%;">Description<br>Of<br>Goods</th>
            <th style="width: 10%;">Rate</th>
            <th style="width: 14%;">Value<br>Excl<br>Taxes</th>
            <th style="width: 9%;">Rate<br>Of<br>Sales<br>Tax<br>%</th>
            <th style="width: 11%;">Amount<br>Of<br>Sales Tax</th>
            <th style="width: 12%;">Value<br>Including<br>Sales Tax</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $calc_height = 360 - (count($items) * 45);
          if ($calc_height < 80) $calc_height = 80;

          foreach ($items as $item): 
            $item_further_rate = isset($item['further_tax_rate']) ? floatval($item['further_tax_rate']) : 0.00;
            $item_further_amt = isset($item['further_tax_amount']) ? floatval($item['further_tax_amount']) : 0.00;
            $item_tot_tax = isset($item['total_tax_payable']) && floatval($item['total_tax_payable']) > 0 ? floatval($item['total_tax_payable']) : (floatval($item['tax_amount']) + $item_further_amt);
          ?>
          <tr class="item-dotted-row">
            <td class="align-right">
              <?=number_format($item['quantity'], 1)?> &nbsp; KG
            </td>
            <td class="align-left">
              <strong><?=strtoupper($item['description_of_goods'])?></strong>
              <?php if (!empty($item['hs_code'])): ?>
                <div style="font-size: 9.5px; margin-top: 2px;">
                  H.S. Code : &nbsp; <strong><?=$item['hs_code']?></strong>
                </div>
              <?php endif; ?>
              
            </td>
            <td class="align-right"><?=number_format($item['rate'], 2)?></td>
            <td class="align-right"><?=number_format($item['value_excl_tax'], 2)?></td>
            <td class="align-center"><?=number_format($item['tax_rate'], 2)?></td>
            <td class="align-right"><?=number_format($item['tax_amount'], 2)?></td>
            
            <td class="align-right"><strong><?=number_format($item['value_incl_tax'], 2)?></strong></td>
          </tr>
          <?php endforeach; ?>

          <!-- Spacer row extending down -->
          <tr>
            <td class="empty-spacer-cell" style="height: <?=$calc_height?>px;"></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          </tr>

          <!-- Totals Row -->
          <tr class="totals-divider-row">
            <td class="align-right">
              <?=number_format($sale['total_qty'], 1)?> &nbsp; KG
            </td>
            <td></td>
            <td></td>
            <td class="align-right"><?=number_format($sale['total_value_excl_tax'], 2)?></td>
            <td></td>
            <td class="align-right"><?=number_format($total_sales_tax, 2)?></td>
            <td class="align-right"><?=number_format($total_val_incl, 2)?></td>
          </tr>

          <!-- Total Amount -->
          <tr>
            <td colspan="4" style="border: none;"></td>
            <td colspan="2" class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
              Total Amount:
            </td>
            <td class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
              <?=number_format($inv_net_amount, 2)?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Rupees in Words -->
    <div class="rupees-words">
      <?=$sale['amount_in_words'] ?: number_to_words_pkr($inv_net_amount)?>
    </div>

    <!-- Bottom Footer (FBR / Signature) -->
    <div class="footer-dual">
      <div>
        <?php if (!empty($sale['fbr_invoice_no'])): ?>
          <div class="fbr-qr-box">
            <div>FBR INVOICE NO. : &nbsp; <strong><?=$sale['fbr_invoice_no']?></strong></div>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?=urlencode($sale['fbr_invoice_no'])?>" alt="FBR QR">
          </div>
        <?php endif; ?>
      </div>

      <div class="signature-area">
        <div class="signature-line">
          Authorised Signature
        </div>
      </div>
    </div>
  </div>

<script type="text/javascript">
  window.addEventListener('load', function() {
    setTimeout(function() {
      window.print();
    }, 300);
  });
</script>
</body>
</html>
