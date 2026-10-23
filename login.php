
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once 'includes/head.php'; 




if(isset($_SESSION['userId'])) {
  header('location: dashboard.php');  
}



if($_POST) {    

   $username = $_POST['username'];
   $password = $_POST['pass'];

  if(empty($username) || empty($password)) {
    if($username == "") {
      $msg = "Username is required";
      $sts="danger";
    } 

    if($password == "") {
      $msg = "Password is required";
      $sts="danger";
    }
  } else {
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $connect->query($sql);

    if($result->num_rows == 1) {
      $password = md5($password);
      // exists
      $mainSql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
      $mainResult = $connect->query($mainSql);

      if($mainResult->num_rows == 1) {
        $value = $mainResult->fetch_assoc();
        $user_id = $value['user_id'];
        $_SESSION['user_id']= $value['user_id'];
          setcookie("user_id",$user_id,time()+(86400),"/");

        // set session
        $_SESSION['userId'] = $user_id;
       header('location: dashboard.php');
?>

<?php
      } else{
        
        $msg = "Incorrect username/password combination";
        $sts="danger";
      } // /else
    } else {    
      $msg = "Username doesnot exists";   
      $sts="danger"; 
    } // /else
  } // /else not empty username // password
  
} // /if $_POST
?>
   
  </head>
  <body class="light ">
    <div class="container vh-100">
      <div class="row align-items-center h-100">
        <div  class="col-lg-4 col-md-5 col-10 mx-auto text-center bg-white  shadow  rounded">
          
        <form action="" method="POST">
          <a class="navbar-brand mx-auto mt-2 flex-fill text-center" href="index.php">
            <img src="img/logo/<?=$get_company['logo']?>" style="width: 100px;height: 100px;">
          </a>
          <h1 class="h4 mb-3"><?=$get_company['name']?></h1>
          <h1 class="h6 mb-3">Sign in</h1>
          <?=@getMessage($msg,$sts)?>
          <div class="form-group">
            <label for="inputEmail" class="sr-only">Email address</label>
            <input type="text" id="inputEmail" class="form-control form-control-lg" placeholder="Email address" required="" autofocus="">
          </div>
          <div class="form-group">
            <label for="inputPassword" class="sr-only">Password</label>
            <input type="password" id="inputPassword" class="form-control form-control-lg" placeholder="Password" required="">
          </div>
          <div class="checkbox mb-3">
            <label>
              <input type="checkbox" value="remember-me"> Stay logged in </label>
          </div>
          <button class="btn btn-lg btn-admin btn-block" type="submit">Let me in</button>
          <p class="mt-5 mb-3 text-muted">samcreations©2021</p>
        </form>
        </div>
      </div>
    </div>
    <?php include_once 'includes/foot.php'; ?>
  </body>
</html>
</body>
</html>