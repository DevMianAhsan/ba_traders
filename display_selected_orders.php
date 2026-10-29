
<!DOCTYPE html>
<html>
<?php include_once 'includes/head.php';
    
 $orderIdsArray = explode(',', $_REQUEST['order_ids']);

    // Count the number of order IDs
   $numberOfOrderIds = count($orderIdsArray);
 ?>
<style type="text/css">
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
</style>

<body>
<?php   for ($i=0; $i < 1; $i++) : 
    for ($j=0; $j < 1 ; $j++) { 
        
    
    $totalQTY= 0;
            if($i>0){
           $margin = "margin-top:-270px !important";
           $copy = "Company Copy";
           
       }else{
         $margin = "";
           $copy = "Customer Copy";
       } 

   
  $order=fetchRecord($dbc,"orders","order_id",$orderIdsArray[$j]);
   $getDate=$order['order_date'];

    $order_item=mysqli_query($dbc,"SELECT order_item.*,product.* FROM order_item INNER JOIN product ON order_item.product_id=product.product_id WHERE order_item.order_id='".$order['order_id']."'");
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


       ?>
       
<div class="container">
<?php if ($order['payment_type']!="cash_in_hand"): ?>
                
           
            <header>
                <div class="row">
                     <div class="col-sm-5">
                         <img src="img/logo/<?=$get_company['logo']?>" width="90" height="90" class="img-fluid float-right" style="margin-top: 10px">
                </div>
        <div class="col-sm-7 mt-3">
          <h1 style="margin-left: -20px; color: red;font-weight: bold;font-size: 30px"><?=$get_company['name']?></h1>
          <p style="margin-left: -10px; font-weight: bolder;font-size: 15px">PH  No. :<?=$get_company['company_phone']?></p>
          
  

          
        </div>
        <center style="width: 100%;margin-top: -5px;"></center>
      </div>
            </header>
             <?php endif ?>
    <div class="row">
        <div class="pt-2  col-sm-6  ">
        <p class="h4 border p-2 font-weight-bold float-left"> Invoice # <b>
            <?php
            for ($k=0; $k < $numberOfOrderIds ; $k++) { 
                echo $orderIdsArray[$k].",";
            }
            ?>
                
            </b></p>
    </div>
    <div class="pt-2   col-sm-6">
        
         <p class="h4 border p-2 font-weight-bold float-right"> Date :  <b>
            <?php //getDateFormat("D d-M-Y h:i a",($order['timestamp']))?>
               <?php 
               echo $date = date('D d-M-Y h:i A');
               ?> 


            </b></p>
    </div>
    </div>
<div class="row">
        <div class="  col-sm-3  ">
        <p class="h4 border border-bottom-0 text-center p-0 m-0 font-weight-bold"> Customer Name </p>
        <p class="h4 border p-0 m-0 font-weight-bold text-center"><b><?=ucwords($order['client_name'])?></b></p>
    </div>
    <div class="col-sm-5">
        <h2 class="text-center p-0 m-0">Bill</h2>
        <h4 class="text-center p-0 m-0"><?=@$order_type?></h4>
    </div>
    <div class="  col-sm-4 ">
        <p class="h4 border border-bottom-0 text-center p-0 m-0 font-weight-bold"> Customer Address </p>
        <p class="h4 border p-0 m-0 font-weight-bold text-center"><b><?=$order['client_contact']?></b></p>
    </div>

    </div>
    <div class="row table_row mt-4" style="min-height: <?=$table_row?>;">
        <div class="col-sm-12 p-0">
            <table class="w-100">
                <thead class="thead_row">
                    <th>Sr</th>
                     <th>VOUCHER</th>
                    <th>DESCRIPTION</th>
                    <th>PRICE</th>
                    <th>QUANTITY</th>
                    <th>TOTAL</th>
                </thead>
                <tbody class="tbody_row">
                        <?php  $c=0; 
                         $total_amount = 0;
                        $orderData = mysqli_query($dbc, "SELECT * FROM orders WHERE order_id IN ({$_REQUEST['order_ids']})");

                         while($q = mysqli_fetch_assoc($orderData)){
                           
                         $order_item=mysqli_query($dbc,"SELECT order_item.*,product.* FROM order_item INNER JOIN product ON order_item.product_id=product.product_id WHERE order_item.order_id='".$q['order_id']."'");

                        while ($r=mysqli_fetch_assoc($order_item)) { $c++;

                       ?>
                        <tr>
                            <td><?=$c?></td>
                              <td ><?=$q['voucher_no']?></td>
                            <td ><?=strtoupper($r['product_name'])?></td>
                            <td ><?=$r['rate']?></td>
                            <td ><?=$r['quantity']?></td>
                            <td ><?=$r['rate']*$r['quantity']?></td>
                        </tr>

                   <?php 
                   $totalQTY += @$r['quantity'];
                   $total_amount += @$r['rate']*$r['quantity'];
               } 
           }?>
                    </tbody>
                    <tfoot class="tfoot_row">
              
                           <tr>
                            <td colspan="4"></td>
                            <td class="border"><b>Sub Total</b></td>
                            <td class="border"><?=$total_amount?></td>
                            </tr>
                            <tr>
                            <td colspan="4"></td>
                            <td class="border"><b>Total Quantity</b></td>
                            <td class="border"><?=$totalQTY?></td>
                  
                  
                    <tr>
                        <td colspan="4"></td>
                        <td class="border">GRAND TOTAL</td>
                        
                        <td class="border"><b><?=number_format($total_amount,2)?></b></td>
                    </tr>
                    <?php if (@$_REQUEST['type']=="order" AND $order['payment_type']=="credit_sale"): ?>
                      
                     <tr>
                        <td colspan="3"></td>
                        <td class="border">Previous Balance</td>
                        
                        <td class="border"><b><?=getcustomerBlance($dbc,$order['customer_account'])-$order['due']?></b></td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
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
                    <p class="mt-1 text-dark text-center"> Software Developed By :<b>TheWebConcept.com (0313-7573667)</b> </p>
                     <p class="text-dark text-center p-0 m-0"><?=$copy?></p>
                    </div>
                     <div class="col-sm-3 h4">
                        Recevied By  : _________________ 
                    </div>
                </div>
</div> <!-- end of container --> 
<?php

}
 endfor; ?>


</body>
</html>
<script type="text/javascript">
    window.print();
 //   window.close();
</script>