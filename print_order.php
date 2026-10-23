<style>
        table,td,td,body {
            font-family: Tahoma, Arial, sans-serif;
        }

        h1, p {
            font-family: Tahoma, Arial, sans-serif;
        }
    </style>

        
 <?php  include_once 'includes/head.php';
if ($_REQUEST['type']=="purchase") {
    $print = 1;
}else{
$print = 2;
}

        for ($i=0; $i < $print; $i++) { 
            if($i>0){
           $margin = "margin-top:-270px !important";
           $copy = "Company Copy";
           
       }else{
         $margin = "";
           $copy = "Customer Copy";
       }
       
 if ($_REQUEST['type']=="purchase") {
       $order=fetchRecord($dbc,"purchase","purchase_id",$_REQUEST['id']);
         if ($order['payment_type']=="credit_purchase") {
        
            $order_type="credit purchase";
       
            }else{
                $order_type="cash purchase";
            }
    $order_item=mysqli_query($dbc,"SELECT purchase_item.*,product.* FROM purchase_item INNER JOIN product ON purchase_item.product_id=product.product_id WHERE purchase_item.purchase_id='".$_REQUEST['id']."'");
 }else{
  $order=fetchRecord($dbc,"orders","order_id",$_REQUEST['id']);

    $order_item=mysqli_query($dbc,"SELECT order_item.*,product.* FROM order_item INNER JOIN product ON order_item.product_id=product.product_id WHERE order_item.order_id='".$_REQUEST['id']."'");
    if ($order['payment_type']=="credit_sale") {
        if ($order['payment_type']=="none") {
            $order_type="credit sale";
        }else{
             $order_type=$order['credit_sale_type'];   
        }
    }else{
        $order_type="cash sale";
    }

}

 ?>
<style type="text/css">
 @font-face {
    font-family: 'AvantGardeBookBT';
    src: url('AvantGardeBookBT.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

/* The following rules are deprecated. */ 
@font-face {
    font-family: 'AvantGardeBookBT';
    src: url('AvantGardeBookBT.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

body,p { 
   font-family: 'AvantGardeBookBT'; 
   font-weight: normal; 
   font-style: normal; 

}
input{
   font-family:'Lucida Casual', 'Comic Sans MS';    
   
}
</style>

    <div class="page-content-wrapper" >
    <div class="page-content" >
       
<div id="invoice" style="<?=$margin?>margin-bottom: 0px; border-top: 2px dashed black;">

    <div class="invoice overflow-auto">  
        <div >
            <?php if ($order['payment_type']!="cash_in_hand"): ?>
                
           
            <header>
                <div class="row">
                     <div class="col-sm-5">
                         <img src="img/logo/<?=$get_company['logo']?>" width="80" height="80" class="img-fluid float-right" style="margin-top: 30px">
                </div>
        <div class="col-sm-7 mt-3">
          <h1 style="margin-left: -20px; color: red;font-weight: bold;font-size: 60px"><?=$get_company['name']?></h1>
          <p style="margin-left: -10px; font-weight: bold;font-size: 20px">PH  No. :<?=$get_company['company_phone']?></p>
          
  

          
        </div>
        <center style="width: 100%;margin-top: -5px;"></center>
      </div>
            </header>
             <?php endif ?>
            <main>
                <div class="row contacts">
                    <div class="col invoice-to">
                        <div class="text-gray-light font-weight-bolder h3">CUSTOMER'S DETAILS:</div>
                        <h1 class="to"><?=ucwords($order['client_name'])?></h1>
                        <div class=" font-weight-bolder" style="font-size: 22px"><?=@$order['client_contact']?></div>
                        <div class="address"><?=@$order['address']?></div>
                    </div>
                    <div class="col invoice-details">
                        <h1 class="invoice-id"><?=strtoupper($_REQUEST['type'])?> # <?=$_REQUEST['id']?></h1>
                        <div class="date  h3"><?=ucfirst($_REQUEST['type'])?> Type:<?=@ucfirst($order_type)?></div>
                        <div class="date  h5"><?=ucfirst($_REQUEST['type'])?> Date/Time: <?=Date('D,d-m-Y h:i',strtotime($order['timestamp']))?></div>
                    </div>
                </div>
                <div class=""  
                
                <?php if ($_REQUEST['type']=="order" AND $order['payment_type']=="credit_sale"){ ?>

                style="min-height:1250px;background-color: blue;"
            <?php
}else{
            ?>
             style="min-height:620px;background-color: red;"
             <?php
         }
             ?>
                >
                <table border="0" cellspacing="0" cellpadding="0"  >
                    <thead>
                    <tr>
                        <th>Sr No.</th>
                        <th class="text-left">DESCRIPTION</th>
                        <th class="text-right">PRICE</th>
                        <th class="text-right">QUANTITY</th>
                        <th class="text-right">TOTAL</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $qtyGrand = 0;
                         $c=0;
                         while ($r=mysqli_fetch_assoc($order_item)) { $c++;

                       ?>
                        <tr>
                            <td class="no"><?=$c?></td>
                            <td class="text-left"><h3><?=$r['product_name']?></h3></td>
                            <td class="unit"><h3><?=$r['rate']?></h3></td>
                            <td class="qty"><h3><?=$r['quantity']?></h3></td>
                            <td class="unit"><h3><?=$r['rate']*$r['quantity']?></h3></td>
                        </tr>

                   <?php
                   $qtyGrand +=$r['quantity'];
                    } ?>
                    </tbody>
                    <tfoot>
              
                           <tr>
                            <td colspan="2"></td>
                            <td colspan="2"><b>Sub Total</b></td>
                            <td><h3><?=$order['total_amount']?></h3></td>
                        </tr>
                    <?php
                    if ($order['discount']>0) {
                       
                    
                    ?>
                     <tr>
                        <td colspan="2"></td>
                        <td colspan="2">DISCOUNT%</td>
                        <td><?=$order['discount']?>%</td>
                    </tr>
                    <?php
                }
                    ?>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2">FREIGHT</td>
                        
                        <td><b><?=empty($order['freight'])?"0":$order['freight']?></b></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2">Total Quantity </td>
                        
                        <td><b><?=$qtyGrand?></b></td>
                    </tr>
                    
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2">GRAND TOTAL</td>
                        
                        <td><b><?=number_format($order['grand_total'],2)?></b></td>
                    </tr>
                    <?php if ($_REQUEST['type']=="order" AND $order['payment_type']=="credit_sale"): ?>
                         <tr>
                        <td colspan="2"></td>
                        <td colspan="2">Paid Amount</td>
                        
                        <td><b><?=number_format($order['paid'],2)?></b></td>
                    </tr>
                     <tr>
                        <td colspan="2"></td>
                        <td colspan="2">Previous Balance</td>
                        
                        <td><b><?=getcustomerBlance($dbc,$order['customer_account'])-$order['paid']?></b></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2">Current Balance</td>
                        
                        <td><b><?=getcustomerBlance($dbc,$order['customer_account'])?></b></td>
                    </tr>
                    
                    <?php endif; ?>   
                    </tfoot>
                </table>
            </div>
                <div class="row mb-5" style="font-size: 18px">
                    <div class="col-sm-4 h3">
                        Prepared By : __________________ 
                    </div>
                     <div class="col-sm-4 h3">
                        <?php
                        if (isset($order['vehicle_no'])) {
                            // code...
                        
                        ?>
                        Vehicle No : <b><u><?=strtoupper($order['vehicle_no'])?></u></b>
                        <?php
                    }
                        ?>
                    </div>
                     <div class="col-sm-4 h3">
                        Recevied By  : _________________ 
                    </div>
                </div>
                <!--<div class="thanks">Thank you!</div>-->
                <?php if ($order['payment_type']=="credit_sale"): ?>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="notices">
                    <h5><strong>Thank you! for choosing<b class="name">
                            <?=$get_company['name']?> </b></strong></h5>
                      
                   
                    <p style="font-size: 20px;">Developed By <b> Samz Creation</b>  (0345-7573667)</p>
                  
                                            
                </div>
                    </div>
                    <div class="col-sm-6">
                         <h5 class="mt-2 float-right"><?php echo $copy; ?></h5>
                    </div>
                </div>
                <?php
                endif;
                 ?> 
            </main>
           
                

             
                
           
            

        </div>
              
       
    </div>
</div>
<br/>

<?php
}
?>
    </div>
</div>
<style>
    #invoice{
        padding: 0px;
    }

    .invoice {
        position: relative;
        background-color: #FFF;
        min-height: 700px;
        padding: 15px
    }

    .name{
        color: #cd0606;
    }

    .invoice header {
        padding: 10px 0;
        margin-bottom: 20px;
        border-bottom: 1px solid #cd0606
    }

    .invoice .company-details {
        text-align: right
    }

    .invoice .company-details .name {
        margin-top: 0;
        margin-bottom: 0
    }

    .invoice .contacts {
        margin-bottom: 20px
    }

    .invoice .invoice-to {
        text-align: left
    }

    .invoice .invoice-to .to {
        margin-top: 0;
        margin-bottom: 0
    }

    .invoice .invoice-details {
        text-align: right
    }

    .invoice .invoice-details .invoice-id {
        margin-top: 0;
        color: #cd0606;
    }

    .invoice main {
        padding-bottom: 5px
       /* min-height: 800px !important;*/
    }

    .invoice main .thanks {
        margin-top: -100px;
        font-size: 2em;
        margin-bottom: 50px
    }

    .invoice main .notices {
        padding-left: 6px;
        border-left: 6px solid #cd0606

    }

    .invoice main .notices .notice {
        font-size: 1.2em
    }

    .invoice table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px
    }

    .invoice table td,.invoice table th {
        padding: 10px;
        background: #eee;
        border-bottom: 1px solid #fff
    }

    .invoice table th {
        white-space: nowrap;
        font-weight: 400;
        font-size: 16px
    }

    .invoice table td h3 {
        margin: 0;
        font-weight: 400;
        color: #cd0606;
        font-size: 1.2em
    }

    .invoice table .qty,.invoice table .total,.invoice table .unit {
        text-align: right;
        font-size: 1.2em
    }

    .invoice table .no {
        color: #000;
        font-size: 1.6em;
     
    }

    .invoice table .unit {
        background: #ddd
    }

    .invoice table .total {
        background: #cd0606;
        color: #fff
    }

    .invoice table tbody tr:last-child td {
        border: none
    }

    .invoice table tfoot td {
        background: 0 0;
        border-bottom: none;
        white-space: nowrap;
        text-align: right;
        padding: 10px 20px;
        font-size: 1.2em;
        border-top: 1px solid #aaa
    }

    .invoice table tfoot tr:first-child td {
        border-top: none
    }

    .invoice table tfoot tr:last-child td {
        color: #cd0606;
        font-size: 1.4em;
        border-top: 1px solid #cd0606
    }

    .invoice table tfoot tr td:first-child {
        border: none
    }

    .invoice footer {
        width: 100%;
        text-align: center;
        color: #777;
        border-top: 1px solid #aaa;
        padding: 8px 0
    }
<?php if ($_REQUEST['type']=="order" AND $order['payment_type']=="credit_sale"): ?>
    @media print {
        .invoice {
            font-size: 11px!important;
            overflow: hidden!important;

        }

        .invoice footer {
            position: absolute;
            bottom: 10px;
            page-break-after: always
        }

        .invoice>div:last-child {
            page-break-before: always
        }
    }
<?php endif; ?>
    @media screen and (max-width: 600px) {
        .invoice header {
        padding: 5px 0;
        margin-bottom: 20px;
        border-bottom: 1px solid #cd0606;
        text-align: center;
    }
   .invoice table td,.invoice table th {
        padding: 0px;
        background: #eee;
        border-bottom: 1px solid #fff
    }

    .invoice table th {
        white-space: nowrap;
        font-weight: 300;
        font-size: 14px
    }

    .invoice table td h3 {
        margin: 0;
        font-weight: 300;
        color: #cd0606;
        font-size: 1em
    }

    .invoice table .qty,.invoice table .total,.invoice table .unit {
        text-align: right;
        font-size: 1em
    }

    .invoice table .no {
        color: #fff;
        font-size: 0.8em;
        background: #cd0606
    }

    .invoice table .unit {
        background: #ddd
    }

    .invoice table .total {
        background: #cd0606;
        color: #fff
    }
    }
    h3{
        font-weight: 900 !important;
    }
    thead{
        border-bottom: 2px solid #000 !important;
    }
    thead tr th{
         font-weight: 1000 !important;
         font-size: 19px !important;
    }
</style>
