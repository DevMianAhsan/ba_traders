<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg d-flex justify-content-between align-items-center">
              <div class="card-title-wrap">
                <h4 class="card-text mb-0"><i class="fe fe-shopping-bag mr-2"></i><b>Purchase Order List</b></h4>
              </div>
              <div class="card-action-wrap">
                <a href="purchase_order.php" class="btn btn-admin btn-sm"><i class="fe fe-plus"></i> Add PO</a>
              </div>
            </div>
           <div class="card-body">
                            <table class="table dataTable" id="view_orders_tb">
                  <thead>
                    <tr>
                      <th>ID #</th>
                      <th> Date</th>
                       <th>PO #</th>
                      <th>Customer Name</th>
                      <th>Customer Contact</th>
                      <th>Target Date</th>
                      <th>Amount</th>
                      
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody> 
                      <?php   $q=mysqli_query($dbc,"SELECT * FROM purchase_order ORDER by po_id DESC");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;
                          
                     


                       ?>
                       <tr>
                          
                           <td><?=$r['po_id']?></td>
                          <td><?=$r['po_date']?></td>
                          <td><strong><?=htmlspecialchars($r['po_partypo'])?></strong></td>
                          <td><?=$r['po_customer_name']?></td>
                          <td><?=$r['po_customer_no']?></td>
                          <td><?=$r['po_targetdate']?></td>
                          <td><?=$r['grand_total']?></td>
                         
                
                         
                          <td>
                            <?php  
                            ?>
                           <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" ): ?>
                           <form action="purchase_order.php" method="POST">
                              <input type="hidden" name="edit_order_id" value="<?=base64_encode($r['po_id'])?>">
                              <button type="submit" class="btn btn-admin btn-sm m-1" >Edit</button>
                            </form>
                            
 
                          <?php   endif; ?>
                          
                           <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>
                             <a href="#" onclick="deleteAlert('<?=$r['po_id']?>','purchase_order','po_id','view_orders_tb')" class="btn btn-danger btn-sm m-1">Delete</a>
                            

                          <?php   endif; ?>
                          

                             <a target="_blank" href="print_po.php?id=<?=$r['po_id']?>" class="btn btn-admin2 btn-sm m-1">Print</a>
                             <a target="_blank" href="jobsheet.php?id=<?=$r['po_id']?>" class="btn btn-admin2 btn-sm my-1">Job Sheet</a>
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
