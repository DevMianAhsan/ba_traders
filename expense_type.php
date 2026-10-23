<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 
if (isset($_REQUEST['edit_expense_id'])) {
 $expenses=fetchRecord($dbc,"expenses", "expense_id",base64_decode($_REQUEST['edit_expense_id']));


} $btn_name=isset($_REQUEST['edit_expense_id'])?"Update":"Add";
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
                <b class="text-center card-text">Expenses Type</b>
           
             
                 <a href="expense_type.php" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">

							<form action="php_action/custom_action.php" method="POST" role="form" id="formData">
								<div class="msg"></div>
								<div class="form-group row">
									<div class="col-sm-6">
									<label for="">Name</label>
									<input type="text" class="form-control" value="<?=@$expenses['expense_name']?>" id="add_expense_name" name="add_expense_name"> 
									<input type="hidden" class="form-control " value="<?=@$expenses['expense_id']?>" id="expense_id" name="expense_id">

									</div>
									<div class="col-sm-6">
									<label for="">Expense Status</label>
									<select class="form-control" id="expense_status" name="expense_status"> 
										
										<option  <?=@($expenses['expense_status']==1)?"selected":"selected"?> value="1">Active</option>
										<option <?=@($expenses['expense_status']==0)?"selected":""?> value="0">Inactive</option>
									</select>
								</div>
								</div>
							<?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND isset($_REQUEST['edit_expense_id'])): ?>
								<button type="submit" class="btn btn-admin2 float-right" id="formData_btn">Update</button>
								  <?php   endif ?>
								  <?php if (@$userPrivileges['nav_add']==1 || $fetchedUserRole=="admin" AND !isset($_REQUEST['edit_expense_id'])): ?>
								<button type="submit" class="btn btn-admin float-right" id="formData_btn">Add</button>
								  <?php   endif ?>
							</form>
							
           </div>

          </div> <!-- .row -->

          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">expenses List</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
			<table class="table dataTable" id="tableData">
				<thead>
			<tr>	
				<th>ID</th>
				<th>expenses Name</th>
				<th>Status</th>
				<th>Action</th>
			</tr>
			</thead>
			<tbody>

                      <?php   $q=mysqli_query($dbc,"SELECT * FROM expenses");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;
                      


                       ?>
                       <tr>
                          <td><?=$c?></td>
                          <td><?=$r['expense_name']?></td>
                          <td>
                          	<?php if ($r['expense_status']==1): ?>
                          		Active
                          		<?php else: ?>
                          			Inactive
                          	<?php endif ?>
                          </td>
                          <td>
                          <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                            <form action="expense_type.php" method="POST">
                              <input type="hidden" name="edit_expense_id" value="<?=base64_encode($r['expense_id'])?>">
                              <button type="submit" class="btn btn-admin btn-sm m-1" >Edit</button>
                            </form>
                            

                          <?php   endif ?>
                          <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>

                             <a href="#" onclick="deleteAlert('<?=$r['expense_id']?>','expenses','expense_id','tableData')" class="btn btn-admin2 btn-sm m-1">Delete</a>
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