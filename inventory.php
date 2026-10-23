<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 
if (isset($_REQUEST['edit_product_id'])) {
 $fetchproduct=fetchRecord($dbc,"product", "product_id",base64_decode($_REQUEST['edit_product_id']));


} $btn_name=isset($_REQUEST['edit_product_id'])?"Update":"Add";

?>
<style type="text/css">
  .badge{
    font-size: 15px;
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
                <b class="text-center card-text">Inventory Management</b>
           
           
              </div>
            </div>
  
          </div>
             <?php if (@$_REQUEST['act']=="add"): ?>
             <div class="card-body">  
            <form action="php_action/custom_action.php" id="formData" method="POST">
              <input type="hidden" name="action" value="inventory_module">
              <input type="hidden" name="product_id" value="<?=@base64_encode($fetchproduct['product_id'])?>">


                 <div class="form-group row">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                      <label for="">Product Name</label>
                      <input type="text" class="form-control" id="product_name" placeholder="Product Name" name="product_name"  required value="<?=@$fetchproduct['product_name']?>">                       
                      </div>
                      <div class="col-sm-6 mb-3 mb-sm-0">
                      <label for="">Estimated Price</label>
                      <input type="text" class="form-control" id="current_rate" placeholder="Estimated Price" name="current_rate"  required value="<?=@$fetchproduct['current_rate']?>">                       
                      </div>
                </div>
            <button class="btn btn-admin float-right" type="submit" id="formData_btn">Save</button>             
            </form>
          </div>  
           <?php else: ?>
           <div class="card-body">
           
                
              <table class="table dataTable col-12" style="width: 100%" id="product_tb">
              <thead>
                <tr>
                <th>#</th>
              
                <th>Name</th>
                <th>Selling Price</th>
                <th class="d-print-none">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $q=mysqli_query($dbc,"SELECT * FROM product WHERE status=1 AND inventory=1 ");
                $c=0;
                      while ($r=mysqli_fetch_assoc($q)) {
                   
                        $c++;
                 ?>
                <tr>
                  <td><?=$c?></td>
                  <td><?=$r['product_name']?></td>
                  <td><?=$r['current_rate']?>
                  </td>
                  <td class="d-print-none">
             
          <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                            <form action="inventory.php?act=add" method="POST">
                              <input type="hidden" name="edit_product_id" value="<?=base64_encode($r['product_id'])?>">
                              <button type="submit" class="btn btn-admin btn-sm m-1 d-inline-block" >Edit</button>
                            </form>
               <?php   endif ?>
               <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>
                        <button type="button" onclick="deleteAlert('<?=$r['product_id']?>','product','product_id','product_tb')" class="btn btn-admin2 btn-sm  d-inline-block" >Delete</button>
                      
               <?php   endif ?>
               <a href="print_barcode.php?id=<?=base64_encode($r['product_id'])?>" class="btn btn-primary btn-sm">Barcode</a>
                  </td>

                </tr>
              <?php } ?>
              </tbody>
            </table>
            
            
             <?php endif ?>
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>