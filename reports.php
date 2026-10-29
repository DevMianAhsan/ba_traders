<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  .ledger-table thead th {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    background-color: #2b3035;
    vertical-align: middle;
    border: 1px solid #454d55;
    padding: 10px 8px;
    text-align: center;
  }
  .ledger-table tbody td {
    font-size: 14px;
    vertical-align: middle;
    border: 1px solid #dee2e6;
    padding: 10px 10px;
    color: #212529;
  }
  .ledger-table tbody tr:nth-of-type(even) {
    background-color: #fcfcfc;
  }
  .ledger-table tbody tr:hover {
    background-color: #f4f6f9;
  }
  .item-title {
    font-size: 14px;
    font-weight: 700;
    color: #111;
  }
  .item-meta {
    font-size: 13px;
    color: #555;
    margin-top: 2px;
  }

  @media print {
    .d-print-none, .navbar, .navbar-slide, .toggle-sidebar, .sidebar, #header, footer, .btn, .breadcrumb {
      display: none !important;
    }
    .wrapper, main, .main-content, .container-fluid, .card, .card-body {
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
      box-shadow: none !important;
      background: #fff !important;
      width: 100% !important;
    }
    body {
      background: #fff !important;
      color: #000 !important;
      font-size: 12px !important;
    }
    .ledger-table {
      width: 100% !important;
      border-collapse: collapse !important;
    }
    .ledger-table thead th {
      background-color: #222 !important;
      color: #fff !important;
      border: 1px solid #000 !important;
      font-size: 12px !important;
      padding: 6px 8px !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .ledger-table tbody td, .ledger-table tfoot td {
      border: 1px solid #000 !important;
      font-size: 12px !important;
      padding: 6px 8px !important;
      color: #000 !important;
    }
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
                <b class="text-center card-text"><?=ucfirst($_REQUEST['type'] ?? '')?> Ledger</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">


  
    



    <form action="" method="post" class="d-print-none" >



      <div class="row d-print-none ">



      <div class="form-group col-sm-3">



        <label for=""><?=ucfirst($_REQUEST['type'] ?? '')?> Account</label>



        <select required class="form-control" id="ledger_customer_id" name="customer_id" autofocus="true">
            <option value="">Select Account</option>
            <?php

            $sql = get($dbc,"customers WHERE customer_status = 1 AND customer_type='".($_REQUEST['type'] ?? '')."' ");
            while($row = $sql->fetch_array()) {
              $selected = (isset($_REQUEST['customer_id']) && $_REQUEST['customer_id'] == $row['customer_id']) ? 'selected' : '';
              echo "<option value='".$row['customer_id']."' $selected>".$row['customer_name']."</option>";
            } // while
            ?>
          </select> 
      </div><!-- group -->
  <div class="form-group col-sm-2 ">
        <label for="">From Date</label>
        <input type="date" name="from_date" value="<?=htmlspecialchars($_REQUEST['from_date'] ?? '')?>" class="form-control">
      </div>
  <div class="form-group col-sm-2 ">
        <label for="">To Date</label>
        <input type="date" name="to_date" value="<?=htmlspecialchars($_REQUEST['to_date'] ?? '')?>" class="form-control">
      </div>

      



      <div class="form-group col-sm-4 d-print-none" >



        <br />



        <button class="mt-2 ml-1 btn btn-admin float-right" name="genealledger" type="submit">Ledger Detials</button>

        <button class="mt-2  btn btn-admin2 float-right" name="fullledger" type="submit">Full Details</button>
        <?php 
    if (isset($_POST['genealledger']) OR isset($_POST['fullledger'])):  ?>
        <button class="mt-2 btn btn-primary float-right" onclick="window.print();" style="margin-right: 15px;">Print Report</button>
<?php endif ?> 

      </div><!-- group -->



      </div>



    </form>



  
  


  <?php 



    if (isset($_POST['genealledger']) OR isset($_POST['fullledger'])) :



      $customer = $_POST['customer_id'];

      



?>
<hr>
   <header class="">
                <div class="row">
                     <div class="col-sm-1">
                         <img src="img/logo/<?=$get_company['logo']?>" width="90" height="90" class="img-fluid float-left" style="margin-top: 10px">
                </div>
        <div class="col-sm-5 mt-3">
          <h1 style="margin-left: -20px; color: red;font-weight: bold;font-size: 30px"><?=$get_company['name']?></h1>
          <p style="margin-left: -10px; font-weight: bolder;font-size: 15px">PH  No. :<?=$get_company['company_phone']?></p>
          
  

          
        </div>
        <div class="col-sm-4 offset-2 mt-4">
           
       <?php  $fetchCustomer = fetchRecord($dbc,"customers","customer_id",$customer);?>
       <h5>Account Name:<?=ucwords($fetchCustomer['customer_name'] ?? '') ;?></h5>
       <h5>Phone No:    <?=@$fetchCustomer['customer_phone'] ?? '' ;?></h5>
       
        </div>
      
      </div>
            </header>
   

       <?php 

        if(!empty($customer)){
         ?>
<?php
if (!empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
    $from_date = $_REQUEST['from_date'];
    $to_date = $_REQUEST['to_date'];
    $sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_date BETWEEN '$from_date' AND '$to_date' ORDER BY transaction_date ASC, transaction_id ASC";
    $date_comment = "<h6>FROM : " . date('d-M-Y', strtotime($from_date)) . " TO : " . date('d-M-Y', strtotime($to_date)) . "</h6>";
} elseif (!empty($_REQUEST['from_date']) AND empty($_REQUEST['to_date'])) {
    $from_date = $_REQUEST['from_date'];
    $to_date = '';
    $sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_date >= '$from_date' ORDER BY transaction_date ASC, transaction_id ASC";
    $date_comment = "<h6>FROM : " . date('d-M-Y', strtotime($from_date)) . " ONWARDS</h6>";
} elseif (empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
    $from_date = '';
    $to_date = $_REQUEST['to_date'];
    $sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_date <= '$to_date' ORDER BY transaction_date ASC, transaction_id ASC";
    $date_comment = "<h6>UP TO : " . date('d-M-Y', strtotime($to_date)) . "</h6>";
} else {
    $from_date = '';
    $to_date = '';
    $date_comment = "<h6>Overall</h6>";
    $sql = "SELECT * FROM transactions WHERE customer_id='$customer' ORDER BY transaction_date ASC, transaction_id ASC";
}

// Calculate opening balance if from_date is set
$opening_balance = 0;
if (!empty($from_date)) {
    $pre_bal_res = mysqli_query($dbc, "SELECT SUM(credit - debit) AS pre_net FROM transactions WHERE customer_id = '$customer' AND transaction_date < '$from_date'");
    if ($pre_bal_row = mysqli_fetch_assoc($pre_bal_res)) {
        $opening_balance = floatval($pre_bal_row['pre_net'] ?? 0);
    }
}

$result = mysqli_query($dbc, $sql);
?>
  <center style="width: 100%;margin-top: -5px;"><?=$date_comment?></center>
  <div class="row">
    <div class="col-12">
      <div class="table-responsive">
        <table class="table table-bordered ledger-table mb-2" style="width: 100%;">
          <thead>
            <tr>
              <th style="width: 4%;">#</th>
              <th style="width: 10%;">Date</th>
              <th style="width: 10%;">Ref #</th>
              <th style="width: 8%;">PO #</th>
              <th style="width: 8%;">Terms</th>
              <th style="width: 32%; text-align: left;">Product / Description</th>
              <th style="width: 8%;">WHT Tax</th>
              <th style="width: 10%; text-align: right;">Debit (Rs)</th>
              <th style="width: 10%; text-align: right;">Credit (Rs)</th>
              <th style="width: 10%; text-align: right;">Balance (Rs)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $running_bal = $opening_balance;
            $total_credit = 0;
            $total_debit = 0;
            $total_wht = 0;
            $sr = 0;

            if (!empty($from_date) && $opening_balance != 0):
            ?>
              <tr style="background: #fff3cd; font-weight: 700;">
                <td class="text-center">-</td>
                <td class="text-center"><?=date('d-M-Y', strtotime($from_date))?></td>
                <td class="text-center"><strong>OPENING</strong></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td><strong>Opening Balance (Brought Forward)</strong></td>
                <td class="text-center">-</td>
                <td class="text-right text-primary font-weight-bold"><?=$opening_balance < 0 ? number_format(abs($opening_balance)) : '-'?></td>
                <td class="text-right text-success font-weight-bold"><?=$opening_balance > 0 ? number_format($opening_balance) : '-'?></td>
                <td class="text-right font-weight-bold <?=$opening_balance > 0 ? 'text-danger' : 'text-success'?>">
                  <?=number_format($opening_balance)?>
                </td>
              </tr>
            <?php
            endif;

            if (mysqli_num_rows($result) > 0):
              while ($row = mysqli_fetch_array($result)):
                if ($row['transaction_type'] == "credit_sale" || in_array(strtolower($row['transaction_from']), ['invoice', 'tax_sale', 'order'])):
                  $sr++;
                  $invoice_id = filter_var($row['transaction_remarks'], FILTER_SANITIZE_NUMBER_INT);
                  $fetchTax = null;
                  if (!empty($row['transaction_id'])) {
                      $fetchTax = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM tax_sales WHERE transaction_id = '{$row['transaction_id']}' LIMIT 1"));
                  }
                  if (empty($fetchTax) && !empty($invoice_id)) {
                      $fetchTax = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM tax_sales WHERE invoice_no = '$invoice_id' OR tax_sale_id = '$invoice_id' LIMIT 1"));
                  }

                  $ref_label = "INV #" . ($fetchTax['invoice_no'] ?? $invoice_id);
                  $po_no = $fetchTax['po_no'] ?? '';
                  $terms = '';

                  if (!empty($po_no)) {
                      $po_q = mysqli_query($dbc, "SELECT po_paymentdays FROM purchase_order WHERE po_partypo = '" . mysqli_real_escape_string($dbc, $po_no) . "' LIMIT 1");
                      if ($po_r = mysqli_fetch_assoc($po_q)) {
                          $days_num = intval(filter_var($po_r['po_paymentdays'], FILTER_SANITIZE_NUMBER_INT));
                          if ($days_num > 0) $terms = $days_num . 'days';
                      }
                  }
                  if (empty($terms) && !empty($fetchTax['payment_terms']) && $fetchTax['payment_terms'] != 'CREDIT') {
                      $terms = $fetchTax['payment_terms'];
                  }

                  // Check commercial invoices table if not in tax_sales
                  $fetchInv = null;
                  if (empty($fetchTax) && !empty($invoice_id)) {
                      $fetchInv = fetchRecord($dbc, "invoices", "invoice_id", $invoice_id);
                      if (!empty($fetchInv)) {
                          $ref_label = "INV #" . $fetchInv['invoice_id'];
                          $po_no = $fetchInv['party_po'] ?? $po_no;
                          $terms = $fetchInv['credit_sale_type'] ?? $terms;
                      }
                  }

                  // Items breakdown
                  $items = [];
                  if (!empty($fetchTax)) {
                      $tax_items_q = mysqli_query($dbc, "SELECT tsi.*, p.product_name, p.product_code 
                          FROM tax_sale_items tsi 
                          LEFT JOIN product p ON tsi.product_id = p.product_id 
                          WHERE tsi.tax_sale_id = '{$fetchTax['tax_sale_id']}'");
                      while ($it = mysqli_fetch_assoc($tax_items_q)) {
                          $items[] = [
                              'name' => !empty($it['description_of_goods']) ? $it['description_of_goods'] : ($it['product_name'] ?? 'Product #' . $it['product_id']),
                              'code' => $it['product_code'] ?? '',
                              'qty' => floatval($it['quantity']),
                              'rate' => floatval($it['rate']),
                              'total' => floatval($it['value_excl_tax']),
                              'tax_rate' => floatval($it['tax_rate']),
                              'tax_amount' => floatval($it['tax_amount']),
                          ];
                      }
                  } elseif (!empty($fetchInv)) {
                      $invDetails = json_decode($fetchInv['invoice_details'], true);
                      if (!empty($invDetails['data']) && is_array($invDetails['data'])) {
                          foreach ($invDetails['data'] as $d) {
                              if (!empty($d['dc_no'])) {
                                  $dc_no = intval($d['dc_no']);
                                  $q_items = mysqli_query($dbc, "SELECT oi.*, p.product_name, p.product_code 
                                      FROM order_item oi 
                                      LEFT JOIN product p ON oi.product_id = p.product_id 
                                      WHERE oi.order_id = '$dc_no'");
                                  if ($q_items) {
                                      while ($it = mysqli_fetch_assoc($q_items)) {
                                          $items[] = [
                                              'name' => $it['product_name'] ?? ('Product #' . $it['product_id']),
                                              'code' => $it['product_code'] ?? '',
                                              'qty' => floatval($it['quantity']),
                                              'rate' => floatval($it['rate']),
                                              'total' => floatval($it['total'] ?? ($it['quantity'] * $it['rate'])),
                                              'tax_rate' => floatval($fetchInv['discount'] ?? 0),
                                              'tax_amount' => (floatval($it['total'] ?? ($it['quantity'] * $it['rate'])) * (floatval($fetchInv['discount'] ?? 0) / 100)),
                                          ];
                                      }
                                  }
                              }
                          }
                      }
                  }

                  $row_credit = (float)($row['credit'] ?? 0);
                  $row_debit = (float)($row['debit'] ?? 0);
                  $total_credit += $row_credit;
                  $total_debit += $row_debit;
                  $running_bal += ($row_credit - $row_debit);
                  ?>
                  <tr>
                    <td class="text-center font-weight-bold"><?=$sr?></td>
                    <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($row['transaction_date']))?></td>
                    <td class="text-center">
                      <strong class="text-primary font-weight-bold"><?=$ref_label?></strong>
                    </td>
                    <td class="text-center font-weight-bold">
                      <?=htmlspecialchars($po_no ?: '-')?>
                    </td>
                    <td class="text-center">
                      <?=htmlspecialchars($terms ?: '-')?>
                    </td>
                    <td>
                      <?php if (!empty($items)): ?>
                        <?php foreach ($items as $it): ?>
                          <div class="mb-1">
                            <div class="item-title">
                              <?=htmlspecialchars($it['name'])?>
                              <?php if (!empty($it['code'])): ?>
                                <small class="text-muted font-weight-normal">(<?=htmlspecialchars($it['code'])?>)</small>
                              <?php endif; ?>
                            </div>
                            <div class="item-meta">
                              Qty: <strong><?=number_format($it['qty'])?></strong> &times; Rate: <strong><?=number_format($it['rate'])?> RS</strong> = <strong><?=number_format($it['total'])?> RS</strong>
                            </div>
                            <?php if (!empty($it['tax_amount']) && $it['tax_amount'] > 0): ?>
                              <div class="text-muted" style="font-size: 12px; margin-top: 2px;">
                                Subtotal: <?=number_format($it['total'])?> RS &bull; Tax (<?=$it['tax_rate']?>%): <?=number_format($it['tax_amount'])?> RS
                              </div>
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <div class="item-title"><?=str_replace("=", "<br/>", $row['transaction_remarks'])?></div>
                      <?php endif; ?>
                    </td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-right">
                      <?php if ($row_debit > 0): ?>
                        <span class="text-primary font-weight-bold" style="font-size: 15px;"><?=number_format($row_debit)?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right">
                      <?php if ($row_credit > 0): ?>
                        <span class="text-success font-weight-bold" style="font-size: 15px;"><?=number_format($row_credit)?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right font-weight-bold" style="font-size: 15px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                      <?=number_format($running_bal)?>
                    </td>
                  </tr>
                  <?php

                elseif ($row['transaction_from'] == "voucher"):
                  $invoice_type2 = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM vouchers WHERE transaction_id1 = '{$row['transaction_id']}' OR transaction_id2 = '{$row['transaction_id']}' LIMIT 1"));
                  $v_id = !empty($invoice_type2['voucher_id']) ? $invoice_type2['voucher_id'] : $row['transaction_id'];

                  $voucher_invoices = [];
                  if (!empty($invoice_type2['voucher_id'])) {
                      $q_vi = mysqli_query($dbc, "SELECT * FROM voucher_invoices WHERE voucher_id = '{$invoice_type2['voucher_id']}' ORDER BY id ASC");
                      if ($q_vi && mysqli_num_rows($q_vi) > 0) {
                          while ($vi_row = mysqli_fetch_assoc($q_vi)) {
                              $v_rec = floatval(str_replace(',', '', $vi_row['received_amount'] ?? 0));
                              $v_tax = floatval(str_replace(',', '', $vi_row['tax_amount'] ?? 0));
                              if ($v_rec > 0 || $v_tax > 0) {
                                  $voucher_invoices[] = [
                                      'id' => $vi_row['id'],
                                      'invoice_id' => $vi_row['invoice_id'],
                                      'party_po' => $vi_row['party_po'] ?? '',
                                      'invoice_amount' => floatval(str_replace(',', '', $vi_row['invoice_amount'] ?? 0)),
                                      'tax_percent' => $vi_row['tax_percent'] ?? '',
                                      'tax_amount' => $v_tax,
                                      'received_amount' => $v_rec,
                                  ];
                              }
                          }
                      }
                  }

                  if (!empty($voucher_invoices)) {
                      $sum_vi_settled = 0;
                      foreach ($voucher_invoices as $vi) {
                          $sr++;
                          $v_rec = $vi['received_amount'];
                          $v_tax = $vi['tax_amount'];
                          $v_settled = $v_rec + $v_tax;
                          $sum_vi_settled += $v_settled;

                          $total_debit += $v_rec;
                          $total_wht += $v_tax;
                          $running_bal -= $v_settled;
                          ?>
                          <tr>
                            <td class="text-center font-weight-bold"><?=$sr?></td>
                            <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($row['transaction_date']))?></td>
                            <td class="text-center">
                              <strong class="text-success font-weight-bold">VOU #<?=$v_id?></strong>
                            </td>
                            <td class="text-center font-weight-bold">
                              <?=htmlspecialchars($vi['party_po'] ?: '-')?>
                            </td>
                            <td class="text-center">-</td>
                            <td>
                              <div class="item-title text-success font-weight-bold">Payment Received</div>
                              <?php if (!empty($invoice_type2['td_check_no'])): ?>
                                <div class="text-muted mt-1" style="font-size: 12px;">
                                  Cheque #: <strong><?=htmlspecialchars($invoice_type2['td_check_no'])?></strong>
                                  <?php if (!empty($invoice_type2['voucher_bank_name'])): ?> &bull; Bank: <?=htmlspecialchars($invoice_type2['voucher_bank_name'])?><?php endif; ?>
                                  <?php if (!empty($invoice_type2['td_check_date'])): ?> &bull; Date: <?=htmlspecialchars($invoice_type2['td_check_date'])?><?php endif; ?>
                                </div>
                              <?php endif; ?>
                              <?php if (!empty($invoice_type2['voucher_hint'])): ?>
                                <div class="text-muted mt-1" style="font-size: 12px;"><em><?=htmlspecialchars($invoice_type2['voucher_hint'])?></em></div>
                              <?php endif; ?>
                            </td>
                            <td class="text-center">
                              <?php if ($v_tax > 0): ?>
                                <div class="text-danger font-weight-bold" style="font-size: 14px;">
                                  <?=number_format($v_tax)?>
                                </div>
                                <?php if (!empty($vi['tax_percent']) && floatval($vi['tax_percent']) > 0): ?>
                                  <small class="text-muted font-weight-bold"><?=$vi['tax_percent']?>%</small>
                                <?php endif; ?>
                              <?php else: ?>
                                <span class="text-muted">-</span>
                              <?php endif; ?>
                            </td>
                            <td class="text-right">
                              <?php if ($v_rec > 0): ?>
                                <span class="text-primary font-weight-bold" style="font-size: 15px;">
                                  <?=number_format($v_rec)?>
                                </span>
                              <?php else: ?>
                                <span class="text-muted">-</span>
                              <?php endif; ?>
                            </td>
                            <td class="text-right"><span class="text-muted">-</span></td>
                            <td class="text-right font-weight-bold" style="font-size: 15px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                              <?=number_format($running_bal)?>
                            </td>
                          </tr>
                          <?php
                      }

                      // Unallocated voucher remainder
                      $v_total_raw = (float)($row['debit'] ?? 0);
                      $rem_voucher_debit = $v_total_raw - $sum_vi_settled;
                      if ($rem_voucher_debit > 0.01) {
                          $sr++;
                          $total_debit += $rem_voucher_debit;
                          $running_bal -= $rem_voucher_debit;
                          ?>
                          <tr>
                            <td class="text-center font-weight-bold"><?=$sr?></td>
                            <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($row['transaction_date']))?></td>
                            <td class="text-center">
                              <strong class="text-success font-weight-bold">VOU #<?=$v_id?></strong>
                            </td>
                            <td class="text-center font-weight-bold">-</td>
                            <td class="text-center">-</td>
                            <td>
                              <div class="item-title text-success font-weight-bold">Payment Received (Unallocated)</div>
                              <?php if (!empty($invoice_type2['voucher_hint'])): ?>
                                <div class="text-muted mt-1" style="font-size: 12px;"><em><?=htmlspecialchars($invoice_type2['voucher_hint'])?></em></div>
                              <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="text-muted">-</span></td>
                            <td class="text-right">
                              <span class="text-primary font-weight-bold" style="font-size: 15px;">
                                <?=number_format($rem_voucher_debit)?>
                              </span>
                            </td>
                            <td class="text-right"><span class="text-muted">-</span></td>
                            <td class="text-right font-weight-bold" style="font-size: 15px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                              <?=number_format($running_bal)?>
                            </td>
                          </tr>
                          <?php
                      }
                  } else {
                      // Voucher with no breakdown
                      $sr++;
                      $v_amt = (float)($row['debit'] ?? 0);
                      $total_debit += $v_amt;
                      $running_bal -= $v_amt;
                      ?>
                      <tr>
                        <td class="text-center font-weight-bold"><?=$sr?></td>
                        <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($row['transaction_date']))?></td>
                        <td class="text-center">
                          <strong class="text-success font-weight-bold">VOU #<?=$v_id?></strong>
                        </td>
                        <td class="text-center font-weight-bold">-</td>
                        <td class="text-center">-</td>
                        <td>
                          <div class="item-title text-success font-weight-bold">Payment Received</div>
                          <?php if (!empty($invoice_type2['td_check_no'])): ?>
                            <div class="text-muted mt-1" style="font-size: 12px;">
                              Cheque #: <strong><?=htmlspecialchars($invoice_type2['td_check_no'])?></strong>
                              <?php if (!empty($invoice_type2['voucher_bank_name'])): ?> &bull; Bank: <?=htmlspecialchars($invoice_type2['voucher_bank_name'])?><?php endif; ?>
                              <?php if (!empty($invoice_type2['td_check_date'])): ?> &bull; Date: <?=htmlspecialchars($invoice_type2['td_check_date'])?><?php endif; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (!empty($row['transaction_remarks'])): ?>
                            <div class="text-muted mt-1" style="font-size: 12px;"><?=str_replace("=", "<br/>", $row['transaction_remarks'])?></div>
                          <?php endif; ?>
                        </td>
                        <td class="text-center"><span class="text-muted">-</span></td>
                        <td class="text-right">
                          <span class="text-primary font-weight-bold" style="font-size: 15px;"><?=number_format($v_amt)?></span>
                        </td>
                        <td class="text-right"><span class="text-muted">-</span></td>
                        <td class="text-right font-weight-bold" style="font-size: 15px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                          <?=number_format($running_bal)?>
                        </td>
                      </tr>
                      <?php
                  }

                else:
                  $sr++;
                  $row_credit = (float)($row['credit'] ?? 0);
                  $row_debit = (float)($row['debit'] ?? 0);
                  $total_credit += $row_credit;
                  $total_debit += $row_debit;
                  $running_bal += ($row_credit - $row_debit);
                  ?>
                  <tr>
                    <td class="text-center font-weight-bold"><?=$sr?></td>
                    <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($row['transaction_date']))?></td>
                    <td class="text-center font-weight-bold">#<?=$row['transaction_id']?></td>
                    <td class="text-center font-weight-bold">-</td>
                    <td class="text-center">-</td>
                    <td><?=str_replace("=", "<br/>", $row['transaction_remarks'])?></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-right">
                      <?php if ($row_debit > 0): ?>
                        <span class="text-primary font-weight-bold" style="font-size: 15px;"><?=number_format($row_debit)?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right">
                      <?php if ($row_credit > 0): ?>
                        <span class="text-success font-weight-bold" style="font-size: 15px;"><?=number_format($row_credit)?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right font-weight-bold" style="font-size: 15px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                      <?=number_format($running_bal)?>
                    </td>
                  </tr>
                  <?php
                endif;
              endwhile;
            else:
            ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-muted">No Transaction Found</td>
              </tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="6"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 15px; vertical-align: middle;">Opening Balance</td>
              <td colspan="2" class="h4 text-success font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($opening_balance)?></td>
            </tr>
            <tr>
              <td colspan="6"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 15px; vertical-align: middle;">Total Debits</td>
              <td colspan="2" class="h4 text-info font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($total_debit)?></td>
            </tr>
            <tr>
              <td colspan="6"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 15px; vertical-align: middle;">Total WHT</td>
              <td colspan="2" class="h4 text-danger font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($total_wht)?></td>
            </tr>
            <tr>
              <td colspan="6"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 15px; vertical-align: middle;">Total Credits</td>
              <td colspan="2" class="h4 text-warning font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($total_credit)?></td>
            </tr>
            <tr>
              <td colspan="6"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 15px; vertical-align: middle;">Closing Balance</td>
              <td colspan="2" class="h4 font-weight-bold mb-0 <?=$running_bal > 0 ? 'text-danger' : 'text-success'?>" style="vertical-align: middle;"><?=number_format($running_bal)?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
<?php } ?>

 
  <?php endif; ?>
<script></script>
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
    thead>tr>th{
      font-size: 25px!important;
      color: black!important;
    }
    tr>td{
      font-size: 22px!important;
    }

    }
  </style>