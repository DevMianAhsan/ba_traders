<?php include_once 'includes/head.php';
    

 ?>
<?php
if($_REQUEST['type'] == 'dc'){
     $order=fetchRecord($dbc,"orders","order_id",$_REQUEST['id']);
     $customer =  fetchRecord($dbc,"customers","customer_id",$order['customer_account']);

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Challan</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page-container {
      padding: 30px;
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

    table {
      border-collapse: collapse;
      text-align: center;
      border: 1px solid black;
      margin: 10px 1rem 0 1rem;
    }

    .table-sec {
      margin-right: 1.4rem;
    }

    .table-sec th {
      font-weight: bold;
      padding: 8px 10px;
      border: 1px solid black;
      margin: 0;
      white-space: nowrap;
    }

    .table-sec tfoot {
      text-align: right;
      border: 1px solid black;
    }

    .table-sec tfoot td {
      text-align: right;
      border-right: 1px solid black;
      font-weight: bold;
      padding: 0 10px;
    }

    .table-sec thead {
      background-color: #bfdfff;
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

    .table-sec1 thead th {
      border: 1px solid black;
      padding: 6px 10px;
      font-weight: bold;
    }

    .total-text {
      margin-top: 2rem;
      display: flex;
      justify-content: flex-end;
      align-items: flex-end;

    }



    .total-heading {
      width: 130px;
      display: flex;
      flex-direction: column;
      align-items: flex-end;

    }

    .total-heading h4 {
      margin-top: 6px;
      font-weight: bold;

    }

    .sign-sec {
      justify-content: flex-end;
      padding: 0 20px;
      display: flex;
      margin-top: 60px;
    }

    .sign-sec h4 {
      border: 1px solid black;
      font-weight: bold;
      width: 180px;
      text-align: center;
      padding: 4px 0;
    }

    .address {
      position: absolute;
      bottom: 2rem;
      width: 100%;
      padding: 0 5px;
      text-align: center;
      margin-top: 5rem;
      background-color: rgba(164, 164, 164, 0.721);
    }
  </style>
</head>

<body>
  <div class="page-container">
    <div class="top-sec">
      <img src="img/logo/<?=$get_company['logo']?>" style="width: 200px">
      <h5 style="margin-top: 5px; text-align: center;"><?=$get_company['address']?></h5>
    </div>
    <div class="order-det">
      <div class="order-data">
        <h4>Customer: <span></span></h4>
        <?php
          $cust_name = !empty($customer['customer_name']) ? $customer['customer_name'] : ($order['client_name'] ?? '');
          $cust_address = !empty($customer['customer_address']) ? $customer['customer_address'] : (!empty($order['address']) ? $order['address'] : '');
        ?>
        <h5><?=ucwords(strtolower($cust_name ?? ''))?></h5>
        <?php if (!empty($cust_address)): ?>
        <h5 style="margin-top: 8px;">
          <?=nl2br(htmlspecialchars(ucwords(strtolower($cust_address))))?>
        </h5>
        <?php endif; ?>
      </div>
      <div class="order-n-d">
        <div class="com-det" style="text-align: right; margin-bottom: 10px;">
          <h1>Delivery Challan</h1>
          <p style="text-align: right; border: none; font-weight: normal; margin-top: 0;">Original</p>
        </div>
        <h4>Challan Number</h4>
        <p style="padding-right: 10px"><?=$order['order_id']?></p>
        <h4>Challan Date</h4>
        <p style="padding-right: 10px"><?=$order['order_date']?></p>
      </div>

    </div>
    <div class="table-sec1">
      <table>
        <thead>
          <tr>
            <th style="white-space: nowrap;">Remarks :</th>
            <th style="width: 100% ;"><?=$order['order_narration']?></th>
          </tr>
          <tr>
            <th style="white-space: nowrap;">Vehicle No :</th>
            <th style="width: 100% ;"><?=$order['vehicle_no']?></th>
          </tr>
        </thead>
      </table>
    </div>
    <div class="table-sec">
      <table style="width: 100%;text-align: center;">
        <thead>
          <tr>
            <th>Party PO</th>
            
            <th>Item Description</th>
          
            <th>Quantity</th>
            <th>Uom</th>
            <th>Package</th>
            <th>P.PKG</th>
          </tr>
        </thead>

        <tbody>
          <?php
          $totalQTY = 0;
          $totalPackage = 0;
          $order_item=mysqli_query($dbc,"SELECT order_item.*,product.* FROM order_item INNER JOIN product ON order_item.product_id=product.product_id WHERE order_item.order_id='".$_REQUEST['id']."'");
          while($d = mysqli_fetch_assoc($order_item)):
          ?>
          <tr>
           <th><?=$order['party_po']?></th>
            
            <th><?=ucwords(strtolower($d['product_name'] ?? ''))?></th>
           
            <th><?=$d['quantity']?></th>
            <th><?=ucwords(strtolower($d['product_unit'] ?? ''))?></th>
            <th><?=$d['pckg']?></th>
            <th><?=$d['p_pckg']?></th>
          </tr>
          <?php
          $totalQTY += floatval($d['quantity']); 
          $totalPackage += floatval($d['pckg']);
      endwhile;
          ?>
        </tbody>
        <tfoot>

          <tr>
           
            <td colspan="2">TOTAL:</td>
            <td style="text-align: center;"> <?=number_format($totalQTY)?></td>
            <td></td>
            <td style="text-align: center;"> <?=($totalPackage > 0) ? ((floatval($totalPackage) == intval($totalPackage)) ? number_format($totalPackage) : number_format($totalPackage, 2)) : ''?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="sign-sec">
      <h4>Approved By</h4>
    </div>

  </div>
</body>

</html>

<?php
}else{
    if ($_REQUEST['type'] == "purchase") {
       $order = fetchRecord($dbc, "purchase", "purchase_id", $_REQUEST['id']);
       $customer = !empty($order['customer_account']) ? fetchRecord($dbc, "customers", "customer_id", $order['customer_account']) : null;
       $order_item = mysqli_query($dbc, "SELECT purchase_item.*, product.* FROM purchase_item INNER JOIN product ON purchase_item.product_id=product.product_id WHERE purchase_item.purchase_id='".$_REQUEST['id']."'");
       $doc_title = "Purchase Order";
       $doc_heading = "PURCHASE ORDER";
       $doc_num_label = "PO Number";
       $doc_date_label = "PO Date";
       $doc_id = $order['purchase_id'] ?? $_REQUEST['id'];
       $doc_date = !empty($order['purchase_date']) && $order['purchase_date'] != '0000-00-00' ? $order['purchase_date'] : $order['timestamp'];
       $saleorder = null;
    } else {
       $order = fetchRecord($dbc, "orders", "order_id", $_REQUEST['id']);
       $customer = !empty($order['customer_account']) ? fetchRecord($dbc, "customers", "customer_id", $order['customer_account']) : null;
       $order_item = mysqli_query($dbc, "SELECT order_item.*, product.* FROM order_item INNER JOIN product ON order_item.product_id=product.product_id WHERE order_item.order_id='".$_REQUEST['id']."'");
       $doc_title = "Sale Order";
       $doc_heading = "SALE ORDER";
       $doc_num_label = "Challan Number";
       $doc_date_label = "Challan Date";
       $doc_id = $order['order_id'] ?? $_REQUEST['id'];
       $doc_date = !empty($order['order_date']) && $order['order_date'] != '0000-00-00' ? $order['order_date'] : $order['timestamp'];
       $saleorder = !empty($order['party_po']) ? fetchRecord($dbc, "purchase_order", "po_partypo", $order['party_po']) : null;
    }

    $tax_percent = 0;
    if (!empty($order['discount']) && floatval($order['discount']) > 0) {
        $tax_percent = floatval($order['discount']);
    } elseif (!empty($saleorder['discount']) && floatval($saleorder['discount']) > 0) {
        $tax_percent = floatval($saleorder['discount']);
    }

    $freight = !empty($order['freight']) ? floatval($order['freight']) : (!empty($saleorder['freight']) ? floatval($saleorder['freight']) : 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=$doc_title?> - <?=$doc_id?></title>
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
            if (!empty($saleorder['po_consumer'])) {
          	  echo nl2br(htmlspecialchars(ucwords(strtolower(str_replace(',', "\n", $saleorder['po_consumer'])))));
            } else {
              $cust_name = !empty($customer['customer_name']) ? $customer['customer_name'] : ($order['client_name'] ?? '');
              echo ucwords(strtolower($cust_name ?? ''));
            }
          	?>
          </h5>
          <?php 
          $display_address = !empty($order['address']) ? $order['address'] : (!empty($customer['customer_fbr_address']) ? $customer['customer_fbr_address'] : ($customer['customer_address'] ?? ''));
          if (!empty($display_address)): 
          ?>
          <h5 style="margin-top: 8px;">
            <?=nl2br(htmlspecialchars(ucwords(strtolower($display_address))))?>
          </h5>
          <?php endif; ?>
        </div>
      </div>
      <div class="order-n-d">
        <div class="com-det" style="text-align: right; margin-bottom: 10px;">
          <h1><?=$doc_heading?></h1>
          <p style="text-align: right;">Original</p>
        </div>
        <h4><?=$doc_num_label?></h4>
        <p><?=$doc_id?></p>
        <h4><?=$doc_date_label?></h4>
        <p><?=date('D, d-m-Y', strtotime($doc_date))?></p>
      </div>
    </div>
    <?php 
    $remarks = !empty($order['order_narration']) ? $order['order_narration'] : (!empty($saleorder['po_remarks']) ? $saleorder['po_remarks'] : ($order['note'] ?? ''));
    if (!empty($remarks)): 
    ?>
    <div class="remarks-sec">
      <h4>REMARKS: <span></span></h4>
      <h5><?=$remarks?></h5>
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
          $qtytotal = 0;
          $grandtotal = 0;
          $sr = 1;

        	while($r = mysqli_fetch_assoc($order_item)):
            $row_amount = $r['quantity'] * $r['rate'];
        	?>
        	<tr>
            <td><?=$sr++?></td>
        		<td><?=$order['party_po'] ?? ($order['purchase_id'] ?? '')?></td>
            <td><?=$doc_id?></td>
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
    if (floatval($tax_percent) > 0 || floatval($freight) > 0):
      if (!empty($order['grand_total']) && floatval($order['grand_total']) > 0) {
          $total_value = floatval($order['grand_total']);
      } else {
          $tax_amount = ($grandtotal * $tax_percent) / 100;
          $total_value = $grandtotal + $tax_amount + $freight;
      }
    ?>
    <div class="total-text">
      <div class="" style="text-align: right; padding-right: 10px;">
        <h4>Sub. Total :</h4>
        <?php if (floatval($tax_percent) > 0): ?>
        <h4>Sales Tax:</h4>
        <?php endif; ?>
        <?php if (floatval($freight) > 0): ?>
        <h4>Freight:</h4>
        <?php endif; ?>
        <h4>Total Value :</h4>
      </div>
     
      <div class="" style="text-align: right;">
        <h4>
            <?= (floatval($grandtotal) == intval($grandtotal))
                ? number_format($grandtotal)
                : number_format($grandtotal, 2) ?> RS
        </h4>

        <?php if (floatval($tax_percent) > 0): ?>
        <h4><?= (floatval($tax_percent) == intval($tax_percent)) ? intval($tax_percent) : $tax_percent ?>%</h4>
        <?php endif; ?>

        <?php if (floatval($freight) > 0): ?>
        <h4>
            <?= (floatval($freight) == intval($freight))
                ? number_format($freight)
                : number_format($freight, 2) ?> RS
        </h4>
        <?php endif; ?>

        <h4>
            <?= (floatval($total_value) == intval($total_value))
                ? number_format($total_value)
                : number_format($total_value, 2) ?> RS
        </h4>
      </div>
    </div>
    <?php endif; ?>
    <div class="sign-sec">
      <h4>Approved By</h4>
    </div>

  </div>
</body>

</html>
<?php
}
?>
<script type="text/javascript">
    window.print();
 //   window.close();
</script>
