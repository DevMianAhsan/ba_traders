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
                <b class="text-center card-text">Purchase List</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
                            <table class="table  dataTable" id="view_purchase_tb">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Customer Name</th>
                      <th>Customer Contact</th>
                      <th> Date</th>
                      <th>Amount</th>
                      <th>Purchase Type</th>
                      
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody> 
                      <?php   $q=mysqli_query($dbc,"SELECT * FROM purchase ");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;
                     


                       ?>
                       <tr>
                          <td><?=$r['purchase_id']?></td>
                          <td><?=ucfirst($r['client_name'])?></td>
                          <td><?=$r['client_contact']?></td>
                          <td><?=$r['purchase_date']?></td>
                          <td><?=$r['grand_total']?></td>
                           <td><?=$r['payment_type']?></td>
                
                         
                          <td>
                          <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND $r['payment_type']=="cash_purchase"): ?>
                            <form action="cash_purchase.php" method="POST">
                              <input type="hidden" name="edit_purchase_id" value="<?=base64_encode($r['purchase_id'])?>">
                              <button type="submit" class="btn btn-admin btn-sm m-1" >Edit</button>
                            </form>
                            

                          <?php   endif; ?>
                          <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND $r['payment_type']=="credit_purchase"): ?>
                            <form action="credit_purchase.php" method="POST">
                              <input type="hidden" name="edit_purchase_id" value="<?=base64_encode($r['purchase_id'])?>">
                              <button type="submit" class="btn btn-admin btn-sm m-1" >Edit</button>
                            </form>
                            

                          <?php   endif; ?>
                           <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>
                             <a href="#" onclick="deleteAlert('<?=$r['purchase_id']?>','purchase','purchase_id','view_purchase_tb')" class="btn btn-admin btn-sm m-1">Delete</a>
                            

                          <?php   endif; ?>
                          

                            <a target="_blank" href="print_sale.php?id=<?=$r['purchase_id']?>&type=purchase" class="btn btn-admin2 btn-sm m-1">Print</a> 
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