<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php';
$getCustomer = @$_REQUEST['id'];
if(@$getCustomer){

$Getdata = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM customers WHERE customer_id = '$getCustomer'"));
}

 ?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container">
        	<div class="row">
        		<div class="col-sm-12">
        			

		<div class="card card-info">
				<div class="card-header text-center h4  card-bg card-text"><?=ucfirst($_REQUEST['type'])?> Information</div>
				<div class=" card-body">
					 <form action="php_action/custom_action.php" method="post" id="formData">
					 	<input type="hidden" name="add_manually_user" value="<?=@$_REQUEST['type']?>">
					 	<input type="hidden" name="customer_id" value="<?=@$_REQUEST['id']?>">
					 <div class="form-group row">	
					<div class="col-sm-6">
						
				    <label for="email">Name:</label>
				    <input type="text" class="form-control" id="customer_name" name="customer_name" required autofocus="true" placeholder="Full Name" value="<?=@$Getdata['customer_name']?>">
				  </div>
					
					<div class="col-sm-6">
						<?php if ($_REQUEST['type']!="bank" AND $_REQUEST['type']!="expense"): ?>
							
				    <label for="email">Email:</label>
				    <input type="email" class="form-control" id="customer_email" name="customer_email" placeholder="Email" value="<?=@$Getdata['customer_email']?>" >
						<?php endif ?>
				  	
					</div>
					</div>
						<div class="form-group row">
						<div class="col-sm-6">
						 
				    <label for="email">Phone:</label>
				    <input type="number" class="form-control" id="customer_phone" name="customer_phone" placeholder="Phone" value="<?=@$Getdata['customer_phone']?>" required>
				  </div>
				

				<div class="col-sm-6">
						
				    

				    <label for="active">Status:</label>
				    <select name="customer_status" required class="form-control "> 
				    	<option value="1">Active</option>
				    	<option value="0">Deactive</option>
				    </select>
				  
				  </div>
					</div>

				  
				  
				 
				   
				   <div class="form-group">
				   	<label for="address">Address:</label>
				   <textarea name="customer_address" id="customer_address" cols="30" rows="4" placeholder="Address" class="form-control"><?=@$Getdata['customer_address']?></textarea>
				   </div>
				  
				 <div class="modal-footer">
     <?php
          if(isset($_REQUEST['id'])){
          	?>
          	 <button type="submit" id="formData_btn" class="btn btn-admin2" name="edit_customer">Update</button>
          	 <?php
          	}else{
?>
          <button type="submit" id="formData_btn" class="btn btn-admin" name="add_customer">ADD</button>
          <?php
}
          ?>
        </div>
				  
			</form>
				</div>
		</div>

	
        		</div>
        		<div class="col-sm-12">
        			
	<div class="card card-info mt-3">
		<div class="card-header text-center h4  card-bg card-text" align="center">
			<h5 style="color: white"><span class="glyphicon glyphicon-user"></span> <?=ucfirst($_REQUEST['type'])?> Management system</h5>
		</div>
		<div class="card-body">
		
<table id="tableData" class=" table dataTable ">

	<thead>
		<tr class="">
			<th> ID</th>
			<th>Name</th>
			<th>Email</th>
			<th>Phone</th>
			
			<th>Created Date</th>
			<?php if($_REQUEST['type'] == 'customer'): ?>
			<th> Creadit LIMIT</th>
		<?php endif; ?>
			<th>Action</th>

			
			
		</tr>
	</thead>
	<tbody>
		<?php $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type='".$_REQUEST['type']."'");
		while($r=mysqli_fetch_assoc($q)):
			$customer_id = $r['customer_id'];
		 ?>
		<tr>
			<td><?=$r['customer_id']?></td>
			<td class="text-capitalize"><?=$r['customer_name']?></td>
			<td class="text-lowercase"><?=$r['customer_email']?></td>
			<td><?=$r['customer_phone']?></td>
			
			<td><?=$r['customer_add_date']?></td>
			<?php if($_REQUEST['type'] == 'customer'): ?>
			<td><?=$r['customer_limit']?></td>
		<?php endif; ?>
			<td>
				<button class="btn btn-admin btn-sm float-right" onclick="SetLimit(<?=$r['customer_id']?>,'<?=$r['customer_name']?>')" id="limit">Limit</button>
			  <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                            <form action="customers.php?type=<?=$_REQUEST['type']?>" method="POST">
                              <input type="hidden" name="id" value="<?=$r['customer_id']?>">
                              <button type="submit" class="btn btn-admin btn-sm" >Edit</button>
                            </form>
               <?php   endif ?>

              </td>            
			
			
		</tr>
	<?php endwhile; ?>
	</tbody>
</table>
		</div>
	</div>
  
        		</div>
        	</div>
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    

    <?php
    include_once "limitmodal.php";
    ?>
  </body>
</html>

<script type="text/javascript">

	 

	function SetLimit(customer_id,customer_name){

		$("#LimitCustomer").val(customer_id);
		$("#defaultModalLabel").html('Add Limit of : '+customer_name);
		$('#add_limit_modal').modal('show');

		 $.ajax({
            type: 'POST',
            url: 'php_action/custom_action.php',
            data: {LimitCustomerajax:customer_id},
            dataType:'json',
            beforeSend:function() {
                 
                 },
               success:function (response) {
            	
              if (response.sts=="success") {
                 	$("#td_check_no").val(response.check_no);
					$("#voucher_bank_name").val(response.bank_name);
					$("#td_check_date").val(response.check_date);
					$("#check_type").val(response.check_type);
					$("#check_amount").val(response.check_amount);
					$("#location_info").val(response.check_location);
                 
              }    
            }
        });//ajax call


	}
</script>

<?php include_once 'includes/foot.php'; ?>


