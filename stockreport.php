<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

<script type="text/javascript" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>

<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php';
        //include_once 'includes/code.php';
 ?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container">
          <div class="row ">
            <div class="col-12">

<div class="container">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>


<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/bbbootstrap/libraries@main/choices.min.css">
<script src="https://cdn.jsdelivr.net/gh/bbbootstrap/libraries@main/choices.min.js"></script>
<div class="row d-flex justify-content-center mt-100">
    <div class="col-md-6"> 
        <form class="form d-print-none" method="post" >
        <select id="choices-multiple-remove-button" placeholder="Select upto 10 category" multiple name="category[]">
           
            <!-- <option value="all">All</option> -->
            <?php
            $q = mysqli_query($dbc,"SELECT * FROM categories");
            while($r = mysqli_fetch_assoc($q)):
            ?>
            <option value="<?=$r['categories_id']?>"><?=$r['categories_name']?></option>

<?php
endwhile;
?>
        </select> 
        <input type="submit" name="done" class="btn btn-danger" value="Detail Report">
        <input type="submit" name="details" class="btn btn-danger" value="Simple Report">
        </form>
    </div>
</div>
</div>

<?php

if (isset($_REQUEST['done'])) {
    $total = 0;
    # code...
// print_r($_REQUEST['category']);
// foreach ($_REQUEST['category'] as $key => $value) {
//     echo $value;
//     # code...
// }


?>

<table  style="width: 95%;margin: 10px;background-color: #fff;font-size: 25px;text-align: center;" border="2" cellpadding="15px" cellspacing="5px">
    <tr>
        <th>Product Name</th>
        <th>Category Name</th>
        <th>Category Stock</th>
    </tr>

<?php
foreach ($_REQUEST['category'] as $key => $value) {
    //echo $value;

$qa = mysqli_query($dbc,"SELECT * FROM product WHERE category_id = '$value' AND status = 1");
//echo "SELECT * FROM product WHERE category_id = '$value' AND status = 0";
while($r = mysqli_fetch_assoc($qa)):
$name = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM categories WHERE categories_id = '$value'"))['categories_name'];


?>

    <tr >
        <td><?=ucfirst($r['product_name'])?></td>
        <td><?=$name?></td>
        <td><?=$r['quantity_instock']?></td>
    </tr>
<?php
$total = $total+$r['quantity_instock'];
endwhile;
}
?>
 <tr>
            <td colspan="2">Total</td>
            <td><h3><?=$total?></h3></td>
        </tr>
</table>


<?php
}elseif(@$_REQUEST['details']){

   ?>

<table  style="width: 95%;margin: 10px;background-color: #fff;font-size: 25px;text-align: center;" border="2" cellpadding="15px" cellspacing="5px">
    <tr>
       
        <th>Category Name</th>
        <th>Category Stock</th>
    </tr>

<?php
foreach ($_REQUEST['category'] as $key => $value) {
    //echo $value;

$qa = mysqli_query($dbc,"SELECT sum(quantity_instock) AS Total_Stock FROM product WHERE category_id = '$value' AND  status = 1 GROUP BY category_id ");

//echo "SELECT sum(quantity_instock) AS Total_Stock FROM product WHERE category_id = '$value' GROUP BY category_id ";
while($r = mysqli_fetch_assoc($qa)):
$name = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM categories WHERE categories_id = '$value'"))['categories_name'];


?>

    <tr>
       
        <td><?=ucfirst($name)?></td>
        <td><?=$r['Total_Stock']?></td>
    </tr>
<?php

$total = @$total+$r['Total_Stock'];
endwhile;
}
?>
        
        <tr>
            <td>Total</td>
            <td><h3><?=$total?></h3></td>
        </tr>

</table>

   <?php

}
?>
<style type="text/css">
    .mt-100 {
    margin-top: 100px
}

body {
    background: #00B4DB;
    background: -webkit-linear-gradient(to right, #0083B0, #00B4DB);
    background: linear-gradient(to right, #0083B0, #00B4DB);
    color: #514B64;
    min-height: 100vh
}
</style>

<script type="text/javascript">
    $(document).ready(function(){

var multipleCancelButton = new Choices('#choices-multiple-remove-button', {
removeItemButton: true,
maxItemCount:100,
searchResultLimit:100,
renderChoiceLimit:100
});


});
</script>

<?php

include_once "includes/foot.php";
?>