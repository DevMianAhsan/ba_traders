<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php';
$getCustomer = @$_REQUEST['id'];
if(@$getCustomer){
    $Getdata = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM customers WHERE customer_id = '$getCustomer'"));
}

if (empty($_REQUEST['type'])) {
    if (!empty($Getdata['customer_type'])) {
        $_REQUEST['type'] = $Getdata['customer_type'];
    } else {
        $_REQUEST['type'] = 'customer';
    }
}
?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
        	<div class="row">
        		<div class="col-sm-12">
        			

		<div class="card card-info">
				<div class="card-header text-center h4  card-bg card-text" id="card_form_title"><?=isset($_REQUEST['id']) ? "Edit " : ""?><?=ucfirst($_REQUEST['type'])?> Information</div>
				<div class=" card-body">
					 <form action="php_action/custom_action.php" method="post" id="formData">
					 	<input type="hidden" name="add_manually_user" id="add_manually_user" value="<?=@$_REQUEST['type']?>">
					 	<input type="hidden" name="customer_id" id="customer_id" value="<?=@$_REQUEST['id']?>">
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
				    <input type="text" class="form-control" id="customer_phone" name="customer_phone" placeholder="Phone" value="<?=@$Getdata['customer_phone']?>" required>
				  </div>
				

				<div class="col-sm-6">
						
				    

				    <label for="active">Status:</label>
				    <select name="customer_status" id="customer_status" required class="form-control "> 
				    	<option <?=(@$Getdata['customer_status']=="1" || !isset($Getdata['customer_status']))?"selected":""?> value="1">Active</option>
				    	<option <?=(@$Getdata['customer_status']=="0")?"selected":""?> value="0">Deactive</option>
				    </select>
				  
				  </div>
					</div>
					<?php if ($_REQUEST['type'] == 'customer' || $_REQUEST['type'] == 'supplier'): ?>
					<div class="form-group row">
						<div class="col-sm-3">
							<label for="customer_strn">S.Tax Reg No. (STRN):</label>
							<input type="text" class="form-control" id="customer_strn" name="customer_strn" placeholder="e.g. 32-77-8761-660-49" value="<?=@$Getdata['customer_strn']?>">
						</div>
						<div class="col-sm-3">
							<label for="customer_ntn">NTN No.:</label>
							<input type="text" class="form-control" id="customer_ntn" name="customer_ntn" placeholder="e.g. 5368642-7" value="<?=@$Getdata['customer_ntn']?>">
						</div>
						<div class="col-sm-3">
							<label for="customer_province">Province:</label>
							<input type="text" class="form-control" id="customer_province" name="customer_province" placeholder="e.g. PUNJAB, SINDH" value="<?=@$Getdata['customer_province']?>">
						</div>
						<div class="col-sm-3">
							<label for="customer_cnic">CNIC No.:</label>
							<input type="text" class="form-control" id="customer_cnic" name="customer_cnic" placeholder="CNIC No." value="<?=@$Getdata['customer_cnic']?>">
						</div>
					</div>
					<?php endif; ?>

				  
				  
				 
				   
				   <div class="form-group">
				   	<label for="address">Address:</label>
				   <textarea name="customer_address" id="customer_address" cols="30" rows="4" placeholder="Address" class="form-control"><?=@$Getdata['customer_address']?></textarea>
				   </div>
				  
				 <div class="modal-footer">
     <?php
          if(isset($_REQUEST['id'])){
          	?>
          	 <button type="submit" id="formData_btn" class="btn btn-admin2" name="edit_customer">Update</button>
             <button type="button" class="btn btn-secondary" id="cancel_btn" onclick="cancelCustomerEdit()">Cancel</button>
          	 <?php
          	}else{
?>
          <button type="submit" id="formData_btn" class="btn btn-admin" name="add_customer">ADD</button>
          <button type="button" class="btn btn-secondary" id="cancel_btn" style="display:none;" onclick="cancelCustomerEdit()">Cancel</button>
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
			<?php if($_REQUEST['type'] == 'customer' || $_REQUEST['type'] == 'supplier'): ?>
			<th>STRN</th>
			<th>NTN</th>
			<?php endif; ?>
			
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
			<?php if($_REQUEST['type'] == 'customer' || $_REQUEST['type'] == 'supplier'): ?>
			<td><?=$r['customer_strn']?></td>
			<td><?=$r['customer_ntn']?></td>
			<?php endif; ?>
			
			<td><?=$r['customer_add_date']?></td>
			<?php if($_REQUEST['type'] == 'customer'): ?>
			<td><?=$r['customer_limit']?></td>
		<?php endif; ?>
			<td>
				<button class="btn btn-admin btn-sm float-right" onclick="SetLimit(<?=$r['customer_id']?>,'<?=$r['customer_name']?>')" id="limit">Limit</button>
			  <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                            <button type="button" class="btn btn-admin btn-sm" onclick="editCustomer(<?=$r['customer_id']?>)">Edit</button>
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

	 

	function editCustomer(id) {
		$.ajax({
			type: 'POST',
			url: 'php_action/custom_action.php',
			data: { fetch_customer_details: id },
			dataType: 'json',
			success: function(response) {
				if (response.sts == 'success') {
					$('#customer_id').val(response.customer_id);
					$('#customer_name').val(response.customer_name);
					$('#customer_email').val(response.customer_email || '');
					$('#customer_phone').val(response.customer_phone || '');
					$('#customer_status').val(response.customer_status !== undefined ? response.customer_status : '1');
					$('#customer_strn').val(response.customer_strn || '');
					$('#customer_ntn').val(response.customer_ntn || '');
					$('#customer_province').val(response.customer_province || '');
					$('#customer_cnic').val(response.customer_cnic || '');
					$('#customer_address').val(response.customer_address || '');

					$('#formData_btn').text('Update').removeClass('btn-admin').addClass('btn-admin2');
					$('#cancel_btn').show();

					var currentType = $('#add_manually_user').val() || 'customer';
					$('#card_form_title').text('Edit ' + currentType.charAt(0).toUpperCase() + currentType.slice(1) + ' Information');

					$('html, body').animate({
						scrollTop: $('#formData').offset().top - 80
					}, 300);
					$('#customer_name').focus();
				} else {
					sweeetalert(response.msg || 'Error loading details', 'error', 1500);
				}
			},
			error: function() {
				sweeetalert('Failed to fetch details', 'error', 1500);
			}
		});
	}

	function cancelCustomerEdit() {
		var form = $('#formData');
		form[0].reset();
		form.find('input[type="text"], input[type="number"], input[type="email"], textarea').val('');
		$('#customer_id').val('');
		$('#customer_status').val('1');
		$('#formData_btn').text('ADD').removeClass('btn-admin2').addClass('btn-admin');
		$('#cancel_btn').hide();

		var currentType = $('#add_manually_user').val() || 'customer';
		$('#card_form_title').text(currentType.charAt(0).toUpperCase() + currentType.slice(1) + ' Information');

		var cleanUrl = typeof getCleanUrl === 'function' ? getCleanUrl() : window.location.pathname + '?type=' + currentType;
		if (window.history && window.history.replaceState) {
			window.history.replaceState({}, document.title, cleanUrl);
		}
	}

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


