<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  thead>tr>th{
    font-size: 20px;
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
                <b class="text-center card-text"><?=ucfirst($_REQUEST['type'])?> Ledger</b>
           
             
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

            $sql = get($dbc,"customers WHERE customer_status = 1 AND customer_type='".$_REQUEST['type']."' ");
            while($row = $sql->fetch_array()) {

              echo "<option value='".$row['customer_id']."'>".$row['customer_name']."</option>";
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



        <button class="mt-2 ml-1 btn btn-admin float-right" name="genealledger" type="submit">Ledger Detials</button>

        <button class="mt-2  btn btn-admin2 float-right" name="fullledger" type="submit">Full Details</button>
        <?php 
    if (isset($_POST['genealledger']) OR isset($_POST['fullledger'])):  ?>
        <button class="mt-2 btn btn-primary float-right" onclick="window.print();" style="margin-right: 15px;">Print Report</button>
<?php endif ?> 

      </div><!-- group -->



      </div>



    </form>



  
  


  <?php 



    if (isset($_POST['genealledger']) OR isset($_POST['fullledger'])) :



      $customer = $_POST['customer_id'];

      



?>
<hr>
   <header class="">
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
       <h5>Account Name:<?=@ucwords($fetchCustomer['customer_name']) ;?></h5>
       <h5>Phone No:    <?=@$fetchCustomer['customer_phone'] ;?></h5>
       
        </div>
      
      </div>
            </header>
   

       <?php 

        if(!empty($customer)){
         ?>
<?php
if (!empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
      $sql = "SELECT * FROM transactions WHERE  customer_id='$customer' AND transaction_add_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' ";
       $opening_sql = "SELECT * FROM transactions WHERE  customer_id='$customer'  AND transaction_from='voucher' AND transaction_add_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' ORDER BY transaction_id ASC LIMIT 1 ";
       $date_comment="<h6>FROM :".$_REQUEST['from_date']." TO :".$_REQUEST['to_date']."</h6>";
}else if (!empty($_REQUEST['from_date']) AND empty($_REQUEST['to_date'])) {
      $sql = "SELECT * FROM transactions WHERE  customer_id='$customer' AND transaction_add_date = '".$_REQUEST['from_date']."' ";
      $opening_sql = "SELECT * FROM transactions WHERE  customer_id='$customer'  AND transaction_from='voucher' AND transaction_add_date='".$_REQUEST['from_date']."'  ORDER BY transaction_id ASC LIMIT 1 ";
      $date_comment="<h6>Date :".$_REQUEST['from_date']."</h6>";
}else{
         $opening_sql = "SELECT * FROM transactions WHERE  customer_id='$customer'  AND transaction_from='voucher' ORDER BY transaction_id ASC LIMIT 1 ";
     $date_comment="<h6>Overall</h6>";
      $sql = "SELECT * FROM transactions WHERE  customer_id='$customer' ";
}

  $result = mysqli_query($dbc, $sql);
?>
  <center style="width: 100%;margin-top: -5px;"><?=$date_comment?></center>
  <div class="row">
    <div class="col-12">
      <table class="table table-bordered table-striped" style="width: 100%">
        
   

<thead>
     <tr>



        <th>Transaction #</th>



        <th>Date</th>
        <?php if (isset($_POST['fullledger'])): ?>
          
        
        <th>Transfer From</th>
<?php endif ?>
        <th>Remarks</th>



        <th>Debit</th>



        <th>Credit</th>



        <th>Balance</th>
<?php if (isset($_POST['fullledger'])): ?>
          <th>Remaining</th>

         <th>Extra</th>
<?php endif ?>



      </tr>

</thead>


    <tbody>




 

     




  <?php $temp=$check_remaing_balance=$show_rem_bal=0;


  $debitTotal = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(debit) AS debitTotal FROM transactions WHERE customer_id = '$customer'"));
  $check_remaing_balance=$debitTotal['debitTotal'];
  if ( mysqli_num_rows($result) > 0) :
    while($row = mysqli_fetch_array($result)):
      @$total_debit += $row['debit'];
      $invoice_type=$comment='';
      $remaing_amount=0;
        $remaing_balance1 = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(debit-credit) AS Nettotal FROM transactions WHERE customer_id = '$customer'"));
        $remaing_balance=$remaing_balance1['Nettotal'];

      if ($row['transaction_type']=="credit_sale") {
         $check_remaing_balance=$check_remaing_balance-$row['credit'];
         
        $invoice_id=filter_var($row['transaction_remarks'], FILTER_SANITIZE_NUMBER_INT);
         $fetchinvoive = fetchRecord($dbc,"orders","order_id",$invoice_id);
          $invoice_type=$fetchinvoive['credit_sale_type'];
          if ($check_remaing_balance<0) {
           
                $Date = date('Y-m-d');
                $now = strtotime($Date); // or your date as well                        
                if ($invoice_type=="15days" AND $fetchinvoive['payment_status']==0) {
                  $sale_Type = '15';
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 15 days'));
                }elseif($invoice_type=="30days" AND $fetchinvoive['payment_status']==0){
                  $sale_Type = '30';
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 30 days'));
                }elseif($invoice_type=="5days" AND $fetchinvoive['payment_status']==0){
                  $sale_Type = '30';
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 5 days'));
                }
                else{
                  $sale_Type = 'special';
                  $next_date='special';
                }
                $remaing_amount=$fetchinvoive['due'];
                if($next_date != 'special'){
                  $your_date = strtotime($next_date);
                  $datediff =$your_date- $now;
                  $total_days= round($datediff / (60 * 60 * 24));
                  if ($total_days>0) {
                    $comment='<span class="text-warning">'.(($sale_Type-$total_days)).'/'.$sale_Type.'<br/>'.$total_days.' days left </span>('.$next_date.')';
                  }else{

                      $comment='<span class="text-danger">'.abs($total_days-$sale_Type).'/'.$sale_Type.'  Expired </span>';
                }
              }
               $show_rem_bal=abs($check_remaing_balance);
            }else{
               $show_rem_bal=0;
               $comment='<span class="text-success">Paid </span>';
            } 
      }elseif($row['transaction_from']=="voucher"){
        $show_rem_bal=0;
        $fetchinvoive = fetchRecord($dbc,"orders","order_id",$row['transaction_id']);
        $invoice_type=$row['transaction_type'];
       
         $invoice_type2 = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM vouchers WHERE transaction_id1 = '$row[transaction_id]' "));
         //echo "SELECT * FROM vouchers WHERE transaction_id1 = '$row[transaction_id]'";
        
         if($invoice_type2 > 0){
         $invoice_type =  $invoice_type2['voucher_id'];
       }
        
      }
      @$total_credit+= $row['credit'];
    if ($row['debit']!==0 AND $row['credit']!==0):
      ?>
      <tr>
        <td><?=$row['transaction_id']?></td>

        <td><?=date('D, d-M-Y',strtotime($row['transaction_date']))?></td>
        <?php if (isset($_POST['fullledger'])): ?>
       <td><?=$row['transaction_from']?> (<?=@$invoice_type?>)</td>
        <?php endif ?>
       
        <td><?= str_replace("=","<br/>",$row['transaction_remarks']);?></td>
        <td class="text-primary h6"><?=@number_format($row['debit'])?></td>
        <td class="text-success h6 font-weight-bolder"><?=@number_format($row['credit'])?></td>
         
     
          <?php if ($check_remaing_balance<0 AND $row['transaction_from']=="invoice"): ?>
              <td class="text-danger"><?=number_format(((int)$row['credit']-(int)$row['debit'])+(int)$temp)?></td>
          <?php elseif ($row['transaction_from']=="voucher"): ?>
            <td class="text-info"><?=number_format(((int)$row['credit']-(int)$row['debit'])+(int)$temp)?></td>
          <?php else: ?>
              <td class="text-success"><?=number_format(((int)$row['credit']-(int)$row['debit'])+(int)$temp)?></td>
          <?php endif ?>
      <?php if (isset($_POST['fullledger'])): ?>

          <td class=" font-weight-bolder"><?=@number_format($show_rem_bal)?></td>

              <td><?=$comment?></td>
              <?php endif; ?>
      </tr>
      <?php
    endif;


      


       $temp=((int)$row['credit']-(int)$row['debit'])+$temp; ?>



    <?php endwhile; ?>





        <?php 
          $open_b=mysqli_fetch_assoc(mysqli_query($dbc,$opening_sql));
          if ($open_b['debit']==0) {
            $opening_balance=(int)$open_b['credit'];
          }else{
             $opening_balance=(int)$open_b['debit'];
          }


         ?>
     <tr>
       <?php if (isset($_POST['fullledger'])): ?>
        <td  colspan="3"></td>
      <?php else: ?>
        <td  colspan="2"></td>
       <?php   endif; ?> 
        <td colspan="3" align="right">  Opening Balance </td>
        <td colspan="4" class='h3 text-success'><?=$opening_balance?></td>
      </tr>
       <tr>
            <?php if (isset($_POST['fullledger'])): ?>
        <td  colspan="3"></td>
      <?php else: ?>
        <td  colspan="2"></td>
       <?php   endif; ?> 
        <td colspan="3" align="right">Total Debits</td>
        <td colspan="4" class='h3 text-info'><?=number_format($total_debit)?></td>
      </tr>
       <tr>
              <?php if (isset($_POST['fullledger'])): ?>
        <td  colspan="3"></td>
      <?php else: ?>
        <td  colspan="2"></td>
       <?php   endif; ?> 
        <td colspan="3" align="right">Total Credits</td>
        <td colspan="4" class='h3 text-warning'><?=number_format($total_credit)?></td>
      </tr>

      <tr >



              <?php if (isset($_POST['fullledger'])): ?>
        <td  colspan="3"></td>
      <?php else: ?>
        <td  colspan="2"></td>
       <?php   endif; ?> 



        <td colspan="3" align="right">Closing Balance</td>

     

<?php if ($temp<=0): ?>
             <td colspan="4" class='h3 text-danger'><?=number_format($temp)?></td>
            <?php else: ?>
             <td colspan="4" class='h3 text-success'><?=number_format($temp)?></td>
          <?php endif ?>

      </tr>




    </tbody>


<?php else: ?>
  <tr>
    <td colspan="7" class="text-center">No Transaction Found</td>
  </tr>
  



    <?php endif;  

   ?>

<hr/>
      </table>
    </div>
  </div>
<?php } ?>

 
  <?php endif; ?>
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
      font-size: 25px!important;
      color: black!important;
    }
    tr>td{
      font-size: 22px!important;
    }

    }
  </style>