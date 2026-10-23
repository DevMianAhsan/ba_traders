<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Sale Report</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
    			<form action="" method="post" class="form-inline print_hide" >
			<div class="form-group">
				<label for="">Customer Account</label>
				<select class="form-control" id="clientName" name="customer_id" autofocus="true">
		      	<option value="">~~SELECT~~</option>
		      	<?php 
		      	$sql = "SELECT * FROM customers WHERE customer_status = 1 AND customer_type='customer'";
						$result = $connect->query($sql);

						while($row = $result->fetch_array()) {
							echo "<option value='".$row[0]."'>".$row[1]."</option>";
						} // while
						
		      	?>
		      </select>	
			</div><!-- group -->
			<div class="form-group">
				<label for="">From</label>
				<input type="text" class="form-control" autocomplete="off" name="from_date" id="from" placeholder="From Date">
			</div><!-- group -->
			<div class="form-group">
				<label for="">To</label>
				<input type="text" class="form-control" autocomplete="off" name="to_date" id="to" placeholder="To Date">
			</div><!-- group -->
			<button class="btn btn-admin2" name="search_sale" type="submit">Search</button>
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

    			<table  class="table table-bordered">
			<thead>
				<tr>
					<th>Sr.No</th>
					<th>Dated</th>
					<th>Bill#</th>
					<th>Item</th>
					<th>Sold Qty</th>
					<th>Rate</th>
					<th>Grand Total</th>
					<th>Party Detail</th>
				</tr>  
			</thead>
			<tbody>
				<?php $i=1; 
				if($f_date > 0 AND $t_date>0 AND $customer_id>0  ){
				 $q = mysqli_query($dbc,"SELECT * FROM orders WHERE (order_date BETWEEN '$f_date' AND '$t_date') AND customer_account='$customer_id'");
}elseif($f_date > 0 AND $t_date>0){
	 $q = mysqli_query($dbc,"SELECT * FROM orders WHERE (order_date BETWEEN '$f_date' AND '$t_date')");
}else{
	 $q = mysqli_query($dbc,"SELECT * FROM orders WHERE  customer_account='$customer_id'");
}
				 ?>
				<?php while($r=mysqli_fetch_assoc($q)):

					$fetchCustomer = fetchRecord($dbc,"customers","customer_id",$r['customer_account']);
					$getItem = mysqli_query($dbc,"SELECT * FROM order_item WHERE order_id='$r[order_id]'");

					?>

				<tr>
					<th><?=$i?></th>
					<th><?=date('D, d-M-Y',strtotime($r['order_date']))?></th>
					<th><?=$r['order_id']?></th>
					<th>
						<?php 

					while($fetchItem=mysqli_fetch_assoc($getItem)):
						$fetchProduct = fetchRecord($dbc,"product",'product_id',$fetchItem['product_id']);
						$fetchCategory = fetchRecord($dbc,"categories","categories_id",$fetchProduct['category_id']);?>
						<p><?=$fetchProduct['product_name']?> <small><?=$fetchCategory['categories_name']?></small></p>
					<?php endwhile; ?>
						</th>
					<th>
					<?php 
					$getItem = mysqli_query($dbc,"SELECT * FROM order_item WHERE order_id='$r[order_id]'");
					while($fetchItem=mysqli_fetch_assoc($getItem)):
						$fetchProduct = fetchRecord($dbc,"product",'product_id',$fetchItem['product_id']);
						$fetchCategory = fetchRecord($dbc,"categories","categories_id",$fetchProduct['category_id']);?>
						<p><?=$fetchItem['quantity']?> <span class="text-right">x</span></p>
					<?php endwhile; ?>
						</th>
					<th>
					<?php 
					$getItem = mysqli_query($dbc,"SELECT * FROM order_item WHERE order_id='$r[order_id]'");
					while($fetchItem=mysqli_fetch_assoc($getItem)):
						$fetchProduct = fetchRecord($dbc,"product",'product_id',$fetchItem['product_id']);
						$fetchCategory = fetchRecord($dbc,"categories","categories_id",$fetchProduct['category_id']);?>
						<p><?=$fetchItem['rate']?></p>
					<?php endwhile; ?>
						</th>
					<th>
					<?php 
					$getItem = mysqli_query($dbc,"SELECT * FROM order_item WHERE order_id='$r[order_id]'");
					while($fetchItem=mysqli_fetch_assoc($getItem)):
						$fetchProduct = fetchRecord($dbc,"product",'product_id',$fetchItem['product_id']);
						$fetchCategory = fetchRecord($dbc,"categories","categories_id",$fetchProduct['category_id']);?>
						<p><?=$fetchItem['total']?></p>
					<?php endwhile; ?>
						</th>
					<th><?=$fetchCustomer['customer_name']?> <br><?=$r['client_contact']?></th>
				</tr>
			<?php $i++;endwhile; ?>
			</tbody>
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