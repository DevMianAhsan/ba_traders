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


        $sql = "SELECT * FROM transactions WHERE  customer_id='$customer' AND credit>0 AND transaction_type = 'credit_sale' ORDER BY transaction_id ASC";
        

  $result = mysqli_query($dbc, $sql);
?>
  <center style="width: 100%;margin-top: -5px;"><?=@$date_comment?></center>
  <div class="row">
    <div class="col-12">
      <center>
      <h5>Account Name:<?=@$fetchCustomer['customer_name'] ;?></h5>
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
          $creditSum = 0;
          $totalcreditfinal = 0;
        while($r = mysqli_fetch_assoc($result)):
          $getDebit = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(debit) AS TotalDebit FROM transactions WHERE customer_id = '$customer' "));


           $getcredit = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(credit) AS Totalcredit FROM transactions WHERE customer_id = '$customer'  "));
          
           // echo "<hr/>";
            // echo  $getDebit['TotalDebit'];
            //  echo"<br/>";
            // echo $creditSum;
            // echo"<br/>";
          $creditSum +=$r['credit'];
          
            if($getDebit['TotalDebit']>=$creditSum   ){

            }else{
              $invoice_id=filter_var($r['transaction_remarks'], FILTER_SANITIZE_NUMBER_INT);
           $fetchinvoive = fetchRecord($dbc,"orders","order_id",$invoice_id);
           $invoice_type=$fetchinvoive['credit_sale_type'];
               $Date = date('Y-m-d');
                $now = strtotime($Date); // or your date as well                        
                if ($invoice_type=="15days" AND $fetchinvoive['payment_status']==0) {
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 15 days'));
                  $daytype = 15;
                  $payemnt_date = $next_date;
                }elseif($invoice_type=="30days" AND $fetchinvoive['payment_status']==0){
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 30 days'));
                   $daytype = 30;
                  $payemnt_date = $next_date;
                }elseif($invoice_type=="5days" AND $fetchinvoive['payment_status']==0){
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 5 days'));
                   $daytype = 5;
                  $payemnt_date = $next_date;
                }

                // else{
                //   $next_date='special';
                // }
                 $your_date = strtotime($next_date);
                 $datediff =$your_date- $now;
                   $total_days= round($datediff / (60 * 60 * 24));
                    ($total_days);
                   if(1==1){

                  

        ?>
        <tr>
          <td><?=$r['transaction_id']?></td>
          <td><?=$r['transaction_date']?></td>
          <td><?=$r['transaction_remarks']?></td>
          <td>
            <?php

              $invoice_id=filter_var($r['transaction_remarks'], FILTER_SANITIZE_NUMBER_INT);
           $fetchinvoive = fetchRecord($dbc,"orders","order_id",$invoice_id);
           $invoice_type=$fetchinvoive['credit_sale_type'];

           echo $r['credit'];
         


           

           
            ?>
          </td>


             <td><?=$invoice_type?></td>


         
          
          <td>
            <?php
          
            if($getDebit['TotalDebit']>$creditSum){
              echo '<span class="text-warning">paid</span>';
              $daysnow = 'paid';
            }else{
              echo '<span class="text-danger">pending</span>';
               $daysnow = '';
              $remaining = $r['credit'];
              $blnc = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(credit-debit)AS Nettotal FROM transactions WHERE customer_id = '$customer'  "));
              //echo "SELECT sum(credit-debit)AS Nettotal FROM transactions WHERE customer_id = '$customer' AND transaction_id <= '$r[transaction_id]'";

            }
            ?>
          </td>

           <td class="h5">
            <?php
              if($daysnow == 'paid'){
                echo $daysnow;
              }else{
           $Date = date('Y-m-d');
                $now = strtotime($Date); // or your date as well                        
                if ($invoice_type=="15days" AND $fetchinvoive['payment_status']==0) {
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 15 days'));
                   $daytype = 15;
                  $payemnt_date = $next_date;
                }elseif($invoice_type=="30days" AND $fetchinvoive['payment_status']==0){
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 30 days'));
                   $daytype = 30;
                  $payemnt_date = $next_date;
                }elseif($invoice_type=="5days" AND $fetchinvoive['payment_status']==0){
                  $next_date= date('Y-m-d', strtotime($fetchinvoive['order_date']. ' + 5 days'));
                   $daytype = 5;
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

}

            ?>
          </td>
            <td><?php
             if($daysnow == 'paid'){
           
          }else{
            echo date('D,d-m-y',strtotime(@$payemnt_date));
          }
            ?></td>
          
          <td class="text-danger">
            <?php
            if (@$total_days<=0) {
              echo @$remaining;
            
            $totalcreditfinal += @$remaining;
            }else{

            }
           

            ?>
          </td>

        </tr>

        


            <?php
          }
          }
          endwhile;
        
            ?>
    </tbody>
    <tfoot>
      <tr>

          <td colspan="8" class="text-right text-danger">Due Amount</td>
          <td class="text-danger h3"><?=number_format($totalcreditfinal)?></td>
        </tr>
        <tr>

          <td colspan="8" class="text-right">Total Due Blance</td>
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