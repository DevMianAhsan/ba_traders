<?php 
include_once 'db_connect.php';
require_once '../includes/functions.php';
	 /*Delete*/
 if (!empty($_REQUEST['delete_id'])) {
 	# code...
 	$id=$_REQUEST['delete_id'];
 	$table=$_REQUEST['table'];
 	$fld=$_REQUEST['fld'];

 	if ($table == "brands") {
 		$check = mysqli_query($dbc, "SELECT product_id FROM product WHERE brand_id='$id' AND status=1");
 		if (mysqli_num_rows($check) > 0) {
 			$count = mysqli_num_rows($check);
 			echo json_encode(['msg'=>"Cannot delete: This Brand is assigned to $count active product(s)!", "sts"=>"danger"]);
 			exit();
 		}
 	}
 	if ($table == "categories") {
 		$check = mysqli_query($dbc, "SELECT product_id FROM product WHERE category_id='$id' AND status=1");
 		if (mysqli_num_rows($check) > 0) {
 			$count = mysqli_num_rows($check);
 			echo json_encode(['msg'=>"Cannot delete: This Category is assigned to $count active product(s)!", "sts"=>"danger"]);
 			exit();
 		}
 	}

 	if(mysqli_query($dbc,"DELETE FROM $table WHERE $fld='$id'")){
 		$msg = "Data Has been deleted...";
 		$sts ="success";
 	}else{
 			$msg = mysqli_error($dbc);
 			$sts = "danger";
 	}
 	echo json_encode(['msg'=>$msg,"sts"=>$sts]);
 	exit();
 }
 if (isset($_REQUEST['delete_bymanually'])) {
 	# code...
 	$id=$_REQUEST['delete_bymanually'];
 	$table=$_REQUEST['table'];
 	$row=$_REQUEST['row'];

 	if($table == "tax_sales"){
 		$tax_sale = fetchRecord($dbc, "tax_sales", "tax_sale_id", $id);
 		if (!empty($tax_sale['transaction_id'])) {
 			@deleteFromTable($dbc, "transactions", 'transaction_id', $tax_sale['transaction_id']);
 		}
  		mysqli_query($dbc, "DELETE FROM tax_sale_items WHERE tax_sale_id='$id'");
  		if(mysqli_query($dbc, "DELETE FROM tax_sales WHERE tax_sale_id='$id'")){
  			$msg = "Tax Sale Invoice deleted successfully";
  			$sts = "success";
  		}else{
  			$msg = mysqli_error($dbc);
  			$sts = "error";
  		}
  	}elseif($table == "tax_purchases"){
  		$tax_pur = fetchRecord($dbc, "tax_purchases", "tax_purchase_id", $id);
  		if (!empty($tax_pur['transaction_id'])) {
  			@deleteFromTable($dbc, "transactions", 'transaction_id', $tax_pur['transaction_id']);
  		}
  		mysqli_query($dbc, "DELETE FROM tax_purchase_items WHERE tax_purchase_id='$id'");
  		if(mysqli_query($dbc, "DELETE FROM tax_purchases WHERE tax_purchase_id='$id'")){
  			$msg = "Tax Purchase Invoice deleted successfully";
  			$sts = "success";
  		}else{
  			$msg = mysqli_error($dbc);
  			$sts = "error";
  		}
  	}elseif($table == "invoices"){
 		$invoices=fetchRecord($dbc,"invoices", "invoice_id", $id);
 		@deleteFromTable($dbc,"transactions",'transaction_id',$invoices['transaction_id']);
 		if(mysqli_query($dbc,"DELETE FROM invoices WHERE invoice_id='$id'")){
 			$msg = "Data Has been deleted...";
 			$sts ="success";
 		}else{
 			$msg = mysqli_error($dbc);
 			$sts = "error";
 		}
 	}

 	
 	if ($table=="vouchers") {
 		$vouchers=fetchRecord($dbc,"vouchers", "voucher_id", $id);
 		@deleteFromTable($dbc,"transactions",'transaction_id',$vouchers['transaction_id1']);
 		@deleteFromTable($dbc,"transactions",'transaction_id',$vouchers['transaction_id2']);
 		@deleteFromTable($dbc,"checks",'voucher_id',$id);
 		@deleteFromTable($dbc,"voucher_invoices",'voucher_id',$id);
 		if(mysqli_query($dbc,"DELETE FROM vouchers WHERE voucher_id='$id'")){
 			$msg = "Data Has been deleted...";
 			$sts ="success";
 		}else{
 			$msg = mysqli_error($dbc);
 			$sts = "error";
 		}

 	}elseif ($table=="orders") {
 		$orders=fetchRecord($dbc,'orders', $row, $id);
 		$get_company =mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM company ORDER BY id DESC LIMIT 1"));

 		if ($get_company['stock_manage']==1) {
						$proQ=get($dbc,"order_item WHERE order_id='".$id."' ");

						while ($proR=mysqli_fetch_assoc($proQ)) {
							$newqty=0;
							$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$proR['product_id']."' "));
							$newqty=(int)$quantity_instock['quantity_instock']+(int)$proR['quantity'];
							$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$newqty' WHERE product_id='".$proR['product_id']."' ");
					

						}
					}
 		deleteFromTable($dbc,"transactions",'transaction_id',$orders['transaction_paid_id']);
 		deleteFromTable($dbc,"transactions",'transaction_id',$orders['transaction_id']);
 		if(mysqli_query($dbc,"DELETE FROM orders WHERE $row='$id'")){
 			$msg = "Data Has been deleted...";
 			$sts ="success";
 		}else{
 			$msg = mysqli_error($dbc);
 			$sts = "error";
 		}

 	}elseif ($table=="purchase") {

 		$get_company =mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM company ORDER BY id DESC LIMIT 1"));

 		if ($get_company['stock_manage']==1) {
						$proQ=get($dbc,"purchase_item WHERE purchase_id='".$id."' ");

						while ($proR=mysqli_fetch_assoc($proQ)) {
							$newqty=0;
							$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$proR['product_id']."' "));
							$newqty=(int)$quantity_instock['quantity_instock']-(int)$proR['quantity'];
							$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$newqty' WHERE product_id='".$proR['product_id']."' ");
					

						}
					}
 		$vouchers=fetchRecord($dbc,'purchase', $row, $id);
 		@deleteFromTable($dbc,"transactions",'transaction_id',$vouchers['transaction_paid_id']);
 		@deleteFromTable($dbc,"transactions",'transaction_id',$vouchers['transaction_id']);
 		if(mysqli_query($dbc,"DELETE FROM purchase WHERE $row='$id'")){
 			$msg = "Data Has been deleted...";
 			$sts ="success";
 		}else{
 			$msg = mysqli_error($dbc);
 			$sts = "error";
 		}

 	}elseif ($table=="product") {
 		if(mysqli_query($dbc,"UPDATE product SET status=0 WHERE $row='$id'")){
 			$msg = "Product Has been deleted...";
 			$sts ="success";
 		}else{
 			$msg = mysqli_error($dbc);
 			$sts = "error";
 		}
 	}elseif ($table=="brands") {
 		$check = mysqli_query($dbc, "SELECT product_id FROM product WHERE brand_id='$id' AND status=1");
 		if (mysqli_num_rows($check) > 0) {
 			$count = mysqli_num_rows($check);
 			$msg = "Cannot delete: This Brand is assigned to $count active product(s)!";
 			$sts = "error";
 		} else {
 			if(mysqli_query($dbc,"DELETE FROM brands WHERE $row='$id'")){
 				$msg = "Brand has been deleted...";
 				$sts ="success";
 			}else{
 				$msg = mysqli_error($dbc);
 				$sts = "error";
 			}
 		}
 	}elseif ($table=="categories") {
 		$check = mysqli_query($dbc, "SELECT product_id FROM product WHERE category_id='$id' AND status=1");
 		if (mysqli_num_rows($check) > 0) {
 			$count = mysqli_num_rows($check);
 			$msg = "Cannot delete: This Category is assigned to $count active product(s)!";
 			$sts = "error";
 		} else {
 			if(mysqli_query($dbc,"DELETE FROM categories WHERE $row='$id'")){
 				$msg = "Category has been deleted...";
 				$sts ="success";
 			}else{
 				$msg = mysqli_error($dbc);
 				$sts = "error";
 			}
 		}
 	}else{
 		if(mysqli_query($dbc,"DELETE FROM $table WHERE $row='$id'")){
 			$msg = $table." Has been deleted...";
 			$sts ="success";
 		}else{
 			$msg = mysqli_error($dbc);
 			$sts = "error";
 		}
 	}


 	echo json_encode(['msg'=>$msg,"sts"=>$sts]);
 }
  ?>
