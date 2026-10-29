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
      <h5 style="margin-top: 5px; text-align: center;">Phone: <?=$get_company['company_phone']?></h5>
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
          $totalQTY +=$d['quantity']; 
      endwhile;
          ?>
        </tbody>
        <tfoot>

          <tr>
           
            <td colspan="2">TOTAL:</td>
            <td> <?=number_format($totalQTY)?></td>
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
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sale Order</title>
  <style>
    table,td,td,body {
        font-family: Tahoma, Arial, sans-serif;
    }

    h1, p,span {
        font-family: Tahoma, Arial, sans-serif;
    }

    * {
      margin: 0;
      padding: 0;
      font-family: Tahoma, Arial, sans-serif;
    }

    .container{
        height: 100% !important;
        background-color: #fff;
        border-bottom: 1px dashed #000 !important;
    }
    .border{

        border: 1px solid #000 !important;
    }
    .thead_row th{
        color: #000 !important;
        font-size: 18px !important;
        border: 1px solid #000;
        text-align: center;
    }
     tbody td{
    
        border: 1px solid #000;
        text-align: center;
    }
    table{
        width: 100%;
        

    }
    .table_row{

        border: 1px solid #000;
        padding: 0px;
    }
    .tbody_row tr td{
        font-size: 17px;
        font-weight: bolder;
        text-align: center;
        color: #000;

    }
    .tfoot_row tr td{
        font-size: 17px;
        font-weight: bolder;
        color: #000;
        text-align: center;
    }

    .top-sec {
      text-align: center;
      margin-top: 0;
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
  </style>
</head>

<body>
<?php   for ($i=0; $i < 1; $i++) : 
    $totalQTY= 0;
    if($i>0){
       $margin = "margin-top:-270px !important";
       $copy = "Company Copy";
    }else{
       $margin = "";
       $copy = "Customer Copy";
    } 

    if ($_REQUEST['type']=="purchase") {
       $order=fetchRecord($dbc,"purchase","purchase_id",$_REQUEST['id']);
       $customer = !empty($order['customer_account']) ? fetchRecord($dbc,"customers","customer_id",$order['customer_account']) : null;
       $table_row="390px";
       $getDate=$order['purchase_date'];
       if ($order['payment_type']=="credit_purchase") {
          $order_type="credit purchase";
       }else{
          $order_type="cash purchase";
       }
       $order_item=mysqli_query($dbc,"SELECT purchase_item.*,product.* FROM purchase_item INNER JOIN product ON purchase_item.product_id=product.product_id WHERE purchase_item.purchase_id='".$_REQUEST['id']."'");
    }else{
       $order=fetchRecord($dbc,"orders","order_id",$_REQUEST['id']);
       $customer = !empty($order['customer_account']) ? fetchRecord($dbc,"customers","customer_id",$order['customer_account']) : null;
       $getDate=$order['order_date'];

       $order_item=mysqli_query($dbc,"SELECT order_item.*,product.* FROM order_item INNER JOIN product ON order_item.product_id=product.product_id WHERE order_item.order_id='".$_REQUEST['id']."'");
       if ($order['payment_type']=="credit_sale") {
            $table_row="390px";
            if ($order['payment_type']=="none") {
                $order_type="credit sale";
            }else{
                 $order_type=$order['credit_sale_type']." (Credit)";   
            }
       }else{
            $order_type="cash sale";
            $table_row="450px";
       }
    }
?>
       
<div class="container">
    <div class="top-sec">
      <img src="img/logo/<?=$get_company['logo']?>" style="width: 200px">
      <h5 style="margin-top: 5px; text-align: center;"><?=$get_company['address']?></h5>
      <h5 style="margin-top: 5px; text-align: center;">Phone: <?=$get_company['company_phone']?></h5>
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
          <h1><?=($_REQUEST['type'] == 'purchase') ? 'Purchase Order' : 'Sale Order'?></h1>
          <p style="text-align: right; border: none; font-weight: normal; margin-top: 0;">Office Copy</p>
        </div>
        <h4><?=($_REQUEST['type'] == 'purchase') ? 'Purchase Order No' : 'Sales Order No'?></h4>
        <p style="padding-right: 10px"><?=$_REQUEST['id']?></p>
        <h4><?=($_REQUEST['type'] == 'purchase') ? 'Purchase Order Date' : 'Sales Order Date'?></h4>
        <p style="padding-right: 10px"><?=empty($order['order_date']) ? (empty($order['purchase_date']) ? date('Y-m-d', strtotime($order['timestamp'])) : $order['purchase_date']) : $order['order_date']?></p>
      </div>

    </div>

    <div class="row table_row mt-4" style="min-height: <?=$table_row?>;">
        <div class="col-sm-12 p-0">
            <table class="w-100">
                <thead class="thead_row">
                    <th>Sr</th>
                    <th>DESCRIPTION</th>
                     <th>RATE MM</th>
                    <th>PRICE</th>
                    <th>QUANTITY</th>
                    <th>TOTAL</th>
                </thead>
                <tbody class="tbody_row">
                        <?php  $c=0; while ($r=mysqli_fetch_assoc($order_item)) { $c++;

                       ?>
                        <tr>
                            <td><?=$c?></td>
                            <td ><?=strtoupper($r['product_name'])?></td>
                            <td ><?=$r['rate_mm']?></td>
                            <td ><?=$r['rate']?></td>
                            <td ><?=$r['quantity']?></td>
                            <td ><?=$r['rate']*$r['quantity']?></td>
                        </tr>

                   <?php 
                   $totalQTY += $r['quantity'];
               } ?>
                    </tbody>
                    <tfoot class="tfoot_row">
              
                           <tr>
                            <td colspan="4"></td>
                            <td class="border"><b>Sub Total</b></td>
                            <td class="border"><?=$order['total_amount']?></td>
                            </tr>
                            <tr>
                            <td colspan="4"></td>
                            <td class="border"><b>Total Quantity</b></td>
                            <td class="border"><?=$totalQTY?></td>
                            </tr>
                    <?php
                    if ($order['discount']>0) {
                       
                    
                    ?>
                     <tr>
                        <td colspan="4"></td>
                        <td class="border">Tax%</td>
                        <td class="border"><?=$order['discount']?>%</td>
                    </tr>
                    <?php
                }
                    ?>
                    <tr>
                        <td colspan="4"></td>
                        <td class="border">FREIGHT</td>
                        
                        <td class="border"><b><?=empty($order['freight'])?"0":$order['freight']?></b></td>
                    </tr>
                    <tr>
                        <td colspan="4"></td>
                        <td class="border">GRAND TOTAL</td>
                        
                        <td class="border"><b><?=number_format($order['grand_total'],2)?></b></td>
                    </tr>
                    <?php if ($_REQUEST['type']=="order" AND $order['payment_type']=="credit_sale"): ?>
                      
                     <tr>
                        <td colspan="4"></td>
                        <td class="border">Previous Balance</td>
                        
                        <td class="border"><b><?=getcustomerBlance($dbc,$order['customer_account'])-$order['due']?></b></td>
                    </tr>
                    <tr>
                        <td colspan="4"></td>
                        <td class="border">Current Balance</td>
                        
                        <td class="border"><b><?=number_format(getcustomerBlance($dbc,$order['customer_account']),2)?></b></td>
                    </tr>
                    
                    <?php endif; ?>   
                    </tfoot>
            </table>
        </div>
    </div>
    <div class="row" style="font-size: 18px">
                    <div class="col-sm-3 h4">
                        Prepared By : __________________ 
                    </div>
                     <div class="col-sm-6 ">
                        <?php
                        if (isset($order['vehicle_no'])) {
                            // code...
                        
                        ?>
                       <p class="4 mt-1 text-center"> Vehicle No : <b><u><?=strtoupper($order['vehicle_no'])?></u></b></p>

                        <?php
                    }
                        ?>
                    <p class="mt-1 text-dark text-center"> Software Developed By :<br/><b>Samz Creation (0345-7573667)</b> </p>
                    
                    </div>
                     <div class="col-sm-3 h4">
                        Recevied By  : _________________ 
                    </div>
                </div>
</div> <!-- end of container --> 
<?php endfor; ?>


</body>
</html>
<?php
}
?>
<script type="text/javascript">
    window.print();
 //   window.close();
</script>
