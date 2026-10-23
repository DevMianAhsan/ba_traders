<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 
if (isset($_REQUEST['edit_brand_id'])) {
 $brands=fetchRecord($dbc,"brands", "brand_id",base64_decode($_REQUEST['edit_brand_id']));


} $btn_name=isset($_REQUEST['edit_brand_id'])?"Update":"Add";
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
                <b class="text-center card-text">Brands</b>
           
             
                 <a href="brands.php" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">

							<form action="php_action/panel.php" method="POST" role="form" id="formData">
								<div class="msg"></div>
								<div class="form-group row">
									<div class="col-sm-6">
									<label for="">Brand</label>
									<input type="text" class="form-control" value="<?=@$brands['brand_name']?>" id="add_brand_name" name="add_brand_name"> 
									<input type="hidden" class="form-control " value="<?=@$brands['brand_id']?>" id="brand_id" name="brand_id">

									</div>
									<div class="col-sm-6">
									<label for="">Brand Status</label>
									<select class="form-control" id="brand_status" name="brand_status"> 
										
										<option  <?=@($brands['brand_status']==1)?"selected":"selected"?> value="1">Active</option>
										<option <?=@($brands['brand_status']==0)?"selected":""?> value="0">Inactive</option>
									</select>
								</div>
								</div>
							<?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND isset($_REQUEST['edit_brand_id'])): ?>
								<button type="submit" class="btn btn-admin2 float-right" id="formData_btn">Update</button>
								  <?php   endif ?>
								  <?php if (@$userPrivileges['nav_add']==1 || $fetchedUserRole=="admin" AND !isset($_REQUEST['edit_brand_id'])): ?>
								<button type="submit" class="btn btn-admin float-right" id="formData_btn">Add</button>
								  <?php   endif ?>
							</form>
							
           </div>

          </div> <!-- .row -->

          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Brands List</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
			<table class="table dataTable" id="tableData">
				<thead>
			<tr>	
				<th>ID</th>
				<th>Brands Name</th>
				<th>Status</th>
				<th>Action</th>
			</tr>
			</thead>
			<tbody>

                      <?php   $q=mysqli_query($dbc,"SELECT * FROM brands WHERE brand_status=1 ");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;
                      


                       ?>
                       <tr>
                          <td><?=$c?></td>
                          <td><?=$r['brand_name']?></td>
                          <td>
                          	<?php if ($r['brand_status']==1): ?>
                          		Active
                          		<?php else: ?>
                          			Inactive
                          	<?php endif ?>
                          </td>
                          <td>
                          <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                            <form action="brands.php" method="POST">
                              <input type="hidden" name="edit_brand_id" value="<?=base64_encode($r['brand_id'])?>">
                              <button type="submit" class="btn btn-admin btn-sm m-1" >Edit</button>
                            </form>
                            

                          <?php   endif ?>
                          <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>

                             <a href="#" onclick="deleteAlert('<?=$r['brand_id']?>','brands','brand_id','tableData')" class="btn btn-admin2 btn-sm m-1">Delete</a>
                          <?php   endif ?>
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