<!doctype html>
<html lang="en">
<?php include_once 'includes/head.php'; $current_date=date('Y-m-d');
  $d=strtotime("last Day");

$yesterday_date=date("Y-m-d", $d);
$previous_week = strtotime("-1 week +1 day"); 
$start_week = strtotime("last sunday midnight",$previous_week);
$end_week = strtotime("next saturday",$start_week);

$start_week = date("Y-m-d",$start_week);
$end_week = date("Y-m-d",$end_week);
$d = strtotime("today");
$last_start_week = strtotime("last sunday midnight",$d);
$last_end_week = strtotime("next saturday",$d);
$start = date("Y-m-d",$last_start_week); 
$end = date("Y-m-d",$last_end_week); 
$start_of_month= date('Y-m-01', strtotime(date('Y-m-d')));
// Last day of the month.
$end_of_month= date('Y-m-t', strtotime($current_date));
 ?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="row justify-content-center">
            <div class="col-12">
              <div class="row align-items-center mb-2">
                <div class="col">
                  <h2 class="h5 page-title">Welcome!</h2>
                </div>
                <div class="col-auto">
                  <form class="form-inline">
                    <div class="form-group d-none d-lg-inline">
                      <label for="reportrange" class="sr-only">Date Ranges</label>
                      <div id="reportrange" class="px-2 py-2 text-muted">
                        <span class="small"></span>
                      </div>
                    </div>
                    <div class="form-group">
                      <button type="button" onclick="location.reload()" class="btn btn-sm" title="Refresh Dashboard"><span class="fe fe-refresh-ccw fe-16 text-muted"></span></button>
                      <button type="button" class="btn btn-sm mr-2"><span class="fe fe-filter fe-16 text-muted"></span></button>
                    </div>
                  </form>
                </div>
              </div>
              


              <div class="row">
                
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-primary text-white border-0" onclick="window.location.href='credit_orders.php'" style="cursor: pointer;" title="View Delivery Challans / Credit Sales">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-trending-up text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Today Sales</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                              @$total_sales=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(grand_total) as total_sales FROM orders WHERE order_date='$current_date' AND (credit_sale_type='30days' OR payment_type='credit_sale') "))['total_sales'];
                             $total=isset($total_sales)?$total_sales:"0";
                             echo '<small class="text-white-50" style="font-size: 15px;">Rs. </small>' . number_format($total);
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='credit_sale.php?credit_type=30days#'" style="cursor: pointer;" title="New Delivery Challan / Credit Sale">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-shopping-cart text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-muted mb-0">Today Orders</p>
                          <span class="h3 mb-0">
                            <?php 
                              @$total_orders=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT count(*) as total_orders FROM orders WHERE order_date='$current_date' AND (credit_sale_type='30days' OR payment_type='credit_sale') "))['total_orders'];
                             echo $total=isset($total_orders)?$total_orders:"0";
                             ?>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='credit_orders.php'" style="cursor: pointer;" title="View Delivery Challans / Credit Sales">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-bar-chart-2 text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col">
                          <p class="small text-muted mb-0">Total Sales</p>
                          <div class="row align-items-center no-gutters">
                            <div class="col-12">
                              <span class="h3 mr-2 mb-0">
                            <?php 
                             if($UserData['user_role'] == 'admin'){
                              $total_sales=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(grand_total) as total_sales FROM orders WHERE (credit_sale_type='30days' OR payment_type='credit_sale')"))['total_sales'];
                              $total=isset($total_sales)?$total_sales:"0";
                              echo '<small class="text-muted" style="font-size: 15px;">Rs. </small>' . number_format($total);
                            }else{
                              echo "0";
                            }
                             ?>
                            
                          </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='credit_orders.php'" style="cursor: pointer;" title="View Delivery Challans / Credit Sales">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-package text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col">
                          <p class="small text-muted mb-0">Total Orders</p>
                          <span class="h3 mb-0">
                            <?php 
                             if($UserData['user_role'] == 'admin'){
                              @$total_orders=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT count(*) as total_orders FROM orders WHERE (credit_sale_type='30days' OR payment_type='credit_sale')"))['total_orders'];
                             echo $total=isset($total_orders)?$total_orders:"0";
                           }else{
                            echo "0";
                           }
                             ?>
                           
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              
              </div><!-- First row end -->

               <div class="row">
                
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-primary text-white border-0" onclick="window.location.href='tax_purchase_list.php'" style="cursor: pointer;" title="View Tax Purchases">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-truck text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Today Purchase</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                              @$total_purchase=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(invoice_amount) as total_sales FROM tax_purchases where invoice_date='$current_date' "))['total_sales'];
                             $total_purchase2=isset($total_purchase)?$total_purchase:"0";
                             echo '<small class="text-white-50" style="font-size: 15px;">Rs. </small>' . number_format($total_purchase2);
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='tax_purchase.php'" style="cursor: pointer;" title="Add New Tax Purchase">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-file-text text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-muted mb-0">Today Purchases</p>
                          <span class="h3 mb-0">
                            <?php 
                              @$total_purchases=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT count(*) as total_orders FROM tax_purchases where invoice_date='$current_date' "))['total_orders'];
                              $total_purchases2=isset($total_purchases)?$total_purchases:"0";
                             echo number_format($total_purchases2); 
                             ?>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='tax_purchase_list.php'" style="cursor: pointer;" title="View Tax Purchases">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-truck text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col">
                          <p class="small text-muted mb-0">Total Purchase</p>
                          <div class="row align-items-center ">
                            <div class="col-12">
                              <span class="h3 mr-2 mb-0">
                            <?php 
                            if($UserData['user_role'] == 'admin'){
                              @$total_sales=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(invoice_amount) as total_sales FROM tax_purchases"))['total_sales'];
                              $total=isset($total_sales)?$total_sales:"0";
                              echo '<small class="text-muted" style="font-size: 15px;">Rs. </small>' . number_format($total);
                            }else{
                             echo "0";
                            }
                              ?>
                            
                          </span>
                            </div>
                            
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='tax_purchase_list.php'" style="cursor: pointer;" title="View Tax Purchases">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-layers text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col">
                          <p class="small text-muted mb-0">Total Purchases</p>
                          <span class="h3 mb-0">
                            <?php 
                             if($UserData['user_role'] == 'admin'){
                              @$total_orders=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT count(*) as total_orders FROM tax_purchases"))['total_orders'];
                             echo $total=isset($total_orders)?$total_orders:"0";
                           }else{
                            echo "0";
                           }
                             ?>
                           
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              
              </div><!-- Second row end  -->
<div class="row">
  
                
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-primary text-white border-0" onclick="window.location.href='voucher.php?act=lists'" style="cursor: pointer;" title="View Received Vouchers / Payments">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-check-circle text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Today Received (With WHT)</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                              // Customer vouchers today (cash/bank amount)
                              $q_v_today = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(CAST(REPLACE(v.voucher_amount, ',', '') AS DECIMAL(15,2))) as v_amt FROM vouchers v JOIN customers c ON v.customer_id1 = c.customer_id WHERE v.voucher_date = '$current_date' AND c.customer_type = 'customer'"));
                              $v_amt_today = floatval($q_v_today['v_amt'] ?? 0);

                              // WHT deducted today
                              $q_wht_today = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(CAST(REPLACE(COALESCE(vi.tax_amount, '0'), ',', '') AS DECIMAL(15,2))) as v_wht FROM voucher_invoices vi JOIN vouchers v ON vi.voucher_id = v.voucher_id WHERE v.voucher_date = '$current_date' AND (v.customer_id1 IN (SELECT customer_id FROM customers WHERE customer_type = 'customer') OR vi.invoice_id IN (SELECT order_id FROM orders) OR vi.invoice_id IN (SELECT invoice_no FROM tax_sales))"));
                              $v_wht_today = floatval($q_wht_today['v_wht'] ?? 0);

                              // Direct orders paid today (if any)
                              $q_ord_today = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(paid) as ord_paid FROM orders WHERE order_date = '$current_date'"));
                              $ord_paid_today = floatval($q_ord_today['ord_paid'] ?? 0);

                              $today_received_without_wht = $ord_paid_today + $v_amt_today;
                              $today_received_with_wht = $today_received_without_wht + $v_wht_today;

                              echo '<small class="text-white-50" style="font-size: 15px;">Rs. </small>' . number_format($today_received_with_wht);
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='voucher.php?act=lists'" style="cursor: pointer;" title="View Received Vouchers / Payments">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-pocket text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-muted mb-0">Today Received (Without WHT)</p>
                          <span class="h3 mb-0">
                            <?php 
                              echo '<small class="text-muted" style="font-size: 15px;">Rs. </small>' . number_format($today_received_without_wht);
                             ?>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='dues_report.php'" style="cursor: pointer;" title="View Customer Receivables in Dues Report">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-users text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col">
                          <p class="small text-muted mb-0">Total Receivable from Customers</p>
                          <div class="row align-items-center no-gutters">
                            <div class="col-12">
                              <span class="h3 mr-2 mb-0">
                            <?php 
                              $pending_dues_sum = 0;
                              $q_all_custs = mysqli_query($dbc, "SELECT customer_id FROM customers WHERE customer_type = 'customer' AND customer_status = 1");
                              while ($ac = mysqli_fetch_assoc($q_all_custs)) {
                                $cb = getcustomerBlance($dbc, $ac['customer_id']);
                                if ($cb >= 1.0) { $pending_dues_sum += $cb; }
                              }
                              echo '<small class="text-muted" style="font-size: 15px;">Rs. </small>' . number_format($pending_dues_sum);
                             ?>
                          </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow border-0" onclick="window.location.href='dues_report.php'" style="cursor: pointer;" title="View Supplier Pending Payables in Dues Report">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-primary">
                            <i class="fe fe-16 fe-truck text-white mb-0"></i>
                          </span>
                        </div>
                        <div class="col">
                          <p class="small text-muted mb-0">Total Payable to Supplier</p>
                          <div class="row align-items-center no-gutters">
                            <div class="col-12">
                              <span class="h3 mr-2 mb-0">
                                <?php 
                                  $supp_sql = "SELECT customer_id FROM customers WHERE customer_status = 1 AND customer_type = 'supplier'";
                                  $q_supp = mysqli_query($dbc, $supp_sql);
                                  $total_supplier_pending_pay = 0;
                                  while ($s = mysqli_fetch_assoc($q_supp)) {
                                    $sid = $s['customer_id'];
                                    $q_all = mysqli_query($dbc, "SELECT SUM(CAST(debit AS DECIMAL(15,2))) AS tot_dr, SUM(CAST(credit AS DECIMAL(15,2))) AS tot_cr FROM transactions WHERE customer_id = '$sid'");
                                    $r_all = mysqli_fetch_assoc($q_all);
                                    $net_pending = floatval($r_all['tot_dr'] ?? 0) - floatval($r_all['tot_cr'] ?? 0);
                                    if (abs($net_pending) <= 1.5) {
                                      $net_pending = 0.0;
                                    }
                                    if ($net_pending >= 1.0) {
                                      $total_supplier_pending_pay += $net_pending;
                                    }
                                  }
                                  echo '<small class="text-muted" style="font-size: 15px;">Rs. </small>' . number_format($total_supplier_pending_pay);
                                ?>
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              
              
</div>
<!-- row start-->

<div class="row align-items-stretch">
                <div class="col-md-6 mb-4 d-flex">
                  <div class="card shadow w-100 d-flex flex-column mb-0">
                    <div class="card-header bg-primary d-flex justify-content-between align-items-center py-3">
                      <strong class="text-white mb-0">Accounts</strong>
                      <a class="small text-white font-weight-bold" href="reports.php?type=bank">Bank Ledger</a>
                    </div>
                    <div class="card-body px-4 d-flex flex-column flex-fill">
                      <table class="table table-hover table-striped mb-0">
                        <thead class="bg-light">
                          <tr>
                            <th class="py-2 text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                              Account Name
                            </th>
                            <th class="py-2 text-right text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                              Available Balance (Rs)
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                            $bank_accounts = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_type = 'bank' AND customer_status = 1 ORDER BY customer_id ASC");
                            $total_available_all = 0;
                            while ($b_acc = mysqli_fetch_assoc($bank_accounts)) {
                              $b_id = $b_acc['customer_id'];
                              $b_tx = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(credit-debit) as bal FROM transactions WHERE customer_id = '$b_id'"));
                              $b_bal = !empty($b_tx['bal']) ? floatval($b_tx['bal']) : 0;
                              $total_available_all += $b_bal;
                              $is_cash = (stripos($b_acc['customer_name'], 'cash') !== false);
                          ?>
                          <tr>
                            <td class="py-2 font-weight-normal align-middle">
                              <a href="reports.php?type=bank&customer_id=<?=$b_id?>" class="text-dark" title="View Ledger">
                                <i class="fe <?=$is_cash ? 'fe-pocket text-success' : 'fe-credit-card text-primary'?> mr-2"></i> <?=htmlspecialchars(ucwords($b_acc['customer_name']))?>
                              </a>
                            </td>
                            <td class="text-right font-weight-bold py-2 align-middle <?=($b_bal < 0 ? 'text-danger' : ($b_bal > 0 ? 'text-success' : 'text-dark'))?>" style="font-size: 14px;">
                              <?=number_format($b_bal)?> <span class="small font-weight-normal text-muted">Rs</span>
                            </td>
                          </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div> <!-- .card-body -->
                    <div class="card-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center mt-auto">
                      <span class="small font-weight-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">
                        <i class="fe fe-pocket mr-1 text-success"></i> Total Available Amount
                      </span>
                      <strong class="<?=($total_available_all < 0 ? 'text-danger' : 'text-success')?> font-weight-bold" style="font-size: 15px;">
                        <?=number_format($total_available_all)?> <span class="small font-weight-normal text-muted">Rs</span>
                      </strong>
                    </div>
                  </div> <!-- .card -->
                </div> <!-- .col -->
                <div class="col-md-6 mb-4 d-flex">
                  <div class="card shadow w-100 d-flex flex-column mb-0">
                    <div class="card-header bg-primary d-flex justify-content-between align-items-center py-3">
                      <strong class="text-white mb-0">Customer Pending Dues</strong>
                      <a class="small text-white font-weight-bold" href="dues_report.php">View All Dues</a>
                    </div>
                    <div class="card-body px-4 d-flex flex-column flex-fill">
                      
                      <div class="list-group list-group-flush my-n3 flex-fill">
                        <?php 
                          $cust_query = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_type = 'customer' AND customer_status = 1 ORDER BY customer_name ASC");
                          $pending_customers = [];
                          $total_pending_dues_card = 0;
                          while ($cust = mysqli_fetch_assoc($cust_query)) {
                            $bal = getcustomerBlance($dbc, $cust['customer_id']);
                            if ($bal >= 1.0) {
                              $cust['balance'] = $bal;
                              $pending_customers[] = $cust;
                              $total_pending_dues_card += $bal;
                            }
                          }
                          // Sort by balance DESC (highest due first)
                          usort($pending_customers, function($a, $b) {
                            return $b['balance'] <=> $a['balance'];
                          });

                          if (empty($pending_customers)) {
                        ?>
                          <div class="list-group-item text-center py-4 text-muted">
                            <i class="fe fe-check-circle text-success fe-24 mb-2 d-block"></i>
                            No pending customer dues found.
                          </div>
                        <?php 
                          } else {
                            $c = 0;
                            foreach ($pending_customers as $customer) {
                              $c++;
                        ?>
                        <div class="list-group-item">
                          <div class="row align-items-center">
                            <div class="col-2 col-md-1 text-center">
                              <span class="badge badge-light font-weight-bold" style="font-size: 13px;"><?=$c?></span>
                            </div>
                            <div class="col">
                              <a href="reports.php?type=customer&customer_id=<?=$customer['customer_id']?>" class="text-dark font-weight-bold" title="View Customer Ledger">
                                <?=htmlspecialchars(ucwords($customer['customer_name']))?>
                              </a>
                              <div class="my-0 text-muted small">
                                <?php 
                                  $contact_info = array_filter([$customer['customer_phone'], $customer['customer_email']]);
                                  echo !empty($contact_info) ? htmlspecialchars(implode(', ', $contact_info)) : '-';
                                ?>
                              </div>
                            </div>
                            <div class="col-auto text-right">
                              <span class="font-weight-bold text-danger" style="font-size: 14px;">
                                <span class="small text-muted font-weight-normal">Rs. </span><?=number_format($customer['balance'])?>
                              </span>
                            </div>
                          </div>
                        </div>
                        <?php 
                            }
                          } 
                        ?>
                      </div> <!-- / .list-group -->
                    </div> <!-- / .card-body -->
                    <div class="card-footer bg-light py-2 px-3 border-top d-flex justify-content-between align-items-center mt-auto">
                      <span class="small font-weight-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">
                        <i class="fe fe-alert-circle mr-1 text-danger"></i> Total Customer Receivables
                      </span>
                      <strong class="text-danger font-weight-bold" style="font-size: 15px;">
                        Rs. <?=number_format($total_pending_dues_card)?>
                      </strong>
                    </div>
                  </div> <!-- .card -->
                </div> <!-- .col -->
 </div> <!-- .row -->
<!-- Row end -->


    <!-- .row -->
              <div class="row align-items-stretch">
                <div class="col-md-6 mb-4 d-flex">
                 <div class="card shadow w-100 d-flex flex-column mb-0">
                    <div class="card-header bg-primary d-flex justify-content-between align-items-center py-3">
                      <strong class="card-title text-white mb-0">Pending Delivery Challans</strong>
                      <a class="small text-white font-weight-bold" href="credit_orders.php">View all</a>
                    </div>
                    <div class="card-body px-3 py-2 flex-fill d-flex flex-column">
                      <div class="table-responsive flex-fill">
                        <table class="table table-striped table-hover table-borderless mb-0">
                          <thead>
                            <tr>
                              <th>Order No.</th>
                              <th>Customer Details</th>
                              <th>Order Date</th>
                              <th>Payment Date</th>
                              <th>Amount</th>
                              <th>Remaining Days</th>
                            </tr>
                          </thead>
                          <tbody> 
                            <?php 
                              $q = mysqli_query($dbc, "SELECT * FROM orders WHERE (credit_sale_type='30days' OR credit_sale_type='15days' OR payment_type='credit_sale' OR payment_type='cash_in_hand') AND payment_status=0 ORDER BY order_id DESC LIMIT 5");
                              $c = 0;
                              $now = strtotime(date('Y-m-d'));
                              while ($row = mysqli_fetch_assoc($q)) { 
                                $c++;
                                if ($row['credit_sale_type'] == "15days") {
                                  $next_date = date('Y-m-d', strtotime($row['order_date'] . ' + 15 days'));
                                } else {
                                  $next_date = date('Y-m-d', strtotime($row['order_date'] . ' + 30 days'));
                                }
                                $your_date = strtotime($next_date);
                                $datediff = $your_date - $now;
                                $total_days = round($datediff / (60 * 60 * 24));
                            ?>
                            <tr>
                              <td><?=$row['order_id']?></td>
                              <td><?=htmlspecialchars(ucwords($row['client_name']))?> (<?=htmlspecialchars($row['client_contact'])?>)</td>
                              <td><?=$row['order_date']?></td>
                              <td><?=$next_date?></td>
                              <td><small class="text-muted">Rs. </small><?=number_format((float)$row['due'])?></td>
                              <td>
                                <?php if ($total_days < 6): ?>
                                  <span class="badge badge-danger"><?=$total_days?></span>
                                <?php else: ?> 
                                  <span class="badge badge-success"><?=$total_days?></span>
                                <?php endif; ?>
                              </td>
                            </tr>
                            <?php } ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 mb-4 d-flex">
                  <div class="card shadow w-100 d-flex flex-column mb-0">
                    <div class="card-header bg-primary d-flex justify-content-between align-items-center py-3">
                      <strong class="card-title text-white mb-0">Sale Details</strong>
                      <a class="small text-white font-weight-bold" href="analytics.php">View all</a>
                    </div>
                    <div class="card-body px-4 py-3 d-flex flex-column justify-content-between flex-fill">
                      <?php 
                        // Exact credit sales condition matching Row 1
                        $credit_cond = "(credit_sale_type='30days' OR payment_type='credit_sale')";

                        $q_today = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(grand_total) as s FROM orders WHERE order_date = '$current_date' AND $credit_cond"));
                        $sale_today = floatval($q_today['s'] ?? 0);

                        $q_yest = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(grand_total) as s FROM orders WHERE order_date = '$yesterday_date' AND $credit_cond"));
                        $sale_yesterday = floatval($q_yest['s'] ?? 0);

                        $q_this_week = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(grand_total) as s FROM orders WHERE order_date BETWEEN '$start' AND '$end' AND $credit_cond"));
                        $sale_this_week = floatval($q_this_week['s'] ?? 0);

                        $q_last_week = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(grand_total) as s FROM orders WHERE order_date BETWEEN '$start_week' AND '$end_week' AND $credit_cond"));
                        $sale_last_week = floatval($q_last_week['s'] ?? 0);

                        // Last 14 days credit sales data for responsive chart
                        $chart_dates = [];
                        $chart_sales = [];
                        $chart_orders = [];
                        for ($i = 13; $i >= 0; $i--) {
                            $d = date('Y-m-d', strtotime("-$i days", strtotime($current_date)));
                            $d_label = date('d M', strtotime($d));
                            $q_d = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT COUNT(*) as cnt, SUM(grand_total) as s FROM orders WHERE order_date = '$d' AND $credit_cond"));
                            $chart_dates[] = $d_label;
                            $chart_sales[] = floatval($q_d['s'] ?? 0);
                            $chart_orders[] = intval($q_d['cnt'] ?? 0);
                        }
                      ?>
                      <div class="row no-gutters border rounded mb-3 bg-light">
                        <div class="col-6 text-center py-2 border-right border-bottom">
                          <p class="text-muted mb-1 small text-uppercase font-weight-bold" style="font-size: 11px;">Today</p>
                          <h6 class="mb-0 font-weight-bold" style="font-size: 15px;">
                            <small class="text-muted">Rs. </small><?=number_format($sale_today)?>
                          </h6>
                        </div>
                        <div class="col-6 text-center py-2 border-bottom">
                          <p class="text-muted mb-1 small text-uppercase font-weight-bold" style="font-size: 11px;">Yesterday</p>
                          <h6 class="mb-0 font-weight-bold" style="font-size: 15px;">
                            <small class="text-muted">Rs. </small><?=number_format($sale_yesterday)?>
                          </h6>
                        </div>
                        <div class="col-6 text-center py-2 border-right">
                          <p class="text-muted mb-1 small text-uppercase font-weight-bold" style="font-size: 11px;">This Week</p>
                          <h6 class="mb-0 font-weight-bold" style="font-size: 15px;">
                            <small class="text-muted">Rs. </small><?=number_format($sale_this_week)?>
                          </h6>
                        </div>
                        <div class="col-6 text-center py-2">
                          <p class="text-muted mb-1 small text-uppercase font-weight-bold" style="font-size: 11px;">Last Week</p>
                          <h6 class="mb-0 font-weight-bold" style="font-size: 15px;">
                            <small class="text-muted">Rs. </small><?=number_format($sale_last_week)?>
                          </h6>
                        </div>
                      </div>

                      <div class="chart-widget flex-fill w-100" style="min-height: 200px;">
                        <div id="salesTrendChart" style="width: 100%; min-height: 200px;"></div>
                      </div>
                    </div> <!-- .card-body -->
                  </div> <!-- .card -->
                </div>
              </div>
                 <!-- .row --> 



                 
                         
            </div> <!-- .col-12 -->
          </div> <!-- .row -->
          
               
        </div> <!-- .container-fluid -->
     
        <?php include_once 'includes/pending_po_modal.php'; ?>

        <div class="modal fade modal-shortcut modal-slide" tabindex="-1" role="dialog" aria-labelledby="defaultModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="defaultModalLabel">Shortcuts</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body px-5">
                <div class="row align-items-center">
                  <div class="col-6 text-center">
                    <div class="squircle bg-success justify-content-center">
                      <i class="fe fe-cpu fe-32 align-self-center text-white"></i>
                    </div>
                    <p>Control area</p>
                  </div>
                  <div class="col-6 text-center">
                    <div class="squircle bg-primary justify-content-center">
                      <i class="fe fe-activity fe-32 align-self-center text-white"></i>
                    </div>
                    <p>Activity</p>
                  </div>
                </div>
                <div class="row align-items-center">
                  <div class="col-6 text-center">
                    <div class="squircle bg-primary justify-content-center">
                      <i class="fe fe-droplet fe-32 align-self-center text-white"></i>
                    </div>
                    <p>Droplet</p>
                  </div>
                  <div class="col-6 text-center">
                    <div class="squircle bg-primary justify-content-center">
                      <i class="fe fe-upload-cloud fe-32 align-self-center text-white"></i>
                    </div>
                    <p>Upload</p>
                  </div>
                </div>
                <div class="row align-items-center">
                  <div class="col-6 text-center">
                    <div class="squircle bg-primary justify-content-center">
                      <i class="fe fe-users fe-32 align-self-center text-white"></i>
                    </div>
                    <p>Users</p>
                  </div>
                  <div class="col-6 text-center">
                    <div class="squircle bg-primary justify-content-center">
                      <i class="fe fe-settings fe-32 align-self-center text-white"></i>
                    </div>
                    <p>Settings</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/moment.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/simplebar.min.js"></script>
    <script src='js/daterangepicker.js'></script>
    <script src='js/jquery.stickOnScroll.js'></script>
    <script src="js/tinycolor-min.js"></script>
    <script src="js/config.js"></script>
    <script src="js/d3.min.js"></script>
    <script src="js/topojson.min.js"></script>
    <script src="js/datamaps.all.min.js"></script>
    <script src="js/datamaps-zoomto.js"></script>
    <script src="js/datamaps.custom.js"></script>
    <script src="js/Chart.min.js"></script>
    <script>
      /* defind global options */
      Chart.defaults.global.defaultFontFamily = base.defaultFontFamily;
      Chart.defaults.global.defaultFontColor = colors.mutedColor;
    </script>
    <script src="js/gauge.min.js"></script>
    <script src="js/jquery.sparkline.min.js"></script>
    <script src="js/apexcharts.min.js"></script>
    <script src="js/apexcharts.custom.js"></script>
    <script>
      (function() {
        var el = document.querySelector("#salesTrendChart");
        if (!el) return;

        var categories = <?=json_encode($chart_dates ?? [])?>;
        var salesData = <?=json_encode($chart_sales ?? [])?>;
        var ordersData = <?=json_encode($chart_orders ?? [])?>;

        var options = {
          series: [{
            name: 'Credit Sales',
            data: salesData
          }],
          chart: {
            type: 'bar',
            height: 220,
            toolbar: { show: false },
            zoom: { enabled: false }
          },
          plotOptions: {
            bar: {
              horizontal: false,
              columnWidth: '55%',
              borderRadius: 4,
              endingShape: 'rounded'
            }
          },
          colors: ['#930027'],
          dataLabels: {
            enabled: false
          },
          stroke: {
            show: true,
            width: 1,
            colors: ['transparent']
          },
          xaxis: {
            categories: categories,
            labels: {
              show: true,
              rotate: -45,
              rotateAlways: false,
              hideOverlappingLabels: true,
              style: {
                colors: '#6c757d',
                fontSize: '11px',
                fontFamily: 'inherit'
              }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
          },
          yaxis: {
            labels: {
              show: true,
              style: {
                colors: '#6c757d',
                fontSize: '11px',
                fontFamily: 'inherit'
              },
              formatter: function(val) {
                if (val >= 1000000) {
                  return (val / 1000000).toFixed(1) + 'M';
                } else if (val >= 1000) {
                  return (val / 1000).toFixed(0) + 'K';
                }
                return val;
              }
            }
          },
          grid: {
            borderColor: '#f1f1f1',
            strokeDashArray: 3,
            padding: {
              top: 0,
              right: 10,
              bottom: 0,
              left: 10
            }
          },
          tooltip: {
            theme: 'light',
            custom: function(opts) {
              var idx = opts.dataPointIndex;
              var date = categories[idx] || '';
              var amount = salesData[idx] || 0;
              var ordersCount = ordersData[idx] || 0;
              return '<div class="p-2 border rounded bg-white shadow-sm" style="font-size: 12px; line-height: 1.4;">' +
                     '<strong>' + date + '</strong><br/>' +
                     '<span class="font-weight-bold" style="color: #930027;">Rs. ' + Number(amount).toLocaleString() + '</span><br/>' +
                     '<span class="text-muted">' + ordersCount + ' Order' + (ordersCount === 1 ? '' : 's') + '</span>' +
                     '</div>';
            }
          },
          responsive: [{
            breakpoint: 576,
            options: {
              chart: { height: 180 },
              plotOptions: {
                bar: { columnWidth: '70%' }
              },
              xaxis: {
                labels: {
                  rotate: -45,
                  style: { fontSize: '9px' }
                }
              }
            }
          }]
        };

        var chart = new ApexCharts(el, options);
        chart.render();
      })();
    </script>
    <script src='js/jquery.mask.min.js'></script>
    <script src='js/select2.min.js'></script>
    <script src='js/jquery.steps.min.js'></script>
    <script src='js/jquery.validate.min.js'></script>
    <script src='js/jquery.timepicker.js'></script>
    <script src='js/dropzone.min.js'></script>
    <script src='js/uppy.min.js'></script>
    <script src='js/quill.min.js'></script>
    <script>
      barChartjs=document.getElementById("barChar");barChartjs&&new Chart(barChartjs,{type:"bar",data:ChartData,options:ChartOptions});
      $('.select2').select2(
      {
        theme: 'bootstrap4',
      });
      $('.select2-multi').select2(
      {
        multiple: true,
        theme: 'bootstrap4',
      });
      $('.drgpicker').daterangepicker(
      {
        singleDatePicker: true,
        timePicker: false,
        showDropdowns: true,
        locale:
        {
          format: 'MM/DD/YYYY'
        }
      });
      $('.time-input').timepicker(
      {
        'scrollDefault': 'now',
        'zindex': '9999' /* fix modal open */
      });
      /** date range picker */
      if ($('.datetimes').length)
      {
        $('.datetimes').daterangepicker(
        {
          timePicker: true,
          startDate: moment().startOf('hour'),
          endDate: moment().startOf('hour').add(32, 'hour'),
          locale:
          {
            format: 'M/DD hh:mm A'
          }
        });
      }
      var start = moment().subtract(29, 'days');
      var end = moment();

      function cb(start, end)
      {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
      }
      $('#reportrange').daterangepicker(
      {
        startDate: start,
        endDate: end,
        ranges:
        {
          'Today': [moment(), moment()],
          'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days': [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month': [moment().startOf('month'), moment().endOf('month')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
      }, cb);
      cb(start, end);
      $('.input-placeholder').mask("00/00/0000",
      {
        placeholder: "__/__/____"
      });
      $('.input-zip').mask('00000-000',
      {
        placeholder: "____-___"
      });
      $('.input-money').mask("#.##0,00",
      {
        reverse: true
      });
      $('.input-phoneus').mask('(000) 000-0000');
      $('.input-mixed').mask('AAA 000-S0S');
      $('.input-ip').mask('0ZZ.0ZZ.0ZZ.0ZZ',
      {
        translation:
        {
          'Z':
          {
            pattern: /[0-9]/,
            optional: true
          }
        },
        placeholder: "___.___.___.___"
      });
      // editor
      var editor = document.getElementById('editor');
      if (editor)
      {
        var toolbarOptions = [
          [
          {
            'font': []
          }],
          [
          {
            'header': [1, 2, 3, 4, 5, 6, false]
          }],
          ['bold', 'italic', 'underline', 'strike'],
          ['blockquote', 'code-block'],
          [
          {
            'header': 1
          },
          {
            'header': 2
          }],
          [
          {
            'list': 'ordered'
          },
          {
            'list': 'bullet'
          }],
          [
          {
            'script': 'sub'
          },
          {
            'script': 'super'
          }],
          [
          {
            'indent': '-1'
          },
          {
            'indent': '+1'
          }], // outdent/indent
          [
          {
            'direction': 'rtl'
          }], // text direction
          [
          {
            'color': []
          },
          {
            'background': []
          }], // dropdown with defaults from theme
          [
          {
            'align': []
          }],
          ['clean'] // remove formatting button
        ];
        var quill = new Quill(editor,
        {
          modules:
          {
            toolbar: toolbarOptions
          },
          theme: 'snow'
        });
      }
      // Example starter JavaScript for disabling form submissions if there are invalid fields
      (function()
      {
        'use strict';
        window.addEventListener('load', function()
        {
          // Fetch all the forms we want to apply custom Bootstrap validation styles to
          var forms = document.getElementsByClassName('needs-validation');
          // Loop over them and prevent submission
          var validation = Array.prototype.filter.call(forms, function(form)
          {
            form.addEventListener('submit', function(event)
            {
              if (form.checkValidity() === false)
              {
                event.preventDefault();
                event.stopPropagation();
              }
              form.classList.add('was-validated');
            }, false);
          });
        }, false);
      })();
    </script>
    <script>
      var uptarg = document.getElementById('drag-drop-area');
      if (uptarg)
      {
        var uppy = Uppy.Core().use(Uppy.Dashboard,
        {
          inline: true,
          target: uptarg,
          proudlyDisplayPoweredByUppy: false,
          theme: 'dark',
          width: 770,
          height: 210,
          plugins: ['Webcam']
        }).use(Uppy.Tus,
        {
          endpoint: 'https://master.tus.io/files/'
        });
        uppy.on('complete', (result) =>
        {
          console.log('Upload complete! We’ve uploaded these files:', result.successful)
        });
      }
    </script>
    <script src="js/apps.js"></script>
    <script src="js/custom.js"></script>
    <script src="js/enter_navigation.js"></script>
  </body>
</html>