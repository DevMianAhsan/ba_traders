<?php 

//session_start();
include_once "db_connect.php";
include_once "includes/functions.php";
include_once "includes/code.php";
  $getpage = basename($_SERVER['REQUEST_URI']);

  if(!isset($_SESSION['userId']) AND $getpage!="index.php") {

	 ?>
	 <script>window.location.assign('index.php');
	  </script>
	 <?php
	 }else{

  @$fetchedUserData=fetchRecord($dbc,"users", "user_id",@$_SESSION['userId']);
  @$fetchedUserRole=$fetchedUserData['user_role'];
  //echo $getpage ;
   @$checkurlvalidQ = mysqli_query($dbc, "SELECT *  FROM privileges WHERE user_id ='".$_SESSION["userId"]."' AND nav_url='$getpage' ");
  
if (mysqli_num_rows($checkurlvalidQ)>0) {
	$userPrivileges=mysqli_fetch_assoc($checkurlvalidQ);
	
}
	 }
?>