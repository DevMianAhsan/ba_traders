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
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Income Report</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
    			
		<?php getMessage(@$msg,@$sts); ?>
		<form action="" method="post" class="form-inline print_hide" >
			<div class="row form-group">
			<div class="col-sm-5 ">
				<label for="">From</label>
				<input type="text" class="form-control" autocomplete="off" name="from_date" id="from" placeholder="From Date">
			</div><!-- group -->
			<div class="col-sm-5 ">
				<label for="">To</label>
				<input type="text" class="form-control" autocomplete="off" name="to_date" id="to" placeholder="To Date">
			</div><!-- group -->
			<div class="col-sm-2">
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
		<table class="table">
			<thead>
				<tr>
					<th>Sr.No</th>
					<th>Date</th>
					<th>Expense Type</th>
					<th>Amount</th>
					
				</tr>
			</thead>
			<tbody>
				<?php $i=1; 
				$q = mysqli_query($dbc,"SELECT * FROM budget WHERE (budget_date BETWEEN '$f_date' AND '$t_date') AND budget_type = 'income' "); ?>
				<?php while($r=mysqli_fetch_assoc($q)):

					

					?>

				<tr>
					<th><?=$i?></th>
					<th><?=date('D, d-M-Y',strtotime($r['budget_date']))?></th>
					<th><?= $r['budget_name']?></th>
					<th><?= $r['budget_amount']?></th>
					
					
						
					
				</tr>
			<?php $i++;
			endwhile; ?>
			</tbody>
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