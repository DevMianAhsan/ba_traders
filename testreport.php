<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  thead>tr>th{
    font-size: 19px;
    font-weight: bolder;
    color: #000;
  }
  tr>td{
    font-size: 18px;
    font-weight: bolder;
  }
</style>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text"><?=ucfirst($_REQUEST['type'])?> Due Amount</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">


  
    



    <form action="" method="post" class="d-print-none" >



      <div class="row d-print-none ">



      <div class="form-group col-sm-3">



        <label for=""><?=ucfirst($_REQUEST['type'])?> Account</label>



        <select required class="form-control" id="ledger_customer_id" name="customer_id" autofocus="true">
            <option value="">Select Account</option>
            <?php

            $sql = get($dbc,"customers WHERE customer_status = 1 AND customer_type='".$_REQUEST['type']."' ORDER BY customer_name ASC");
            while($row = $sql->fetch_array()) {
              $selected='';
              if (isset($_POST['customer_id']) AND $_POST['customer_id']==$row['customer_id']) {
                $selected='selected';
              }

              echo "<option ".$selected." value='".$row['customer_id']."'>".$row['customer_name']."</option>";
            } // while
            ?>
          </select> 
      </div><!-- group -->
  <div class="form-group col-sm-2 ">



        <label for="">From Date</label>



        <input type="date" name="from_date" class="form-control">



                                             

                                            

      </div>
  <div class="form-group col-sm-2 ">



        <label for="">To Date</label>



        <input type="date" name="to_date" class="form-control">



                                             

                                            

      </div>

      



      <div class="form-group col-sm-4 d-print-none" >



        <br />



        <!-- <button class="mt-2 ml-1 btn btn-admin float-right" name="genealledger" type="submit">Ledger Detials</button> -->

        <button class="mt-2  btn btn-admin2 float-right" name="fullledger" type="submit">Get Due amount Details</button>
        <?php 
    if (isset($_POST['genealledger']) OR isset($_POST['fullledger'])):  ?>
        <button class="mt-2 btn btn-primary float-right" onclick="window.print();" style="margin-right: 15px;">Print Report</button>
<?php endif ?> 

      </div><!-- group -->



      </div>



    </form>



  
  


  <?php 



    if (isset($_POST['genealledger']) OR isset($_POST['fullledger'])){



      $customer = $_POST['customer_id'];

      



?>
<hr>
   <header class="d-none d-print-block">
                <div class="row">
                     <div class="col-sm-1">
                         <img src="img/logo/<?=$get_company['logo']?>" width="90" height="90" class="img-fluid float-left" style="margin-top: 10px">
                </div>
        <div class="col-sm-5 mt-3">
          <h1 style="margin-left: -20px; color: red;font-weight: bold;font-size: 30px"><?=$get_company['name']?></h1>
          <p style="margin-left: -10px; font-weight: bolder;font-size: 15px">PH  No. :<?=$get_company['company_phone']?></p>
          
  

          
        </div>
        <div class="col-sm-4 offset-2 mt-4">
           
       <?php  $fetchCustomer = fetchRecord($dbc,"customers","customer_id",$customer);?>
       <h5>Account Name:<?=@$fetchCustomer['customer_name'] ;?></h5>
       <h5>Phone No:    <?=@$fetchCustomer['customer_phone'] ;?></h5>
       
        </div>
      
      </div>
            </header>


   


    <?php


        $sql = "SELECT * FROM transactions WHERE  customer_id='$customer' AND credit>0  ORDER BY transaction_id ASC";
        

  $result = mysqli_query($dbc, $sql);
?>
  <center style="width: 100%;margin-top: -5px;"><?=@$date_comment?></center>
  <div class="row">
    <div class="col-12">
      <center>
      <h5>Account Name: <?=@$fetchCustomer['customer_name'] ;?></h5>
       <h5>Phone No:    <?=@$fetchCustomer['customer_phone'] ;?></h5>
     </center>
      <table class="table table-bordered table-striped" style="width: 100%">
        
   

<thead>
     <tr>
        <th>Transaction #</th>
        <th>Date</th>
        <th>Remarks</th>
        <th>Amount</th>
       <th>Sale Type</th>
          <th>status</th>
        <th>Days</th>
          <th>Payment Date</th>
        <th>Due Amount</th>
      </tr>

</thead>


    <tbody>
             <?php
          $creditSum =$totalcreditfinal = 0;
            $TotalDebit = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(debit) AS TotalDebit FROM transactions WHERE customer_id = '$customer' "))['TotalDebit'];
            $Totalcredit = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(credit) AS Totalcredit FROM transactions WHERE customer_id = '$customer'  "))['Totalcredit'];
        while($r = mysqli_fetch_assoc($result)):

          
            
                $Date = date('Y-m-d');
                $now = strtotime($Date); // or your date as well            
         //     if ($r['transaction_from']!=="voucher") {
                     $invoice_id = filter_var($r['transaction_remarks'], FILTER_SANITIZE_NUMBER_INT);
                     $fetchinvoive = null;
                     $invoice_type = '';
                     $order_date = $r['transaction_date'] ?? date('Y-m-d');
                     $payment_status = 0;
                     if (strtolower($r['transaction_from']) == "invoice" || strtolower($r['transaction_from']) == "tax_sale") {
                         $fetchTax = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM tax_sales WHERE invoice_no = '$invoice_id' OR tax_sale_id = '$invoice_id' LIMIT 1"));
                         if (!empty($fetchTax)) {
                             $fetchinvoive = $fetchTax;
                             $order_date = !empty($fetchTax['invoice_date']) ? $fetchTax['invoice_date'] : $order_date;
                             $invoice_type = !empty($fetchTax['payment_terms']) ? $fetchTax['payment_terms'] : '';
                             if (!empty($fetchTax['po_no'])) {
                                 $po_q = mysqli_query($dbc, "SELECT po_paymentdays FROM purchase_order WHERE po_partypo = '" . mysqli_real_escape_string($dbc, $fetchTax['po_no']) . "' LIMIT 1");
                                 if ($po_r = mysqli_fetch_assoc($po_q)) {
                                     $days_num = intval(filter_var($po_r['po_paymentdays'], FILTER_SANITIZE_NUMBER_INT));
                                     if ($days_num > 0) $invoice_type = $days_num . 'days';
                                 }
                             }
                         } else {
                             $fetchinvoive = fetchRecord($dbc, "invoices", "invoice_id", $invoice_id);
                             if (!empty($fetchinvoive)) {
                                 $order_date = !empty($fetchinvoive['invoice_date']) ? $fetchinvoive['invoice_date'] : $order_date;
                                 $invoice_type = $fetchinvoive['credit_sale_type'] ?? '';
                                 if (empty($invoice_type) && !empty($fetchinvoive['party_po'])) {
                                     $po_q = mysqli_query($dbc, "SELECT po_paymentdays FROM purchase_order WHERE po_partypo = '" . mysqli_real_escape_string($dbc, $fetchinvoive['party_po']) . "' LIMIT 1");
                                     if ($po_r = mysqli_fetch_assoc($po_q)) {
                                         $days_num = intval(filter_var($po_r['po_paymentdays'], FILTER_SANITIZE_NUMBER_INT));
                                         if ($days_num > 0) $invoice_type = $days_num . 'days';
                                     }
                                 }
                             }
                         }
                     }
                     if (empty($fetchinvoive) || empty($invoice_type)) {
                         $fetchOrder = fetchRecord($dbc, "orders", "order_id", $invoice_id);
                         if (!empty($fetchOrder)) {
                             $fetchinvoive = $fetchOrder;
                             $invoice_type = $fetchOrder['credit_sale_type'] ?? '';
                             $order_date = !empty($fetchOrder['order_date']) ? $fetchOrder['order_date'] : $order_date;
                             $payment_status = $fetchOrder['payment_status'] ?? 0;
                         }
                     }
                     $parsed_days = intval(filter_var($invoice_type, FILTER_SANITIZE_NUMBER_INT));
                     if ($parsed_days > 0 && $payment_status == 0) {
                         $next_date = date('Y-m-d', strtotime($order_date . ' + ' . $parsed_days . ' days'));
                         $daytype = $parsed_days;
                         $payemnt_date = $next_date;
                     } else {
                         $next_date = date('Y-m-d', strtotime($order_date . ' + 30 days'));
                         $daytype = 30;
                         $payemnt_date = $next_date;
                     }
                  $your_date = strtotime(@$next_date);
                  $datediff =$your_date- $now;
                  $total_days= round($datediff / (60 * 60 * 24));
                  if (@$total_days<=0) {
                      $TotalDebit=$TotalDebit-$r['credit'];
                 }
               // } else{
               //                      $TotalDebit=$TotalDebit-$r['credit'];

               // }


           
           if ($TotalDebit<0):

            ?>
          <tr>
            <td><?=$r['transaction_id']?></td>
            <td><?=$r['transaction_date']?></td>
            <td><?=$r['transaction_remarks']?></td>
            <td><?=$r['credit']?> </td>
            <td><?=$invoice_type?></td>
            <td><span class="text-danger">pending</span></td>
                     <td class="h5">
            <?php
             
       $Date = date('Y-m-d');
                $now = strtotime($Date); // or your date as well                        
                $parsed_days = intval(filter_var($invoice_type, FILTER_SANITIZE_NUMBER_INT));
                if ($parsed_days > 0 && $payment_status == 0) {
                  $next_date = date('Y-m-d', strtotime($order_date . ' + ' . $parsed_days . ' days'));
                  $daytype = $parsed_days;
                  $payemnt_date = $next_date;
                } else {
                  $next_date = date('Y-m-d', strtotime($order_date . ' + 30 days'));
                  $daytype = 30;
                  $payemnt_date = $next_date;
                }
                // else{
                //   $next_date='special';
                // }
                 $your_date = strtotime($next_date);
                 $datediff =$your_date- $now;
                   $total_days= round($datediff / (60 * 60 * 24));
                   if($daytype<=0){
                   echo abs($total_days+$daytype) ." / ". $daytype;
               }else{
                 echo abs($total_days-$daytype) ." / ". $daytype;
               }



            ?>
          </td>
          <td><?=date('D,d-m-y',strtotime(@$payemnt_date));?></td>
            <td class="text-danger">

            <?php
            if (@$total_days<=0) {
              echo -($TotalDebit);
            
            }
            ?>
          </td>


          <?php  endif;?>
        
          </tr>
          <?php  endwhile; 

          ?>

    </tbody>
    <tfoot>
      <tr>

          <td colspan="8" class="text-right text-danger">Due Amount</td>
          <td class="text-danger h3"><?=-($TotalDebit)?></td>
        </tr>
        <tr>

          <td colspan="8" class="text-right">Total Due Balance</td>
          <td class="h3">
            <?php
            $q = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(credit-debit) as newblance from transactions WHERE customer_id = '$customer'  "));
            echo number_format($q['newblance']);
            ?>
          </td>
        </tr>
    </tfoot>

<?php
}else{
?>
  <tr>
    <td colspan="7" class="text-center">No Transaction Found</td>
  </tr>
 <?php
}
 ?> 



   
<hr/>
      </table>  
    </div>
  </div>


 
  
<script></script>
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>

<style type="text/css">
   @media print {
    thead>tr>th{
      font-size: 30px!important;
      color: black!important;
    }
    tr>td{
      font-size: 30px!important;
    }

    }
  </style>
