<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 
if (isset($_REQUEST['edit_categories_id'])) {
 $categories=fetchRecord($dbc,"categories", "categories_id",base64_decode($_REQUEST['edit_categories_id']));


} $btn_name=isset($_REQUEST['edit_categories_id'])?"Update":"Add";
?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Categories</b>
           
             
                 <a href="categories.php" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">

							<form action="php_action/panel.php" method="POST" role="form" id="formData">
								<div class="msg"></div>
								<div class="form-group row">
									<div class="col-sm-3">
									<label for="">Name</label>
									<input type="text" class="form-control" value="<?=@$categories['categories_name']?>" id="categories_name" name="add_category_name"> 
									<input type="hidden" class="form-control " value="<?=@$categories['categories_id']?>" id="categories_id" name="categories_id">

									</div>
                  <div class="col-sm-3">
                  <label for="">Sale Price</label>
                  <input type="text" class="form-control" value="<?=@$categories['category_price']?>" id="category_price" name="category_price" required min="1"> 
                </div>
                 <div class="col-sm-3">
                  <label for="">Purchase Price</label>
                  <input type="text" class="form-control" value="<?=@$categories['category_purchase']?>" id="category_price" name="category_purchase" required min="1"> 
                </div>
									<div class="col-sm-3">
									<label for=""> Status</label>
									<select class="form-control" id="categories_status" name="categories_status"> 
										
										<option  <?=@($categories['categories_status']==1)?"selected":"selected"?> value="1">Active</option>
										<option <?=@($categories['categories_status']==0)?"selected":""?> value="0">Inactive</option>
									</select>
								</div>
								</div>
							<?php 
						//	 if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND isset($_REQUEST['edit_categories_id'])): 
						    if (isset($_REQUEST['edit_categories_id'])):
						?>
								<button type="submit" class="btn btn-admin2 float-right" id="formData_btn">Update</button>
								  <?php   endif
								  ?>
								  <?php 
								  //if (@$userPrivileges['nav_add']==1 || $fetchedUserRole=="admin" AND !isset($_REQUEST['edit_categories_id'])): 
								if (!isset($_REQUEST['edit_categories_id'])):     
								  ?>
								<button type="submit" class="btn btn-admin float-right" id="formData_btn">Add</button>
								  <?php   endif ?>
							</form>
							
           </div>

          </div> <!-- .row -->

          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Categories List</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
			<table class="table dataTable" id="tableData">
				<thead>
			<tr>	
				<th>ID</th>
				<th> Name</th>
        <th>Sale Price</th>
        <th>Purchase Price</th>
				<th>Status</th>
				<th>Action</th>
			</tr>
			</thead>
			<tbody>

                      <?php   $q=mysqli_query($dbc,"SELECT * FROM categories");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;
                      


                       ?>
                       <tr>
                          <td><?=$c?></td>
                          <td><?=$r['categories_name']?></td>
                          <td><?=$r['category_price']?></td>
                          <td><?=$r['category_purchase']?></td>
                          <td>
                          	<?php if ($r['categories_status']==1): ?>
                          		Active
                          		<?php else: ?>
                          			Inactive
                          	<?php endif ?>
                          </td>
                          <td>
                          <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                            <form action="categories.php" method="POST">
                              <input type="hidden" name="edit_categories_id" value="<?=base64_encode($r['categories_id'])?>">
                              <button type="submit" class="btn btn-admin btn-sm m-1" >Edit</button>
                            </form>
                            

                          <?php   endif ?>
                          <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>

                             <a href="#" onclick="deleteAlert('<?=$r['categories_id']?>','categories','categories_id','tableData')" class="btn btn-admin2 btn-sm m-1">Delete</a>
                          <?php   endif ?>
                           <a target="_blank" href="stock.php?type=simple&category=<?=$r['categories_id']?>" class="btn btn-admin  btn-sm mx-1">Print Stock</a>
             <a target="_blank" href="stock.php?type=amount&category=<?=$r['categories_id']?>" class="btn btn-admin2  btn-sm mx-1">Print Stock With Amount</a>
              <a target="_blank" href="stock.php?type=amount&category=<?=$r['categories_id']?>&stock=0" class="btn btn-admin2  btn-sm mx-1">Print Stock With Amount + AQ</a>

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