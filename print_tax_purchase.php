<?php
include_once 'php_action/db_connect.php';
include_once 'includes/functions.php';

$id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$purchase = fetchRecord($dbc, 'tax_purchases', 'tax_purchase_id', $id);

if (!$purchase) {
    die("Tax Purchase Invoice not found.");
}

$items = [];
$q = mysqli_query($dbc, "SELECT * FROM tax_purchase_items WHERE tax_purchase_id = '$id' ORDER BY item_id ASC");
while ($r = mysqli_fetch_assoc($q)) {
    $items[] = $r;
}

// Supplier details dynamically fetched from customers table
$supplier_id = !empty($purchase['supplier_id']) ? intval($purchase['supplier_id']) : 0;
$get_supplier = [];
if ($supplier_id > 0) {
    $get_supplier = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM customers WHERE customer_id = '$supplier_id'"));
}
$supplier_name = $get_supplier['customer_name'] ?? '';
$supplier_address = $get_supplier['customer_address'] ?? '';
$supplier_contact = $get_supplier['customer_phone'] ?? '';
$supplier_strn = $get_supplier['customer_strn'] ?? '';
$supplier_ntn = $get_supplier['customer_ntn'] ?? '';

// Buyer (Company) details dynamically fetched from company table
$company_id = !empty($purchase['company_id']) ? intval($purchase['company_id']) : 0;
if ($company_id > 0) {
    $get_company = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company WHERE id = '$company_id'"));
}
if (empty($get_company)) {
    $get_company = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM company ORDER BY id DESC LIMIT 1"));
}
$buyer_name = $get_company['name'] ?? 'BA TRADERS';
$buyer_address = $get_company['address'] ?? '';
$buyer_contact = $get_company['company_phone'] ?? '';
$buyer_strn = $get_company['s_tax_no'] ?? '';
$buyer_ntn = $get_company['ntn_no'] ?? '';
$buyer_cnic = $get_company['cnic_no'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sales Tax Invoice - <?=$purchase['invoice_no']?></title>
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

    /* Bottom section */
    .bottom-tax-box {
      width: 100%;
      border-collapse: collapse;
      font-size: 11px;
    }
    .bottom-tax-box td {
      border: 1.5px solid #000;
      padding: 4px 6px;
      font-weight: bold;
    }

    .rupees-words {
      margin-top: 14px;
      font-size: 12px;
      font-weight: bold;
      letter-spacing: 0.5px;
      line-height: 1.4;
    }

    .signature-area {
      margin-top: 65px;
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
      <h1>PURCHASE TAX INVOICE</h1>
    </div>

    <!-- Metadata Row -->
    <div class="meta-row-top">
      <div class="meta-left">
        <div class="meta-item">
          <span class="lbl">Invoice No. :</span>
          <strong><?=$purchase['invoice_no']?></strong>
        </div>
        <div class="meta-item">
          <span class="lbl">Date :</span>
          <strong><?=date('d/m/Y', strtotime($purchase['invoice_date']))?></strong>
        </div>
      </div>
      <div class="meta-right">
        <div class="meta-item">
          <span class="lbl">Time of Supply :</span>
          <span><?=$purchase['time_of_supply'] ?: '_____________________'?></span>
        </div>
        <div class="meta-item">
          <span class="lbl">Payment Terms :</span>
          <strong><?=$purchase['payment_terms'] ?: 'CREDIT'?></strong>
        </div>
        <div class="copy-tag"><?=$purchase['copy_type'] ?: 'ORIGINAL'?></div>
      </div>
    </div>

    <!-- Rounded Parties Box -->
    <div class="parties-card">
      <!-- Left Column: Supplier -->
      <div class="party-col left-col">
        <div class="party-field">
          <div class="party-lbl">Supplier Name :</div>
          <div class="party-val name-val"><?=strtoupper($supplier_name)?></div>
        </div>
        <div class="party-field" style="min-height: 48px;">
          <div class="party-lbl">Address :</div>
          <div class="party-val"><?=nl2br(htmlspecialchars($supplier_address))?></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">Contact No. :</div>
          <div class="party-val"><?=$supplier_contact?></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">S.Tax Reg No. :</div>
          <div class="party-val"><strong><?=$supplier_strn?></strong></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">NTN No. :</div>
          <div class="party-val"><strong><?=$supplier_ntn?></strong></div>
        </div>
      </div>

      <!-- Right Column: Buyer (Company) -->
      <div class="party-col">
        <div class="party-field">
          <div class="party-lbl">Buyer Name :</div>
          <div class="party-val name-val"><?=strtoupper($buyer_name)?></div>
        </div>
        <div class="party-field" style="min-height: 48px;">
          <div class="party-lbl">Address :</div>
          <div class="party-val"><?=nl2br(htmlspecialchars($buyer_address))?></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">Contact No. :</div>
          <div class="party-val"><?=$buyer_contact?></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">S.Tax Reg No. :</div>
          <div class="party-val"><strong><?=$buyer_strn?></strong></div>
        </div>
        <div class="party-field">
          <div class="party-lbl">NTN No. :</div>
          <div class="party-val">
            <strong><?=$buyer_ntn?></strong>
            <?php if (!empty($buyer_cnic)): ?>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              <strong>CNIC No. :</strong> <?=$buyer_cnic?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Description Row -->
    <div class="desc-line">
      <strong>Description :</strong> &nbsp; <?=$purchase['header_description']?>
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
              <?=number_format($purchase['total_qty'], 1)?> &nbsp; KG
            </td>
            <td></td>
            <td></td>
            <td class="align-right"><?=number_format($purchase['total_value_excl_tax'], 2)?></td>
            <td></td>
            <td class="align-right"><?=number_format($purchase['total_sales_tax'], 2)?></td>
            <td class="align-right"><?=number_format($purchase['total_value_incl_tax'], 2)?></td>
          </tr>

          <!-- Total Amount -->
          <tr>
            <td colspan="4" style="border: none;"></td>
            <td colspan="2" class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
              Total Amount:
            </td>
            <td class="align-right" style="border: 1.5px solid #000; font-weight: bold; padding: 4px 6px;">
              <?=number_format($purchase['invoice_amount'], 2)?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Rupees in Words -->
    <div class="rupees-words">
      <?=$purchase['amount_in_words'] ?: number_to_words_pkr($purchase['invoice_amount'])?>
    </div>

    <!-- Signature -->
    <div class="signature-area">
      <div class="signature-line">
        Authorised Signature
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
