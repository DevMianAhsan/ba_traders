  <?php  
  date_default_timezone_set("Asia/Karachi");
  $user_id_current = $_SESSION['userId'];
  // Base
  $base_color = "#930027";

$UserData = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM users WHERE user_id = '$user_id_current'"));
$_SESSION['user_role'] = $UserData['user_role'];
 if (isset($_REQUEST['credit_type']) AND $_REQUEST['credit_type']=="15days") {
    $credit_sale_type="15days";
      $credit_sale_type_text="15 days";
    // code...
  }elseif (isset($_REQUEST['credit_type']) AND $_REQUEST['credit_type']=="30days") {
    $credit_sale_type="30days";
    $credit_sale_type_text="30 days";
    // code...
  }else{
    $credit_sale_type="5days";
    $credit_sale_type_text="5 days(special)";

  }
 ?>
  <nav class="navbar navbar-expand-lg navbar-light bg-white flex-row border-bottom shadow">
        <div class="container-fluid">
          <a class="navbar-brand mx-lg-1 mr-0" href="dashboard.php">
            <img src="img/logo/<?=$get_company['logo']?>" class="img-fluid" alt="" style="width: 100px;">
          </a>
          <button class="navbar-toggler mt-2 mr-auto toggle-sidebar text-muted">
            <i class="fe fe-menu navbar-toggler-icon"></i>
          </button>
          <div class="navbar-slide bg-white ml-lg-4" id="navbarSupportedContent">
            <a href="#" class="btn toggle-sidebar d-lg-none text-muted ml-2 mt-3" data-toggle="toggle">
              <i class="fe fe-x"><span class="sr-only"></span></i>
            </a>
            <ul class="navbar-nav mr-auto">
              <li class="nav-item dropdown">
                <a href="dashboard.php"  class="nav-link">
                  <span class="ml-lg-2">Dashboard</span>
                </a>
    
              </li>


           
        <?php       
        if ($UserData['user_role']!='admin') {
          # code...
        

        $getNav = mysqli_query($dbc,"SELECT * FROM menus where parent_id=0 AND page!='dashboard.php' ORDER BY sort_order ASC LIMIT 9 OFFSET 0 ");    $r=1;
          while ($fetch_nav=mysqli_fetch_assoc($getNav)) {   $c=0;
             $getChild = mysqli_query($dbc,"SELECT * FROM menus where parent_id='".$fetch_nav['id']."' AND page!='dashboard.php' ");
                while ($child=mysqli_fetch_assoc($getChild)) {
                    if (countWhens($dbc,"privileges",'user_id',$user_id_current,'nav_id',$child['id'])>0) {
                                        $c++;
                                         }}
                      if ($c>0 && $r<9) {
                                    ?>
                <li class="nav-item dropdown">
                <a href="<?=$fetch_nav['page']?>" id="ui-elementsDropdown" class="dropdown-toggle nav-link" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="ml-lg-2"><?=strtoupper($fetch_nav['title'])?></span>
                </a>
                 <?php 
                    if (countWhen($dbc,"menus",'parent_id',$fetch_nav['id'])>0) {
                                 ?>
                        <div class="dropdown-menu" aria-labelledby="ui-elementsDropdown">
                                    <?php 
                                    $getChild = mysqli_query($dbc,"SELECT * FROM menus where parent_id='".$fetch_nav['id']."' AND page!='dashboard.php' ");
                                 while ($child=mysqli_fetch_assoc($getChild)) {
                                     if (countWhens($dbc,"privileges",'user_id',$user_id_current,'nav_id',$child['id'])>0) {
                                     ?>
                                      <a class="nav-link pl-lg-2" href="<?=$child['page']?>"><span class="ml-1"><?=strtoupper($child['title'])?></span></a>
                                     
                                    <?php } 
                                  }//end while child ?>
                                  </div>       


                              <?php     }//check statement ?>
                            </li>


                               <?php   $r++;
                               } }if ($r>9) {
                                 
                                ?>
                                 <li class="nav-item dropdown more">
                <a class="dropdown-toggle more-horizontal nav-link" href="#" id="moreDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="ml-2 sr-only">More</span>
                </a>
                <ul class="dropdown-menu" aria-labelledby="moreDropdown">
                  <?php     $getNav2 = mysqli_query($dbc,"SELECT * FROM menus where parent_id=0 AND page!='dashboard.php' ORDER BY sort_order ASC LIMIT 10 OFFSET 10 ");    $r=1;
                                 while ($fetch_nav2=mysqli_fetch_assoc($getNav2)) { 
                                        $c=0;
                                           $getChild = mysqli_query($dbc,"SELECT * FROM menus where parent_id='".$fetch_nav2['id']."' AND page!='dashboard.php' ");
                while ($child=mysqli_fetch_assoc($getChild)) {
                    if (countWhens($dbc,"privileges",'user_id',$user_id_current,'nav_id',$child['id'])>0) {
                                        $c++;
                                         }}
                                         if ($c>0) {
                                           # code...
                                         
                                         ?>
                    <li class="nav-item dropdown">
                    <a class="dropdown-toggle nav-link pl-lg-2" href="#" data-toggle="collapse" id="pagesDropdown" aria-expanded="false">
                      <span class="ml-1"><?=strtoupper($fetch_nav2['title'])?></span>
                    </a>
                                          
                                        <ul class="dropdown-menu" aria-labelledby="pagesDropdown">
                                            <?php 
                                    $getChild = mysqli_query($dbc,"SELECT * FROM menus where parent_id='".$fetch_nav2['id']."' AND page!='dashboard.php' ");
                                 while ($child=mysqli_fetch_assoc($getChild)) {
                                     if (countWhens($dbc,"privileges",'user_id',$user_id_current,'nav_id',$child['id'])>0) {
                                     ?>
                                         
                                          <a class="nav-link pl-lg-2" href="<?=$child['page']?>">
                        <span class="ml-1"><?=strtoupper($child['title'])?></span>
                      </a>
                                        <?php }} ?>  
                                         
                                        </ul>
                                      </li>
                                      <?php  } } ?>
                                    </ul>
                                  </li>
                             <?php  }
                               //end of fetch nav
}else{ /*user validation*/
    # code...
        

        $getNav = mysqli_query($dbc,"SELECT * FROM menus where parent_id=0 AND page!='dashboard.php' ORDER BY sort_order ASC LIMIT 9 OFFSET 0 ");    $r=1;
          while ($fetch_nav=mysqli_fetch_assoc($getNav)) {   $c=0;
             $getChild = mysqli_query($dbc,"SELECT * FROM menus where parent_id='".$fetch_nav['id']."' AND page!='dashboard.php' ");
                while ($child=mysqli_fetch_assoc($getChild)) {
                                        $c++;
                                         
                                       }
                      if ($c>0 && $r<10) {
                                    ?>
                <li class="nav-item dropdown">
                <a href="<?=$fetch_nav['page']?>" id="ui-elementsDropdown" class="dropdown-toggle nav-link" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="ml-lg-2"><?=strtoupper($fetch_nav['title'])?></span>
                </a>
                 <?php 
                    if (countWhen($dbc,"menus",'parent_id',$fetch_nav['id'])>0) {
                                 ?>
                        <div class="dropdown-menu" aria-labelledby="ui-elementsDropdown">
                                    <?php 
                                    $getChild = mysqli_query($dbc,"SELECT * FROM menus where parent_id='".$fetch_nav['id']."' AND page!='dashboard.php' ");
                                 while ($child=mysqli_fetch_assoc($getChild)) {
                                  
                                     ?>
                                      <a class="nav-link pl-lg-2" href="<?=$child['page']?>"><span class="ml-1"><?=strtoupper($child['title'])?></span></a>
                                     
                                    <?php 
                                  }//end while child ?>
                                  </div>       


                              <?php     }//check statement ?>
                            </li>


                               <?php   $r++;
                               } }if ($r>9) {
                                 
                                ?>
                                 <li class="nav-item dropdown more">
                <a class="dropdown-toggle more-horizontal nav-link" href="#" id="moreDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="ml-2 sr-only">More</span>
                </a>
                <ul class="dropdown-menu" aria-labelledby="moreDropdown">
                  <?php     $getNav2 = mysqli_query($dbc,"SELECT * FROM menus where parent_id=0 AND page!='dashboard.php' ORDER BY sort_order ASC LIMIT 10 OFFSET 10 ");    $r=1;
                                 while ($fetch_nav2=mysqli_fetch_assoc($getNav2)) {
                                        $c=0; ?>
                    <li class="nav-item dropdown">
                    <a class="dropdown-toggle nav-link pl-lg-2" href="#" data-toggle="collapse" id="pagesDropdown" aria-expanded="false">
                      <span class="ml-1"><?=strtoupper($fetch_nav2['title'])?></span>
                    </a>
                                          
                                        <ul class="dropdown-menu" aria-labelledby="pagesDropdown">
                                            <?php 
                                    $getChild = mysqli_query($dbc,"SELECT * FROM menus where parent_id='".$fetch_nav2['id']."' AND page!='dashboard.php' ");
                                 while ($child=mysqli_fetch_assoc($getChild)) {

                                     ?>
                                         
                                          <a class="nav-link pl-lg-2" href="<?=$child['page']?>">
                        <span class="ml-1"><?=strtoupper($child['title'])?></span>
                      </a>
                                        <?php } ?>  
                                         
                                        </ul>
                                      </li>
                                      <?php   } ?>
                                    </ul>
                                  </li>
                             <?php  }
}

                             ?>
          
              
            </ul>
          </div>
                   <ul class="navbar-nav d-flex flex-row">
            <li class="nav-item">
              <a class="nav-link text-muted my-2" href="./#" id="modeSwitcher" data-mode="light">
                <i class="fe fe-sun fe-16"></i>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-muted my-2" href="./#" data-toggle="modal" data-target=".modal-shortcut">
                <i class="fe fe-grid fe-16"></i>
              </a>
            </li>
            <li class="nav-item nav-notif">
              <a class="nav-link text-muted my-2" href="./#" data-toggle="modal" data-target=".modal-notif">
                <i class="fe fe-bell fe-16"></i>
                <span class="dot dot-md bg-success"></span>
              </a>
            </li>
            <li class="nav-item dropdown ml-lg-0">
              <a class="nav-link dropdown-toggle text-muted" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="avatar avatar-sm mt-2">
                  <img src="img/logo/user.png" alt="..." class="avatar-img rounded-circle">
                </span>
              </a>
              <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                <li class="nav-item">
                  <a class="nav-link pl-3" href="setting.php">Profile</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link pl-3" href="logout.php">Logut</a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
     </nav> 