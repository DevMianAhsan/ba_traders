<?php
include_once 'includes/head.php';
	if(ISSET($_REQUEST['id'])){
		$fetchproduct=fetchRecord($dbc,"product", "product_id",base64_decode($_REQUEST['id']));
	
echo "<div class='bg-white border border-dark' style='width:115px;'><img alt='testing' src='barcode.php?codetype=Code128&size=50&text=".$fetchproduct['product_code']."&print=false' class='img-fluid  '> <br><p class='text-center  border-bottom border-dark mb-0'>".$fetchproduct['product_code']."</p><p class='text-center p-0 m-0 border-top'>Price :".$fetchproduct['current_rate']."</p></div>";
		?>
<?php
	}
?>

<?php 	include_once 'includes/foot.php'; ?>