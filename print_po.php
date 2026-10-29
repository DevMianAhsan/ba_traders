<!DOCTYPE html>
<html lang="en">

    <?php
include_once "includes/head.php";

$po_order =   $order=fetchRecord($dbc,"purchase_order","po_id",$_REQUEST['id']);

    ?>


<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Purchase Order</title>
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

    .table-sec th {
      font-weight: bold;
      padding: 8px 10px;
      border: 1px solid black;
      margin: 0;
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
      display: flex;
      justify-content: flex-end;
      margin-top: 60px;
      padding: 0 1rem;
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
      width: 25cm;
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
      <?php
       if(empty($po_order['po_consumer'])){
      ?>
      <img src="img/logo/<?=$get_company['logo']?>" style="width: 200px">
      <h5 style="margin-top: 5px; text-align: center;"><?=$get_company['address']?></h5>
      <h5 style="margin-top: 5px; text-align: center;">Phone: <?=$get_company['company_phone']?></h5>
      <?php
    } 
      ?>
    </div>
    <div class="order-det">
      <div class="order-data">
        <h4>Customer: <span></span></h4>
        <h5>
        <?php
     $customer = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM customers WHERE customer_id = '$po_order[po_customer]' ")) ;
     echo ucwords(strtolower($customer['customer_name'] ?? ''));
        ?>  
        </h5>
        <?php if (!empty($customer['customer_address'])): ?>
        <h5 style="margin-top: 8px;">
          <?=nl2br(htmlspecialchars(ucwords(strtolower($customer['customer_address']))))?>
        </h5>
        <?php endif; ?>
        
      <?php
      if(!empty($po_order['po_consumer'])){
      ?>
        <h4>Sold To: <span></span></h4>
        <h5>
        <?php
  echo    $newString = str_replace(',', '<br/>', strtoupper($po_order['po_consumer']));

        ?>  
        </h5>
        <?php
}
        ?>
      </div>
      <div class="order-n-d">
        <div class="com-det" style="text-align: right; margin-bottom: 10px;">
          <h1>Sale Order</h1>
          <p style="text-align: right; border: none; font-weight: normal; margin-top: 0;">Office Copy</p>
        </div>
        <h4>Sales Order No</h4>
        <p style="padding-right: 10px"><?=$po_order['po_id']?></p>
        <h4>Sales Order Date</h4>
        <p style="padding-right: 10px"><?=$po_order['po_date']?></p>
      </div>

    </div>
    <div class="table-sec1">
      <table style="width: 100%; table-layout: fixed;">
        <thead>
          <tr>
            <th style="width: 16.66%; font-size: 13px; padding: 6px 4px;">Payment Days :</th>
            <th style="width: 16.66%; font-size: 13px; padding: 6px 4px;"><?=$po_order['po_paymentdays']?></th>
            <th style="width: 16.66%; font-size: 13px; padding: 6px 4px;">Target Date :</th>
            <th style="width: 16.66%; font-size: 13px; padding: 6px 4px;"><?=$po_order['po_targetdate']?></th>
            <th style="width: 16.66%; font-size: 13px; padding: 6px 4px;">Party PO# :</th>
            <th style="width: 16.66%; font-size: 13px; padding: 6px 4px;"><?=$po_order['po_partypo']?></th>
          </tr>
        </thead>
      </table>
    </div>
    <div class="table-sec">
      <table style="width: 100%;">
        <thead >
          <tr>
            <th>Sr #</th>
            <th>Item Description</th>
            <th>Quantity</th>
            <th>UOM</th>
            <th>Rate</th>
            <th>Amount</th>
          </tr>
        </thead>

        <tbody >
          <?php

          $q1 = mysqli_query($dbc,"SELECT * FROM purchase_order_item 
                INNER JOIN product ON product.product_id = purchase_order_item.product_id
                WHERE po_id = '$po_order[po_id]'
                ");
          $x = 1;
          $totalQty = 0;
          $grandtotal = 0;
            while($r = mysqli_fetch_assoc($q1)): 
 
          ?>
          <tr>
            <th><?=$x?></th>
            <th> <?=ucwords(strtolower($r['product_name'] ?? ''))?></th>
            <th><?=$r['quantity']?></th>
            <th> <?=ucwords(strtolower($r['product_unit'] ?? ''))?></th>
            <th><?=(floatval($r['rate']) == intval($r['rate'])) ? number_format($r['rate']) : number_format($r['rate'], 2)?></th>
            <th><?=(floatval($r['quantity']*$r['rate']) == intval($r['quantity']*$r['rate'])) ? number_format($r['quantity']*$r['rate']) : number_format($r['quantity']*$r['rate'], 2)?> RS</th>
          </tr>
          <?php
          $totalQty += $r['quantity'];
          $grandtotal += $r['quantity']*$r['rate'];
          $x++;
        endwhile;
          ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="2" style="font-size: 20px">Total  </th>
            <th><?=$totalQty?></th>
            <th colspan="2"></th>
            <th><?=(floatval($grandtotal) == intval($grandtotal)) ? number_format($grandtotal) : number_format($grandtotal, 2)?> RS</th>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="total-text">
      <div class="" style="text-align: right; padding-right: 10px;">
        <br/>
        <h4>Sub. Total :</h4>
        <h4>Sales Tax:</h4>
        <h4>Total Value :</h4>
      </div>
      <div class="" style="text-align: right;">
        <h4><?=(floatval($grandtotal) == intval($grandtotal)) ? number_format($grandtotal) : number_format($grandtotal, 2)?> RS</h4>
        <h4><?=$po_order['discount']?>%</h4>
        <h4><?=(floatval($po_order['grand_total']) == intval($po_order['grand_total'])) ? number_format($po_order['grand_total']) : number_format($po_order['grand_total'], 2)?> RS</h4>
      </div>

    </div>
    <div class="sign-sec">
      <h4>Approved By</h4>
    </div>
    <!-- <div class="address">
      <h5><?=$get_company['address']?></h5>
      <h5>Phone : <span><?=$get_company['company_phone']?>,<?=$get_company['personal_phone']?></span> | Email <span><?=$get_company['email']?></span></h5>
    </div> -->
  </div>
</body>

</html>

<script type="text/javascript">
  window.print();
</script>