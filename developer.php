<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php';
if (!empty($_REQUEST['edit_menu_id'])) {
    # code...
    $fetchMenu=fetchRecord($dbc,"menus","id",base64_decode($_REQUEST['edit_menu_id']));
  }
 ?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container">
          

        <div class="card">

          <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Developer Mode</b>
           
             
                 <a href="developer.php" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>

            <div class="card-body">
              
        <form action="php_action/panel.php" method="post" id="add_nav_menus_fm">
          <input type="hidden" name="action" value="add_nav_menu">
          <input type="hidden" name="edit_menu_id" value="<?=@base64_decode($_GET['edit_menu_id'])?>">
          <div class="form-group">
            <label for="">Page Title</label>
            <input type="text" class="form-control" placeholder="Page Title" name="nav_title" value="<?=@$fetchMenu['title']?>">
          </div><!-- group -->
          <div class="form-group">
            <label for="">Page url (.php)</label>
            <input type="text" class="form-control" id="nav_page" placeholder="Page Url (.php)" name="nav_page" value="<?=@$fetchMenu['page']?>#">
          </div><!-- group -->
          <div class="form-group">
            <label for="">Parent</label>
            <select name="nav_parent_id" id="" class="form-control">
            <option  value="0">No parent</option>
              <?php $q=mysqli_query($dbc,"SELECT DISTINCT(title),id FROM menus where parent_id=0");
              while($r=mysqli_fetch_assoc($q)):
               ?>
              <option <?=(@$fetchMenu['parent_id']==$r['id'])?"selected":""?> value="<?=$r['id']?>"><?=ucwords($r['title'])?></option>
            <?php endwhile; ?>
            </select>
          </div><!-- group -->
          <div class="row form-group ">
            <div  class="col-sm-12">
              <div class="form-check form-check-inline ml-4">
              <input class="form-check-input" type="checkbox" <?=@($fetchMenu['nav_edit']==1)?"checked":""?> name="nav_edit" id="nav_edit" value="1" />
              <label class="form-check-label" for="nav_edit">Edit</label>
            </div>

            <div class="form-check form-check-inline ml-4">
              <input class="form-check-input" name="nav_delete" <?=@($fetchMenu['nav_delete']==1)?"checked":""?> type="checkbox" id="nav_delete" value="1" />
              <label class="form-check-label" for="nav_delete">Delete</label>
            </div>
            <div class="form-check form-check-inline ml-4">
              <input class="form-check-input" type="checkbox" <?=@($fetchMenu['nav_add']==1)?"checked":""?> name="nav_add" id="nav_add" value="1" />
              <label class="form-check-label" for="nav_add">Add</label>
            </div>
            </div>
          </div>
          <div class="form-group">
            <label for="">Icon</label>
          <input type="text" name="nav_icon" value="<?=@$fetchMenu['icon']?>" class="form-control">
          </div><!-- group -->
          <button class="btn btn-admin" id="add_nav_menus_btn">Save</button>
        </form>
      
            </div>

            </div>


    <div class="card mt-2">

  <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Pages Lists</b>
           
             
                 <a href="modify_developer.php" class="btn btn-admin float-right btn-sm">Modify Pages</a>
              </div>
            </div>
  
          </div>

  <div class="card-body">

    <table class="table dataTable">
      <thead>
        <th>
          Title
        </th>
        <th>Url </th>
        <th>Parent</th>
        <th>Featured</th>
        <th>Action</th>
      </thead>
      
      <tbody>
              <?php $q=mysqli_query($dbc,"SELECT * FROM menus WHERE parent_id=0");
              while($r=mysqli_fetch_assoc($q)):
                $fetchParent = fetchRecord($dbc,"menus","id",$r['parent_id']);?>
                <tr>
                <td><?=ucwords($r['title'])?></td>
                <td><?=$r['page']?></td>
                <td><?=($r['parent_id']==0)?"Parent":$fetchParent['title']?></td>
                <td>
                  <?php if ($r['nav_edit']==1): ?>
                    Edit <br>
                  <?php endif ?>
                  <?php if ($r['nav_delete']==1): ?>
                    Delete <br>
                  <?php endif ?>
                  <?php if ($r['nav_add']==1): ?>
                    Add <br>
                  <?php endif ?>
                </td>
                <td>
              
                  <a href="developer.php?edit_menu_id=<?=base64_encode($r['id'])?>" class="btn btn-sm btn-admin" >Edit</a> | <a href="#"  onclick="deleteData('menus','id',<?=$r['id']?>,'developer.php')" class="btn btn-sm btn-admin2">Delete</a>
                </td>
                
              </tr>
              <?php  if ((mysqli_num_rows(mysqli_query($dbc,"SELECT * FROM menus WHERE parent_id='".$r['id']."'  ")))>0) {
                $q2=mysqli_query($dbc,"SELECT * FROM menus WHERE parent_id='".$r['id']."'");
              while($r2=mysqli_fetch_assoc($q2)):
                //$fetchParent = fetchRecord($dbc,"menus","id",$r['parent_id']);
               ?>
              <tr>
                <td><?=ucwords($r2['title'])?></td>
                <td><?=$r2['page']?></td>
                <td><?=$r['title']?></td>
                <td>
                  <?php if ($r2['nav_edit']==1): ?>
                    Edit <br>
                  <?php endif ?>
                  <?php if ($r2['nav_delete']==1): ?>
                    Delete <br>
                  <?php endif ?>
                  <?php if ($r2['nav_add']==1): ?>
                    Add <br>
                  <?php endif ?>
                </td>
                <td>
              
                  <a href="developer.php?edit_menu_id=<?=base64_encode($r2['id'])?>" class="btn btn-sm btn-admin" >Edit</a> | <a href="#"  onclick="deleteData('menus','id',<?=$r2['id']?>,'developer.php')" class="btn btn-sm btn-admin2">Delete</a>
                </td>
                
              </tr>
            <?php endwhile; } endwhile; ?>
            </tbody>
    </table>

    

  </div>

</div>




          
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>