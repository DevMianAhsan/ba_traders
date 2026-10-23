<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Orders List</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
                            <table class="table  dataTable" id="view_orders_tb">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Customer Name</th>
                      <th>Customer Contact</th>
                      <th>Order Date</th>
                      <th>Amount</th>
                      <th>Order Type</th>
                     
                      
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody> 
                      <?php   $q=mysqli_query($dbc,"SELECT * FROM orders WHERE payment_type='cash_in_hand' AND page_type != 'goodsissue' ORDER BY order_id DESC LIMIT 500");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;
                     


                       ?>
                       <tr>
                          <td><?=$r['order_id']?></td>
                          <td><?=ucfirst($r['client_name'])?></td>
                          <td><?=$r['client_contact']?></td>
                          <td><?=$r['order_date']?></td>
                          <td><?=$r['grand_total']?></td>
                           <td><?=$r['payment_type']?></td>
                          
                
                         
                          <td>
                            <?php  if (@$get_company['sale_interface']=="barcode") {
                                      $cash_sale_url="cash_salebarcode.php";
                                      $credit_sale_url="credit_sale.php";
                                    }elseif ($get_company['sale_interface']=="keyboard") {
                                      $cash_sale_url="cash_salegui.php";
                                      $credit_sale_url="credit_sale.php";
                                    }else{
                                      $cash_sale_url="cash_sale.php";
                                      $credit_sale_url="credit_sale.php";
                                    }
                            ?>
                           <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND $r['payment_type']=="cash_in_hand"): ?>
                           <form action="<?=$cash_sale_url?>" method="POST">
                              <input type="hidden" name="edit_order_id" value="<?=base64_encode($r['order_id'])?>">
                              <button type="submit" class="btn btn-admin btn-sm m-1" >Edit</button>
                            </form>
                            
 
                          <?php   endif; ?>
                           <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND $r['payment_type']=="credit_sale"): ?>
                           <form action="<?=$credit_sale_url?>" method="POST">

                              <input type="hidden" name="edit_order_id" value="<?=base64_encode($r['order_id'])?>">
                              <input type="hidden" name="credit_type" value="<?=$r['credit_sale_type']?>">



                              <button type="submit" class="btn btn-admin btn-sm m-1" >Edit</button>
                            </form>
                            

                          <?php   endif; ?>
                           <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>
                             <a href="#" onclick="deleteAlert('<?=$r['order_id']?>','orders','order_id','view_orders_tb')" class="btn btn-admin btn-sm m-1">Delete</a>
                            

                          <?php   endif; ?>
                          

                             <a target="_blank" href="print_sale.php?type=order&id=<?=$r['order_id']?>" class="btn btn-admin2 btn-sm m-1">Print</a>
                              <a target="_blank" href=" print_gatepass.php?type=order&id=<?=$r['order_id']?>" class="btn btn-admin2 btn-sm my-1">Gatepass</a>
                            </td>
                       </tr>
                     <?php  } ?>
                  </tbody>  
                </table>
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>