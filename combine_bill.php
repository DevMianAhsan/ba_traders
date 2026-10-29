<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  thead tr th{
    font-size: 19px !important;
    font-weight: bolder !important;
    color: #000 !important;
  }
  tbody tr th,tbody tr th p{
    font-size: 18px !important;
    font-weight: bolder !important;
      color: #000 !important;
  }
</style>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row d-print-none">
              <div class="col-12 mx-auto h4">
                 <b class="text-center card-text">Sale Report</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
    			<form action="" method="get" class=" d-print-none" >
							<div class="row">
								<div class="col-sm-3">
										<div class="form-group">
				<label for="">Customer Account</label>
				<select class="form-control searchableSelect" id="clientName" name="customer_id" autofocus="true">
		      	<option value="">~~SELECT~~</option>
		      	<?php 
		      	$sql = "SELECT * FROM customers WHERE customer_status = 1 AND customer_type='customer' ORDER BY customer_name ASC";
						$result = $connect->query($sql);

						while($row = $result->fetch_array()) {
							echo "<option value='".$row['customer_id']."'>".htmlspecialchars(getCustomerDisplayName($row['customer_name'], $row['customer_phone']))."</option>";
						} // while
						
		      	?>
		      </select>	
			</div><!-- group -->

								</div>
								<div  class="col-sm-3">
										<div class="form-group">
				<label for="">From</label>
				<input type="text" class="form-control" autocomplete="off" name="from_date" id="from" placeholder="From Date">
			</div><!-- group -->
								</div>
								<div class="col-sm-3">
									<div class="form-group">
				<label for="">To</label>
				<input type="text" class="form-control" autocomplete="off" name="to_date" id="to" placeholder="To Date">
			</div><!-- group -->
								</div>
								<div class="col-sm-2">
									<div class="form-group">
										<label>Type</label>
										<select  class="form-control searchableSelect" name="sale_type">
											<option value="all">Select Type</option>
											<option value="cash_in_hand">Cash Sale</option>
											<option value="credit_sale">Credit Sale</option>
										</select>
									</div>
								</div>
								<div class="col-sm-1">
										<label style="visibility: hidden;">a</label><br>
			<button class="btn btn-admin2" name="search_sale" type="submit">Search</button>
									
								</div>
							</div>
				
			
		</form>
           </div>
          </div> <!-- .card -->
          <?php if(isset($_REQUEST['search_sale'])): 
			$qty=0;
			 $f_date=$_REQUEST['from_date'];
			 $t_date = $_REQUEST['to_date'];
			 $customer_id = $_REQUEST['customer_id'];
			?>
          <div class="card">
          	 <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Sale Report</b>
			<button onclick="window.print();"  class="btn btn-admin btn-sm float-right print_btn print_hide">Print Report</button>
           	
             
              </div>
            </div>
  
          </div>
          	<div class="card-body">
          				<div class="float-right">
          					<button id="showSelectedOrders" class="btn btn-admin">Print Selected Orders</button>
          				</div>
    			<table  class="table table-bordered">
			<thead>
				<tr>
					<th>Sr.No</th>
					<th>Dated</th>
					<th style="width: 100px">Challan#</th>
					<th>Party PO</th>
					<th>Total</th>
					<th>Party Detail</th>
					<th>Sale Type</th>
				</tr>  
			</thead>
			<tbody>
				<?php $i=1; 
				if (!empty($_REQUEST['sale_type']) AND $_REQUEST['sale_type']!='all') {
					$paymentType="AND payment_type='".$_REQUEST['sale_type']."' ";
				}else{
						$paymentType='';

				}
				if (!empty($_REQUEST['customer_id'])) {
					$customer_account="customer_account='".$_REQUEST['customer_id']."' AND";
				}else{
						$customer_account='';

				}
				if($f_date > 0 AND $t_date>0 AND $customer_id>0  ){
				 $q ="SELECT * FROM orders WHERE ".$customer_account." (order_date BETWEEN '$f_date' AND '$t_date')   ".$paymentType." ";
}elseif($f_date > 0 AND $t_date>0){
	 $q = "SELECT * FROM orders WHERE ".$customer_account." (order_date BETWEEN '$f_date' AND '$t_date') ".$paymentType." ";
}else{
		if (!empty($_REQUEST['customer_id'])) {
					$customer_account="customer_account='".$_REQUEST['customer_id']."' ";
				}else{
						$customer_account='';

				}
				if (!empty($_REQUEST['sale_type']) AND $_REQUEST['sale_type']!='all' AND empty($_REQUEST['customer_id'])) {
					$paymentType="payment_type='".$_REQUEST['sale_type']."' ";
				}else{
						$paymentType='';

				}
				//echo "sa";
	 $q = "SELECT * FROM orders WHERE  ".$customer_account." ".$paymentType." ";
}
				 ?>
				<?php 
				//echo $q;
 $query=mysqli_query($dbc,$q);

 				$Grandgrandtotal = 0;
 				$creditGrand = 0;
				while($r=mysqli_fetch_assoc($query)):

					$fetchCustomer = fetchRecord($dbc,"customers","customer_id",$r['customer_account']);
					$getItem = mysqli_query($dbc,"SELECT * FROM order_item WHERE order_id='$r[order_id]'");

					?>

				<tr>
					<th><input type="checkbox" name="selected_orders[]" value="<?=$r['order_id']?>" style="width: 20px;height: 20px">	</th>
					<th><?=date('D, d-M-Y',strtotime($r['order_date']))?></th>
				
					<th><?=$r['order_id']?></th>
					<th><?=$r['party_po']?></th>
					<th><?=$r['grand_total']?></th>
						
				
						
					<th>
						<?=$r['client_name']?> <br><?=$r['client_contact']?>

					</th>
					<th><?=$r['payment_type']?></th>
				</tr>
			<?php $i++;endwhile; ?>
			</tbody>
			<tr>
				<td colspan="4"><center><h3>Cash Sale</h3></center></td>
				<td><h3><?=$Grandgrandtotal?></h3></td>
				<td><h3>Credit Sale </h3></td>
				<td><h3><?=$creditGrand?></h3></td>
			</tr>
		</table>
           </div>
          </div>
          <?php endif; ?>

        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>
<script>
	$( function() {
		var dateFormat = "yy-mm-dd";
			from = $( "#from" )
				.datepicker({
					changeMonth: true,
					numberOfMonths: 1,
					dateFormat : "yy-mm-dd",
				})
				.on( "change", function() {
					to.datepicker( "option", "minDate", getDate( this ) );
				}),
			to = $( "#to" ).datepicker({
				changeMonth: true,
				numberOfMonths: 1,
				dateFormat : "yy-mm-dd",
			})
			.on( "change", function() {
				from.datepicker( "option", "maxDate", getDate( this ) );
			});

		function getDate( element ) {
			var date;
			try {
				date = $.datepicker.parseDate( dateFormat, element.value );
			} catch( error ) {
				date = null;
			}

			return date;
		}
	} );
	</script>

	
<script>
$(document).ready(function() {
    $('#showSelectedOrders').click(function() {
        // Get the selected order IDs
        var selectedOrders = [];
        $('input[name="selected_orders[]"]:checked').each(function() {
            selectedOrders.push($(this).val());
        });

        // Display the selected order IDs (you can customize how you want to display them)
        // Create a URL with the selected order IDs
        var url = 'display_selected_orders.php?order_ids=' + selectedOrders.join(',');

        // Open a new window with the URL
        window.open(url, '_blank');
    });
});
</script>
