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
                <b class="text-center card-text">Daily Profit Summary</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
    		
			<table class="table">
				<thead>
					<tr>
						<th>Dated</th>
						<th>Sale</th>
						<th>Profit</th>
						<th>Expense</th>
						<th>Net Profit</th>
					</tr>
				</thead>
				<tbody>

					<?php 
					 $expense=$net_profit=0;
					$q = $dbc->query("SELECT DISTINCT(order_date) FROM orders WHERE order_status=1 ORDER BY order_date DESC"); 
					while($r=$q->fetch_assoc()):
						$getOrder = $dbc->query("SELECT * FROM orders WHERE order_date='$r[order_date]'");
						$getBudget = $dbc->query("SELECT * FROM budget WHERE budget_date='$r[order_date]' AND budget_type='expense'");
						$order_date=$r['order_date'];
						$grand_total=0;
					?>
					<tr>
						<td><?=date('D, d-M-Y',strtotime($r['order_date']))?></td>
						<td>
							<?php while($fetchOrderGrand=$getOrder->fetch_assoc()): ?>
								<?php $grand_total+=$fetchOrderGrand['grand_total']; ?>
							<?php endwhile; ?>
							<?php echo $grand_total; ?>
							
						</td>
						<td>
						
							<?php while($fetchOrder=$getOrder->fetch_assoc()): ?>
								<?php	
									
										 $sql = "SELECT * FROM order_item WHERE order_id = '$fetchOrder[order_id]' AND order_item_status=1";
											$query = $dbc->query($sql);
									while ($result = $query->fetch_assoc()) {
										  $product_id= $result['product_id'];
										 $sold_quantity= $result['quantity'];
										 $sold_rate= $result['rate'];
										
									$sql_item = "SELECT * FROM product WHERE product_id = '$product_id'";
									$query_item = $dbc->query($sql_item);
									while ($result_item = $query_item->fetch_assoc()) {
										 $product_purchase= $result_item['purchase_rate'];
									
									$sold_income = $sold_quantity * $sold_rate;
									$purchase_income = $product_purchase * $sold_quantity; 
								}
								 	$net_profit+=$sold_income-$purchase_income ;
								 }//while
										?>
							<?php endwhile; ?>
							<h2 class="label label-success" style="font-size: 25px"><?php echo  @$net_profit; ?></h2>
						</td>
						<td>
							<?php while($fetchBudget=$getBudget->fetch_assoc()): ?>
								<?php $expense+=$fetchBudget['budget_amount']; ?>
							<?php endwhile; ?>
							<h2 class="label label-danger" style="font-size: 25px"><?php echo  @$expense; ?></h2>
						</td>
						<td>
							<h2 class="label label-warning" style="font-size: 25px"><?php echo  @($net_profit-$expense); ?></h2>
						</td>
					</tr>
				<?php $expense= $net_profit=0;endwhile; ?>
				</tbody>
			</table>
			
           </div>
          </div> <!-- .card -->

          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Month Profit Summary</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">
    		
			<table class="table">
				<thead>
					<tr>
						<th>Dated</th>
						<th>Sale</th>
						<th>Profit</th>
						<th>Expense</th>
						<th>Net Profit</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					// echo $current_month = date('Y-m');
					$grand_total= $expense=$net_profit=0;

					$q = $dbc->query("SELECT DISTINCT(DATE_FORMAT(order_date,'%Y-%m')) AS 'month' FROM orders  WHERE order_status=1 ORDER BY order_date DESC");
					while($r=$q->fetch_assoc()):
						$current_month=$r['month'];
						$mon = date('Y-m',strtotime($r['month']));
						$getOrder = $dbc->query("SELECT * FROM orders WHERE order_date LIKE '%$current_month%' AND order_status=1");
						$getOrderGrand = $dbc->query("SELECT * FROM orders WHERE order_date LIKE '%$current_month%' AND order_status=1");
						$getBudget = $dbc->query("SELECT * FROM budget WHERE budget_date LIKE '%$current_month%' AND budget_type='expense'");
						
					?>
					<tr>
						<td><?= date('M-Y',strtotime($mon)) ?></td>
						<td>
							<?php while($fetchOrderGrand=$getOrderGrand->fetch_assoc()): ?>
								<?php $grand_total+=$fetchOrderGrand['grand_total']; ?>
							<?php endwhile; ?>
							<?php echo $grand_total; ?>
							
						</td>
						<td>
						
							<?php while($fetchOrder=$getOrder->fetch_assoc()): ?>
								<?php	
									
										 $sql = "SELECT * FROM order_item WHERE order_id = '$fetchOrder[order_id]' AND order_item_status=1";
											$query = $dbc->query($sql);
									while ($result = $query->fetch_assoc()) {
										  $product_id= $result['product_id'];
										 $sold_quantity= $result['quantity'];
										 $sold_rate= $result['rate'];
										
									$sql_item = "SELECT * FROM product WHERE product_id = '$product_id'";
									$query_item = $dbc->query($sql_item);
									while ($result_item = $query_item->fetch_assoc()) {
										 $product_purchase= $result_item['purchase_rate'];
									
									$sold_income = $sold_quantity * $sold_rate;
									$purchase_income = $product_purchase * $sold_quantity; 
								}
								 	$net_profit+=$sold_income-$purchase_income ;
								 }//while
										?>
							<?php endwhile; ?>
							<h2 class="label label-success" style="font-size: 25px"><?php echo  @$net_profit; ?></h2>
						</td>
						<td>
							<?php while($fetchBudget=$getBudget->fetch_assoc()): ?>
								<?php $expense+=$fetchBudget['budget_amount']; ?>
							<?php endwhile; ?>
							<h2 class="label label-danger" style="font-size: 25px"><?php echo  @$expense; ?></h2>
						</td>
						<td>
							<h2 class="label label-warning" style="font-size: 25px"><?php echo  @($net_profit-$expense); ?></h2>
						</td>
					</tr>
				<?php $grand_total=$expense= $net_profit=0;endwhile; ?>
					
				</tbody>
			</table>
			
           </div>
          </div> <!-- .card -->
        
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>