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
                <b class="text-center card-text">Pending Bills</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">


  
    



    <form action="" method="post" class="d-print-none" >



      <div class="row d-print-none ">



  
  <div class="form-group col-sm-3 ">



        <label for="">From Date</label>



        <input type="date" name="from_date" class="form-control">



                                             

                                            

      </div>
  <div class="form-group col-sm-3 ">



        <label for="">To Date</label>



        <input type="date" name="to_date" class="form-control">



                                             

                                            

      </div>

      



      <div class="form-group col-sm-3 d-print-none" >



        <br />



        <button class="mt-2 btn btn-admin float-right" name="search_it" type="submit">Search</button>
        <button class="mt-2 btn btn-admin2 float-right" onclick="window.print();" style="margin-right: 15px;">Print Report</button>


      </div><!-- group -->



      </div>



    </form>



  
  


  <?php 



    if (isset($_REQUEST['search_it'])) :



?>
  <div class="row">
    <div class="col-12">
      <table class="table table-bordered table-striped" id="tableData2" style="width: 100%">
        



    



      <?php



      //echo  DateFormat($f_date , '%Y-%m-%d');



       ?>

<thead>
     <tr>
       <th>Order No.</th>
       <th>Customer Details</th>
       <th>Order Date</th>
        <th>Piad Amount</th>
       <th>Remaining Amount</th>
        <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
       <th>Action</th>
     <?php endif; ?>
     </tr>
</thead>
    <tbody>
<?php
    if (!empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
      $sql = "SELECT * FROM orders WHERE  payment_type='cash_in_hand' AND payment_status=0 AND order_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' ";
    }else if (!empty($_REQUEST['from_date']) AND empty($_REQUEST['to_date'])) {
      $sql = "SELECT * FROM orders WHERE  payment_type='cash_in_hand' AND payment_status=0 AND order_date = '".$_REQUEST['from_date']."' ";
    }else{
    
      $sql = "SELECT * FROM orders WHERE  payment_type='cash_in_hand' AND payment_status=0 ";

    }

  $result = mysqli_query($dbc, $sql);



  $temp=0;



  if ( mysqli_num_rows($result) > 0) :

    $totalPiad = 0;
    $totalDue = 0;

    while($row = mysqli_fetch_array($result)):?>

    <tr>
      <td><?=$row['order_id']?></td>
      <td><?=$row['client_name']?> (<?=$row['client_contact']?>)</td>
      <td><?=$row['order_date']?></td>
      <td><span style="font-size: 18px;" class=" badge badge-success"><?=$row['paid']?></span> </td>
      <td><span style="font-size: 18px;" class=" badge badge-danger"><?=$row['due']?></span> </td>
      <td>
                    <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                           <form action="cash_sale.php" method="POST">

                              <input type="hidden" name="edit_order_id" value="<?=base64_encode($row['order_id'])?>">
                              <input type="hidden" name="credit_type" value="<?=$r['credit_sale_type']?>">
                              <button type="submit" class="btn btn-admin2 btn-sm mr-1 float-left" >Edit</button>
                            </form>
                    <?php   endif; ?>
                    
                    <button class="btn btn-admin btn-sm float-right" data-toggle="modal" data-target="#pending_bills_modal" onclick="pending_bills(`<?=base64_encode($row['order_id'])?>`)" type="button">Payit</button>
      </td>
    </tr>
    
     <?php
      $totalPiad += $row['paid'];
      $totalDue += $row['due'];
     endwhile; ?>

     <tr>
       <td colspan="3"><h3>Total</h3></td>
       <td><?=number_format($totalPiad)?></td>
        <td><?=number_format($totalDue)?></td>
        <td>.</td>
     </tr>
    </tbody>
<?php else: ?>
  <tr>
    <td colspan="7" class="text-center">No Order Found</td>
  </tr>
    <?php endif;  

  ?>
<hr/>
      </table>
    </div>
  </div>


 
  <?php endif; ?>
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>


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

<div class="modal fade" id="pending_bills_modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="defaultModalLabel">Pending Bill</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
         <form action="php_action/custom_action.php" id="formData2"> 
          <input type="hidden" id="order_id" name="order_id">
          <div class="row form-group">
          <div class="col-sm-2">
            <label>Customer Detail</label>
          </div>
          <div class="col-sm-4">
            <input type="text" readonly name="bill_customer_name" id="bill_customer_name" class="form-control">
          </div>
          <div class="col-sm-2">
            <label>Bill Grand Total</label>
          </div>
          <div class="col-sm-4">
               <input type="number" readonly name="bill_grand_total" id="bill_grand_total" class="form-control">
             </div>
          </div>
          <div class="row form-group">
          <div class="col-sm-2">
            <label>Paid Amount</label>
          </div>
          <div class="col-sm-4">
            <input type="number" readonly name="bill_paid_ammount" id="bill_paid_ammount" class="form-control">
          </div>
          <div class="col-sm-2">
            <label>Remaing Amount</label>
          </div>
          <div class="col-sm-4">
               <input type="number" readonly name="bill_remaining" id="bill_remaining" class="form-control">
             </div>
          </div>
        <div class="row form-group">
          <div class="col-sm-2">
            <label>Paid Amount</label>
          </div>
          <div class="col-sm-4">
            <input type="text" required name="bill_paid" id="bill_paid" class="form-control">
          </div>
          <div class="col-sm-2">
            <label>Select Account</label>
          </div>
             <div class="col-sm-4">
                    <select class="form-control" id="bill_payment_account" name="bill_payment_account" required>
                     
                      <?php $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type='bank'");
                      while($r=mysqli_fetch_assoc($q)): ?>
                        <option <?=@($fetchOrder['payment_account']==$r['customer_id'])?"selected":""?>   value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                      <?php endwhile; ?>  
                    </select>
                  
          </div>
        </div>
       <div class="row">
                  <div class="col-sm-6 offset-6">
                    
                  <button class="btn btn-admin float-right " type="submit" id="formData2_btn">Save</button>
                
                  </div>
                </div>
       </form>
      </div>
    </div>
  </div>
</div>
<?php include_once 'includes/foot.php'; ?>