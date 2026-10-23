
<!DOCTYPE html>
<html>
<?php include_once 'includes/head.php';
    

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
<?php 
for ($i=0; $i <2 ; $i++):

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
       
<div class="container mt-5">
                
           
       <!--      <header>
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
            </header> -->
        
    <div class="row">
        <div class="pt-2  col-sm-6  ">
        <p class="h4 border p-2 font-weight-bold float-left">Gatepass #<b><?=$_REQUEST['id']?></b></p>
    </div>
    <div class="pt-2   col-sm-6">
        <p class="h4 border p-2 font-weight-bold float-right"> Date <b><?=getDateFormat("D d-M-Y h:i a",$order['timestamp'])?></b></p>
    </div>
    </div>
<div class="row">
        <div class="  col-sm-3  ">
        <p class="h4 border border-bottom-0 text-center p-0 m-0 font-weight-bold"> Customer Name </p>
        <p class="h4 border p-0 m-0 font-weight-bold text-center"><b><?=ucwords($order['client_name'])?></b></p>
    </div>
    <div class="col-sm-5">
        <h2 class="text-center p-0 m-0">Gatepass</h2>
        <h4 class="text-center p-0 m-0"></h4>
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
                    <th>DESCRIPTION</th>
                    <th>QUANTITY</th>
                </thead>
                <tbody class="tbody_row">
                        <?php  $c=0; while ($r=mysqli_fetch_assoc($order_item)) { $c++;

                       ?>
                        <tr>
                            <td><?=$c?></td>
                            <td ><?=strtoupper($r['product_name'])?></td>
                            <td ><?=$r['quantity']?></td>
                        </tr>

                   <?php 
                   $totalQTY += $r['quantity'];
               } ?>
                    </tbody>
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
                    <p class="mt-1 text-dark text-center"> Developed By :<b>Samz Creation (0345-7573667)</b> </p>
                     <p class="text-dark text-center p-0 m-0"><?=$copy?></p>
                    </div>
                     <div class="col-sm-3 h4">
                        Recevied By  : _________________ 
                    </div>
                </div>
</div> <!-- end of container --> 
<?php endfor; ?> 


</body>
</html>
<script type="text/javascript">
    window.print();
</script>