<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; $current_month = date('Y-m'); ?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Profit/Loss Report</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
    	
		<ul class="nav nav-pills nav-fill mb-3" id="pills-tab" role="tablist">
			<li  class="nav-item "><a aria-controls="pills-profile" aria-selected="false"  data-toggle="pill" class="nav-link active" href="#income">Income</a></li>	
			<li class="nav-item "><a aria-controls="pills-profile" aria-selected="false"  data-toggle="pill" class="nav-link" href="#expense">Expense</a></li>
			<li class="nav-item "><a aria-controls="pills-profile" aria-selected="false"  data-toggle="pill" class="nav-link" href="#analysis">Analysis</a></li>
		</ul><!-- nav -->
		<div class="tab-content">
			<div class="tab-pane  active" id="income">
				<table class="table">
					<thead>
						<tr>
							<th>Dated</th>
							<th>Name</th>
							<th>Amount</th>
						</tr>
					</thead>
					<tbody>
						<?php $q = mysqli_query($dbc,"SELECT * FROM budget WHERE budget_type='income' AND budget_date LIKE '%$current_month%' ORDER BY budget_add_date DESC");
							while($r=mysqli_fetch_assoc($q)):
								$arr=explode("#",$r['budget_name']);
								$order_id = $arr[count($arr)-1];
						 ?>
						<tr>
							<td><?=date('D, d-M-Y',strtotime($r['budget_date']))?></td>
							<td><a target="_blank" href="orders.php?o=editOrd&i=<?=$order_id?>"><?=$r['budget_name']?></a></td>
							<td><?=$r['budget_amount']?></td>
						</tr>
					<?php endwhile; ?>
					</tbody>
				</table>
			</div><!-- income -->
			<div class="tab-pane fade" id="expense">
				<table class="table">
					<thead>
						<tr>
							<th>Dated</th>
							<th>Name</th>
							<th>Amount</th>
						</tr>
					</thead>
					<tbody>
						<?php $q = mysqli_query($dbc,"SELECT * FROM budget WHERE budget_type='expense' AND budget_date LIKE '%$current_month%' ORDER BY budget_add_date DESC");
							while($r=mysqli_fetch_assoc($q)):
						 ?>
						<tr>
							<td><?=date('D, d-M-Y',strtotime($r['budget_date']))?></td>
							<td><?=$r['budget_name']?></td>
							<td><?=$r['budget_amount']?></td>
							
						</tr>
					<?php endwhile; ?>
					</tbody>
				</table>
			</div><!-- expense -->
			<div class="tab-pane fade" id="analysis">
				<div class="row">
			<?php $q=mysqli_query($dbc,"SELECT DISTINCT(DATE_FORMAT(budget_date,'%M-%Y')) AS 'month' FROM budget");
			$sum_income=0;
			$sum_expense=0;
			while($r=mysqli_fetch_assoc($q)):
				//debug_mode($r);
				$date=date("Y-m",strtotime($r['month']));
				$getSumIncome=mysqli_query($dbc,"SELECT * FROM budget WHERE budget_date LIKE '%$date%' AND budget_type='income'");
				while ($fetchSumIncome=mysqli_fetch_assoc($getSumIncome)) {
					# code...
					$sum_income+=$fetchSumIncome['budget_amount'];
				}
				$getSumExpense=mysqli_query($dbc,"SELECT * FROM budget WHERE budget_date LIKE '%$date%' AND budget_type='expense'");
				while ($fetchSumExpense=mysqli_fetch_assoc($getSumExpense)) {
					# code...
					$sum_expense+=$fetchSumExpense['budget_amount'];
				}
			 ?>
			<div class="col-sm-3 badge-pill badge-secondary " style="border: 1px white solid;">
				<a href="index.php?nav=single_view&table_name=income&filter=<?=$date?>" style="text-decoration: none;">
					<div class="thumbnail text-center" style='font-size: 16px !important'>
						<h3 class="text-center mt-2 text-white"><?=$r['month']?></h3>
						<span class="text-white">Income: <?=$sum_income?></span><br>
						<span class="text-white">Expense: <?=$sum_expense?></span><br>
						<span class="text-white" >Profit: <?=$sum_income-$sum_expense?></span>
					</div>
				</a><!-- thumbnail -->
			</div><!-- col -->
		
			<?php $sum_income=0;$sum_expense=0; endwhile; ?>
		</div><!-- row -->

			</div><!-- analysis -->
		</div><!-- content -->
	
           </div>
          </div> <!-- .card -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>