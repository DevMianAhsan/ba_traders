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
                <b class="text-center card-text">Profile</b>
              </div>
            </div>
  
          </div>
           <div class="card-body">

		<form action="php_action/panel.php" method="post" id="formData">
			<span class="responseProfile"></span>
			<input type="hidden" name="action" value="update_profile">
			<input type="hidden" name="user_id" value="<?=$fetchedUserData['user_id']?>">
			
			

			<div class="row">

				<div class="col-sm-6">

					<div class="panel panel-default panel-body">

						<div class="form-group">

							<label for="">Full Name</label>

							<input type="text" name="user_fullname" value="<?=@$fetchedUserData['fullname']?>" class="form-control">

						</div><!-- group -->

						<div class="form-group">

							<label for="">Address</label>

							<textarea name="user_address" class="form-control" id="" cols="30" rows="4"><?=@$fetchedUserData['address']?></textarea>

						</div><!-- group -->

					</div><!-- panel -->

				</div><!-- col -->

				<div class="col-sm-6">

					<div class="panel panel-default panel-body">

						<div class="form-group">

							<label for="">Username </label>

							<input type="text" name="username" value="<?=@$fetchedUserData['username']?>" class="form-control" disabled>

						</div><!-- group -->

						<div class="form-group">

							<label for="">Email</label>

							<input type="text" name="user_email" value="<?=@$fetchedUserData['email']?>" class="form-control" readonly>

						</div><!-- group -->

						<div class="form-group">

							<label for="">Phone No.</label>

							<input type="text"  name="user_phone" value="<?=@$fetchedUserData['phone']?>" class="form-control">

						</div><!-- group -->


					</div><!-- panel -->	

				</div><!-- col -->

			</div><!-- row -->

			<button class="btn btn-admin" type="submit" id="formData_btn">Update Profile</button>

		</form>

		<hr>

		<div class="panel panel-default panel-body">

			<h3>Change Password</h3>
		<form action="php_action/panel.php" method="POST" id="formData1">
			<span class="response"></span>
			<input type="hidden" name="action" value="reset_password">
			<input type="hidden" name="user_id" value="<?=$fetchedUserData['user_id']?>">
			
		     <div class="form-group">
					<div class="input-group">
						<input required type="password" class="form-control" name="current_password" placeholder="Old Password">
						<div class="input-group-append">
							<span class="input-group-text toggle-password" role="button" tabindex="-1" title="Show/Hide Password">
								<i class="fe fe-eye"></i>
							</span>
						</div>
					</div>
				</div>
				<input type="hidden" class="form-control" name="user_email" value="<?=@$fetchedUserData['user_email']?>">
		          <div class="form-group">
					<div class="input-group">
						<input type="password" class="form-control" name="new_password" placeholder="New Password" required>
						<div class="input-group-append">
							<span class="input-group-text toggle-password" role="button" tabindex="-1" title="Show/Hide Password">
								<i class="fe fe-eye"></i>
							</span>
						</div>
					</div>
				</div>

		          <div class="form-group">
					<div class="input-group">
						<input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
						<div class="input-group-append">
							<span class="input-group-text toggle-password" role="button" tabindex="-1" title="Show/Hide Password">
								<i class="fe fe-eye"></i>
							</span>
						</div>
					</div>
				</div><br>

		          <button class="btn btn-admin2" type="submit" id="formData1_btn">UPDATE PASSWORD</button>
	    </form>

	</div>

	</div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>
<style>
.toggle-password {
	cursor: pointer;
	user-select: none;
}
.toggle-password:hover {
	color: #930027;
}
/* Prevent double eye icon in Edge/IE */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
	display: none;
}
</style>
<script>
$(document).ready(function() {
	$(document).off('click', '.toggle-password').on('click', '.toggle-password', function(e) {
		e.preventDefault();
		var $group = $(this).closest('.input-group');
		var $input = $group.find('input');
		if ($input.length) {
			var isPassword = $input.attr('type') === 'password';
			$input.attr('type', isPassword ? 'text' : 'password');
			
			var $icon = $(this).find('i, span');
			if ($icon.hasClass('fe-eye')) {
				$icon.removeClass('fe-eye').addClass('fe-eye-off');
			} else if ($icon.hasClass('fe-eye-off')) {
				$icon.removeClass('fe-eye-off').addClass('fe-eye');
			}
			
			if ($icon.hasClass('fa-eye')) {
				$icon.removeClass('fa-eye').addClass('fa-eye-slash');
			} else if ($icon.hasClass('fa-eye-slash')) {
				$icon.removeClass('fa-eye-slash').addClass('fa-eye');
			}
			
			$input.focus();
		}
	});

	$('#formData1').on('reset', function() {
		var $form = $(this);
		setTimeout(function() {
			$form.find('input[name="current_password"], input[name="new_password"], input[name="confirm_password"]').attr('type', 'password');
			$form.find('.toggle-password i').removeClass('fe-eye-off fa-eye-slash').addClass('fe-eye');
		}, 10);
	});

	$(document).ajaxSuccess(function(event, xhr, settings) {
		if (settings && settings.data && typeof settings.data === 'string' && settings.data.indexOf('action=reset_password') !== -1) {
			$('#formData1').find('input[name="current_password"], input[name="new_password"], input[name="confirm_password"]').attr('type', 'password');
			$('#formData1').find('.toggle-password i').removeClass('fe-eye-off fa-eye-slash').addClass('fe-eye');
		}
	});
});
</script>