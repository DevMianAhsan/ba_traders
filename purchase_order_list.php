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
                <b class="text-center card-text"> Order List</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
                            <table class="table  dataTableAsc" id="view_orders_tb">
                  <thead>
                    <tr>
                      <th>PO ID</th>
                      <th> Date</th>
                       <th>STM / PO</th>
                      <th>Customer Name</th>
                      <th>Customer Contact</th>
                 
                      <th>Sold To </th>
                      <th>Amount</th>
                      
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody> 
                      <?php   $q=mysqli_query($dbc,"SELECT * FROM purchase_order ORDER by po_id DESC LIMIT 500 ");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;
                          
                     


                       ?>
                       <tr>
                          
                           <td><?=$r['po_id']?></td>
                          <td><?=$r['po_date']?></td>
                          <td><?=$r['po_partystm']?> /  <?=$r['po_partypo']?></td>
                          <td><?=$r['po_customer_name']?></td>
                          <td><?=$r['po_customer_no']?></td>
                          <td><?=$r['po_consumer']?></td>
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
<script type="text/javascript" src="js/panelV2.js"></script>