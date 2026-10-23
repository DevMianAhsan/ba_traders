<?php include_once "includes/head.php" 
   
     ?>
     <style type="text/css">
       tr>td{
        font-size: 20px;
        font-weight: bold;
       }
       tr>th{
        font-size: 22px;
        font-weight: bolder;
       }
     </style>
<center><h5>Stock List <?=date('D,d-m-Y')?></h5></center>
<?php if (isset($_REQUEST['category'])) {
    @$categoryFetched=fetchRecord($dbc,"categories","categories_id",$_REQUEST['category']);
  ?>
<center><h6>Category:<?=strtoupper($categoryFetched['categories_name'])?></h6></center>
<?php } ?>
<center>
<table  style="width: 95%;margin: 10px;background-color: #fff;font-size: 25px;text-align: center;" border="2" cellpadding="15px" cellspacing="5px">
  <tr>
    <th>Sr. No.</th>
    <th>Code</th>
    <th>Name</th>
    <th>Category</th>
    <th>Stock</th>
    <?php if (isset($_REQUEST['type']) AND $_REQUEST['type']=="amount"): ?>
    <th>Purchase Rate</th>
    <th>Rate</th>
    <th>15 Days Rate</th>
    <th>30 Days Rate</th>

    <?php endif ?>

  </tr>

<tr>
 <?php 

 if (isset($_REQUEST['category'])) {
//    $query=mysqli_query($dbc,"SELECT product_name,category_id,product_code,quantity_instock, BIN(product_code) AS binray_not_needed_column
// FROM product WHERE category_id='".$_REQUEST['category']."'
// ORDER BY binray_not_needed_column ASC  ");
  if(@$_REQUEST['stock']=='0'){
     $qbc = "SELECT * FROM product WHERE inventory=0 AND category_id = '".$_REQUEST['category']."' AND status = 1 AND quantity_instock >0 ORDER BY LENGTH(product_code) ASC, product_code ASC";

  }else{
  $qbc = "SELECT * FROM product WHERE inventory=0 AND category_id = '".$_REQUEST['category']."' AND status = 1 ORDER BY LENGTH(product_code) ASC, product_code ASC";
}
//echo $qbc; 
  $query = mysqli_query($dbc,$qbc);


  //$query=mysqli_query($dbc,"SELECT product_name,category_id,product_code,quantity_instock,current_rate,f_days,t_days ,TRIM('".$categoryFetched['categories_name']."' FROM `product_code`) FROM product WHERE status=1 AND category_id='".$_REQUEST['category']."'  ");

 }else{
  $query=mysqli_query($dbc,"SELECT * FROM product WHERE status=1 AND inventory=0 ORDER BY product_name ASC ");
}
    $c=0;
    //echo "SELECT product_name,category_id,product_code,quantity_instock ,TRIM('".$categoryFetched['categories_name']."' FROM `product_code`) FROM product WHERE status=1 AND category_id='".$_REQUEST['category']."'  ";
    $finaltotal = 0;
    while($r=mysqli_fetch_assoc($query)):
    @$categoryFetched=fetchRecord($dbc,"categories","categories_id",$r['category_id']);

      $c++;
  ?>
  <td><?=$c?></td>
  <td><?=$r['product_code']?></td>
  <td><?=$r['product_name']?></td>
  <td><?=@strtoupper($categoryFetched['categories_name'])?></td>
  <td><?=$r['quantity_instock']?></td>
     <?php if (isset($_REQUEST['type']) AND $_REQUEST['type']=="amount"): ?>
     <td><?=$r['purchase_rate']?></td>
    <td><?=$r['current_rate']?></td>
    <td><?=$r['f_days']?></td>
    <td><?=$r['t_days']?></td>

    <?php endif;
    $finaltotal +=$r['quantity_instock'];
    $finalrate +=  $r['current_rate'];
    $fifdaysrate += $r['f_days'];
     $thirdaysrate += $r['f_days'];
     $totalpurchase +=$r['purchase_rate']; 
    

     ?>
  </tr>

<?php endwhile; ?>

<tr>
  <th colspan="4"><h3>Total </h3></th>
  <th><?=$finaltotal?></th>
  <th><?=number_format($totalpurchase)?></th>
  <th><?=number_format($finalrate)?></th>
  <th><?=number_format($fifdaysrate)?></th>
  <th><?=number_format($thirdaysrate)?></th>
</tr>

<tfoot>
    <th>Sr. No.</th>
    <th>Code</th>
    <th>Name</th>
    <th>Category</th>
    <th>Stock</th>
    <?php if (isset($_REQUEST['type']) AND $_REQUEST['type']=="amount"): ?>
    <th>purchase</th>
    <th>Rate</th>
    <th>15 Days Rate</th>
    <th>30 Days Rate</th>

    <?php endif ?>

  </tfoot>

</table></center>