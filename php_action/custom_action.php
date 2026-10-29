<?php 
	require_once '../php_action/db_connect.php';
	require_once '../includes/functions.php';


//print_r($_REQUEST);
	/*invocie*/
//print_r($_REQUEST);
//if(isset($_REQUEST['page_type'] == 'invoice')){
	if (isset($_REQUEST['page_type']) && $_REQUEST['page_type'] == 'invoice') {
		$res = ['data' => []];
		if (!empty($_REQUEST['dc_no'])) {
			for ($i = 0; $i < count($_REQUEST['dc_no']); $i++) { 
				$res['data'][$i] = [
					'sr' => $i,
					'party_po' => is_array($_REQUEST['party_po']) ? ($_REQUEST['party_po'][$i] ?? $_REQUEST['party_po'][0]) : $_REQUEST['party_po'],
					'dc_no' => $_REQUEST['dc_no'][$i],
					'po_amount' => $_REQUEST['po_amount'][$i]
				];
			}
		}

		$party_po_val = '';
		if (!empty($_REQUEST['party_po'])) {
			$party_po_val = is_array($_REQUEST['party_po']) ? $_REQUEST['party_po'][0] : $_REQUEST['party_po'];
		}

		// Fetch po_paymentdays from purchase_order table for this party_po
		$credit_sale_type = '30days';
		if (!empty($party_po_val)) {
			$po_party_clean = mysqli_real_escape_string($dbc, $party_po_val);
			$po_q = mysqli_query($dbc, "SELECT po_paymentdays FROM purchase_order WHERE po_partypo = '$po_party_clean' LIMIT 1");
			if ($po_row = mysqli_fetch_assoc($po_q)) {
				$days_num = intval(filter_var($po_row['po_paymentdays'], FILTER_SANITIZE_NUMBER_INT));
				if ($days_num > 0) {
					$credit_sale_type = $days_num . 'days';
				}
			}
		} elseif (!empty($_REQUEST['credit_sale_type'])) {
			$credit_sale_type = $_REQUEST['credit_sale_type'];
		}

		$sub_total = 0;
		if (!empty($_REQUEST['po_amount'])) {
			foreach ($_REQUEST['po_amount'] as $amt) {
				$sub_total += floatval($amt);
			}
		}

		$discount_val = isset($_REQUEST['ordered_discount']) ? $_REQUEST['ordered_discount'] : 0;
		$freight_val = isset($_REQUEST['freight']) ? $_REQUEST['freight'] : 0;
		$total_amount_val = !empty($_REQUEST['total_amount']) ? $_REQUEST['total_amount'] : $sub_total;
		$order_narration = !empty($_REQUEST['order_narration']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['order_narration'])) : '';

		if (!empty($_REQUEST['product_order_id'])) {
			$invoice_id = intval($_REQUEST['product_order_id']);
			$fetchInv = fetchRecord($dbc, 'invoices', 'invoice_id', $invoice_id);
			$transaction_id = !empty($fetchInv['transaction_id']) ? intval($fetchInv['transaction_id']) : 0;

			$data = [
				'customer_id' => $_REQUEST['customer_account'],
				'invoice_date' => $_REQUEST['order_date'],
				'total_amount' => $total_amount_val,
				'discount' => $discount_val,
				'freight' => $freight_val,
				'grand_total' => $_REQUEST['grand_total'],
				'order_narration' => $order_narration,
				'user_id' => @$_SESSION['userId'],
				'party_po' => $party_po_val,
				'credit_sale_type' => $credit_sale_type,
				'invoice_details' => json_encode($res),
				'transaction_id' => $transaction_id
			];

			if (update_data($dbc, 'invoices', $data, 'invoice_id', $invoice_id)) {
				// Sync payment days to Delivery Challans (orders)
				if (!empty($_REQUEST['dc_no']) && is_array($_REQUEST['dc_no'])) {
					foreach ($_REQUEST['dc_no'] as $dc_id) {
						$dc_id = intval($dc_id);
						if ($dc_id > 0) {
							mysqli_query($dbc, "UPDATE orders SET credit_sale_type = '$credit_sale_type' WHERE order_id = '$dc_id'");
						}
					}
				}
				if (!empty($party_po_val)) {
					$po_party_clean = mysqli_real_escape_string($dbc, $party_po_val);
					mysqli_query($dbc, "UPDATE orders SET credit_sale_type = '$credit_sale_type' WHERE party_po = '$po_party_clean'");
				}

				$data = [
					'sts' => 'success',
					'msg' => 'Invoice Updated successfully',
					'order_id' => $invoice_id,
					'type' => 'invoice'
				];
			} else {
				$data = [
					'sts' => 'error',
					'msg' => mysqli_error($dbc),
				];
			}
		} else {
			// Commercial invoice adds record only (no transaction created)
			$data = [
				'customer_id' => $_REQUEST['customer_account'],
				'invoice_date' => $_REQUEST['order_date'],
				'total_amount' => $total_amount_val,
				'discount' => $discount_val,
				'freight' => $freight_val,
				'grand_total' => $_REQUEST['grand_total'],
				'order_narration' => $order_narration,
				'user_id' => @$_SESSION['userId'],
				'party_po' => $party_po_val,
				'credit_sale_type' => $credit_sale_type,
				'invoice_details' => json_encode($res),
				'transaction_id' => 0
			];

			if (insert_data($dbc, 'invoices', $data)) {
				$invoice_id = mysqli_insert_id($dbc);

				// Sync payment days to Delivery Challans (orders)
				if (!empty($_REQUEST['dc_no']) && is_array($_REQUEST['dc_no'])) {
					foreach ($_REQUEST['dc_no'] as $dc_id) {
						$dc_id = intval($dc_id);
						if ($dc_id > 0) {
							mysqli_query($dbc, "UPDATE orders SET credit_sale_type = '$credit_sale_type' WHERE order_id = '$dc_id'");
						}
					}
				}
				if (!empty($party_po_val)) {
					$po_party_clean = mysqli_real_escape_string($dbc, $party_po_val);
					mysqli_query($dbc, "UPDATE orders SET credit_sale_type = '$credit_sale_type' WHERE party_po = '$po_party_clean'");
				}

				$data = [
					'sts' => 'success',
					'msg' => 'Invoice Added successfully',
					'order_id' => $invoice_id,
					'type' => 'invoice'
				];
			} else {
				$data = [
					'sts' => 'error',
					'msg' => mysqli_error($dbc),
				];
			}
		}

		echo json_encode($data);
	}

	/*Invocie end*/
	/*parrtypo detil for invice*/
	if (isset($_REQUEST['getpodinvoice'])) {
		$party_po = $_REQUEST['party_po'];
		if(!empty($party_po)){

			$q = mysqli_query($dbc,"SELECT party_po, grand_total as totalamount , order_id as dc_no FROM orders WHERE party_po = '$party_po'  ");
			//echo mysqli_num_rows($q); 
			
			if(mysqli_num_rows($q) > 0){
			while($r4 = mysqli_fetch_assoc($q)):
				$data[] = $r4;

			endwhile;
				
			$data = [
				'sts' => 'success',
				'msg' => 'Get Data',
				'data' => $data
				
			];
		}else{
			$data = [
				'sts' => 'false',
				'msg' => 'DC Not Found',
				'data' => ''
			];
		}
			echo json_encode($data);
		

		}
	}


	/*po iunvocie end*/

	/*Get getproD*/
	if(isset($_REQUEST['getproD'])){
		$pid = $_REQUEST['pro_id']; 
		//print_r($_REQUEST);
		$getpro = fetchRecord($dbc,"product", "product_id", $_REQUEST['pro_id']);
		if($getpro){
			$res = $getpro;
		}else{
			$res = [
				'sts' => 'false',
				'msg' => 'Error'
			];
		}
		echo json_encode($res);
	}


	/*check party po*/
	if(isset($_REQUEST['findColumn'])){
		$column = $_REQUEST['findColumn'];
		$value = $_REQUEST['value'];
		$q = mysqli_query($dbc,"SELECT $column FROM purchase_order WHERE $column = '$value' ");
		if(mysqli_num_rows($q)>0){
			$res = [
				'sts' => 'false',
				'msg' => 'Already Assigned'
			];
		}else{
			$res = [
				'sts' => 'true',
				'msg' => 'ok'
			];
		}
		echo json_encode($res);
	}


	/*pARTRY PO LIST*/
	
		if(isset($_REQUEST['getpoDetails'])){
			$excludeOrder = "";
			if (!empty($_REQUEST['order_id'])) {
				$excludeOrder = " AND o.order_id != '".mysqli_real_escape_string($dbc, $_REQUEST['order_id'])."' ";
			}
			$party_po_clean = mysqli_real_escape_string($dbc, $_REQUEST['party_po']);
			$cust_filter = "";
			if (!empty($_REQUEST['customer_id'])) {
				$cust_clean = mysqli_real_escape_string($dbc, $_REQUEST['customer_id']);
				$cust_filter = " AND p.po_customer = '$cust_clean' ";
			}

			$fQuery = "SELECT
    poi.product_id,
    product.product_name,
    poi.quantity AS total_ordered_quantity,
    SUM(COALESCE(oitem.quantity, 0)) AS total_delivered_quantity
FROM
    purchase_order p
INNER JOIN
    purchase_order_item poi ON p.po_id = poi.po_id
LEFT JOIN
    orders o ON p.po_partypo = o.party_po AND o.customer_account = p.po_customer {$excludeOrder}
LEFT JOIN
    order_item oitem ON o.order_id = oitem.order_id AND poi.product_id = oitem.product_id
LEFT JOIN 
    product ON product.product_id = poi.product_id
WHERE
    p.po_partypo = '".$party_po_clean."' {$cust_filter}
GROUP BY
    poi.product_id, product.product_name, poi.quantity, poi.po_id
";
		$q = mysqli_query($dbc,$fQuery);
		if($q && mysqli_num_rows($q)>0){
		while ($r = mysqli_fetch_assoc($q)) {
			?>
			<tr>
				<td><?=$r['product_id']?></td>
				<td><?=$r['product_name']?></td>
				<td><?=$r['total_ordered_quantity']?></td>
				<td><?=$r['total_delivered_quantity']?></td>
				<td><?=$r['total_ordered_quantity']-$r['total_delivered_quantity']?></td>
			</tr>

			<?php
		}
	}else{
		echo '<tr><td colspan="5" class="text-center text-danger">No PO Details Found</td></tr>';
	}
	exit;
	}


	if(isset($_REQUEST['get_party_po'])){
		$customer_id = !empty($_REQUEST['customer_id']) ? mysqli_real_escape_string($dbc, $_REQUEST['customer_id']) : '';
		if (empty($customer_id)) {
			echo '<option value="">Select Customer first</option>';
			exit;
		}

		$is_invoice = (isset($_REQUEST['page_type']) && $_REQUEST['page_type'] == 'invoice');

		if ($is_invoice) {
			$q = mysqli_query($dbc, "
				SELECT p.*
				FROM purchase_order p
				WHERE p.po_customer = '$customer_id'
				AND NOT EXISTS (
					SELECT 1
					FROM invoices i
					WHERE i.party_po = p.po_partypo
					AND i.customer_id = p.po_customer
				)
				ORDER BY p.po_id DESC
			");
		} else {
			$q = mysqli_query($dbc, "
				SELECT p.*
				FROM purchase_order p
				WHERE p.po_customer = '$customer_id'
				ORDER BY p.po_id DESC
			");
		}

		if($q && mysqli_num_rows($q) > 0){
			$emitted = [];
			while ($r = mysqli_fetch_assoc($q)) {
				$po = trim($r['po_partypo']);
				if ($po === '' || in_array($po, $emitted)) {
					continue;
				}
				$emitted[] = $po;
				echo '<option data-paymentdays="'.$r['po_paymentdays'].'" value="'.$po.'">'.$po.'</option>';
			}
			if (empty($emitted)) {
				echo '<option value="">Not Found</option>';
			}
		}else{
			echo '<option value="">Not Found</option>';
		}
		exit;
	}

	if (isset($_REQUEST['get_tax_sale_po_products'])) {
		$party_po = !empty($_REQUEST['party_po']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['party_po'])) : '';
		$customer_id = !empty($_REQUEST['customer_id']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['customer_id'])) : '';

		$cust_filter = "";
		if (!empty($customer_id)) {
			$cust_filter = " AND p.po_customer = '$customer_id' ";
		}

		$q = mysqli_query($dbc, "
			SELECT 
				poi.product_id,
				poi.quantity AS ordered_qty,
				poi.rate AS po_rate,
				prod.product_name,
				prod.hs_code,
				prod.current_rate,
				prod.product_description,
				prod.product_unit
			FROM purchase_order p
			INNER JOIN purchase_order_item poi ON p.po_id = poi.po_id
			INNER JOIN product prod ON poi.product_id = prod.product_id
			WHERE p.po_partypo = '$party_po' {$cust_filter}
			ORDER BY prod.product_name ASC
		");

		$products = [];
		$seen = [];
		if ($q && mysqli_num_rows($q) > 0) {
			while ($r = mysqli_fetch_assoc($q)) {
				$pid = $r['product_id'];
				if (isset($seen[$pid])) {
					continue;
				}
				$seen[$pid] = true;

				$effective_rate = floatval($r['po_rate']) > 0 ? floatval($r['po_rate']) : floatval($r['current_rate']);
				$products[] = [
					'product_id' => $r['product_id'],
					'product_name' => $r['product_name'],
					'hs_code' => !empty($r['hs_code']) ? $r['hs_code'] : '',
					'rate' => $effective_rate,
					'po_rate' => floatval($r['po_rate']),
					'current_rate' => floatval($r['current_rate']),
					'ordered_qty' => floatval($r['ordered_qty']),
					'unit' => 'KG',
					'description' => !empty($r['product_description']) ? $r['product_description'] : $r['product_name']
				];
			}
		}

		echo json_encode(['sts' => 'success', 'data' => $products]);
		exit;
	}

	if (isset($_REQUEST['get_customer_invoices'])) {
		$customer_id = mysqli_real_escape_string($dbc, $_REQUEST['get_customer_invoices']);
		$exclude_voucher = !empty($_REQUEST['voucher_id']) ? intval($_REQUEST['voucher_id']) : 0;

		$invoices = [];
		$other_invoices = [];

		if (!empty($customer_id) && $customer_id !== 'all') {
			$q = mysqli_query($dbc, "SELECT ts.*, c.customer_name FROM tax_sales ts LEFT JOIN customers c ON ts.customer_id = c.customer_id WHERE ts.customer_id = '$customer_id' ORDER BY ts.tax_sale_id DESC");
			while ($r = mysqli_fetch_assoc($q)) {
				$inv_id = !empty($r['invoice_no']) ? $r['invoice_no'] : $r['tax_sale_id'];
				$ts_id = $r['tax_sale_id'];
				$exclude_sql = $exclude_voucher > 0 ? " AND voucher_id != '$exclude_voucher'" : "";
				$q_paid = mysqli_query($dbc, "SELECT SUM(CAST(received_amount AS DECIMAL(15,2)) + CAST(COALESCE(tax_amount, 0) AS DECIMAL(15,2))) AS total_paid FROM voucher_invoices WHERE invoice_id = '$inv_id' $exclude_sql");
				$r_paid = mysqli_fetch_assoc($q_paid);
				$paid = !empty($r_paid['total_paid']) ? floatval($r_paid['total_paid']) : 0;
				$grand_total = floatval($r['grand_total']);
				$due = $grand_total - $paid;
				if ($due < 0) {
					$due = 0;
				}
				$invoices[] = [
					'invoice_id' => $inv_id,
					'tax_sale_id' => $ts_id,
					'customer_id' => $r['customer_id'],
					'customer_name' => $r['customer_name'],
					'party_po' => !empty($r['po_no']) ? $r['po_no'] : '',
					'invoice_date' => $r['invoice_date'],
					'grand_total' => $grand_total,
					'already_paid' => $paid,
					'due' => $due
				];
			}

		} else {
			$invoices = [];
		}
		echo json_encode(['sts' => 'success', 'data' => $invoices]);
		exit;
	}

	if (isset($_REQUEST['get_single_invoice_info'])) {
		$inv_id = mysqli_real_escape_string($dbc, $_REQUEST['get_single_invoice_info']);
		$exclude_voucher = !empty($_REQUEST['voucher_id']) ? intval($_REQUEST['voucher_id']) : 0;
		$q = mysqli_query($dbc, "SELECT ts.*, c.customer_name FROM tax_sales ts LEFT JOIN customers c ON ts.customer_id = c.customer_id WHERE ts.invoice_no = '$inv_id' OR ts.tax_sale_id = '$inv_id'");
		if ($r = mysqli_fetch_assoc($q)) {
			$inv_num = !empty($r['invoice_no']) ? $r['invoice_no'] : $r['tax_sale_id'];
			$ts_id = $r['tax_sale_id'];
			$exclude_sql = $exclude_voucher > 0 ? " AND voucher_id != '$exclude_voucher'" : "";
			$q_paid = mysqli_query($dbc, "SELECT SUM(CAST(received_amount AS DECIMAL(15,2)) + CAST(COALESCE(tax_amount, 0) AS DECIMAL(15,2))) AS total_paid FROM voucher_invoices WHERE invoice_id = '$inv_num' $exclude_sql");
			$r_paid = mysqli_fetch_assoc($q_paid);
			$paid = !empty($r_paid['total_paid']) ? floatval($r_paid['total_paid']) : 0;
			$grand_total = floatval($r['grand_total']);
			$due = $grand_total - $paid;
			if ($due < 0) $due = 0;
			echo json_encode([
				'sts' => 'success',
				'invoice_id' => $inv_num,
				'tax_sale_id' => $ts_id,
				'customer_id' => $r['customer_id'],
				'customer_name' => $r['customer_name'],
				'party_po' => !empty($r['po_no']) ? $r['po_no'] : '',
				'invoice_date' => $r['invoice_date'],
				'grand_total' => $grand_total,
				'already_paid' => $paid,
				'due' => $due
			]);
		} else {
			echo json_encode(['sts' => 'error', 'msg' => 'Invoice not found']);
		}
		exit;
	}



	/*Purchase Order*/

	 if (isset($_REQUEST['purchase_order_client_name'])) {
	 	 //print_r($_REQUEST);
	 	// exit();
 	$get_company =mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM company ORDER BY id DESC LIMIT 1"));
if (!empty($_REQUEST['product_ids'])) {
 		# code...
 	$total_ammount=$total_grand=0;

 	$data=[
 			'po_date'=> $_REQUEST['order_date'],	
			'po_customer'=> $_REQUEST['customer_account'],	
			'po_customer_name'=> $_REQUEST['purchase_order_client_name'],	
			'po_customer_no'=> $_REQUEST['client_contact'],	
			'po_partystm'=> @$_REQUEST['party_stm'] ?? '',
			'po_partypo'=>@$_REQUEST['party_po'],
			'po_remarks'=>@$_REQUEST['order_narration'],
			'po_paymentdays'=>@$_REQUEST['payment_days'],
			'po_targetdate'=>$_REQUEST['target_date'],
			'po_consumer'=>@$_REQUEST['party_consumer'] ?? '',
			'po_jobdesc'=>@$_REQUEST['jobcard_desc'] ?? '',
			
			'user_id'=>@$_REQUEST['voucher_no'],
			'freight' => @$_REQUEST['freight'],
			'discount' => @$_REQUEST['ordered_discount'],
		];
		//'payment_status'=>1,
		 if ($_REQUEST['product_order_id']=="") {
		 	
		 	if (insert_data($dbc,'purchase_order', $data)) {
		 		$last_id = mysqli_insert_id($dbc);
				$x=0;
				foreach ($_REQUEST['product_ids'] as $key => $value) {
					$total=$qty=0;
					$product_quantites=(double)$_REQUEST['product_quantites'][$x];
					$product_rates=(double)$_REQUEST['product_rates'][$x];
					$total=$product_quantites*$product_rates;
					$total_ammount+=(double)$total;
					$order_items=['product_id'=>$_REQUEST['product_ids'][$x],
						'rate'=>$product_rates,
						'total'=>$total,
						'po_id'=>$last_id,
						'quantity'=>$product_quantites,
						
					];
					
					//print_r($order_items);
					insert_data($dbc,'purchase_order_item', $order_items);

					$x++;
				}//end of foreach

				
			$total_grand=@(double)$_REQUEST['freight']+$total_ammount+$total_ammount*((double)$_REQUEST['ordered_discount']/100);

				
				$newOrder=[
					
					'grand_total'=> $total_grand,	
					
					];
		 	 if (update_data($dbc,'purchase_order', $newOrder , 'po_id',$last_id)) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				 $msg = "Purchase Order Has been Added";
					$sts = 'success';
					
				}else{
					$msg = mysqli_error($dbc);
					$sts ="danger";
				}
				
			}else{
				$msg =mysqli_error($dbc);
				$sts = "danger";
			}
		 }else{
		 		if (update_data($dbc,'purchase_order', $data,'po_id',$_REQUEST['product_order_id'])) {
				$last_id = $_REQUEST['product_order_id'];
				


				deleteFromTable($dbc,"purchase_order_item",'po_id',$_REQUEST['product_order_id']);
				
				$x=0;
				foreach ($_REQUEST['product_ids'] as $key => $value) {
					$total=$qty=0;
					$product_quantites=(double)$_REQUEST['product_quantites'][$x];
					$product_rates=(double)$_REQUEST['product_rates'][$x];
					$total=$product_quantites*$product_rates;
					$total_ammount+=(double)$total;
					$order_items=['product_id'=>$_REQUEST['product_ids'][$x],
						'rate'=>$product_rates,
						'total'=>$total,
						'po_id'=>$_REQUEST['product_order_id'],
						'quantity'=>$product_quantites,
						
					];
				
					insert_data($dbc,'purchase_order_item', $order_items);

					$x++;
				}//end of foreach
				
			
			$total_grand=@(double)$_REQUEST['freight']+$total_ammount+$total_ammount*((double)$_REQUEST['ordered_discount']/100);
 			
			$newOrder=[
					
					'grand_total'=> $total_grand,	
					
					];
			
				
		 	 if (update_data($dbc,'purchase_order', $newOrder , 'po_id',$_REQUEST['product_order_id'])) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				 $msg = "Data Has been Updated";
					$sts = 'success';
					
				}else{
					$msg = mysqli_error($dbc);
					$sts ="danger";
				}
				
			}else{
				$msg =mysqli_error($dbc);
				$sts = "danger";
			}

		 }
		 	}else{
		$msg = "Please Add Any Product";
		$sts = 'error';
	}
		 echo json_encode(['msg'=>$msg,'sts'=>$sts,'order_id'=>@$last_id,'type'=>"purchase_order",'subtype'=>$_REQUEST['payment_type']]);
 }


	/*Purchase Order End*/
if (isset($_REQUEST['add_manually_user'])) {
		$data=[
			'customer_name'=>@$_REQUEST['customer_name'],
			'customer_phone'=>@$_REQUEST['customer_phone'],
			'customer_email'=>@$_REQUEST['customer_email'],
			'customer_address'=>@$_REQUEST['customer_address'],
			'customer_strn'=>@$_REQUEST['customer_strn'],
			'customer_ntn'=>@$_REQUEST['customer_ntn'],
			'customer_province'=>@$_REQUEST['customer_province'],
			'customer_cnic'=>@$_REQUEST['customer_cnic'],
			'customer_status'=>@$_REQUEST['customer_status'],
			'customer_type'=>@$_REQUEST['add_manually_user'],
		];
	if ($_REQUEST['customer_id']=="") {

			if (insert_data($dbc, "customers", $data)) {

			
				$res=['msg'=>ucfirst($_REQUEST['add_manually_user'])." Added Successfully",'sts'=>'success'];

				}else{

					$res=['msg'=>mysqli_error($dbc),'sts'=>'error'];
				}

	}else{
				if (update_data($dbc,"customers", $data, "customer_id", $_REQUEST['customer_id'])) {

				
					$res=['msg'=>ucfirst($_REQUEST['add_manually_user'])." Updated Successfully",'sts'=>'success'];

				}else{

					$res=['msg'=>mysqli_error($dbc),'sts'=>'error'];
				}

	}
echo json_encode($res);
}

if (isset($_REQUEST['new_voucher_date'])) {
	if (isset($_REQUEST['voucher_debit'])) {
		$_REQUEST['voucher_debit'] = floatval(str_replace(',', '', $_REQUEST['voucher_debit']));
	}
		
	if ($_REQUEST['voucher_id']=="") {
				if ($_REQUEST['voucher_group']=="general_voucher") {
					$voucher_details = [];
					if (!empty($_REQUEST['invoice_id']) && is_array($_REQUEST['invoice_id'])) {
						foreach ($_REQUEST['invoice_id'] as $k => $inv_id) {
							$inv_id = trim($inv_id);
							$rec_amt = isset($_REQUEST['received_amount'][$k]) ? floatval(str_replace(',', '', $_REQUEST['received_amount'][$k])) : 0;
							$tax_amt = isset($_REQUEST['tax_amount'][$k]) ? floatval(str_replace(',', '', $_REQUEST['tax_amount'][$k])) : 0;
							$tax_pct = isset($_REQUEST['tax_percent'][$k]) ? floatval($_REQUEST['tax_percent'][$k]) : 0;
							if ($inv_id !== '' && ($rec_amt > 0 || $tax_amt > 0)) {
								$voucher_details[] = [
									'invoice_id' => $inv_id,
									'party_po' => isset($_REQUEST['inv_party_po'][$k]) ? trim($_REQUEST['inv_party_po'][$k]) : '',
									'invoice_amount' => isset($_REQUEST['invoice_amount'][$k]) ? floatval(str_replace(',', '', $_REQUEST['invoice_amount'][$k])) : 0,
									'tax_percent' => $tax_pct,
									'tax_amount' => $tax_amt,
									'received_amount' => $rec_amt,
								];
							}
						}
					}
					$voucher_details_json = !empty($voucher_details) ? json_encode($voucher_details) : null;

					$data=[
			'customer_id1'=>@$_REQUEST['voucher_from_account'],
			'customer_id2'=>@$_REQUEST['voucher_to_account'],
			'voucher_date'=>@$_REQUEST['new_voucher_date'],
			'voucher_hint'=>@$_REQUEST['voucher_hint'],
			'voucher_type'=>@$_REQUEST['voucher_type'],
			'voucher_amount'=>@$_REQUEST['voucher_debit'],
			'voucher_group'=>@$_REQUEST['voucher_group'],
			'td_check_no'=>@$_REQUEST['td_check_no'],
			'voucher_bank_name'=>@$_REQUEST['voucher_bank_name'],
			'td_check_date'=>@$_REQUEST['td_check_date'],
			'check_type'=>@$_REQUEST['check_type'],
			'voucher_details'=>$voucher_details_json,
			'addby_user_id'=>@$_SESSION['userId'],
			];
		}else{
		$data=[
			'customer_id1'=>@$_REQUEST['voucher_from_account'],
			'customer_id2'=>@$_REQUEST['voucher_to_account'],
			'voucher_date'=>@$_REQUEST['new_voucher_date'],
			'voucher_hint'=>@$_REQUEST['voucher_hint'],
			'voucher_type'=>@$_REQUEST['voucher_type'],
			'voucher_amount'=>@$_REQUEST['voucher_debit'],
			'voucher_group'=>@$_REQUEST['voucher_group'],
			'addby_user_id'=>@$_SESSION['userId'],
			];
		}
			if (insert_data($dbc, "vouchers", $data)) {
				$last_id=mysqli_insert_id($dbc);
				if ($_REQUEST['voucher_group']=="expense_voucher") {
				$voucher_to_account=fetchRecord($dbc,"customers", "customer_id", $_REQUEST['voucher_to_account']);
					$budget=[
					'budget_amount'=>@$_REQUEST['voucher_debit'],
					'budget_type'=>"expense",
					'budget_date'=>$_REQUEST['new_voucher_date'],
					'voucher_id'=>$last_id,
					'voucher_type'=>@$_REQUEST['voucher_type'],
					'budget_name'=>@"expense added to ".@$voucher_to_account['customer_name'],
				];
								insert_data($dbc, "budget", $budget);
				}elseif($_REQUEST['voucher_group']=="general_voucher" AND !empty($_REQUEST['td_check_no']) ){
						$data_checks=[
			'check_no'=>$_REQUEST['td_check_no'],
			'check_bank_name'=>$_REQUEST['voucher_bank_name'],
			'check_expiry_date'=>$_REQUEST['td_check_date'],
			'check_type'=>$_REQUEST['check_type'],
			'voucher_id'=>$last_id,
			'check_status'=>0,
			];
			insert_data($dbc, "checks", $data_checks);
			}

			if ($_REQUEST['voucher_group']=="general_voucher" && !empty($voucher_details)) {
				foreach ($voucher_details as $v_inv) {
					$inv_row = [
						'voucher_id' => $last_id,
						'invoice_id' => $v_inv['invoice_id'],
						'party_po' => $v_inv['party_po'],
						'invoice_amount' => $v_inv['invoice_amount'],
						'tax_percent' => isset($v_inv['tax_percent']) ? $v_inv['tax_percent'] : 0,
						'tax_amount' => isset($v_inv['tax_amount']) ? $v_inv['tax_amount'] : 0,
						'received_amount' => $v_inv['received_amount'],
					];
					insert_data($dbc, "voucher_invoices", $inv_row);
				}
			}


				$debit=[
					'debit'=>@$_REQUEST['voucher_debit'],
					'credit'=>0,
					'customer_id'=>@$_REQUEST['voucher_from_account'],
					'transaction_from'=>'voucher',
					'transaction_type'=>@$_REQUEST['voucher_type'],
					'transaction_remarks'=>@$_REQUEST['voucher_hint'],
					'transaction_date'=>@$_REQUEST['new_voucher_date'],
				];
				insert_data($dbc, "transactions", $debit);
				$transaction_id1=mysqli_insert_id($dbc);
				$credit=[
					'credit'=>@$_REQUEST['voucher_debit'],
					'debit'=>0,
					'customer_id'=>@$_REQUEST['voucher_to_account'],
					'transaction_from'=>'voucher',
					'transaction_type'=>@$_REQUEST['voucher_type'],
					'transaction_remarks'=>@$_REQUEST['voucher_hint'],
					'transaction_date'=>@$_REQUEST['new_voucher_date'],
				];

				insert_data($dbc, "transactions", $credit);
				$transaction_id2=mysqli_insert_id($dbc);
				$newData=['transaction_id1'=>$transaction_id1,'transaction_id2'=>$transaction_id2];
				if (update_data($dbc,"vouchers", $newData, "voucher_id", $last_id)) {
					$res=['msg'=>"Voucher Added Successfully",'sts'=>'success','voucher_id'=>base64_encode($last_id)];
				}else{ $res=['msg'=>mysqli_error($dbc),'sts'=>'error']; }

			}else{

					$res=['msg'=>mysqli_error($dbc),'sts'=>'error'];
			}

	}else{
	if ($_REQUEST['voucher_group']=="general_voucher") {
			$voucher_details = [];
			if (!empty($_REQUEST['invoice_id']) && is_array($_REQUEST['invoice_id'])) {
				foreach ($_REQUEST['invoice_id'] as $k => $inv_id) {
					$inv_id = trim($inv_id);
					$rec_amt = isset($_REQUEST['received_amount'][$k]) ? floatval(str_replace(',', '', $_REQUEST['received_amount'][$k])) : 0;
					$tax_amt = isset($_REQUEST['tax_amount'][$k]) ? floatval(str_replace(',', '', $_REQUEST['tax_amount'][$k])) : 0;
					$tax_pct = isset($_REQUEST['tax_percent'][$k]) ? floatval($_REQUEST['tax_percent'][$k]) : 0;
					if ($inv_id !== '' && ($rec_amt > 0 || $tax_amt > 0)) {
						$voucher_details[] = [
							'invoice_id' => $inv_id,
							'party_po' => isset($_REQUEST['inv_party_po'][$k]) ? trim($_REQUEST['inv_party_po'][$k]) : '',
							'invoice_amount' => isset($_REQUEST['invoice_amount'][$k]) ? floatval(str_replace(',', '', $_REQUEST['invoice_amount'][$k])) : 0,
							'tax_percent' => $tax_pct,
							'tax_amount' => $tax_amt,
							'received_amount' => $rec_amt,
						];
					}
				}
			}
			$voucher_details_json = !empty($voucher_details) ? json_encode($voucher_details) : null;

			$data=[
			'customer_id1'=>@$_REQUEST['voucher_from_account'],
			'customer_id2'=>@$_REQUEST['voucher_to_account'],
			'voucher_date'=>@$_REQUEST['new_voucher_date'],
			'voucher_hint'=>@$_REQUEST['voucher_hint'],
			'voucher_type'=>@$_REQUEST['voucher_type'],
			'voucher_amount'=>@$_REQUEST['voucher_debit'],
			'voucher_group'=>@$_REQUEST['voucher_group'],
			'editby_user_id'=>@$_SESSION['userId'],
			'td_check_no'=>@$_REQUEST['td_check_no'],
			'voucher_bank_name'=>@$_REQUEST['voucher_bank_name'],
			'check_type'=>@$_REQUEST['check_type'],
			'td_check_date'=>@$_REQUEST['td_check_date'],
			'voucher_details'=>$voucher_details_json,
		];		
	}else{
		$data=[
			'customer_id1'=>@$_REQUEST['voucher_from_account'],
			'customer_id2'=>@$_REQUEST['voucher_to_account'],
			'voucher_date'=>@$_REQUEST['new_voucher_date'],
			'voucher_hint'=>@$_REQUEST['voucher_hint'],
			'voucher_type'=>@$_REQUEST['voucher_type'],
			'voucher_amount'=>@$_REQUEST['voucher_debit'],
			'voucher_group'=>@$_REQUEST['voucher_group'],
			'editby_user_id'=>@$_SESSION['userId'],
		];	
	}
		if (update_data($dbc,"vouchers", $data, "voucher_id", $_REQUEST['voucher_id'])) {
						$last_id=$_REQUEST['voucher_id'];

				$transactions=fetchRecord($dbc,"vouchers", "voucher_id", $_REQUEST['voucher_id']);
				
	
			if ($_REQUEST['voucher_group']=="expense_voucher") {
				$voucher_to_account=fetchRecord($dbc,"customers", "customer_id", $_REQUEST['voucher_to_account']);
					$budget=[
					'budget_amount'=>@$_REQUEST['voucher_debit'],
					'budget_type'=>"expense",
					'budget_date'=>$_REQUEST['new_voucher_date'],
					'voucher_id'=>$last_id,
					'voucher_type'=>@$_REQUEST['voucher_type'],
					'budget_name'=>@"expense added to ".@$voucher_to_account['customer_name'],
				];
				
				update_data($dbc,"budget", $budget, "voucher_id", $_REQUEST['voucher_id']);
				
			}elseif($_REQUEST['voucher_group']=="general_voucher"){
						$data_checks=[
							'check_no'=>$_REQUEST['td_check_no'],
							'check_bank_name'=>$_REQUEST['voucher_bank_name'],
							'check_expiry_date'=>$_REQUEST['td_check_date'],
							'check_type'=>$_REQUEST['check_type'],
							'voucher_id'=>$last_id,
							];
			update_data($dbc, "checks", $data_checks, "voucher_id", $_REQUEST['voucher_id']);
			}

			if ($_REQUEST['voucher_group']=="general_voucher") {
				mysqli_query($dbc, "DELETE FROM voucher_invoices WHERE voucher_id = '".$_REQUEST['voucher_id']."'");
				if (!empty($voucher_details)) {
					foreach ($voucher_details as $v_inv) {
						$inv_row = [
							'voucher_id' => $_REQUEST['voucher_id'],
							'invoice_id' => $v_inv['invoice_id'],
							'party_po' => $v_inv['party_po'],
							'invoice_amount' => $v_inv['invoice_amount'],
							'tax_percent' => isset($v_inv['tax_percent']) ? $v_inv['tax_percent'] : 0,
							'tax_amount' => isset($v_inv['tax_amount']) ? $v_inv['tax_amount'] : 0,
							'received_amount' => $v_inv['received_amount'],
						];
						insert_data($dbc, "voucher_invoices", $inv_row);
					}
				}
			}

					$debit=[
					'debit'=>@$_REQUEST['voucher_debit'],
					'credit'=>0,
					'customer_id'=>@$_REQUEST['voucher_from_account'],
					'transaction_from'=>'voucher',
					'transaction_type'=>@$_REQUEST['voucher_type'],
					'transaction_remarks'=>@$_REQUEST['voucher_hint'],
					'transaction_date'=>@$_REQUEST['new_voucher_date'],
				];
			
				update_data($dbc,"transactions", $debit, "transaction_id", $transactions['transaction_id1']);
				
				$credit=[
					'credit'=>@$_REQUEST['voucher_debit'],
					'debit'=>0,
					'customer_id'=>@$_REQUEST['voucher_to_account'],
					'transaction_from'=>'voucher',
					'transaction_type'=>@$_REQUEST['voucher_type'],
					'transaction_remarks'=>@$_REQUEST['voucher_hint'],
					'transaction_date'=>@$_REQUEST['new_voucher_date'],
				];

				update_data($dbc,"transactions", $credit, "transaction_id", $transactions['transaction_id2']);
				
					$res=['msg'=>"Voucher Updated Successfully",'sts'=>'success','voucher_id'=>base64_encode($last_id)];

				}else{

					$res=['msg'=>mysqli_error($dbc),'sts'=>'error'];
				}

	}
echo json_encode($res);
}
if (isset($_REQUEST['new_sin_voucher_date'])) {
		if (!empty($_REQUEST['voucher_debit'])) {
			$amount=$_REQUEST['voucher_debit'];
		}else{
			$amount=$_REQUEST['voucher_credit'];
		}
	if ($_REQUEST['voucher_id']=="") {
		$data=[
			'customer_id1'=>@$_REQUEST['voucher_from_account'],
			'voucher_date'=>@$_REQUEST['new_sin_voucher_date'],
			'voucher_hint'=>@$_REQUEST['voucher_hint'],
			'voucher_amount'=>$amount,
			'voucher_group'=>@$_REQUEST['voucher_group'],
			'addby_user_id'=>@$_SESSION['userId'],
		];
			if (insert_data($dbc, "vouchers", $data)) {
				$last_id=mysqli_insert_id($dbc);
	
		if (!empty($_REQUEST['voucher_debit'])) {
			$debit=[
					'debit'=>$amount,
					'credit'=>0,
					'customer_id'=>@$_REQUEST['voucher_from_account'],
					'transaction_from'=>'voucher',
					'transaction_type'=>"single_voucher",
					'transaction_remarks'=>@$_REQUEST['voucher_hint'],
					'transaction_date'=>@$_REQUEST['new_sin_voucher_date'],
				];
				insert_data($dbc, "transactions", $debit);
		}else{
			$credit=[
					'credit'=>$amount,
					'debit'=>0,
					'customer_id'=>@$_REQUEST['voucher_from_account'],
					'transaction_from'=>'voucher',
					'transaction_type'=>"single_voucher",
					'transaction_remarks'=>@$_REQUEST['voucher_hint'],
					'transaction_date'=>@$_REQUEST['new_sin_voucher_date'],
				];
				insert_data($dbc, "transactions", $credit);
		}

				
				
				$transaction_id1=mysqli_insert_id($dbc);

				$newData=['transaction_id1'=>$transaction_id1];
				if (update_data($dbc,"vouchers", $newData, "voucher_id", $last_id)) {
					$res=['msg'=>"Voucher Added Successfully",'sts'=>'success','voucher_id'=>base64_encode($last_id)];
				}else{ $res=['msg'=>mysqli_error($dbc),'sts'=>'error']; }

			}else{

					$res=['msg'=>mysqli_error($dbc),'sts'=>'error'];
			}

	}else{
		$data=[
			'customer_id1'=>@$_REQUEST['voucher_from_account'],
			'voucher_date'=>@$_REQUEST['new_sin_voucher_date'],
			'voucher_hint'=>@$_REQUEST['voucher_hint'],
			'voucher_amount'=>$amount,
			'voucher_group'=>@$_REQUEST['voucher_group'],
			'editby_user_id'=>@$_SESSION['userId'],
		];
		
		if (update_data($dbc,"vouchers", $data, "voucher_id", $_REQUEST['voucher_id'])) {
						$last_id=$_REQUEST['voucher_id'];

				$transactions=fetchRecord($dbc,"vouchers", "voucher_id", $_REQUEST['voucher_id']);
				
				if (!empty($_REQUEST['voucher_debit'])) {
			$debit=[
					'debit'=>@$_REQUEST['voucher_debit'],
					'credit'=>0,
					'customer_id'=>@$_REQUEST['voucher_from_account'],
					'transaction_from'=>'voucher',
					'transaction_type'=>"single_voucher",
					'transaction_remarks'=>@$_REQUEST['voucher_hint'],
					'transaction_date'=>@$_REQUEST['new_sin_voucher_date'],
				];
				update_data($dbc,"transactions", $debit, "transaction_id", $transactions['transaction_id1']);
				
		}else{
			$credit=[
					'credit'=>@$_REQUEST['voucher_credit'],
					'debit'=>0,
					'customer_id'=>@$_REQUEST['voucher_from_account'],
					'transaction_from'=>'voucher',
					'transaction_type'=>"single_voucher",
					'transaction_remarks'=>@$_REQUEST['voucher_hint'],
					'transaction_date'=>@$_REQUEST['new_sin_voucher_date'],
				];
				update_data($dbc,"transactions", $credit, "transaction_id", $transactions['transaction_id1']);
			;
		}
		
				
					$res=['msg'=>"Voucher Updated Successfully",'sts'=>'success','voucher_id'=>base64_encode($last_id)];

				}else{

					$res=['msg'=>mysqli_error($dbc),'sts'=>'error'];
				}

	}
echo json_encode($res);
}
if(!empty($_REQUEST['action']) AND $_REQUEST['action']=="product_module"){
		$purchase_rate=$total=0;
		$category_price=fetchRecord($dbc,"categories","categories_id",$_REQUEST['category_id']);

		$total=(double)@$_REQUEST['product_mm']*(double)@$_REQUEST['product_inch']*(double)@$_REQUEST['product_meter'];
		if (isset($_REQUEST['purchase_rate']) && $_REQUEST['purchase_rate'] !== '') {
			$purchase_rate = (double)$_REQUEST['purchase_rate'];
		} else {
			$purchase_rate = ($total*(double)@$category_price['category_purchase'])/54;
			$purchase_rate = round($purchase_rate);
		}
					
		$data_array=[
 			'product_name'=>$_REQUEST['product_name'],
 			'product_name2'=>@$_REQUEST['product_name2'],
			'product_code'=>@$_REQUEST['product_code'],
			'hs_code'=>@$_REQUEST['hs_code'],
			'brand_id'=>@$_REQUEST['brand_id'],
			'category_id'=>@$_REQUEST['category_id'],
			'product_mm'=>@$_REQUEST['product_mm'],
			'product_inch'=>@$_REQUEST['product_inch'],
			'product_meter'=>@$_REQUEST['product_meter'],
			'current_rate'=>@$_REQUEST['current_rate'],
			'product_description'=>@$_REQUEST['product_description'],
			't_days'=>!empty($_REQUEST['t_days']) ? $_REQUEST['t_days'] : @$_REQUEST['current_rate'],
			'f_days'=>!empty($_REQUEST['f_days']) ? $_REQUEST['f_days'] : @$_REQUEST['current_rate'],
			'alert_at'=>@$_REQUEST['alert_at'],
			'availability'=>@$_REQUEST['availability'],
			'purchase_rate'=>$purchase_rate,
			'status'=>1,
			'product_unit'=>@$_REQUEST['product_unit'],
		];
		if ($_REQUEST['product_id']=="") {
		
		if(insert_data($dbc,"product",$data_array)){
			$last_id=mysqli_insert_id($dbc);

			if ($_FILES['product_image']['tmp_name']) {
				upload_pic($_FILES['product_image'],'../img/uploads/');
				$product_image=$_SESSION['pic_name'];
				$data_image=[
				'product_image'=>$product_image,
				];
				update_data($dbc,"product",$data_image,"product_id",$last_id);
			}

		
			$response=[
						"msg"=>"Product Has Been Added",
						"sts"=>"success",
						"id"=>base64_encode($last_id),
						"link"=>base64_encode('add_stock'),
					];
			}else{
					$response=[
						"msg"=>mysqli_error($dbc),
						"sts"=>"error"
					];
				}	

		}else{
			$decoded_id = base64_decode($_REQUEST['product_id']);
			if(update_data($dbc,"product",$data_array,"product_id",$decoded_id)){
			$last_id=$decoded_id;
			
			if ($_FILES['product_image']['tmp_name']) {
				upload_pic($_FILES['product_image'],'../img/uploads/');
				$product_image=$_SESSION['pic_name'];
				$data_image=[
				'product_image'=>$product_image,
				];
				update_data($dbc,"product",$data_image,"product_id",$last_id);
			}

			$response=[
						"msg"=>"Product Updated",
						"sts"=>"success",
						"id"=>base64_encode($last_id),
						"link"=>base64_encode('add_stock'),
					];
			}else{
					$response=[
						"msg"=>mysqli_error($dbc),
						"sts"=>"error"
					];
				}
		}
		echo json_encode($response);
	}

if(!empty($_REQUEST['action']) AND $_REQUEST['action']=="inventory_module"){
					
		$data_array=[
 			'product_name'=>$_REQUEST['product_name'],
			'product_code'=>rand(),
			'brand_id'=>0,
			'category_id'=>0,
			'current_rate'=>@$_REQUEST['current_rate'],
			'alert_at'=>5,
			'availability'=>1,
			'purchase_rate'=>$_REQUEST['current_rate'],
			'status'=>1,
			'inventory'=>1,
		];
		if ($_REQUEST['product_id']=="") {
		
		if(insert_data($dbc,"product",$data_array)){
			$last_id=mysqli_insert_id($dbc);
		
			$response=[
						"msg"=>"Inventory Product Has Been Added",
						"sts"=>"success",
					];
			}else{
					$response=[
						"msg"=>mysqli_error($dbc),
						"sts"=>"error"
					];
				}	

		}else{
			if(update_data($dbc,"product",$data_array,"product_id",base64_decode($_REQUEST['product_id']))){
			$last_id=$_REQUEST['product_id'];
			
		

			$response=[
						"msg"=>"Product Updated",
						"sts"=>"success",
					];
			}else{
					$response=[
						"msg"=>mysqli_error($dbc),
						"sts"=>"error"
					];
				}
		}
		echo json_encode($response);
	}
if (isset($_REQUEST['get_products_list'])) {

	if ($_REQUEST['type']=="code") {
			$q=mysqli_query($dbc,"SELECT * FROM product WHERE product_code LIKE '%".$_REQUEST['get_products_list']."%' AND status=1 ");
		if (mysqli_num_rows($q)>0) {
			while ($r=mysqli_fetch_assoc($q)) {
				$p_rate = $r['current_rate'];
				if (@$_REQUEST['payment_type'] == 'cash_purchase' || @$_REQUEST['payment_type'] == 'credit_purchase') {
					$p_rate = (isset($r['purchase_rate']) && $r['purchase_rate'] !== '') ? $r['purchase_rate'] : 0;
					if ($p_rate == 0 || $p_rate == '0') {
						$last_pi = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT rate FROM purchase_item WHERE product_id = '$r[product_id]' AND rate != 0 ORDER BY purchase_item_id DESC LIMIT 1"));
						if ($last_pi && !empty($last_pi['rate'])) {
							$p_rate = $last_pi['rate'];
						}
					}
				}
				echo '<option value="'.$r['product_id'].'" data-price="'.$p_rate.'" data-uom="'.$r['product_unit'].'" data-code="'.$r['product_code'].'">'.$r['product_name'].'</option>';
			}
		}else{
			echo '<option value="">Not Found</option>';	
		}
	}
	if ($_REQUEST['type']=="product") {
			$q=mysqli_query($dbc,"SELECT * FROM product WHERE product_id='".$_REQUEST['get_products_list']."' AND status=1 ");
		if (mysqli_num_rows($q)>0) {
			$r=mysqli_fetch_assoc($q);
				echo $r['product_code'];
			}
	}
}
if (isset($_REQUEST['getPrice'])) {
		if ($_REQUEST['type']=="product") {
		$record=fetchRecord($dbc,"product","product_id",$_REQUEST['getPrice']);

		}else{
		$record=fetchRecord($dbc,"product","product_code",$_REQUEST['getPrice']);

		}
		if (isset($_REQUEST['credit_sale_type'])  AND $_REQUEST['credit_sale_type']=="15days") {
			$price=$record['f_days'];
		}elseif (isset($_REQUEST['credit_sale_type'])  AND $_REQUEST['credit_sale_type']=="30days") {
			$price=@$record['t_days'];
		}else{
			if ($_REQUEST['payment_type']=="credit_purchase" OR $_REQUEST['payment_type']=="cash_purchase") {
				$price=$record['purchase_rate'];
				if ($price == 0 OR $price == '0') {
				$q = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM purchase_item WHERE product_id = '$record[product_id]' AND rate != 0  ORDER BY purchase_item_id DESC "));
				$price = $q['rate'];
				}else{
					$price=$record['purchase_rate'];
				}
			}else{
				$price=$record['current_rate'];
			}
			
		
		}
			

		$excludeOrder = "";
		if (!empty($_REQUEST['order_id'])) {
			$excludeOrder = " AND o.order_id != '".mysqli_real_escape_string($dbc, $_REQUEST['order_id'])."' ";
		}
		$party_po_clean = mysqli_real_escape_string($dbc, @$_REQUEST['party_po']);

		$fetselectedp ="SELECT
    poi.product_id,
    product.product_name,
    poi.quantity AS total_ordered_quantity,
    SUM(COALESCE(oitem.quantity, 0)) AS total_delivered_quantity,
    poi.rate as p_rate
FROM
    purchase_order p
INNER JOIN
    purchase_order_item poi ON p.po_id = poi.po_id
LEFT JOIN
    orders o ON p.po_partypo = o.party_po AND o.customer_account = p.po_customer {$excludeOrder}
LEFT JOIN
    order_item oitem ON o.order_id = oitem.order_id AND poi.product_id = oitem.product_id
LEFT JOIN 
    product ON product.product_id = poi.product_id
WHERE
    p.po_partypo = '".$party_po_clean."'
AND 
  product.product_id =   '".$record['product_id']."'
GROUP BY
    poi.product_id, product.product_name, poi.quantity, poi.po_id
";
	$dthis = mysqli_fetch_assoc(mysqli_query($dbc,$fetselectedp));
		
			$remaining_qty = 0;
			if ($dthis && isset($dthis['total_ordered_quantity'])) {
				$remaining_qty = (double)$dthis['total_ordered_quantity'] - (double)$dthis['total_delivered_quantity'];
			}

			$response=[
						"price"=>@$price,
						"current_rate"=>@$record['current_rate'],
						"uom"=>@$record['product_unit'],
						"product_unit"=>@$record['product_unit'],
						"product_code"=>@$record['product_code'],
						"qty"=>@(double)$record['quantity_instock'],
						"sts"=>"success",
						"type"=>@$_REQUEST['credit_sale_type'],
						"qty2" => $remaining_qty,
						'so_rate' => @$dthis['p_rate'],
					];
		
		
		echo json_encode($response);
}

/*---------------------- cash sale-order   -------------------------------------------------------------------*/
 if (isset($_REQUEST['sale_order_client_name'])) {
 //	print_r($_REQUEST);
 	$get_company =mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM company ORDER BY id DESC LIMIT 1"));
 	if (!empty($_REQUEST['product_ids'])) {
 		# code...
 	
 	$total_ammount=$total_grand=0;

 	$data=[
 			'order_date'=> $_REQUEST['order_date'],	
			'client_name'=> $_REQUEST['sale_order_client_name'],	
			'client_contact'=> $_REQUEST['client_contact'],
			'paid'=> $_REQUEST['paid_ammount'],
			'payment_account'=>@$_REQUEST['payment_account'],
			'payment_type'=>'cash_in_hand',
			'vehicle_no' => @$_REQUEST['vehicle_no'],
			'freight'=>@$_REQUEST['freight'],
			'page_type' => @$_REQUEST['page_type'],
			'order_narration'=>@$_REQUEST['order_narration'],
		];
		
		 if ($_REQUEST['product_order_id']=="") {
		 	
		 	if (insert_data($dbc,'orders', $data)) {
		 		$last_id = mysqli_insert_id($dbc);
		 		$paidAmount=@(double)$_REQUEST['paid_ammount'];
		 		if ($paidAmount>0 ) {
			 		$debit=[
						'debit'=>@$_REQUEST['paid_ammount'],
						'credit'=>0,
						'customer_id'=>@$_REQUEST['payment_account'],
						'transaction_from'=>'invoice',
						'transaction_type'=>"cash_in_hand",
						'transaction_remarks'=>"cash_sale by order id#".$last_id,
						'transaction_date'=>$_REQUEST['order_date'],
					];
					insert_data($dbc,'transactions', $debit);
					$transaction_paid_id = mysqli_insert_id($dbc);
				}
				
				$x=0;
				foreach ($_REQUEST['product_ids'] as $key => $value) {
					$total=$qty=0;
					$product_quantites=(double)$_REQUEST['product_quantites'][$x];
					$product_rates=(double)$_REQUEST['product_rates'][$x];
					$total=(double)$product_quantites*$product_rates;
					$total_ammount+=(double)$total;
					$order_items=['product_id'=>$_REQUEST['product_ids'][$x],
						'rate'=>$product_rates,
						'rate_mm' => $_REQUEST['rate_mm'][$x],
						'total'=>$total,
						'order_id'=>$last_id,
						'quantity'=>$product_quantites,
						'order_item_status'=>1,
					];
					if ($get_company['stock_manage']==1) {
						$product_id=$_REQUEST['product_ids'][$x];
					$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$product_id."' "));
					@$qty=(double)$quantity_instock['quantity_instock']-(double)$product_quantites;
					$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$qty' WHERE product_id='".$product_id."' ");
					}
					insert_data($dbc,'order_item', $order_items);

					$x++;
				}//end of foreach
				$total_grand=@(double)$_REQUEST['freight']+$total_ammount+$total_ammount*((double)$_REQUEST['ordered_discount']/100);
				 	
				 	$due_amount=(double)$total_grand-@(double)$_REQUEST['paid_ammount'];
 				
 			if ($due_amount>0) {
 					$payment_status=0; //pending
 				
 				}else{
 					$payment_status=1; //completed
 					
 			}	
		$newOrder=[
			'total_amount'=>$total_ammount,				
			'discount'=> $_REQUEST['ordered_discount'],	
			'grand_total'=> $total_grand,	
			'payment_status'=>$payment_status,
			'due'=> $due_amount,
			'order_status'=>1,
			'transaction_paid_id'=> @$transaction_paid_id,		
		];
		 	 if (update_data($dbc,'orders', $newOrder , 'order_id',$last_id)) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				 $msg = "Order Has been Added";
					$sts = 'success';
					
				}else{
					$msg = mysqli_error($dbc);
					$sts ="danger";
				}
				
			}else{
				$msg =mysqli_error($dbc);
				$sts = "danger";
			}
		 }else{
		 		if (update_data($dbc,'orders', $data,'order_id',$_REQUEST['product_order_id'])) {
				$last_id = $_REQUEST['product_order_id'];
				if ($get_company['stock_manage']==1) {
						$proQ=get($dbc,"order_item WHERE order_id='".$last_id."' ");

						while ($proR=mysqli_fetch_assoc($proQ)) {
							$newqty=0;
							$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$proR['product_id']."' "));
							$newqty=(double)$quantity_instock['quantity_instock']+(double)$proR['quantity'];
							$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$newqty' WHERE product_id='".$proR['product_id']."' ");
					

						}
					}
				deleteFromTable($dbc,"order_item",'order_id',$_REQUEST['product_order_id']);
				
				$x=0;
				foreach ($_REQUEST['product_ids'] as $key => $value) {
					$total=$qty=0;
					$product_quantites=(double)$_REQUEST['product_quantites'][$x];
					$product_rates=(double)$_REQUEST['product_rates'][$x];
					$total=$product_quantites*$product_rates;
					$total_ammount+=(double)$total;
					$order_items=['product_id'=>$_REQUEST['product_ids'][$x],
						'rate'=>$product_rates,
						'total'=>$total,
						'rate_mm'=>@$_REQUEST['rate_mm'][$x],
						'order_id'=>$_REQUEST['product_order_id'],
						'quantity'=>$product_quantites,
						'order_item_status'=>1,
					];
					if ($get_company['stock_manage']==1) {
						$product_id=$_REQUEST['product_ids'][$x];
					$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$product_id."' "));
					$qty=(double)$quantity_instock['quantity_instock']-$product_quantites;
					$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$qty' WHERE product_id='".$product_id."' ");
					}
					//update_data($dbc,'order_item', $order_items , 'order_id',$_REQUEST['product_order_id']);
					insert_data($dbc,'order_item', $order_items);

					$x++;
				}//end of foreach
				$total_grand=@(double)$_REQUEST['freight']+$total_ammount+$total_ammount*((double)$_REQUEST['ordered_discount']/100);
					$due_amount=(double)$total_grand-@(double)$_REQUEST['paid_ammount'];
 				if ($due_amount>0) {
 					$payment_status=0; //pending
 				
 				}else{
 					$payment_status=1; //completed
 					
 				}	
			$newOrder=[

			'total_amount'=>$total_ammount,				
			'discount'=> $_REQUEST['ordered_discount'],	
			'grand_total'=> $total_grand,
			'payment_status'=>$payment_status,
			'due'=> $due_amount,	
		];
				$paidAmount=@(double)$_REQUEST['paid_ammount'];
		 		if ($paidAmount>0) {
			$credit1=[
					'debit'=>@$_REQUEST['paid_ammount'],
					'credit'=>0,
					'customer_id'=>@$_REQUEST['payment_account'],

				];
				$transactions=fetchRecord($dbc,"orders", "order_id", $_REQUEST['product_order_id']);
				update_data($dbc,"transactions", $credit1, "transaction_id", $transactions['transaction_paid_id']);
				}
		 	 if (update_data($dbc,'orders', $newOrder , 'order_id',$_REQUEST['product_order_id'])) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				 $msg = "Data Has been Updated";
					$sts = 'success';
					
				}else{
					$msg = mysqli_error($dbc);
					$sts ="danger";
				}
				
			}else{
				$msg =mysqli_error($dbc);
				$sts = "danger";
			}

		 }
	}else{
		$msg = "Please Add Any Product";
		$sts = 'error';
	}
		 echo json_encode(['msg'=>$msg,'sts'=>$sts,'order_id'=>@$last_id,'type'=>"order",'subtype'=>$_REQUEST['payment_type']]);
 }
/*---------------------- credit sale-order   -------------------------------------------------------------------*/
 if (isset($_REQUEST['credit_order_client_name'])) {
 	$get_company =mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM company ORDER BY id DESC LIMIT 1"));
if (!empty($_REQUEST['product_ids'])) {
 		# code...
		if (!empty($_REQUEST['party_po'])) {
			$party_po_clean = mysqli_real_escape_string($dbc, $_REQUEST['party_po']);
			$excludeOrder = "";
			if (!empty($_REQUEST['product_order_id'])) {
				$excludeOrder = " AND o.order_id != '".mysqli_real_escape_string($dbc, $_REQUEST['product_order_id'])."' ";
			}
			foreach ($_REQUEST['product_ids'] as $chkKey => $chkProId) {
				$chkQty = (double)$_REQUEST['product_quantites'][$chkKey];
				if ($chkQty <= 0) {
					echo json_encode(['msg' => "Product quantity must be greater than 0", 'sts' => 'error']);
					exit;
				}
				$chkProId_clean = mysqli_real_escape_string($dbc, $chkProId);
				$chkSql = "SELECT
					poi.product_id,
					product.product_name,
					poi.quantity AS total_ordered_quantity,
					SUM(COALESCE(oitem.quantity, 0)) AS total_delivered_quantity
				FROM
					purchase_order p
				INNER JOIN
					purchase_order_item poi ON p.po_id = poi.po_id
				LEFT JOIN
					orders o ON p.po_partypo = o.party_po {$excludeOrder}
				LEFT JOIN
					order_item oitem ON o.order_id = oitem.order_id AND poi.product_id = oitem.product_id
				LEFT JOIN 
					product ON product.product_id = poi.product_id
				WHERE
					p.po_partypo = '{$party_po_clean}'
					AND poi.product_id = '{$chkProId_clean}'
				GROUP BY
					poi.product_id, product.product_name, poi.quantity, poi.po_id";
				$chkRes = mysqli_query($dbc, $chkSql);
				if ($chkRow = mysqli_fetch_assoc($chkRes)) {
					$chkRemaining = (double)$chkRow['total_ordered_quantity'] - (double)$chkRow['total_delivered_quantity'];
					if ($chkQty > $chkRemaining) {
						$prodLabel = !empty($chkRow['product_name']) ? $chkRow['product_name'] : "Product ID #".$chkProId;
						echo json_encode([
							'msg' => "Cannot add challan: Quantity for '{$prodLabel}' ({$chkQty}) exceeds Remaining Quantity ({$chkRemaining})!",
							'sts' => 'error'
						]);
						exit;
					}
				}
			}
		}

 	$total_ammount=$total_grand=0;

 	$po_for_order = !empty($_REQUEST['party_po']) ? $_REQUEST['party_po'] : '';
 	$order_credit_sale_type = @$_REQUEST['credit_sale_type'];
 	if (!empty($po_for_order)) {
 		$po_party_clean = mysqli_real_escape_string($dbc, $po_for_order);
 		$po_q = mysqli_query($dbc, "SELECT po_paymentdays FROM purchase_order WHERE po_partypo = '$po_party_clean' LIMIT 1");
 		if ($po_row = mysqli_fetch_assoc($po_q)) {
 			$days_num = intval(filter_var($po_row['po_paymentdays'], FILTER_SANITIZE_NUMBER_INT));
 			if ($days_num > 0) {
 				$order_credit_sale_type = $days_num . 'days';
 			}
 		}
 	}

 	$data=[
 			'order_date'=> $_REQUEST['order_date'],	
			'client_name'=> $_REQUEST['credit_order_client_name'],	
			'client_contact'=> $_REQUEST['client_contact'],
			'paid'=> $_REQUEST['paid_ammount'],
			'order_narration'=>@$_REQUEST['order_narration'],
			'payment_account'=>@$_REQUEST['payment_account'],
			'customer_account'=>@$_REQUEST['customer_account'],
			'payment_type'=>'credit_sale',
			'credit_sale_type'=>!empty($order_credit_sale_type) ? $order_credit_sale_type : @$_REQUEST['credit_sale_type'],
			'vehicle_no'=>@$_REQUEST['vehicle_no'],
			'freight'=>@$_REQUEST['freight'],
			'party_po'=>@$_REQUEST['party_po'],

		];
		//'payment_status'=>1,
		 if ($_REQUEST['product_order_id']=="") {
		 	
		 	if (insert_data($dbc,'orders', $data)) {
		 		$last_id = mysqli_insert_id($dbc);
				$x=0;
				foreach ($_REQUEST['product_ids'] as $key => $value) {
					$total=$qty=0;
					$product_quantites=(double)$_REQUEST['product_quantites'][$x];
					$product_rates=(double)$_REQUEST['product_rates'][$x];
					$total=$product_quantites*$product_rates;
					$total_ammount+=(double)$total;
					$order_items=['product_id'=>$_REQUEST['product_ids'][$x],
						'rate'=>$product_rates,
						'rate_mm'=>@$_REQUEST['rate_mm'][$x],
						'total'=>$total,
						'order_id'=>$last_id,
						'quantity'=>$product_quantites,
						'order_item_status'=>1,
						'pckg'=>$_REQUEST['package'][$x],
						'p_pckg'=>$_REQUEST['p_package'][$x],
					];
					
					if ($get_company['stock_manage']==1) {
						$product_id=$_REQUEST['product_ids'][$x];
					$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$product_id."' "));
					$qty=(double)$quantity_instock['quantity_instock']-$product_quantites;
					$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$qty' WHERE product_id='".$product_id."' ");
					}
					insert_data($dbc,'order_item', $order_items);

					$x++;
				}//end of foreach

				$total_grand=@(double)$_REQUEST['freight']+$total_ammount+$total_ammount*((double)$_REQUEST['ordered_discount']/100);
				$due_amount=(double)$total_grand-@(double)$_REQUEST['paid_ammount'];
 				
				// $credit=[
				// 	'debit'=>$due_amount,
				// 	'credit'=>0,
				// 	'customer_id'=>@$_REQUEST['customer_account'],
				// 	'transaction_from'=>'invoice',
				// 	'transaction_type'=>"credit_sale",
				// 	'transaction_remarks'=>"credit_sale by order id#".$last_id,
				// 	'transaction_date'=>$_REQUEST['order_date'],
				// ];
			if ($due_amount>0) {
 					$payment_status=0; //pending
 					//insert_data($dbc,'transactions', $credit);
					$transaction_id = mysqli_insert_id($dbc);
 				}else{
 					$payment_status=1; //completed
 					$transaction_id=0;
 				}
				$paidAmount=@(double)$_REQUEST['paid_ammount'];
		 // 	if ($paidAmount>0) {
			// 	$credit1=[
			// 		'credit'=>@$_REQUEST['paid_ammount'],
			// 		'debit'=>0,
			// 		'customer_id'=>@$_REQUEST['payment_account'],
			// 		'transaction_from'=>'invoice',
			// 		'transaction_type'=>"credit_sale",
			// 		'transaction_remarks'=>"credit_sale by order id#".$last_id,
			// 		'transaction_date'=>$_REQUEST['order_date'],
			// 	];
			// 	insert_data($dbc,'transactions', $credit1);
			// 	$transaction_paid_id = mysqli_insert_id($dbc);
			// }

				
				$newOrder=[
					'payment_status'=>$payment_status,
					'total_amount'=>$total_ammount,				
					'discount'=> $_REQUEST['ordered_discount'],	
					'grand_total'=> $total_grand,	
					'due'=> $due_amount,
					'order_status'=>1,
					'transaction_id'=> @$transaction_id,
					'transaction_paid_id'=> @$transaction_paid_id,
					];
		 	 if (update_data($dbc,'orders', $newOrder , 'order_id',$last_id)) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				 $msg = "Order Has been Added";
					$sts = 'success';
					
				}else{
					$msg = mysqli_error($dbc);
					$sts ="danger";
				}
				
			}else{
				$msg =mysqli_error($dbc);
				$sts = "danger";
			}
		 }else{
		 		if (update_data($dbc,'orders', $data,'order_id',$_REQUEST['product_order_id'])) {
				$last_id = $_REQUEST['product_order_id'];
				if ($get_company['stock_manage']==1) {
						$proQ=get($dbc,"order_item WHERE order_id='".$last_id."' ");

						while ($proR=mysqli_fetch_assoc($proQ)) {
							$newqty=0;
							$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$proR['product_id']."' "));
							$newqty=(double)$quantity_instock['quantity_instock']+(double)$proR['quantity'];
							$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$newqty' WHERE product_id='".$proR['product_id']."' ");
							

						}
					}
				deleteFromTable($dbc,"order_item",'order_id',$_REQUEST['product_order_id']);
				
				$x=0;
				foreach ($_REQUEST['product_ids'] as $key => $value) {
					$total=$qty=0;
					$product_quantites=(double)$_REQUEST['product_quantites'][$x];
					$product_rates=(double)$_REQUEST['product_rates'][$x];
					$total=$product_quantites*$product_rates;
					$total_ammount+=(double)$total;
					$order_items=['product_id'=>$_REQUEST['product_ids'][$x],
						'rate'=>$product_rates,
						'rate_mm'=>@$_REQUEST['rate_mm'][$x],
						'total'=>$total,
						'order_id'=>$_REQUEST['product_order_id'],
						'quantity'=>$product_quantites,
						'order_item_status'=>1,
						'pckg'=>$_REQUEST['package'][$x],
						'p_pckg'=>$_REQUEST['p_package'][$x],
					];
					if ($get_company['stock_manage']==1) {
						$product_id=$_REQUEST['product_ids'][$x];
					$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$product_id."' "));
					$qty=(double)$quantity_instock['quantity_instock']-$product_quantites;
					$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$qty' WHERE product_id='".$product_id."' ");
					}
					insert_data($dbc,'order_item', $order_items);

					$x++;
				}//end of foreach
				$total_grand=@(double)$_REQUEST['freight']+$total_ammount+$total_ammount*((double)$_REQUEST['ordered_discount']/100);
				$due_amount=(double)$total_grand-@(double)$_REQUEST['paid_ammount'];

			$transactions=fetchRecord($dbc,"orders", "order_id", $_REQUEST['product_order_id']);
			@deleteFromTable($dbc,"transactions",'transaction_id',$transactions['transaction_id']);
			@deleteFromTable($dbc,"transactions",'transaction_id',$transactions['transaction_paid_id']);
				
				// $credit=[
				// 	'debit'=>$due_amount,
				// 	'credit'=>0,
				// 	'customer_id'=>@$_REQUEST['customer_account'],
				// 	'transaction_from'=>'invoice',
				// 	'transaction_type'=>"credit_sale",
				// 	'transaction_remarks'=>"credit_sale by order id#".$last_id,
				// 	'transaction_date'=>$_REQUEST['order_date'],
				// ];
			if ($due_amount>0) {
 					$payment_status=0; //pending
 					//insert_data($dbc,'transactions', $credit);
					$transaction_id = mysqli_insert_id($dbc);
 				}else{
 					$payment_status=1; //completed
 					$transaction_id=0;
 				}
				$paidAmount=@(double)$_REQUEST['paid_ammount'];
		 // 	if ($paidAmount>0) {
			// 	$credit1=[
			// 		'credit'=>@$_REQUEST['paid_ammount'],
			// 		'debit'=>0,
			// 		'customer_id'=>@$_REQUEST['payment_account'],
			// 		'transaction_from'=>'invoice',
			// 		'transaction_type'=>"credit_sale",
			// 		'transaction_remarks'=>"credit_sale by order id#".$last_id,
			// 		'transaction_date'=>$_REQUEST['order_date'],
			// 	];
			// 	insert_data($dbc,'transactions', $credit1);
			// 	$transaction_paid_id = mysqli_insert_id($dbc);
			// }
 			
			$newOrder=[
					'payment_status'=>$payment_status,
					'total_amount'=>$total_ammount,				
					'discount'=> $_REQUEST['ordered_discount'],	
					'grand_total'=> $total_grand,	
					'due'=> $due_amount,
					'transaction_id'=> @$transaction_id,
					'transaction_paid_id'=> @$transaction_paid_id,
					];
			
				
		 	 if (update_data($dbc,'orders', $newOrder , 'order_id',$_REQUEST['product_order_id'])) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				 $msg = "Data Has been Updated";
					$sts = 'success';
					
				}else{
					$msg = mysqli_error($dbc);
					$sts ="danger";
				}
				
			}else{
				$msg =mysqli_error($dbc);
				$sts = "danger";
			}

		 }
		 	}else{
		$msg = "Please Add Any Product";
		$sts = 'error';
	}
		 $print_type = !empty($_REQUEST['print_type']) ? $_REQUEST['print_type'] : "dc";
		 echo json_encode(['msg'=>$msg,'sts'=>$sts,'order_id'=>@$last_id,'type'=>$print_type,'subtype'=>$_REQUEST['payment_type']]);
 }

 if (isset($_REQUEST['getProductPills'])) {
	$q=mysqli_query($dbc,"SELECT * FROM product WHERE brand_id='".$_REQUEST['getProductPills']."' ");
	if (mysqli_num_rows($q)>0) {
			while ($r=mysqli_fetch_assoc($q)) {
				echo '<li class="nav-item text-capitalize"  ><button type="button" onclick="addProductOrder('.$r["product_id"].','.$r["quantity_instock"].',`plus`)" class="btn btn-primary  m-1 ">'.$r["product_name"].'</button></li>';			
								 	
	 } }else{
	 	echo '<li class="nav-item text-capitalize ">No Product Has Been Added</li>';	
	}
								 
}
 if (isset($_REQUEST['getCustomer_name'])) {
	$q=mysqli_query($dbc,"SELECT DISTINCT client_name FROM  orders WHERE client_contact='".$_REQUEST['getCustomer_name']."' ");
	if (mysqli_num_rows($q)>0) {
			$r=mysqli_fetch_assoc($q);
				echo $r['client_name'];			
								 	
	 } else{
	 	echo '';	
	}
								 
}
if (isset($_REQUEST['getProductDetails'])) {
	$product=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT  product.*,brands.* FROM product INNER JOIN brands ON product.brand_id=brands.brand_id   WHERE product.product_id='".$_REQUEST['getProductDetails']."' AND product.status=1  "));
	echo json_encode($product);

}
if (isset($_REQUEST['getProductDetailsBycode'])) {
	$product=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT  product.*,brands.* FROM product INNER JOIN brands ON product.brand_id=brands.brand_id   WHERE product.product_code='".$_REQUEST['getProductDetailsBycode']."' AND product.status=1  "));
	echo json_encode($product);

}
/*---------------------- cash purchase   -------------------------------------------------------------------*/
 if (isset($_REQUEST['cash_purchase_supplier'])) {
 	if (!empty($_REQUEST['product_ids'])) {
 		# code...
 	$total_ammount=$total_grand=0;
 	$get_company =mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM company ORDER BY id DESC LIMIT 1"));

 	$data=[
 			'purchase_date'=> $_REQUEST['purchase_date'],	
			'client_name'=> @$_REQUEST['cash_purchase_supplier'],	
			'client_contact'=> @$_REQUEST['client_contact'],
			'purchase_narration'=> @$_REQUEST['purchase_narration'],
			'payment_account'=>@$_REQUEST['payment_account'],
			'customer_account'=>@$_REQUEST['customer_account'],
			'paid'=> $_REQUEST['paid_ammount'],
			'payment_status'=>1,
			'payment_type'=>$_REQUEST['payment_type'],
			'freight' => $_REQUEST['freight'],
		];
		
		 if ($_REQUEST['product_purchase_id']=="") {
		 	
		 	if (insert_data($dbc,'purchase', $data)) {
		 		$last_id = mysqli_insert_id($dbc);
		 
				$x=0;
				foreach ($_REQUEST['product_ids'] as $key => $value) {
					$total=$qty=0;
					$product_quantites=(double)$_REQUEST['product_quantites'][$x];
					$product_rates=(double)$_REQUEST['product_rates'][$x];
					$total=(double)$product_quantites*$product_rates;
					$total_ammount+=(double)$total;
					
					$order_items=['product_id'=>$_REQUEST['product_ids'][$x],
						'rate'=>$product_rates,
						'rate_mm'=>@$_REQUEST['rate_mm'][$x],
						'total'=>$total,
						'purchase_id'=>$last_id,
						'quantity'=>$product_quantites,
						'purchase_item_status'=>1,
					];
					
					insert_data($dbc,'purchase_item', $order_items);
					
					if ($get_company['stock_manage']==1) {
						$product_id=$_REQUEST['product_ids'][$x];
					$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$product_id."' "));
					$qty=(double)$quantity_instock['quantity_instock']+$product_quantites;
					$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$qty' WHERE product_id='".$product_id."' ");
					}
					


					$x++;
				}//end of foreach
				$total_grand=@(double)$_REQUEST['freight']+$total_ammount+$total_ammount*((double)$_REQUEST['ordered_discount']/100);
				 	
				$due_amount=(double)$total_grand-@(double)$_REQUEST['paid_ammount'];
		 if ($_REQUEST['payment_type']=="credit_purchase") :
		 		if ($due_amount>0) {
					$debit=[
					'debit'=>0,
					'credit'=>$due_amount,
					'customer_id'=>@$_REQUEST['customer_account'],
					'transaction_from'=>'purchase',
					'transaction_type'=>$_REQUEST['payment_type'],
					'transaction_remarks'=>"purchased on  purchased id#".$last_id,
					'transaction_date'=>$_REQUEST['purchase_date'],
				];
				insert_data($dbc,'transactions', $debit);
				$transaction_id = mysqli_insert_id($dbc);
			}
		endif;
			$paidAmount=@(double)$_REQUEST['paid_ammount'];
		 if ($paidAmount>0) {
				$credit=[
					'credit'=>0,
					'debit'=>@$_REQUEST['paid_ammount'],
					'customer_id'=>@$_REQUEST['payment_account'],
					'transaction_from'=>'purchase',
					'transaction_type'=>$_REQUEST['payment_type'],
					'transaction_remarks'=>"purchased by purchased id#".$last_id,
					'transaction_date'=>$_REQUEST['purchase_date'],
				];
				insert_data($dbc,'transactions', $credit);
				$transaction_paid_id = mysqli_insert_id($dbc);
		}
 					
			$newOrder=[

			'total_amount'=>$total_ammount,				
			'discount'=> $_REQUEST['ordered_discount'],	
			'grand_total'=> $total_grand,	
			'due'=> $due_amount,
			'transaction_paid_id'=>@$transaction_paid_id,
			'transaction_id'=>@$transaction_id,		
		];
		 	 if (update_data($dbc,'purchase', $newOrder , 'purchase_id',$last_id)) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				 $msg = "Purchase Has been Added";
					$sts = 'success';
					
				}else{
					$msg = mysqli_error($dbc);
					$sts ="danger";
				}
				
			}else{
				$msg =mysqli_error($dbc);
				$sts = "danger";
			}
		 }else{
		 		if (update_data($dbc,'purchase', $data,'purchase_id',$_REQUEST['product_purchase_id'])) {
				$last_id = $_REQUEST['product_purchase_id'];

				
				if ($get_company['stock_manage']==1) {
						$proQ=get($dbc,"purchase_item WHERE purchase_id='".$last_id."' ");

						while ($proR=mysqli_fetch_assoc($proQ)) {
							$newqty=0;
							$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$proR['product_id']."' "));
							$newqty=(double)$quantity_instock['quantity_instock']-(double)$proR['quantity'];
							$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$newqty' WHERE product_id='".$proR['product_id']."' ");
							

						}
					}
				deleteFromTable($dbc,"purchase_item",'purchase_id',$_REQUEST['product_purchase_id']);
				$x=0;
				foreach ($_REQUEST['product_ids'] as $key => $value) {


					$total=$qty=0;
					$product_quantites=(double)$_REQUEST['product_quantites'][$x];
					$product_rates=(double)$_REQUEST['product_rates'][$x];
					$total=$product_quantites*$product_rates;
					$total_ammount+=(double)$total;
					$purchase_item=['product_id'=>$_REQUEST['product_ids'][$x],
						'rate'=>$product_rates,
						'rate_mm'=>@$_REQUEST['rate_mm'][$x],
						'total'=>$total,
						'purchase_id'=>$_REQUEST['product_purchase_id'],
						'quantity'=>$product_quantites,
						'purchase_item_status'=>1,
					];
					
					//update_data($dbc,'order_item', $order_items , 'purchase_id',$_REQUEST['product_purchase_id']);
					insert_data($dbc,'purchase_item', $purchase_item);
					
					if ($get_company['stock_manage']==1) {
						$product_id=$_REQUEST['product_ids'][$x];
						$quantity_instock=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT quantity_instock FROM  product WHERE product_id='".$product_id."' "));
						$qty=(double)$quantity_instock['quantity_instock']+$product_quantites;
						$quantity_update=mysqli_query($dbc,"UPDATE product SET  quantity_instock='$qty' WHERE product_id='".$product_id."' ");
					}

					$x++;
				}//end of foreach
				$total_grand=@(double)$_REQUEST['freight']+$total_ammount+$total_ammount*((double)$_REQUEST['ordered_discount']/100);
				$due_amount=(double)$total_grand-@(double)$_REQUEST['paid_ammount'];
 				
				
			$transactions=fetchRecord($dbc,"purchase", "purchase_id", $_REQUEST['product_purchase_id']);
			@deleteFromTable($dbc,"transactions",'transaction_id',$transactions['transaction_id']);
			@deleteFromTable($dbc,"transactions",'transaction_id',$transactions['transaction_paid_id']);
			
				
		 if ($_REQUEST['payment_type']=="credit_purchase") :
		 		if ($due_amount>0) {
					$debit=[
					'debit'=>0,
					'credit'=>$due_amount,
					'customer_id'=>@$_REQUEST['customer_account'],
					'transaction_from'=>'purchase',
					'transaction_type'=>$_REQUEST['payment_type'],
					'transaction_remarks'=>"purchased on  purchased id#".$last_id,
					'transaction_date'=>$_REQUEST['purchase_date'],
				];
				insert_data($dbc,'transactions', $debit);
				$transaction_id = mysqli_insert_id($dbc);
			}
		endif;
			$paidAmount=@(double)$_REQUEST['paid_ammount'];
		 if ($paidAmount>0) {
				$credit=[
					'debit'=>@$_REQUEST['paid_ammount'],
					'credit'=>0,
					'customer_id'=>@$_REQUEST['payment_account'],
					'transaction_from'=>'purchase',
					'transaction_type'=>$_REQUEST['payment_type'],
					'transaction_remarks'=>"purchased by purchased id#".$last_id,
					'transaction_date'=>$_REQUEST['purchase_date'],
				];
				insert_data($dbc,'transactions', $credit);
				$transaction_paid_id = mysqli_insert_id($dbc);
		}
 					
			$newOrder=[

			'total_amount'=>$total_ammount,				
			'discount'=> $_REQUEST['ordered_discount'],	
			'grand_total'=> $total_grand,	
			'due'=> $due_amount,
			'transaction_paid_id'=>@$transaction_paid_id,
			'transaction_id'=>@$transaction_id,		
		];		

		 	 if (update_data($dbc,'purchase', $newOrder , 'purchase_id',$_REQUEST['product_purchase_id'])) {
				# code...
				//echo "<script>alert('company Updated....!')</script>";
				 $msg = "Purchase Has been Updated";
					$sts = 'success';
					
				}else{
					$msg = mysqli_error($dbc);
					$sts ="danger";
				}
				
			}else{
				$msg =mysqli_error($dbc);
				$sts = "danger";
			}

		 }
		 }else{
		$msg = "Please Add Any Product";
		$sts = 'error';
	}
		 echo json_encode(['msg'=>$msg,'sts'=>$sts,'order_id'=>@$last_id,'type'=>"purchase",'subtype'=>$_REQUEST['payment_type']]);
 }
/*---------------------- credit Purchase-order  end -------------------------------------------------------------------*/
if (isset($_REQUEST['get_products_code'])) {
	 $q=mysqli_query($dbc,"SELECT *  FROM product WHERE product_code='".$_REQUEST['get_products_code']."' AND status=1 ");
	if (mysqli_num_rows($q)>0) {
		$r=mysqli_fetch_assoc($q);
		$response=[
				"msg"=>"This Product Code Already Assign to ".$r['product_name'],
				"sts"=>"error",
			];

	}else{
		$response=[
				"msg"=>"",
				"sts"=>"success"
			];

	}
	echo json_encode($response);
}
if (isset($_REQUEST['getBalance'])) {
	 $from_balance=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT SUM(credit-debit) AS from_balance FROM transactions WHERE customer_id='".$_REQUEST['getBalance']."'"));
	 $cust = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM customers WHERE customer_id = '".$_REQUEST['getBalance']."'"));
	if (!empty($from_balance['from_balance'])) {
		$response1 = [
			'blnc' => round($from_balance['from_balance']),
			'custLimit' => round($cust['customer_limit']),
		];
	}else{
		$response1 = [
			'blnc' => '0',
			'custLimit' =>round($cust['customer_limit']),
		];
	}
	echo json_encode($response1);
}
if (isset($_REQUEST['pending_bills_detils'])) {
	 $pending_bills_detils=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM orders WHERE order_id='".base64_decode($_REQUEST['pending_bills_detils'])."'"));
	echo  json_encode($pending_bills_detils);
}
if (isset($_REQUEST['add_expense_name'])) {
		$data_array = [
			'expense_name'=>$_REQUEST['add_expense_name'],
			'expense_status'=>$_REQUEST['expense_status'],
		];
	if ($_REQUEST['expense_id']=='') {
		if (insert_data($dbc,"expenses",$data_array)) {
			# code...
			$response=[
				"msg"=>"expense Added successfully",
				"sts"=>"success"
			];

		}else{
			$response=[
				"msg"=>mysqli_error($dbc),
				"sts"=>"danger"
			];
		}
	}else{
		if (update_data($dbc,"expenses",$data_array,"expense_id",$_REQUEST['expense_id'])) {
			# code...
			$response=[
				"msg"=>"expense Updated successfully",
				"sts"=>"success"
			];

		}else{
			$response=[
				"msg"=>mysqli_error($dbc),
				"sts"=>"error"
			];
		}
	}
		echo json_encode($response);

	}

if (isset($_REQUEST['setAmountPaid'])) {
	$newOrder=[
					'payment_status'=>1,
					'paid'=>$_REQUEST['paid'],				
					'due'=>0,
					];
		 	 if (update_data($dbc,'orders', $newOrder , 'order_id',$_REQUEST['setAmountPaid'])) {
		
				$response=[ 'msg' =>"Amount Has been Paid",
					'sts' => 'success'];
					
				}else{
					$response=[ 'msg' =>mysqli_error($dbc),
					'sts' => 'error'];
				}
				echo json_encode($response);
}
if (isset($_REQUEST['setCheckStatus'])) {
	$newStat=[
					'check_status'=>$_REQUEST['status'],
					];
		 	 if (update_data($dbc,'checks', $newStat , 'check_id',$_REQUEST['setCheckStatus'])) {
		
				$response=[ 'msg' =>"Action Has been Perform Successfully",
					'sts' => 'success'];
					
				}else{
					$response=[ 'msg' =>mysqli_error($dbc),
					'sts' => 'error'];
				}
				echo json_encode($response);
}
if (isset($_REQUEST['bill_customer_name'])) {
	$paidAmount=(double)$_REQUEST['bill_paid_ammount']+(double)$_REQUEST['bill_paid'];
	

	if ($paidAmount>0) {
		$transactions=fetchRecord($dbc,"orders", "order_id", $_REQUEST['order_id']);
		$order_date=date('Y-m-d');
		if ($transactions['transaction_paid_id']>0) {
			$credit1=[
					'credit'=>@$paidAmount,
					'debit'=>0,
					'customer_id'=>@$_REQUEST['bill_payment_account'],
				];
	
				update_data($dbc,"transactions", $credit1, "transaction_id", $transactions['transaction_paid_id']);
				$transaction_paid_id = $transactions['transaction_paid_id'];
		}else{
			$credit1=[
					'credit'=>@$paidAmount,
					'debit'=>0,
					'customer_id'=>@$_REQUEST['bill_payment_account'],
					'transaction_from'=>'invoice',
					'transaction_type'=>"cash_in_hand",
					'transaction_remarks'=>"cash_sale by order id#".$_REQUEST['order_id'],
					'transaction_date'=>$order_date,
					];
					insert_data($dbc,'transactions', $credit1);
					$transaction_paid_id = mysqli_insert_id($dbc);
		}
		
	}
		$due_amount=(double)$_REQUEST['bill_grand_total']-$paidAmount;
 			if ($due_amount>0) {
 					$payment_status=0; //pending
 			}else{
 					$payment_status=1; //completed
 			}	
		$newOrder=[
				'payment_status'=>$payment_status,
				'paid'=>$paidAmount,				
				'due'=>$due_amount,
				'transaction_paid_id'=>$transaction_paid_id,
				];
	if (update_data($dbc,'orders', $newOrder , 'order_id',$_REQUEST['order_id'])) {
		
	$response=[ 'msg' =>"Amount Has been Paid",
					'sts' => 'success'];
					
	}else{
		$response=[ 'msg' =>mysqli_error($dbc),
					'sts' => 'error'];
	}
	echo json_encode($response);
}
if(isset($_REQUEST['LimitCustomer'])){
	$data=[
		'check_no' => $_REQUEST['td_check_no'],
		'check_bank_name' => $_REQUEST['voucher_bank_name'],
		'check_expiry_date' => $_REQUEST['td_check_date'],
		'voucher_id' => 0,
		'customer_id' => $_REQUEST['LimitCustomer'],
		'check_amount' => $_REQUEST['check_amount'],
		'check_location' => $_REQUEST['location_info'],
		'check_type' => $_REQUEST['check_type'],
	];
	$cust = $_REQUEST['LimitCustomer'];
	$limitNow =  $_REQUEST['check_amount'];

 	$check = mysqli_query($dbc,"SELECT * FROM checks WHERE customer_id = '$cust' AND voucher_id = 0 AND check_amount != 0");
 	//echo "SELECT * FROM checks WHERE customer_id = '$cust' AND voucher_id = 0 AND check_amount != 0";
 	
 	if(mysqli_num_rows($check)>0){
 		$qq = mysqli_fetch_assoc($check);
 	//echo $qq['check_id'];
 	if (update_data($dbc,'checks', $data , 'check_id',$qq['check_id'])) {
 		mysqli_query($dbc,"UPDATE customers SET customer_limit = '$limitNow' WHERE customer_id = '$cust'");

 	$response=[ 'msg' =>"Data Updated successfully",
					'sts' => 'success'];
				}
 	}else{
 		if(insert_data($dbc,'checks', $data)){
 			mysqli_query($dbc,"UPDATE customers SET customer_limit = '$limitNow' WHERE customer_id = '$cust'");
 			$response=[ 'msg' =>"Amount Has been Paid",
					'sts' => 'success'];
 		}else{
 			$response=[ 'msg' =>mysqli_error($dbc),
					'sts' => 'error'];
 		}
 	}

echo json_encode($response);
}


if(isset($_REQUEST['LimitCustomerajax'])){
	$cust = $_REQUEST['LimitCustomerajax'];
	$check = mysqli_query($dbc,"SELECT * FROM checks WHERE customer_id = '$cust' AND voucher_id = 0 AND check_amount != 0");
	//echo "SELECT * FROM checks WHERE customer_id = '$cust' AND voucher_id = 0 AND check_amount != 0";
	if(mysqli_num_rows($check)>0){
	$qq = mysqli_fetch_assoc($check);
		//print_r($qq);
		$response=[
			'check_no' => $qq['check_no'],
			'bank_name' => $qq['check_bank_name'],
			'check_date' =>$qq['check_expiry_date'],
			'check_type'=> $qq['check_type'],
			'check_amount' => $qq['check_amount'],
			'check_location' => $qq['check_location'],
			'sts' 			=> 'success',

		];
	}else{
		$response = '';
		}


	echo json_encode($response);
}

if(isset($_REQUEST['getCustomerLimit'])){
	$cust = $_REQUEST['getCustomerLimit'];
	$q = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT customer_limit as customer_limit FROM customers WHERE customer_id = '$cust'"));
	echo $q['customer_limit'];

}



/* -------------------------------------------------------------
 * Tax Sale Invoice Handler
 * ------------------------------------------------------------- */
if (isset($_REQUEST['tax_sale_action']) && $_REQUEST['tax_sale_action'] == 'save_tax_sale') {
    $tax_sale_id = !empty($_REQUEST['tax_sale_id']) ? intval($_REQUEST['tax_sale_id']) : 0;
    
    $customer_id = !empty($_REQUEST['customer_id']) ? intval($_REQUEST['customer_id']) : NULL;
    $company_id = !empty($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 5;

    $invoice_no = !empty($_REQUEST['invoice_no']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['invoice_no'])) : '';
    $invoice_date = !empty($_REQUEST['invoice_date']) ? $_REQUEST['invoice_date'] : date('Y-m-d');
    $time_of_supply = !empty($_REQUEST['time_of_supply']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['time_of_supply'])) : '';
    $payment_terms = !empty($_REQUEST['payment_terms']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['payment_terms'])) : 'CREDIT';
    $copy_type = !empty($_REQUEST['copy_type']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['copy_type'])) : 'ORIGINAL';
    $header_description = !empty($_REQUEST['header_description']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['header_description'])) : '';
    $po_no = !empty($_REQUEST['po_no']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['po_no'])) : '';
    $fbr_invoice_no = !empty($_REQUEST['fbr_invoice_no']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['fbr_invoice_no'])) : '';
    $remarks = !empty($_REQUEST['remarks']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['remarks'])) : '';

    $total_qty = !empty($_REQUEST['total_qty']) ? floatval($_REQUEST['total_qty']) : 0;
    $total_value_excl_tax = !empty($_REQUEST['total_value_excl_tax']) ? floatval($_REQUEST['total_value_excl_tax']) : 0;
    $total_sales_tax = !empty($_REQUEST['total_sales_tax']) ? floatval($_REQUEST['total_sales_tax']) : 0;
    $total_further_tax = !empty($_REQUEST['total_further_tax']) ? floatval($_REQUEST['total_further_tax']) : 0;
    $total_tax_payable = !empty($_REQUEST['total_tax_payable']) ? floatval($_REQUEST['total_tax_payable']) : 0;
    $total_value_incl_tax = !empty($_REQUEST['total_value_incl_tax']) ? floatval($_REQUEST['total_value_incl_tax']) : 0;
    $itax_236g_rate = isset($_REQUEST['itax_236g_rate']) ? floatval($_REQUEST['itax_236g_rate']) : 0.10;
    $itax_236g_amount = !empty($_REQUEST['itax_236g_amount']) ? floatval($_REQUEST['itax_236g_amount']) : 0;
    $total_tax_amount = !empty($_REQUEST['total_tax_amount']) ? floatval($_REQUEST['total_tax_amount']) : $total_tax_payable;
    $grand_total = !empty($_REQUEST['grand_total']) ? floatval($_REQUEST['grand_total']) : $total_value_incl_tax;
    $amount_in_words = !empty($_REQUEST['amount_in_words']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['amount_in_words'])) : '';
    $user_id = !empty($_SESSION['userId']) ? intval($_SESSION['userId']) : 1;

    $sale_data = [
        'invoice_no' => $invoice_no,
        'invoice_date' => $invoice_date,
        'time_of_supply' => $time_of_supply,
        'payment_terms' => $payment_terms,
        'copy_type' => $copy_type,
        'header_description' => $header_description,
        'po_no' => $po_no,
        'customer_id' => $customer_id,
        'company_id' => $company_id,
        'total_qty' => $total_qty,
        'total_value_excl_tax' => $total_value_excl_tax,
        'total_sales_tax' => $total_sales_tax,
        'total_further_tax' => $total_further_tax,
        'total_tax_payable' => $total_tax_payable,
        'total_value_incl_tax' => $total_value_incl_tax,
        'itax_236g_rate' => $itax_236g_rate,
        'itax_236g_amount' => $itax_236g_amount,
        'total_tax_amount' => $total_tax_amount,
        'grand_total' => $grand_total,
        'amount_in_words' => $amount_in_words,
        'fbr_invoice_no' => $fbr_invoice_no,
        'remarks' => $remarks,
        'user_id' => $user_id
    ];

    // Store transaction in transactions table same like invoice.php
    $trans_amount = !empty($_REQUEST['invoice_amount']) && floatval($_REQUEST['invoice_amount']) > 0 
        ? floatval($_REQUEST['invoice_amount']) 
        : $grand_total;

    $trans_remarks = !empty($remarks) ? $remarks : ("Sales Tax Invoice #" . $invoice_no);

    $trans_record = [
        'credit' => $trans_amount,
        'debit' => 0,
        'customer_id' => $customer_id,
        'transaction_from' => 'invoice',
        'transaction_type' => 'credit_sale',
        'transaction_remarks' => $trans_remarks,
        'transaction_date' => $invoice_date,
    ];

    if ($tax_sale_id > 0) {
        $existing_ts = fetchRecord($dbc, 'tax_sales', 'tax_sale_id', $tax_sale_id);
        $transaction_id = !empty($existing_ts['transaction_id']) ? intval($existing_ts['transaction_id']) : 0;

        if ($transaction_id > 0) {
            update_data($dbc, "transactions", $trans_record, "transaction_id", $transaction_id);
        } else if (!empty($customer_id) && $trans_amount > 0) {
            insert_data($dbc, "transactions", $trans_record);
            $transaction_id = mysqli_insert_id($dbc);
        }

        $sale_data['transaction_id'] = $transaction_id;
        $ok = update_data($dbc, 'tax_sales', $sale_data, 'tax_sale_id', $tax_sale_id);
    } else {
        $transaction_id = 0;
        if (!empty($customer_id) && $trans_amount > 0) {
            insert_data($dbc, "transactions", $trans_record);
            $transaction_id = mysqli_insert_id($dbc);
        }
        $sale_data['transaction_id'] = $transaction_id;
        $ok = insert_data($dbc, 'tax_sales', $sale_data);
        if ($ok) {
            $tax_sale_id = mysqli_insert_id($dbc);
            if (empty($invoice_no) && empty($remarks) && $transaction_id > 0) {
                update_data($dbc, "transactions", ['transaction_remarks' => "Sales Tax Invoice #" . $tax_sale_id], "transaction_id", $transaction_id);
            }
        }
    }

    if ($ok) {
        // Remove old items if updating
        mysqli_query($dbc, "DELETE FROM tax_sale_items WHERE tax_sale_id = '$tax_sale_id'");

        // Insert new items
        if (!empty($_REQUEST['item_description']) && is_array($_REQUEST['item_description'])) {
            foreach ($_REQUEST['item_description'] as $k => $desc) {
                $desc = trim($desc);
                if ($desc === '') continue;

                $pid = !empty($_REQUEST['item_product_id'][$k]) ? intval($_REQUEST['item_product_id'][$k]) : NULL;
                $unit = 'KG';
                if (empty($unit)) {
                    $unit = 'KG';
                }
                $hs = !empty($_REQUEST['item_hscode'][$k]) ? $_REQUEST['item_hscode'][$k] : '';
                $origin = !empty($_REQUEST['item_origin'][$k]) ? $_REQUEST['item_origin'][$k] : '';
                $qty = !empty($_REQUEST['item_qty'][$k]) ? floatval($_REQUEST['item_qty'][$k]) : 0;
                $rate = !empty($_REQUEST['item_rate'][$k]) ? floatval($_REQUEST['item_rate'][$k]) : 0;
                $val_excl = !empty($_REQUEST['item_val_excl'][$k]) ? floatval($_REQUEST['item_val_excl'][$k]) : ($qty * $rate);
                $tax_rate = isset($_REQUEST['item_tax_rate'][$k]) ? floatval($_REQUEST['item_tax_rate'][$k]) : 18.00;
                $tax_amt = !empty($_REQUEST['item_tax_amount'][$k]) ? floatval($_REQUEST['item_tax_amount'][$k]) : ($val_excl * ($tax_rate / 100));
                $further_tax_rate = isset($_REQUEST['item_further_tax_rate'][$k]) ? floatval($_REQUEST['item_further_tax_rate'][$k]) : 0.00;
                $further_tax_amt = !empty($_REQUEST['item_further_tax_amount'][$k]) ? floatval($_REQUEST['item_further_tax_amount'][$k]) : ($val_excl * ($further_tax_rate / 100));
                $total_tax_payable = !empty($_REQUEST['item_total_tax'][$k]) ? floatval($_REQUEST['item_total_tax'][$k]) : ($tax_amt + $further_tax_amt);
                $val_incl = !empty($_REQUEST['item_val_incl'][$k]) ? floatval($_REQUEST['item_val_incl'][$k]) : ($val_excl + $total_tax_payable);

                $item_row = [
                    'tax_sale_id' => $tax_sale_id,
                    'product_id' => $pid,
                    'description_of_goods' => $desc,
                    'unit' => $unit,
                    'hs_code' => $hs,
                    'origin' => $origin,
                    'quantity' => $qty,
                    'rate' => $rate,
                    'value_excl_tax' => $val_excl,
                    'tax_rate' => $tax_rate,
                    'tax_amount' => $tax_amt,
                    'further_tax_rate' => $further_tax_rate,
                    'further_tax_amount' => $further_tax_amt,
                    'total_tax_payable' => $total_tax_payable,
                    'value_incl_tax' => $val_incl
                ];
                insert_data($dbc, 'tax_sale_items', $item_row);
            }
        }

        echo json_encode([
            'sts' => 'success',
            'msg' => 'Tax Sale Invoice Saved Successfully',
            'order_id' => $tax_sale_id,
            'type' => 'tax_sale'
        ]);
    } else {
        echo json_encode([
            'sts' => 'error',
            'msg' => mysqli_error($dbc)
        ]);
    }
    exit;
}

/* -------------------------------------------------------------
 * Tax Purchase Invoice Handler
 * ------------------------------------------------------------- */
if (isset($_REQUEST['tax_purchase_action']) && $_REQUEST['tax_purchase_action'] == 'save_tax_purchase') {
    $tax_purchase_id = !empty($_REQUEST['tax_purchase_id']) ? intval($_REQUEST['tax_purchase_id']) : 0;

    $supplier_id = !empty($_REQUEST['supplier_id']) ? intval($_REQUEST['supplier_id']) : NULL;
    $company_id = !empty($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 5;

    $invoice_no = !empty($_REQUEST['invoice_no']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['invoice_no'])) : '';
    $invoice_date = !empty($_REQUEST['invoice_date']) ? $_REQUEST['invoice_date'] : date('Y-m-d');
    $time_of_supply = !empty($_REQUEST['time_of_supply']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['time_of_supply'])) : '';
    $payment_terms = !empty($_REQUEST['payment_terms']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['payment_terms'])) : 'CREDIT';
    $copy_type = !empty($_REQUEST['copy_type']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['copy_type'])) : 'ORIGINAL';

    $header_description = !empty($_REQUEST['header_description']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['header_description'])) : '';
    $gd_number = !empty($_REQUEST['gd_number']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['gd_number'])) : '';
    $remarks = !empty($_REQUEST['remarks']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['remarks'])) : '';

    $total_qty = !empty($_REQUEST['total_qty']) ? floatval($_REQUEST['total_qty']) : 0;
    $total_value_excl_tax = !empty($_REQUEST['total_value_excl_tax']) ? floatval($_REQUEST['total_value_excl_tax']) : 0;
    $total_sales_tax = !empty($_REQUEST['total_sales_tax']) ? floatval($_REQUEST['total_sales_tax']) : 0;
    $total_further_tax = !empty($_REQUEST['total_further_tax']) ? floatval($_REQUEST['total_further_tax']) : 0;
    $total_tax_payable = !empty($_REQUEST['total_tax_payable']) ? floatval($_REQUEST['total_tax_payable']) : 0;
    $total_value_incl_tax = !empty($_REQUEST['total_value_incl_tax']) ? floatval($_REQUEST['total_value_incl_tax']) : 0;
    $itax_236g_rate = isset($_REQUEST['itax_236g_rate']) ? floatval($_REQUEST['itax_236g_rate']) : 0.10;
    $itax_236g_amount = !empty($_REQUEST['itax_236g_amount']) ? floatval($_REQUEST['itax_236g_amount']) : 0;
    $invoice_amount = !empty($_REQUEST['invoice_amount']) ? floatval($_REQUEST['invoice_amount']) : 0;
    $amount_in_words = !empty($_REQUEST['amount_in_words']) ? mysqli_real_escape_string($dbc, trim($_REQUEST['amount_in_words'])) : '';
    $user_id = !empty($_SESSION['userId']) ? intval($_SESSION['userId']) : 1;

    $purchase_data = [
        'invoice_no' => $invoice_no,
        'invoice_date' => $invoice_date,
        'time_of_supply' => $time_of_supply,
        'payment_terms' => $payment_terms,
        'copy_type' => $copy_type,
        'supplier_id' => $supplier_id,
        'company_id' => $company_id,
        'header_description' => $header_description,
        'gd_number' => $gd_number,
        'total_qty' => $total_qty,
        'total_value_excl_tax' => $total_value_excl_tax,
        'total_sales_tax' => $total_sales_tax,
        'total_further_tax' => $total_further_tax,
        'total_tax_payable' => $total_tax_payable,
        'total_value_incl_tax' => $total_value_incl_tax,
        'itax_236g_rate' => $itax_236g_rate,
        'itax_236g_amount' => $itax_236g_amount,
        'invoice_amount' => $invoice_amount,
        'amount_in_words' => $amount_in_words,
        'remarks' => $remarks,
        'user_id' => $user_id
    ];

    // Store transaction in transactions table like credit_purchase.php
    $trans_data = [
        'debit' => $invoice_amount,
        'credit' => 0,
        'customer_id' => $supplier_id,
        'transaction_from' => 'purchase',
        'transaction_type' => 'credit_purchase',
        'transaction_remarks' => "purchased on tax purchase invoice #" . $invoice_no,
        'transaction_date' => $invoice_date,
    ];

    if ($tax_purchase_id > 0) {
        $existing_tp = fetchRecord($dbc, 'tax_purchases', 'tax_purchase_id', $tax_purchase_id);
        $transaction_id = !empty($existing_tp['transaction_id']) ? intval($existing_tp['transaction_id']) : 0;
        
        if ($transaction_id > 0) {
            update_data($dbc, 'transactions', $trans_data, 'transaction_id', $transaction_id);
        } else if (!empty($supplier_id) && $invoice_amount > 0) {
            insert_data($dbc, 'transactions', $trans_data);
            $transaction_id = mysqli_insert_id($dbc);
        }
        $purchase_data['transaction_id'] = $transaction_id;
        $ok = update_data($dbc, 'tax_purchases', $purchase_data, 'tax_purchase_id', $tax_purchase_id);
    } else {
        if (!empty($supplier_id) && $invoice_amount > 0) {
            insert_data($dbc, 'transactions', $trans_data);
            $transaction_id = mysqli_insert_id($dbc);
            $purchase_data['transaction_id'] = $transaction_id;
        }
        $ok = insert_data($dbc, 'tax_purchases', $purchase_data);
        if ($ok) {
            $tax_purchase_id = mysqli_insert_id($dbc);
        }
    }

    if ($ok) {
        mysqli_query($dbc, "DELETE FROM tax_purchase_items WHERE tax_purchase_id = '$tax_purchase_id'");

        if (!empty($_REQUEST['item_description']) && is_array($_REQUEST['item_description'])) {
            foreach ($_REQUEST['item_description'] as $k => $desc) {
                $desc = trim($desc);
                if ($desc === '') continue;

                $pid = !empty($_REQUEST['item_product_id'][$k]) ? intval($_REQUEST['item_product_id'][$k]) : NULL;
                $unit = 'KG';
                if (empty($unit)) {
                    $unit = 'KG';
                }
                $hs = !empty($_REQUEST['item_hscode'][$k]) ? $_REQUEST['item_hscode'][$k] : '';
                $origin = !empty($_REQUEST['item_origin'][$k]) ? $_REQUEST['item_origin'][$k] : '';
                $qty = !empty($_REQUEST['item_qty'][$k]) ? floatval($_REQUEST['item_qty'][$k]) : 0;
                $rate = !empty($_REQUEST['item_rate'][$k]) ? floatval($_REQUEST['item_rate'][$k]) : 0;
                $val_excl = !empty($_REQUEST['item_val_excl'][$k]) ? floatval($_REQUEST['item_val_excl'][$k]) : ($qty * $rate);
                $tax_rate = isset($_REQUEST['item_tax_rate'][$k]) ? floatval($_REQUEST['item_tax_rate'][$k]) : 18.00;
                $tax_amt = !empty($_REQUEST['item_tax_amount'][$k]) ? floatval($_REQUEST['item_tax_amount'][$k]) : ($val_excl * ($tax_rate / 100));
                $further_tax_rate = isset($_REQUEST['item_further_tax_rate'][$k]) ? floatval($_REQUEST['item_further_tax_rate'][$k]) : 0.00;
                $further_tax_amt = !empty($_REQUEST['item_further_tax_amount'][$k]) ? floatval($_REQUEST['item_further_tax_amount'][$k]) : ($val_excl * ($further_tax_rate / 100));
                $total_tax_payable = !empty($_REQUEST['item_total_tax'][$k]) ? floatval($_REQUEST['item_total_tax'][$k]) : ($tax_amt + $further_tax_amt);
                $val_incl = !empty($_REQUEST['item_val_incl'][$k]) ? floatval($_REQUEST['item_val_incl'][$k]) : ($val_excl + $total_tax_payable);

                $item_row = [
                    'tax_purchase_id' => $tax_purchase_id,
                    'product_id' => $pid,
                    'description_of_goods' => $desc,
                    'unit' => $unit,
                    'hs_code' => $hs,
                    'origin' => $origin,
                    'quantity' => $qty,
                    'rate' => $rate,
                    'value_excl_tax' => $val_excl,
                    'tax_rate' => $tax_rate,
                    'tax_amount' => $tax_amt,
                    'further_tax_rate' => $further_tax_rate,
                    'further_tax_amount' => $further_tax_amt,
                    'total_tax_payable' => $total_tax_payable,
                    'value_incl_tax' => $val_incl
                ];
                insert_data($dbc, 'tax_purchase_items', $item_row);
            }
        }

        echo json_encode([
            'sts' => 'success',
            'msg' => 'Tax Purchase Invoice Saved Successfully',
            'order_id' => $tax_purchase_id,
            'type' => 'tax_purchase'
        ]);
    } else {
        echo json_encode([
            'sts' => 'error',
            'msg' => mysqli_error($dbc)
        ]);
    }
    exit;
}

if (isset($_REQUEST['fetch_customer_details'])) {
    $cid = intval($_REQUEST['fetch_customer_details']);
    $cdata = fetchRecord($dbc, 'customers', 'customer_id', $cid);
    if ($cdata) {
        echo json_encode([
            'sts' => 'success',
            'customer_id' => $cdata['customer_id'],
            'customer_name' => $cdata['customer_name'],
            'customer_email' => $cdata['customer_email'],
            'customer_phone' => $cdata['customer_phone'],
            'customer_address' => $cdata['customer_address'],
            'customer_strn' => $cdata['customer_strn'],
            'customer_ntn' => $cdata['customer_ntn'],
            'customer_province' => $cdata['customer_province'],
            'customer_cnic' => $cdata['customer_cnic'],
            'customer_status' => $cdata['customer_status'],
            'customer_type' => $cdata['customer_type']
        ]);
    } else {
        echo json_encode(['sts' => 'error', 'msg' => 'Customer not found']);
    }
    exit;
}

?>
