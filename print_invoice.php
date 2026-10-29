<?php include_once 'includes/head.php'; 
$invoice = fetchRecord($dbc, "invoices", "invoice_id", $_REQUEST['invoice']);
$saleorder = fetchRecord($dbc, "purchase_order", "po_partypo", $invoice['party_po'] ?? '');

$tax_percent = 0;
if (!empty($saleorder['discount']) && floatval($saleorder['discount']) > 0) {
    $tax_percent = floatval($saleorder['discount']);
} elseif (!empty($invoice['discount']) && floatval($invoice['discount']) > 0) {
    $tax_percent = floatval($invoice['discount']);
}

$freight = !empty($saleorder['freight']) ? floatval($saleorder['freight']) : (!empty($invoice['freight']) ? floatval($invoice['freight']) : 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice - <?=$invoice['invoice_id'] ?? ''?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page-container {
      padding: 30px;
    }

    @font-face {
      font-family: tahoma;
      src: url(./fonts/Tahoma\ Regular\ font.ttf);
    }

    @font-face {
      font-family: tahomabold;
      src: url(./fonts/TAHOMAB0.TTF);
    }

    * {
      margin: 0;
      padding: 0;
      font-family: Tahoma, Arial, sans-serif;
    }

    .top-sec {
      text-align: center;
      margin-top: 0;
    }

    .top-sec h5 {
      color: #001a4e;
      font-weight: bold;
      margin-top: 5px;
      text-align: center;
      font-size: 15px;
    }

    .order-det {
      display: flex;
      justify-content: space-between;
      margin-top: 1.5rem;
    }

    .order-data {
      line-height: 1.5rem;
      font-weight: bold;
    }

    .order-n-d p {
      border: 1px solid #111111;
      text-align: right;
      margin-top: 8px;
      font-weight: bold;
      padding-right: 10px;
    }

    .order-n-d .com-det p {
      border: none;
      font-weight: normal;
      margin-top: 0;
      text-align: right;
    }

    .order-n-d h4 {
      font-weight: bold;
      margin-top: 2px;
    }

    .remarks-sec {
      display: flex;
      margin-top: 1rem;
      margin-bottom: 0.5rem;
      justify-content: space-between;
    }

    table {
      border-collapse: collapse;
      text-align: center;
      border: 1px solid black;
      width: 100%;
      column-gap: 80px;
    }

    th, td {
      border: 2px solid black;
      padding: 5px 0px;
    }

    thead th {
      background-color: #AFCDEA;
      padding: 10px 0px !important;
      font-weight: bold;
    }

    tbody {
      border-left: 2px solid black;
    }

    tfoot th {
      padding-top: 5px;
    }

    .total-text {
      margin-top: 2rem;
      display: flex;
      justify-content: flex-end;
      align-items: flex-end;
    }

    .total-text h4 {
      font-weight: bold;
      color: #001a4e;
      margin-bottom: 0.5rem;
      line-height: 1.2;
    }

    .sign-sec {
      display: flex;
      justify-content: flex-end;
      margin-top: 60px;
    }

    .sign-sec h4 {
      border: 1px solid black;
      font-weight: bold;
      width: 180px;
      text-align: center;
      padding: 4px 0;
    }
  </style>
</head>

<body>
  <div class="page-container">
    <div class="top-sec">
      <img src="img/logo/<?=$get_company['logo']?>" style="width: 200px">
      <h5><?=$get_company['address']?></h5>
      <h5>Phone: <?=$get_company['company_phone']?></h5>
    </div>
    <div class="order-det">
      <div class="order-data" style="width: 70%;">
        <div>
          <h4>Customer:</h4>
          <h5> 
          	<?php
            $cust_id = !empty($invoice['customer_id']) ? $invoice['customer_id'] : ($saleorder['po_customer'] ?? 0);
            $customer = !empty($cust_id) ? mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM customers WHERE customer_id = '$cust_id'")) : null;
            if (!empty($saleorder['po_consumer'])) {
          	  echo nl2br(htmlspecialchars(ucwords(strtolower(str_replace(',', "\n", $saleorder['po_consumer'])))));
            } else {
              if ($customer) {
                echo ucwords(strtolower($customer['customer_name'] ?? ''));
              }
            }
          	?>
          </h5>
          <?php if (!empty($customer['customer_address'])): ?>
          <h5 style="margin-top: 8px;">
            <?=nl2br(htmlspecialchars(ucwords(strtolower($customer['customer_address']))))?>
          </h5>
          <?php endif; ?>
        </div>
      </div>
      <div class="order-n-d">
        <div class="com-det" style="text-align: right; margin-bottom: 10px;">
          <h1>INVOICE</h1>
          <p style="text-align: right;">Original</p>
        </div>
        <h4>Challan Number</h4>
        <p><?=$invoice['invoice_id']?></p>
        <h4>Challan Date</h4>
        <p><?=date('D, d-m-Y',strtotime($invoice['invoice_date']))?></p>
      </div>
    </div>
    <?php if (!empty($saleorder['po_remarks'])): ?>
    <div class="remarks-sec">
      <h4>REMARKS: <span></span></h4>
      <h5><?=$saleorder['po_remarks']?></h5>
    </div>
    <?php endif; ?>
    <div class="table-sec">
      <table>
        <thead>
          <tr>
            <th style="width: 6%;">Sr #</th>
            <th>PO #</th>
            <th>Challan #</th>
            <th style="width: 35%;">Item Description</th>
            <th>Quantity</th>
            <th>UOM</th>
            <th>Rate</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
        	<?php
        	$res = json_decode($invoice['invoice_details']);
          $qtytotal = 0;
          $grandtotal = 0;
          $sr = 1;
        	$data = $res->data ?? [];
         
        	for ($i=0; $i < count($data); $i++) { 
        		$dcDetails = mysqli_query($dbc,"SELECT * FROM orders INNER JOIN order_item ON orders.order_id = order_item.order_id
              INNER JOIN product ON product.product_id = order_item.product_id
              INNER JOIN brands ON brands.brand_id = product.brand_id
              INNER JOIN categories ON categories.categories_id = product.category_id
             WHERE orders.order_id = '".$data[$i]->dc_no."'");
        		while($r = mysqli_fetch_assoc($dcDetails)):
              $row_amount = $r['quantity'] * $r['rate'];
        	?>
        	<tr>
            <td><?=$sr++?></td>
        		<td><?=$data[$i]->party_po?></td>
            <td><?=$data[$i]->dc_no?></td>
            <td><?=ucwords(strtolower(trim($r['product_name'] ?? '')))?></td>
            <td><?=number_format($r['quantity'])?></td>
            <td><?=ucwords(strtolower($r['product_unit'] ?? ''))?></td>
            <td><?=(floatval($r['rate']) == intval($r['rate'])) ? number_format($r['rate']) : number_format($r['rate'], 2)?></td>
            <td><?=(floatval($row_amount) == intval($row_amount)) ? number_format($row_amount) : number_format($row_amount, 2)?> RS</td>
        	</tr>
        	<?php
          $qtytotal += $r['quantity'];
          $grandtotal += $row_amount;
            endwhile;
          }
        	?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="4" style="text-align: right; padding-right: 5px;">TOTAL:</th>
            <th><?=number_format($qtytotal)?></th>
            <th></th>
            <th></th>
            <th><?=(floatval($grandtotal) == intval($grandtotal)) ? number_format($grandtotal) : number_format($grandtotal, 2)?> RS</th>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php
    if (floatval($tax_percent) > 0):
      if (!empty($saleorder['grand_total']) && floatval($saleorder['grand_total']) > 0) {
          $total_value = $saleorder['grand_total'];
      } else {
          $tax_amount = ($grandtotal * $tax_percent) / 100;
          $total_value = $grandtotal + $tax_amount + $freight;
      }
    ?>
    <div class="total-text">
      <div class="" style="text-align: right; padding-right: 10px;">
        <h4>Sub. Total :</h4>
        <h4>Sales Tax:</h4>
        <h4>Total Value :</h4>
      </div>
      <div class="" style="text-align: right;">
        <h4><?=(floatval($grandtotal) == intval($grandtotal)) ? number_format($grandtotal) : number_format($grandtotal, 2)?> RS</h4>
        <h4><?=$tax_percent?>%</h4>
        <h4><?=(floatval($total_value) == intval($total_value)) ? number_format($total_value) : number_format($total_value, 2)?> RS</h4>
      </div>
    </div>
    <?php endif; ?>
    <div class="sign-sec">
      <h4>Approved By</h4>
    </div>

  </div>
</body>

</html>
