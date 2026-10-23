<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  #post_list>li{
    cursor: all-scroll;
    padding: 3px;
    margin-bottom: 5px;
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
                <b class="text-center card-text">Modify Navbar</b>
           
             
                 <a href="developer.php" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">
             <div class="row">
               <div class="col-sm-5 mx-auto">
                 

            <ul class="list-unstyled rearrange_nav" id="post_list">
              <?php $query = get($dbc, "menus WHERE parent_id=0 ORDER BY sort_order ASC  ");
      $rowCount = mysqli_num_rows($query);
      if($rowCount > 0){ $c=1;
        while($row = mysqli_fetch_assoc($query)){
          $value=$row['sort_order'];
          //$user = fetchRecord($dbc,"users","user_id",$row['user_id']);
          //$fetchuser = fetchRecord($dbc,"users","user_id",$row['user_id']);
          //$image=($fetchuser['user_pic']==null)?"default.png":$fetchuser['user_pic']; 
          ?>
      
        <li data-post-id="<?=$row["id"]?>" class="row border shadow" id="userRank<?=$row["id"]?>_<?=$value?>">
      
              <div class="col-sm-10 mt-2  ">
                <strong><p class="text-primary d-inline mt-3"><?=ucfirst($row["title"])?></p></strong>
              </div>
              <div class="col-sm-2 mt-2 ">
                <strong class="text-center"><?=$c?></strong> 
              </div>
           </li>
           <?php  
    $c++; }} ?>


            </ul>
           
               </div>
             </div>
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>