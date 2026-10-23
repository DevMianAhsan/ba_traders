<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  th{
    font-size: 18px;

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
                <h2 class="text-center card-text">Analytics</h2>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">


  
    



    <form action="" method="post" class="" >



      <div class="row d-print-none ">



      <div class="form-group col-sm-2"></div><!-- group -->
  <div class="form-group col-sm-3 ">



        <label for="">From Date</label>



        <input type="date" name="from_date" class="form-control">



                                             

                                            

      </div>
  <div class="form-group col-sm-3 ">



        <label for="">To Date</label>



        <input type="date" name="to_date" class="form-control">



                                             

                                            

      </div>

      



      <div class="form-group col-sm-3 d-print-none" >



        <br />



        <button class="mt-2 btn btn-admin float-right" name="genealledger" type="submit">Search</button>
        <button class="mt-2 btn btn-admin2 float-right" onclick="window.print();" style="margin-right: 15px;">Print Report</button>


      </div><!-- group -->



      </div>



    </form>
    <hr>

  

 <?php     

   

if (!empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
      $sales = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as total_order,order_id,sum(grand_total) as total_sales FROM orders WHERE order_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' "));
       $salesGet = mysqli_query($dbc,"SELECT * FROM orders  WHERE order_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' ");

      $purchases = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as total_purchase,sum(grand_total) as total_amount FROM purchase WHERE purchase_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' "));

    $purchases_items = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(quantity) as total_items,sum(quantity*rate) as total_rate,purchase.*,purchase_item.* FROM purchase_item INNER JOIN  purchase ON purchase.purchase_id=purchase_item.purchase_id  WHERE purchase_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' "));
      $sales_items = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(quantity) as total_items,sum(quantity*rate) as total_rate,orders.*,order_item.* FROM order_item INNER JOIN  orders ON orders.order_id=order_item.order_id  WHERE order_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' "));

      $instock=$purchases_items['total_items']-$sales_items['total_items'];
      $total_rate=$purchases_items['total_rate']-$sales_items['total_rate'];
          @$expense = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(voucher_amount) as total_amount FROM vouchers  WHERE voucher_group='expense_voucher' AND voucher_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' "));     

             $cash_in_hand_sale = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as cash_in_hand,sum(grand_total) as cash_in_hand_amount FROM orders WHERE order_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' AND payment_type='cash_in_hand' "));

             $credit_sale = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as credit_sale,sum(grand_total) as credit_sale_amount FROM orders WHERE order_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' AND payment_type='credit_sale' "));


}else if (!empty($_REQUEST['from_date']) AND empty($_REQUEST['to_date'])) {
      $sales = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as total_order,order_id,sum(grand_total) as total_sales FROM orders WHERE order_date = '".$_REQUEST['from_date']."' "));
       $salesGet = mysqli_query($dbc,"SELECT * FROM orders  WHERE order_date = '".$_REQUEST['from_date']."' ");
      $purchases = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as total_purchase,sum(grand_total) as total_amount FROM purchase WHERE purchase_date = '".$_REQUEST['from_date']."' "));


          $purchases_items = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(quantity) as total_items,sum(quantity*rate) as total_rate,purchase.*,purchase_item.* FROM purchase_item INNER JOIN  purchase ON purchase.purchase_id=purchase_item.purchase_id WHERE purchase_date = '".$_REQUEST['from_date']."' "));
      $sales_items = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(quantity) as total_items,sum(quantity*rate) as total_rate,orders.*,order_item.* FROM order_item INNER JOIN  orders ON orders.order_id=order_item.order_id WHERE order_date = '".$_REQUEST['from_date']."' "));

      $instock=$purchases_items['total_items']-$sales_items['total_items'];
      $total_rate=$purchases_items['total_rate']-$sales_items['total_rate'];
      @$expense = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(voucher_amount) as total_amount FROM vouchers  WHERE voucher_group='expense_voucher' AND voucher_date = '".$_REQUEST['from_date']."' "));
      $cash_in_hand_sale = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as cash_in_hand,sum(grand_total) as cash_in_hand_amount FROM orders WHERE order_date='".$_REQUEST['from_date']."' AND payment_type='cash_in_hand' "));

             $credit_sale = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as credit_sale,sum(grand_total) as credit_sale_amount FROM orders WHERE order_date='".$_REQUEST['from_date']."' AND payment_type='credit_sale' "));

}else{
        $todaythis = date('Y-m-d');
      $sales = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as total_order,order_id,sum(grand_total) as total_sales FROM orders  WHERE order_date = '".$todaythis."'"));
      $salesGet = mysqli_query($dbc,"SELECT * FROM orders  WHERE order_date = '".$todaythis."' ");
      $purchases = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as total_purchase,sum(grand_total) as total_amount FROM purchase WHERE purchase_date = '".$todaythis."' "));
      $purchases_items = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(quantity) as total_items,sum(quantity*rate) as total_rate,purchase.*,purchase_item.* FROM purchase_item INNER JOIN  purchase ON purchase.purchase_id=purchase_item.purchase_id  "));
      $sales_items = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(quantity) as total_items,sum(quantity*rate) as total_rate,orders.*,order_item.* FROM order_item INNER JOIN  orders ON orders.order_id=order_item.order_id  "));

      $instock=$purchases_items['total_items']-$sales_items['total_items'];
      $total_rate=$purchases_items['total_rate']-$sales_items['total_rate'];

    $expense = mysqli_fetch_array(mysqli_query($dbc,"SELECT sum(voucher_amount) as total_amount FROM vouchers  WHERE voucher_group='expense_voucher' "));

     $cash_in_hand_sale = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as cash_in_hand,sum(grand_total) as cash_in_hand_amount FROM orders WHERE payment_type='cash_in_hand' "));

    $credit_sale = mysqli_fetch_array(mysqli_query($dbc,"SELECT count(*) as credit_sale,sum(grand_total) as credit_sale_amount FROM orders WHERE payment_type='credit_sale' "));
    
   // $total_expense=!empty($expense['total_amount'])?abs($expense['total_amount']):0;

}

   $total_expense=!empty($expense['total_amount'])?abs($expense['total_amount']):0;

 ?>
<?php 
  $net_profit=0;
    while($fetchOrder=mysqli_fetch_assoc($salesGet)): ?>
                <?php 
            $sql = "SELECT * FROM order_item WHERE order_id = '$fetchOrder[order_id]' AND order_item_status=1";
                  $query = $dbc->query($sql);
                  while ($result = $query->fetch_assoc()) {
                  $product_id= $result['product_id'];
                  $sold_quantity= $result['quantity'];
                  $sold_rate= $result['rate'];
                //$purchases_items ="SELECT * FROM purchase_item WHERE purchase_id=  ";
                  $sql_item = "SELECT * FROM purchase_item WHERE product_id = '$product_id'";
                  $query_item = $dbc->query($sql_item);
                  while ($result_item = $query_item->fetch_assoc()) {
                  $product_purchase= $result_item['rate'];
                  $sold_income = $sold_quantity * $sold_rate;
                  $purchase_income = $product_purchase * $sold_quantity; 
                }
                  $net_profit+=@$sold_income-@$purchase_income ;

                 }//while
                    //echo $net_profit;
                    ?>
<?php endwhile; ?>
  <div class="row">
     <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-primary text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-shopping-cart text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Total Orders</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                             echo $sales['total_order'];
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
       <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-secondary text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-shopping-bag text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Total Purchase</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                             echo $purchases['total_purchase'];
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
         <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-primary text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Total Sales</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                                echo $sales['total_sales'];
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
   <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-secondary text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Total Purchase</p>
                          <span class="h3 mb-0 text-white">
                            <?=$pur=isset($purchases['total_amount'])?abs($purchases['total_amount']):"0";
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
    <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-success text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-plus-square text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Puchased Quantity</p>
                          <span class="h3 mb-0 text-white">
                            
                             <?=$pur=isset($purchases_items['total_items'])?abs($purchases_items['total_items']):"0";
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
      <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-info text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-minus-square text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Saled Quantity</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                                echo $sales_items['total_items'];
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
  <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-success text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-package text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">inStock Quatity</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                                echo abs($instock);
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
   <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-info text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">inStock Amount</p>
                          <span class="h3 mb-0 text-white">
                            <?php 
                                
                                 echo $total_rate=isset($total_rate)?abs($total_rate):"0";
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
  <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-dark text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Total Revenue</p>
                          <span class="h3 mb-0 text-white">
                            <?=isset($sales['total_sales'])?abs($sales['total_sales']):"0";
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
   <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-warning text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Total Income</p>
                          <span class="h3 mb-0 text-white">
                            <?=$net_profit;
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
  <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-dark text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Total Expense</p>
                          <span class="h3 mb-0 text-white">
                            <?=$total_expense;
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>
   <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-warning text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Profit</p>
                          <span class="h3 mb-0 text-white">
                            <?=$net_profit-$total_expense;
                             ?>
                          </span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
  <!---------------------------end of box------------------------------------------------------>


  </div>
  <hr>
  <div class="row">
    <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-warning text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-shopping-cart text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Cash in Hand (no.)</p>
                          <span class="h3 mb-0 text-white"><?=@(int)$cash_in_hand_sale['cash_in_hand']?></span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

            <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-dark text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Cash in Hand (pkr)</p>
                          <span class="h3 mb-0 text-white"><?=@(int)$cash_in_hand_sale['cash_in_hand_amount']?></span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!---------------------------end of box------------------------------------------------------>
   <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-warning text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-shopping-cart text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Credit Sale (no.)</p>
                          <span class="h3 mb-0 text-white"><?=@(int)$credit_sale['credit_sale']?></span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!---------------------------end of box------------------------------------------------------>
  <div class="col-md-6 col-xl-3 mb-4">
                  <div class="card shadow bg-dark text-white border-0">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-3 text-center">
                          <span class="circle circle-sm bg-white">
                            <i class="fe fe-16 fe-dollar-sign text-default mb-0"></i>
                          </span>
                        </div>
                        <div class="col pr-0">
                          <p class="small text-white mb-0">Credit Sale (pkr)</p>
                          <span class="h3 mb-0 text-white"><?=@(int)$credit_sale['credit_sale_amount']?></span>
                        <!--   <span class="small text-white">+5.5%</span> -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
    
  </div> <!-- end of row -->
  <hr>
  <div class="row mt-2">
    <div class="col-sm-12">
      <h2 class="text-center">Bank Balance</h2>
    </div>
  </div>
  <div class="row" >
    <div class="col-sm-12" align="center">
      <table class="table" width="100%" align="center">
        <thead>
          <th>Sr No.</th>
          <th>Account Details</th>
          <th>Balance</th>
        </thead>
        <tbody>
          <?php $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_type='bank' AND customer_status=1 ");
          $c=0;
          while($r=mysqli_fetch_assoc($q)):
            $c++;
           ?>
          <tr>
            <td><?=$c?></td>
            <td><?=$r['customer_name']?></td>
            <td><?=number_format(getcustomerBlance($dbc,$r['customer_id']))?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
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
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>


<style type="text/css">
   @media print {
        .h3 {
            font-size: 40px!important;
            overflow: hidden!important;
            color: black!important;
          }
          .small{
            font-size: 30px!important;
             color: black!important;
          }
          .table{
            font-size: 30px!important;
            text-align: center!important;
            width: 95%!important;
          }
       
    }
</style>