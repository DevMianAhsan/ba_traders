
<?php 
include_once 'functions.php';
@include_once '../php_action/db_connect.php'; 




?>
		 
		 <?php
		 	if (isset($_REQUEST['company_submit'])) {
		 			if ($_FILES['logo']['tmp_name']) {
		 			# code...
		 			upload_pic($_FILES['logo'],'img/uploads/');
		 			$data=[
		 			'logo'=>$_SESSION['pic_name'],
		 			'name'=>$_POST['name'],
		 			'address'=>$_POST['address'],
		 			'company_phone'=>$_POST['company_phone'],
		 			'personal_phone'=> $_POST['personal_phone'],
		 			'email' => $_POST['email'],
		 			'stock_manage' => $_POST['stock_manage'],
		 			'sale_interface' => $_POST['sale_interface']
			 		];
		 		}else{
		 			$data=[
		 			'name'=>$_POST['name'],
		 			'address'=>$_POST['address'],
		 			'company_phone'=>$_POST['company_phone'],
		 			'personal_phone'=> $_POST['personal_phone'],
		 			'email' => $_POST['email'],
		 			'stock_manage' => $_POST['stock_manage'],
		 			'sale_interface' => $_POST['sale_interface']
			 		];
		 		}

		 	 if (insert_data($dbc,'company', $data)) {
				# code...
				echo "<script>alert('company Added....!')</script>";
				//$msg = "<script>alert('Company Added')</script>";
					$sts = 'success';
					redirect("company.php",2000);
				}else{
					$msg = mysqli_error($dbc);
					$sts ="error";
				}
		 		
		 	}
		

		 /*edit company profile*/
		 	if (isset($_POST['company_update'])) {
		 		$company_id=  $_REQUEST['company_id'];
		 		if ($_FILES['logo']['tmp_name']) {
		 			# code...
		 			upload_pic($_FILES['logo'],'img/logo/');
		 			$data=[
		 				'logo'=>$_SESSION['pic_name'],
		 			'name'=>$_POST['name'],
		 			'address'=>$_POST['address'],
		 			'company_phone'=>$_POST['company_phone'],
		 			'personal_phone'=> $_POST['personal_phone'],
		 			'email' => $_POST['email'],
		 			'stock_manage' => $_POST['stock_manage'],
		 			'sale_interface' => $_POST['sale_interface']
			 		];
		 		}else{
		 			$data=[
		 			'name'=>$_POST['name'],
		 			'address'=>$_POST['address'],
		 			'company_phone'=>$_POST['company_phone'],
		 			'personal_phone'=> $_POST['personal_phone'],
		 			'email' => $_POST['email'],
		 			'stock_manage' => $_POST['stock_manage'],
		 			'sale_interface' => $_POST['sale_interface']
			 		];
		 		}
		 		
		 			

		 	 if (update_data($dbc,'company', $data , 'id',$company_id)) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				echo $msg = "<script>alert('Company Updated')</script>";
					$sts = 'success';
					redirect("company.php",2000);
				}else{
					$msg = mysqli_error($dbc);
					$sts ="error";
				}	
			}
		   ?>

	
<?php

?>
		<?php
	/*Add Channel*/
if (!empty($_POST['action']) AND $_POST['action']=="add_new_user") {
		
		if (!empty($_REQUEST['password'])) {
			$password=md5($_REQUEST['password']);
		}else{
			if ($_REQUEST['new_user_id']!='') {
				$password=$_REQUEST['old_password'];
			}else{
				$password=md5($_REQUEST['old_password']);
			}
		}

		$data_user=[
			'fullname' => @$_REQUEST['fullname'],
			'username' => $_REQUEST['username'],
			'email' => $_REQUEST['email'],
			'phone' => $_REQUEST['phone'],
			'password' => $password,
			'user_role' => $_REQUEST['user_role'],
			'address' => @$_REQUEST['address'],
			'status' => $_REQUEST['status'],
			'cash_account' => $_REQUEST['cash_account'],
		];
			
	if ($_REQUEST['new_user_id']=='') {
		if(insert_data($dbc,"users",$data_user)){
			$msg = "User Added Successfully";
			$sts ="success";
			redirect("users.php",500);
		}else{
			$msg =mysqli_error($dbc);
			$sts = "error";
		}
	}else{

			if(update_data($dbc,"users",$data_user,'user_id',$_REQUEST['new_user_id'])){
			$msg = "Users Updated Successfully";
			$sts ="success";
			//redirect("users.php",500);
		}else{
			$msg =mysqli_error($dbc);

			$sts = "error"	;
		}

	}
		
		echo json_encode(['msg'=>$msg,'sts'=>$sts]);
	}

	/*Delete budget_category_del_id */
	if (!empty($_REQUEST['user_del_id'])) {
		# code...
		mysqli_query($dbc,"UPDATE users SET status = '0' WHERE user_id = '$_REQUEST[user_del_id]'");
		redirect('users.php',2000);
	}
	/*Fetch budget_category_edit_id */
	if (!empty($_REQUEST['user_edit_id'])) {
		# code...
		$fetchusers = fetchRecord($dbc,"users",'user_id',$_REQUEST['user_edit_id']);
		$users_button=' <button type="submit" id="budget_category" name="user_edit" data-loading-text="Loading..." class="btn btn-admin2 pull pull-right"> Edit </button>';
	}else{
		$users_button=' <button type="submit" id="budget_category" name="users_add" data-loading-text="Loading..." class="btn btn-admin pull pull-right">Save </button>';
	}
	/*Edit budget Category*/
	if (isset($_REQUEST['user_edit'])) {
		# code...
		$user_id = $_REQUEST['user_edit_id'];
		$data_user_update=[

			'username' => $_REQUEST['username'],
			'email' => $_REQUEST['email'],
			'phone' => $_REQUEST['phone'],
			'password' => md5($_REQUEST['password']),
			'user_role' => $_REQUEST['user_role'],
			'address' => $_REQUEST['address'],
			'status' => $_REQUEST['status'],
			

		];
			
	}

?>

<?php


if( isset($_POST['DownloadZip']) )  {
 
 $filename = $_POST['docs'];
 $source = $_POST['docs'];
 $type = $_POST['docs']; 
 
 echo sizeof($filename) ;
 
 //check file is selected for upload
 if(isset($filename) != ""){
 
      //First check whether zip extension is enabled or not
  if(extension_loaded('zip')) {
  
   //create the directory named as "images"
   $folderLocation = "images" ; 
   if (!file_exists($folderLocation)) {
    mkdir($folderLocation, 0777, true);
   }  
         
   $zip_name = time().".zip"; // Zip file name 
   $zip = new ZipArchive;
   if ($zip->open($zip_name, ZipArchive::CREATE) == TRUE){          
   
    foreach($filename as $key=>$tmp_name){
     $temp = $filename[$key];
     $actualfile = $filename[$key];
     // moving image files to temporary locati0n that is "images/"
     move_uploaded_file($temp, $folderLocation."/".$actualfile);
     // adding image file to zip
     $zip->addFile($folderLocation."/".$actualfile, $actualfile );
   
    } 
   // All files are added, so close the zip file.
   $zip->close();
    }
       
  }
  // push to download the zip
  header();
  header('Content-type: application/zip');
  header('Content-Disposition: attachment; filename="skptricks.zip"');
  readfile($zip_name);
  // remove zip file is exists in temp path
  unlink($zip_name);
  //remove image directory once zip file created
  removedir($folderLocation); 
 }
 
} 
 // user defined function to remove directory with their content
function removedir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir") 
           rrmdir($dir."/".$object); 
        else unlink   ($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
 } 
 
?>


