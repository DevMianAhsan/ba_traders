<?php  include_once 'includes/head.php';
  $vouchers=fetchRecord($dbc,"vouchers","voucher_id",base64_decode($_REQUEST['voucher_id']));
  $customer_id1=fetchRecord($dbc,"customers","customer_id",$vouchers['customer_id1']);
  $customer_id2=fetchRecord($dbc,"customers","customer_id",$vouchers['customer_id2']);

$voucher_invoices_list = [];
$q_vi_check = mysqli_query($dbc, "SELECT vi.*, COALESCE(c.customer_name, c2.customer_name) AS customer_name FROM voucher_invoices vi LEFT JOIN tax_sales ts ON (vi.invoice_id = ts.invoice_no OR vi.invoice_id = ts.tax_sale_id) LEFT JOIN customers c ON ts.customer_id = c.customer_id LEFT JOIN vouchers v ON vi.voucher_id = v.voucher_id LEFT JOIN customers c2 ON v.customer_id1 = c2.customer_id WHERE vi.voucher_id = '".$vouchers['voucher_id']."'");
while ($vi_row = mysqli_fetch_assoc($q_vi_check)) {
    $voucher_invoices_list[] = $vi_row;
}
  
 
  
        if (@$vouchers['voucher_group'] == 'single_voucher') {
          
             $from_balance=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT SUM(credit-debit) AS from_balance FROM transactions WHERE customer_id='".$vouchers['customer_id1']."' AND transaction_id = '".$vouchers['transaction_id1']."'  "));
    $previous_balance=(int)$from_balance['from_balance']+(int)$vouchers['voucher_amount'];

    $from_balance2=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT SUM(credit-debit) AS from_balance FROM transactions WHERE customer_id='".$vouchers['customer_id2']."' AND transaction_id = '".@$vouchers['transaction_id12']."'  "));
    $previous_balance2=(int)$from_balance2['from_balance']-(int)$vouchers['voucher_amount'];


            # code...
        }else{
    $from_balance=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT SUM(credit-debit) AS from_balance FROM transactions WHERE customer_id='".$vouchers['customer_id1']."'"));
    $previous_balance=(int)$from_balance['from_balance']+(int)$vouchers['voucher_amount'];

    $from_balance2=mysqli_fetch_assoc(mysqli_query($dbc,"SELECT SUM(credit-debit) AS from_balance FROM transactions WHERE customer_id='".$vouchers['customer_id2']."'"));
    $previous_balance2=(int)$from_balance2['from_balance']-(int)$vouchers['voucher_amount'];
}

 ?>
<style type="text/css">
 @font-face {
    font-family: 'AvantGardeBookBT';
    src: url('AvantGardeBookBT.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

/* The following rules are deprecated. */ 
@font-face {
    font-family: 'AvantGardeBookBT';
    src: url('AvantGardeBookBT.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

body,p { 
   font-family: 'AvantGardeBookBT'; 
   font-weight: normal; 
   font-style: normal; 

}
input{
   font-family:'Lucida Casual', 'Comic Sans MS';    
   
}
body{
    background: #fff;
}
</style>
<?php for ($i=0; $i < 2; $i++) :  
        if($i>0){
        
           $copy = "Company Copy";
           
       }else{
     
           $copy = "Customer Copy";
       } 
    ?>
    <div class="page-content-wrapper">
    <div class="page-content">
<div id="invoice">

    <div class="invoice">  
        <div style="min-width: 600px">
            <header>
                <div class="row">
                     <div class="col-sm-5">
                         <img src="img/logo/<?=$get_company['logo']?>" width="80" height="80" class="img-fluid float-right">
                </div>
        <div class="col-sm-7 mt-1">
          <h1 style="margin-left: -20px; color: red;font-weight: bold;"><?=$get_company['name']?></h1>
          <p style="margin-left: -10px; font-weight: bold;">Ph No.<?=$get_company['company_phone']?></p>
          
   

          
        </div>
        <center style="width: 100%;margin-top: -5px;"></center>
      </div>
            </header>
            <main>
      <?php if ($_REQUEST['type']=="debit" OR $_REQUEST['type']=="both"): ?>

                <div class="row contacts">
                    <div class="col invoice-to">
                        <div class="text-gray-light font-weight-bold text-dark">DETAILS:</div>
                        <h2 class="to"><?=ucfirst(@$customer_id1['customer_name'])?></h2>
                        <div class="email font-weight-bold text-dark"><?=@$customer_id1['customer_phone']?></div>
                        <div class="address font-weight-bold text-dark"><?=@$customer_id1['customer_address']?></div>

                    </div>
                    <div class="col invoice-details">
                        <h1 class="font-weight-bold text-dark invoice-id">Voucher # <?=$vouchers['voucher_id']?></h1>
                        <div class="font-weight-bold text-dark date">Voucher Type: <?=strtoupper($vouchers['voucher_type'])?></div>
                        <div class="font-weight-bold text-dark date">Voucher Date/Time: 
                            
                        <?php 
               echo $date = date('D d-M-Y h:i A', strtotime($vouchers['timestamp'] . " +9 hours"));
               ?> 

                        </div>
                        <?php  if (!empty($vouchers['editby_user_id'])) {
                                     $users=fetchRecord($dbc,"users","user_id",$vouchers['editby_user_id']);
                                ?>    
                        <div class="font-weight-bold text-dark date">Edit By: <?=strtoupper($users['username'])?></div>

                             <?php   }else {
                                     $users=fetchRecord($dbc,"users","user_id",$vouchers['addby_user_id']);
                                      ?>
                                      <div class="font-weight-bold text-dark date">Added By: <?=strtoupper($users['username'])?></div>
                              <?php   } ?>
                         <div class="font-weight-bold text-dark address">Type: <?=ucfirst($customer_id1['customer_type'])?></div>

                        


                    </div>
                </div>
              <div class="row">
                  <div class="col-sm-12">
                        <table border="1" cellspacing="0" cellpadding="0">
                   
                    <tbody>
       
         
                        <tr>
                            <td class="no">Amount Paid</td>
                            <td class="text-left"><h3><?=number_format($vouchers['voucher_amount'])?></h3></td>
                            <td class="unit"><h3>Previous Balance</h3></td>
                            <td class="qty"><?=number_format($previous_balance)?></td>
                            <td class="total"><h3>Current Balance</h3></td>
                            <td class="qty"><?=number_format($from_balance['from_balance'])?></td>
                        </tr>
                        <tr>
                            <td class="no">Narration</td>
                            <td colspan="5"  class="text-center no"><?=ucfirst($vouchers['voucher_hint'])?></td>
                        </tr>

                 
                    </tbody>
                   
                </table>
                  </div>
              </div>
    <?php if (!empty($voucher_invoices_list)): ?>
              <div class="row mt-2">
                  <div class="col-sm-12">
                      <table border="1" cellspacing="0" cellpadding="0" style="margin-top: 8px;">
                          <thead>
                              <tr style="background-color: #f2f2f2;">
                                  <th style="padding: 4px; text-align: center; width: 30px;">#</th>
                                  <th style="padding: 4px; text-align: center;">Invoice #</th>
                                  <th style="padding: 4px; text-align: center;">Customer</th>
                                  <th style="padding: 4px; text-align: center;">Party PO</th>
                                  <th style="padding: 4px; text-align: right;">Total Amount</th>
                                  <th style="padding: 4px; text-align: center;">WHT %</th>
                                  <th style="padding: 4px; text-align: right;">WHT Amount</th>
                                  <th style="padding: 4px; text-align: right;">Amount Received</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php 
                              $v_idx = 0; 
                              $v_sum_total = 0;
                              $v_sum_tax = 0;
                              $v_sum_rec = 0; 
                              foreach ($voucher_invoices_list as $vi_item): 
                                  $v_idx++; 
                                  $v_inv_amt = floatval($vi_item['invoice_amount']);
                                  $v_tax_amt = floatval(!empty($vi_item['tax_amount']) ? $vi_item['tax_amount'] : 0);
                                  $v_rec_amt = floatval($vi_item['received_amount']);
                                  $v_sum_total += $v_inv_amt;
                                  $v_sum_tax += $v_tax_amt;
                                  $v_sum_rec += $v_rec_amt;
                              ?>
                              <tr>
                                  <td style="padding: 4px; text-align: center;"><?=$v_idx?></td>
                                  <td style="padding: 4px; text-align: center;"><strong>Invoice #<?=$vi_item['invoice_id']?></strong></td>
                                  <td style="padding: 4px; text-align: center;"><?=$vi_item['customer_name']?></td>
                                  <td style="padding: 4px; text-align: center;"><?=$vi_item['party_po']?></td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_inv_amt)?> RS</td>
                                  <td style="padding: 4px; text-align: center;"><?=!empty($vi_item['tax_percent']) ? $vi_item['tax_percent'].'%' : '-'?></td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_tax_amt)?> RS</td>
                                  <td style="padding: 4px; text-align: right;"><strong><?=number_format($v_rec_amt)?> RS</strong></td>
                              </tr>
                              <?php endforeach; ?>
                              <tr style="background-color: #fafafa; font-weight: bold;">
                                  <td colspan="4" style="padding: 4px; text-align: right;">Total:</td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_sum_total)?> RS</td>
                                  <td style="padding: 4px; text-align: center;">-</td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_sum_tax)?> RS</td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_sum_rec)?> RS</td>
                              </tr>
                          </tbody>
                      </table>
                  </div>
              </div>
<?php endif; ?>
<?php endif; ?>
                <!--<div class="thanks">Thank you!</div>-->
                <?php if ($_REQUEST['type']=="credit" OR $_REQUEST['type']=="both" AND $vouchers['voucher_group']!="single_voucher"): ?>
                    
                
                <div class="row contacts">
                    <div class="col invoice-to">
                        <div class="text-gray-light font-weight-bold text-dark">  DETAILS:</div>
                        <h2 class="to"><?=ucfirst($customer_id2['customer_name'])?></h2>
                        <div class="email font-weight-bold text-dark"><?=$customer_id2['customer_phone']?></div>
                        <div class="address font-weight-bold text-dark"><?=$customer_id2['customer_address']?></div>
                    </div>
                    <div class="col invoice-details">
                        <h1 class="font-weight-bold text-dark invoice-id">Voucher # <?=$vouchers['voucher_id']?></h1>
                        <div class="font-weight-bold text-dark date">Voucher Type: <?=strtoupper($vouchers['voucher_type'])?></div>
                        <div class="font-weight-bold text-dark date">Voucher Date/Time: 
                            
                            <?php 
               echo $date = date('D d-M-Y h:i A', strtotime($vouchers['timestamp'] . " +9 hours"));
               ?> 
                        </div>
                        <?php  if (!empty($vouchers['editby_user_id'])) {
                                     $users=fetchRecord($dbc,"users","user_id",$vouchers['editby_user_id']);
                                ?>    
                        <div class="font-weight-bold text-dark date">Edit By: <?=strtoupper($users['username'])?></div>

                             <?php   }else {
                                     $users=fetchRecord($dbc,"users","user_id",$vouchers['addby_user_id']);
                                      ?>
                                      <div class="font-weight-bold text-dark date">Added By: <?=strtoupper($users['username'])?></div>
                              <?php   } ?>
                         <div class="font-weight-bold text-dark address"> Type: <?=ucfirst($customer_id2['customer_type'])?></div>

                        


                    </div>
                </div>
              <div class="row">
                  <div class="col-sm-12">
                        <table border="1" cellspacing="0" cellpadding="0">
                   
                    <tbody>
       
         
                        <tr>
                            <td class="no">Amount Paid</td>
                            <td class="text-left"><h3><?=number_format($vouchers['voucher_amount'])?></h3></td>
                            <td class="unit"><h3>Previous Balance</h3></td>
                            <td class="qty"><?=number_format($previous_balance2)?></td>
                            <td class="total"><h3>Current Balance</h3></td>
                            <td class="qty"><?=number_format($from_balance2['from_balance'])?></td>
                        </tr>
                        <tr>
                            <td class="no">Narration</td>
                            <td colspan="5"  class="text-center no"><?=ucfirst($vouchers['voucher_hint'])?></td>
                        </tr>

                 
                    </tbody>
                   
                </table>
                  </div>
              </div>

<?php if (!empty($voucher_invoices_list)): ?>
              <div class="row mt-2">
                  <div class="col-sm-12">
                      <table border="1" cellspacing="0" cellpadding="0" style="margin-top: 8px;">
                          <thead>
                              <tr style="background-color: #f2f2f2;">
                                  <th style="padding: 4px; text-align: center; width: 30px;">#</th>
                                  <th style="padding: 4px; text-align: center;">Invoice #</th>
                                  <th style="padding: 4px; text-align: center;">Customer</th>
                                  <th style="padding: 4px; text-align: center;">Party PO</th>
                                  <th style="padding: 4px; text-align: right;">Total Amount</th>
                                  <th style="padding: 4px; text-align: center;">WHT %</th>
                                  <th style="padding: 4px; text-align: right;">WHT Amount</th>
                                  <th style="padding: 4px; text-align: right;">Amount Received</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php 
                              $v_idx = 0; 
                              $v_sum_total = 0;
                              $v_sum_tax = 0;
                              $v_sum_rec = 0; 
                              foreach ($voucher_invoices_list as $vi_item): 
                                  $v_idx++; 
                                  $v_inv_amt = floatval($vi_item['invoice_amount']);
                                  $v_tax_amt = floatval(!empty($vi_item['tax_amount']) ? $vi_item['tax_amount'] : 0);
                                  $v_rec_amt = floatval($vi_item['received_amount']);
                                  $v_sum_total += $v_inv_amt;
                                  $v_sum_tax += $v_tax_amt;
                                  $v_sum_rec += $v_rec_amt;
                              ?>
                              <tr>
                                  <td style="padding: 4px; text-align: center;"><?=$v_idx?></td>
                                  <td style="padding: 4px; text-align: center;"><strong>Invoice #<?=$vi_item['invoice_id']?></strong></td>
                                  <td style="padding: 4px; text-align: center;"><?=$vi_item['customer_name']?></td>
                                  <td style="padding: 4px; text-align: center;"><?=$vi_item['party_po']?></td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_inv_amt)?> RS</td>
                                  <td style="padding: 4px; text-align: center;"><?=!empty($vi_item['tax_percent']) ? $vi_item['tax_percent'].'%' : '-'?></td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_tax_amt)?> RS</td>
                                  <td style="padding: 4px; text-align: right;"><strong><?=number_format($v_rec_amt)?> RS</strong></td>
                              </tr>
                              <?php endforeach; ?>
                              <tr style="background-color: #fafafa; font-weight: bold;">
                                  <td colspan="4" style="padding: 4px; text-align: right;">Total:</td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_sum_total)?> RS</td>
                                  <td style="padding: 4px; text-align: center;">-</td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_sum_tax)?> RS</td>
                                  <td style="padding: 4px; text-align: right;"><?=number_format($v_sum_rec)?> RS</td>
                              </tr>
                          </tbody>
                      </table>
                  </div>
              </div>
<?php endif; ?>
            <?php endif ?>
                <!--<div class="thanks">Thank you!</div>-->

                <div class="notices">
                    <h4><strong>Thank you so much for choosing
                        <b class="name">
                            <?=$get_company['name']?>
                         </b></strong></h4>
                    <p class="notice"> Software Developed By :  <b class="name">TheWebConcept.com (0313-7573667)</b> <b class="float-right"><?=$copy?></b></p>

                </div>
                 <div class="row mb-5" style="font-size: 18px">
                    <div class="col-sm-4 h3">
                        Prepared By : __________________ 
                    </div>
                     <div class="col-sm-4 h3">
                        <?php
                        if (isset($order['vehicle_no'])) {
                            // code...
                        
                        ?>
                        Vehicle No : <b><u><?=strtoupper($order['vehicle_no'])?></u></b>
                        <?php
                    }
                        ?>
                    </div>
                     <div class="col-sm-4 h3">
                        Recevied By  : _________________ 
                    </div>
                </div>
            </main>
            <footer>
               
            </footer>
        </div>
        <!--DO NOT DELETE THIS div. IT is responsible for showing footer always at the bottom-->
        <div></div>
    </div>
</div>
    </div>
</div>
<?php endfor; ?>
<style>
    #invoice{
        padding: 30px;
    }

    .invoice {
        position: relative;
        background-color: #FFF;
        /min-height: 680px;/
        padding: 15px
    }

    .name{
        color: #000;
    }

    .invoice header {
        padding: 10px 0;
        margin-bottom: 20px;
        border-bottom: 1px solid #000
    }

    .invoice .company-details {
        text-align: right
    }

    .invoice .company-details .name {
        margin-top: 0;
        margin-bottom: 0
    }

    .invoice .contacts {
        margin-bottom: 20px
    }

    .invoice .invoice-to {
        text-align: left
    }

    .invoice .invoice-to .to {
        margin-top: 0;
        margin-bottom: 0
    }

    .invoice .invoice-details {
        text-align: right
    }

    .invoice .invoice-details .invoice-id {
        margin-top: 0;
        color: #000;
    }

    .invoice main {
        padding-bottom: 50px
    }

    .invoice main .thanks {
        margin-top: -100px;
        font-size: 2em;
        margin-bottom: 50px
    }

    .invoice main .notices {
        padding-left: 6px;
        border-left: 6px solid #000
    }

    .invoice main .notices .notice {
        font-size: 1.2em
    }

    .invoice table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px
    }

    .invoice table td,.invoice table th {
        padding: 10px;
        background: #fff;
        border-bottom: 1px solid #000;
        color: #000;
        font-weight: bold;
    }

    .invoice table th {
        white-space: nowrap;
        font-weight: 400;
        font-size: 16px
    }

    .invoice table td h3 {
        margin: 0;
        font-weight: 400;
        color: #000;
        font-size: 1.2em
    }

    .invoice table .qty,.invoice table .total,.invoice table .unit {
        text-align: right;
        font-size: 1.2em
    }

    .invoice table .no {
        color: #000;
        font-size: 1.6em;
        background: #fff;
    }

    .invoice table .unit {
        background: #fff
    }

    .invoice table .total {
        background: #fff;
        color: #000 !important;
    }

    .invoice table tbody tr:last-child td {
        border: none
    }

    .invoice table tfoot td {
        background: 0 0;
        border-bottom: none;
        white-space: nowrap;
        text-align: right;
        padding: 10px 20px;
        font-size: 1.2em;
        border-top: 1px solid #aaa
    }

    .invoice table tfoot tr:first-child td {
        border-top: none
    }

    .invoice table tfoot tr:last-child td {
        color: #000;
        font-size: 1.4em;
        border-top: 1px solid #000
    }

    .invoice table tfoot tr td:first-child {
        border: none
    }

    .invoice footer {
        width: 100%;
        text-align: center;
        color: #777;
        border-top: 3px dotted; #aaa;
        padding: 8px 0
    }

    /*@media print {
        .invoice {
            font-size: 11px!important;
            overflow: hidden!important
        }

        .invoice footer {
            position: absolute;
            bottom: 10px;
            page-break-after: always
        }

        .invoice>div:last-child {
            page-break-before: always
        }
    }*/
</style>