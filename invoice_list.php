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
                <b class="text-center card-text">DC List</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
                            <table class="table  dataTableAsc" id="view_orders_tb">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Invoice ID</th>
                      <th>Invoice Date</th>
                      <th>Customer Details  </th>
                      <th>Party PO</th>
                      <th>Grand Total </th>
                   
                      
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody> 
                      <?php   $q=mysqli_query($dbc,"SELECT * FROM invoices ORDER by invoice_id DESC LIMIT 500 ");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;

                          $CLEINTHIS = fetchRecord($dbc,"customers","customer_id",$r['customer_id']);

                       ?>
                       <tr>
                          <td><?=$c?></td>
                           <td><?=$r['invoice_id']?></td>
                       
                          <td><?=$r['invoice_date']?></td>
                          <td>
                            <?=$CLEINTHIS['customer_name']?><br/>
                            <?=$CLEINTHIS['customer_phone']?>
                          </td>
                          <td><?=$r['party_po']?></td>
                          <td><?=$r['grand_total']?></td>
                          
                        
                
                         
                          <td>
                          
                        
                           <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>
                             <a href="#" onclick="deleteAlert('<?=$r['invoice_id']?>','invoices','invoice_id','view_orders_tb')" class="btn btn-admin btn-sm m-1">Delete</a>
                            

                          <?php   endif; ?>
                          

                             <a target="_blank" href="print_invoice.php?invoice=<?=$r['invoice_id']?>" class="btn btn-admin2 btn-sm m-1">Print</a>
                            
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