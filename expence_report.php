<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  thead tr th{
    font-size: 19px !important;
    font-weight: bolder !important;
    color: #000 !important;
  }
 tbody tr th{
    font-size: 18px !important;
    font-weight: bolder !important;
      color: #000 !important;
  }
</style>
  <body class="horizontal light  ">
    <div class="">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Expence Report</b>
           
             
                 <a href="developer.php" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">
    		 
		<?php getMessage(@$msg,@$sts); ?>
		<form action="" method="post" class=" print_hide" >
			<div class="row " >
				<div class="col-sm-3 form-group ">
				<label for="">Select Type</label>
				<select class="form-control" name="exp_cat">
					<option value="all">All</option>
					<?php
					$q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_type = 'expense' ");
					while($r=mysqli_fetch_assoc($q)):
						?>
						<option value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
						<?php
					endwhile;
				?>
				</select>
				
			</div><!-- group -->

			<div class="col-3 ">
				<label for="">From</label>
				<input type="text" class="form-control" autocomplete="off" name="from_date" id="from" placeholder="From Date">
			</div><!-- group -->
			<div class="col-3 ">
				<label for="">To</label>
				<input type="text" class="form-control" autocomplete="off" name="to_date" id="to" placeholder="To Date">
			</div><!-- group -->
			<div class="col-3" >
				<label class="hidden">.</label>
			<button class="btn btn-admin2 ml-2" name="search_sale" type="submit">Search</button>
			</div>
			</div>
		</form>
		<?php if(isset($_REQUEST['search_sale'])): 
			$qty=0;
			 $f_date=$_REQUEST['from_date'];
			 $t_date = $_REQUEST['to_date'];
			// $customer_id = $_REQUEST['customer_id'];
			?>


					

					<button onclick="window.print();"  class="btn btn-admin float-right print_btn print_hide">Print Report</button>
		<table class="table" style="width: 100%">
			<thead>
				<tr>
					<th>Sr.No</th>
					<th>Date</th>
					<th>Expense Type</th>
					<th>Amount</th>
					<th>From Acc.</th>
					
				</tr>
			</thead>
			<tbody>
				<?php $i=1;
				$gtotal=0;
				$exp_cat = $_REQUEST['exp_cat']; 
				if($exp_cat == 'all'){
				$q = mysqli_query($dbc,"SELECT * FROM vouchers WHERE voucher_group = 'expense_voucher' OR (voucher_date BETWEEN '$f_date' AND '$t_date') ");
				
			}elseif(empty($f_date)){

				$q = mysqli_query($dbc,"SELECT * FROM vouchers WHERE voucher_group = 'expense_voucher'  AND customer_id2 = '$exp_cat' ");
				echo "SELECT * FROM vouchers WHERE voucher_group = 'expense_voucher'  AND customer_id2 = '$exp_cat' OR (voucher_date BETWEEN '$f_date' AND '$t_date')";

			}else{
				$q = mysqli_query($dbc,"SELECT * FROM vouchers WHERE voucher_group = 'expense_voucher'  AND customer_id2 = '$exp_cat' AND (voucher_date BETWEEN '$f_date' AND '$t_date')");
			}
			 while($r=mysqli_fetch_assoc($q)):

			 	$cust_info = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM customers WHERE customer_id = '$r[customer_id2]'"));
				$cust_info1 = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM customers WHERE customer_id = '$r[customer_id1]'"));
					

					?>

				<tr>
					<td><?=$i?></td>
					<td><?=date('D, d-M-Y',strtotime($r['voucher_date']))?></td>
					<td><?= $cust_info['customer_name']?></td>
					<td><?= $r['voucher_amount']?></td>
					<td><?=$cust_info1['customer_name']?></td>
					
					
						
					
				</tr>
			<?php $i++;
			$gtotal += $r['voucher_amount'];
			endwhile; ?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="3" style="text-align: center;">
						<h3>Total : </h3>
					</th>
					<th><h3><?=$gtotal?></h3></th>
					<th> - </th>
				</tr>
			</tfoot>
		</table>
	<?php endif; ?>
	
           </div>
          </div> <!-- .card -->
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