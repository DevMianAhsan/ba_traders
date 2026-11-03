<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 
$report_type = strtolower(trim($_REQUEST['type'] ?? ''));
?>
<style type="text/css">
  .ledger-table thead th {
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    background-color: #2b3035;
    vertical-align: middle;
    border: 1px solid #454d55;
    padding: 6px 4px;
    text-align: center;
  }
  .ledger-table tbody td {
    font-size: 12px;
    vertical-align: middle;
    border: 1px solid #dee2e6;
    padding: 6px 4px;
    color: #212529;
  }
  .ledger-table tbody tr:nth-of-type(even) {
    background-color: #fcfcfc;
  }
  .ledger-table tbody tr:hover {
    background-color: #f4f6f9;
  }
  .item-title {
    font-size: 12px;
    font-weight: 700;
    color: #111;
  }
  .item-meta {
    font-size: 11px;
    color: #555;
    margin-top: 2px;
  }

  @page {
    size: auto;
    margin: 8mm 4mm !important;
  }

  @media print {
    html, body, .wrapper, main, .main-content {
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }

    .container, .container-fluid {
      padding-left: 5mm !important;
      padding-right: 5mm !important;
      padding-top: 0 !important;
      padding-bottom: 0 !important;
      margin-left: auto !important;
      margin-right: auto !important;
      width: 100% !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      border: none !important;
      box-shadow: none !important;
      background: transparent !important;
    }

    .card, .card-body {
      padding-left: 0 !important;
      padding-right: 0 !important;
      margin-left: 0 !important;
      margin-right: 0 !important;
      border: none !important;
      box-shadow: none !important;
      width: 100% !important;
      max-width: 100% !important;
      background: transparent !important;
      box-sizing: border-box !important;
    }

    .row {
      margin-left: 0 !important;
      margin-right: 0 !important;
    }

    .col-12 {
      padding-left: 0 !important;
      padding-right: 0 !important;
    }

    .table-responsive {
      overflow: visible !important;
    }

    .ledger-table thead th {
      font-size: 10.5px !important;
      padding: 4px 2px !important;
      background-color: #222 !important;
      color: #fff !important;
      border: 1px solid #000 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .ledger-table tbody td, .ledger-table tfoot td {
      font-size: 10px !important;
      padding: 4px 2px !important;
      border: 1px solid #000 !important;
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



        <select required class="form-control searchableSelect" id="ledger_customer_id" name="customer_id" autofocus="true">
            <option value="">Select Account</option>
            <?php

            $sql = get($dbc,"customers WHERE customer_status = 1 AND customer_type='".($_REQUEST['type'] ?? '')."' ORDER BY customer_name ASC");
            while($row = $sql->fetch_array()) {
              $selected = (isset($_REQUEST['customer_id']) && $_REQUEST['customer_id'] == $row['customer_id']) ? 'selected' : '';
              echo "<option value='".$row['customer_id']."' $selected>".htmlspecialchars(getCustomerDisplayName($row['customer_name'], $row['customer_phone']))."</option>";
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

        <!-- <button class="mt-2  btn btn-admin2 float-right" name="fullledger" type="submit">Full Details</button> -->
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
<?php
$fetchCustomer = fetchRecord($dbc, "customers", "customer_id", $customer);

$duration_text = "Overall (All Time)";
if (!empty($_REQUEST['from_date']) && !empty($_REQUEST['to_date'])) {
    $duration_text = "From: " . date('d-M-Y', strtotime($_REQUEST['from_date'])) . " To: " . date('d-M-Y', strtotime($_REQUEST['to_date']));
} elseif (!empty($_REQUEST['from_date'])) {
    $duration_text = "From: " . date('d-M-Y', strtotime($_REQUEST['from_date'])) . " Onwards";
} elseif (!empty($_REQUEST['to_date'])) {
    $duration_text = "Up to: " . date('d-M-Y', strtotime($_REQUEST['to_date']));
}

$report_title = strtoupper(($_REQUEST['type'] ?? 'Account') . " LEDGER STATEMENT");
$report_customer = $fetchCustomer;
$report_duration = $duration_text;
include 'includes/report_header.php';
?>
   

       <?php 
        if(!empty($customer)){
          $report_type = strtolower(trim($_REQUEST['type'] ?? ''));

          if ($report_type === 'customer'):
            // ==========================================
            // CUSTOMER LEDGER: NEW DESIGN & LOGIC
            // ==========================================
            // Ensure all tax_sales for this customer have a valid transaction in transactions table
            $sync_ts = mysqli_query($dbc, "SELECT ts.* FROM tax_sales ts LEFT JOIN transactions t ON ts.transaction_id = t.transaction_id WHERE ts.customer_id = '$customer' AND t.transaction_id IS NULL");
            if ($sync_ts && mysqli_num_rows($sync_ts) > 0) {
                while ($ts_row = mysqli_fetch_assoc($sync_ts)) {
                    $trans_amount = floatval($ts_row['grand_total'] ?? 0);
                    $trans_remarks = !empty($ts_row['remarks']) ? $ts_row['remarks'] : ("Sales Tax Invoice #" . ($ts_row['invoice_no'] ?: $ts_row['tax_sale_id']));
                    $trans_record = [
                        'credit' => $trans_amount,
                        'debit' => 0,
                        'customer_id' => $customer,
                        'transaction_from' => 'invoice',
                        'transaction_type' => 'credit_sale',
                        'transaction_remarks' => $trans_remarks,
                        'transaction_date' => $ts_row['invoice_date'],
                    ];
                    if (insert_data($dbc, "transactions", $trans_record)) {
                        $new_tid = mysqli_insert_id($dbc);
                        mysqli_query($dbc, "UPDATE tax_sales SET transaction_id = '$new_tid' WHERE tax_sale_id = '{$ts_row['tax_sale_id']}'");
                    }
                }
            }
            $order_clause = "ORDER BY transaction_date ASC, (CASE WHEN transaction_type = 'credit_sale' OR transaction_from IN ('invoice', 'tax_sale', 'order') THEN 1 ELSE 2 END) ASC, transaction_id ASC";
            if (!empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
                $from_date = $_REQUEST['from_date'];
                $to_date = $_REQUEST['to_date'];
                $sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_date BETWEEN '$from_date' AND '$to_date' $order_clause";
                $date_comment = "<h6>FROM : " . date('d-M-Y', strtotime($from_date)) . " TO : " . date('d-M-Y', strtotime($to_date)) . "</h6>";
            } elseif (!empty($_REQUEST['from_date']) AND empty($_REQUEST['to_date'])) {
                $from_date = $_REQUEST['from_date'];
                $to_date = '';
                $sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_date >= '$from_date' $order_clause";
                $date_comment = "<h6>FROM : " . date('d-M-Y', strtotime($from_date)) . " ONWARDS</h6>";
            } elseif (empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
                $from_date = '';
                $to_date = $_REQUEST['to_date'];
                $sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_date <= '$to_date' $order_clause";
                $date_comment = "<h6>UP TO : " . date('d-M-Y', strtotime($to_date)) . "</h6>";
            } else {
                $from_date = '';
                $to_date = '';
                $date_comment = "<h6>Overall</h6>";
                $sql = "SELECT * FROM transactions WHERE customer_id='$customer' $order_clause";
            }

            // Calculate opening balance if from_date is set
            $opening_balance = 0;
            if (!empty($from_date)) {
                $pre_bal_res = mysqli_query($dbc, "SELECT SUM(credit - debit) AS pre_net FROM transactions WHERE customer_id = '$customer' AND transaction_date < '$from_date'");
                if ($pre_bal_row = mysqli_fetch_assoc($pre_bal_res)) {
                    $opening_balance = floatval($pre_bal_row['pre_net'] ?? 0);
                }
                $pre_wht_res = mysqli_query($dbc, "SELECT SUM(CAST(vi.tax_amount AS DECIMAL(15,2))) AS pre_wht FROM voucher_invoices vi JOIN vouchers v ON vi.voucher_id = v.voucher_id WHERE v.customer_id1 = '$customer' AND v.voucher_date < '$from_date'");
                if ($pre_wht_row = mysqli_fetch_assoc($pre_wht_res)) {
                    $opening_balance -= floatval($pre_wht_row['pre_wht'] ?? 0);
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
              <th style="width: 3%;">#</th>
              <th style="width: 7%;">Date</th>
              <th style="width: 6%;">Ref #</th>
              <th style="width: 7%;">FBR Inv #</th>
              <th style="width: 6%;">PO #</th>
              <th style="width: 5%;">Terms</th>
              <th style="width: 16%; text-align: left;">Description</th>
              <th style="width: 6%; text-align: center;">QTY<br/>(WT)</th>
              <th style="width: 6%; text-align: right;">Rate</th>
              <th style="width: 7%; text-align: right;">Amount (Rs)</th>
              <th style="width: 5%; text-align: center;">Tax %</th>
              <th style="width: 6%; text-align: right;">Tax Amount (Rs)</th>
              <th style="width: 6%; text-align: center;">WHT Tax</th>
              <th style="width: 6%; text-align: right;">Debit (Rs)</th>
              <th style="width: 6%; text-align: right;">Credit (Rs)</th>
              <th style="width: 8%; text-align: right;">Balance (Rs)</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $running_bal = $opening_balance;
            $total_credit = 0;
            $total_debit = 0;
            $total_wht = 0;
            $tot_qty = 0;
            $tot_subtotal = 0;
            $tot_tax_amt = 0;
            $sr = 0;

            if (!empty($from_date) && $opening_balance != 0):
            ?>
              <tr style="background: #fff3cd; font-weight: 700;">
                <td class="text-center">-</td>
                <td class="text-center"><?=date('d-M-Y', strtotime($from_date))?></td>
                <td class="text-center"><strong>OPENING</strong></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td><strong>Opening Balance (Brought Forward)</strong></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
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

                  $inv_total_qty = 0;
                  $inv_subtotal = 0;
                  $inv_tax_rate = 0;
                  $inv_tax_amount = 0;
                  $prod_names = [];
                  $inv_pos = [];
                  $inv_rates = [];
                  if (!empty($po_no)) {
                      $inv_pos[] = trim($po_no);
                  }

                  if (!empty($fetchTax)) {
                      $inv_total_qty = floatval($fetchTax['total_qty'] ?? 0);
                      $inv_subtotal = floatval($fetchTax['total_value_excl_tax'] ?? 0);
                      $inv_tax_amount = floatval($fetchTax['total_sales_tax'] ?? 0);

                      $tax_items_q = mysqli_query($dbc, "SELECT tsi.*, p.product_name FROM tax_sale_items tsi LEFT JOIN product p ON tsi.product_id = p.product_id WHERE tsi.tax_sale_id = '{$fetchTax['tax_sale_id']}'");
                      while ($it = mysqli_fetch_assoc($tax_items_q)) {
                          if ($inv_total_qty <= 0) $inv_total_qty += floatval($it['quantity']);
                          if ($inv_subtotal <= 0) $inv_subtotal += floatval($it['value_excl_tax']);
                          if ($inv_tax_amount <= 0) $inv_tax_amount += floatval($it['tax_amount']);
                          if ($inv_tax_rate <= 0 && floatval($it['tax_rate']) > 0) $inv_tax_rate = floatval($it['tax_rate']);
                          $p_name = !empty($it['description_of_goods']) ? trim($it['description_of_goods']) : (!empty($it['product_name']) ? trim($it['product_name']) : '');
                          if (!empty($p_name) && !in_array($p_name, $prod_names)) {
                              $prod_names[] = $p_name;
                          }
                          $r_val = floatval($it['rate'] ?? 0);
                          if ($r_val > 0) {
                              $inv_rates[] = number_format($r_val, 2);
                          }
                      }
                      if ($inv_tax_rate <= 0 && $inv_subtotal > 0 && $inv_tax_amount > 0) {
                          $inv_tax_rate = round(($inv_tax_amount / $inv_subtotal) * 100, 1);
                      }
                      if ($inv_tax_rate <= 0 && $inv_tax_amount > 0) {
                          $inv_tax_rate = 18;
                      }
                  } elseif (!empty($fetchInv)) {
                      $inv_subtotal = floatval($fetchInv['total_amount'] ?? 0);
                      $inv_tax_rate = floatval($fetchInv['discount'] ?? 0);
                      $inv_tax_amount = ($inv_subtotal * ($inv_tax_rate / 100));

                      $invDetails = json_decode($fetchInv['invoice_details'], true);
                      if (!empty($invDetails['data']) && is_array($invDetails['data'])) {
                          foreach ($invDetails['data'] as $d) {
                              if (!empty($d['party_po'])) {
                                  $inv_pos[] = trim($d['party_po']);
                              }
                              if (!empty($d['dc_no'])) {
                                  $dc_no = intval($d['dc_no']);
                                  $q_oi = mysqli_query($dbc, "SELECT oi.quantity, oi.rate, p.product_name FROM order_item oi LEFT JOIN product p ON oi.product_id = p.product_id WHERE oi.order_id = '$dc_no'");
                                  if ($q_oi) {
                                      while ($r_oi = mysqli_fetch_assoc($q_oi)) {
                                          $inv_total_qty += floatval($r_oi['quantity'] ?? 0);
                                          $p_name = !empty($r_oi['product_name']) ? trim($r_oi['product_name']) : '';
                                          if (!empty($p_name) && !in_array($p_name, $prod_names)) {
                                              $prod_names[] = $p_name;
                                          }
                                          $r_val = floatval($r_oi['rate'] ?? 0);
                                          if ($r_val > 0) {
                                              $inv_rates[] = number_format($r_val, 2);
                                          }
                                      }
                                  }
                              }
                          }
                      }
                  } else {
                      $inv_subtotal = (float)($row['credit'] ?? 0);
                  }

                  $row_credit = (float)($row['credit'] ?? 0);
                  $row_debit = (float)($row['debit'] ?? 0);
                  $total_credit += $row_credit;
                  $total_debit += $row_debit;
                  $running_bal += ($row_credit - $row_debit);

                  $tot_qty += $inv_total_qty;
                  $tot_subtotal += $inv_subtotal;
                  $tot_tax_amt += $inv_tax_amount;

                  $po_display = implode(', ', array_unique(array_filter($inv_pos)));
                  $fbr_inv_no = !empty($fetchTax['fbr_invoice_no']) ? $fetchTax['fbr_invoice_no'] : '';
                  $inv_rate_display = !empty($inv_rates) ? implode(', ', array_unique($inv_rates)) : '-';
                  ?>
                  <tr>
                    <td class="text-center font-weight-bold"><?=$sr?></td>
                    <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($row['transaction_date']))?></td>
                    <td class="text-center">
                      <strong class="text-primary font-weight-bold"><?=$ref_label?></strong>
                    </td>
                    <td class="text-center font-weight-bold">
                      <?=htmlspecialchars($fbr_inv_no ?: '-')?>
                    </td>
                    <td class="text-center font-weight-bold">
                      <?=htmlspecialchars($po_display ?: '-')?>
                    </td>
                    <td class="text-center">
                      <?=htmlspecialchars($terms ?: '-')?>
                    </td>
                    <td>
                      <?php if (!empty($prod_names)): ?>
                        <div class="item-title font-weight-bold"><?=htmlspecialchars(implode(', ', $prod_names))?></div>
                      <?php elseif (!empty($fetchTax)): ?>
                        <div class="item-title font-weight-bold">Sales Tax Invoice</div>
                      <?php elseif (!empty($fetchInv)): ?>
                        <div class="item-title font-weight-bold">Commercial Invoice</div>
                      <?php else: ?>
                        <div class="item-title font-weight-bold"><?=str_replace("=", "<br/>", $row['transaction_remarks'])?></div>
                      <?php endif; ?>

                      <?php if (!empty($fetchTax['remarks'])): ?>
                        <div class="text-muted" style="font-size: 11px;"><em><?=htmlspecialchars($fetchTax['remarks'])?></em></div>
                      <?php endif; ?>
                      <?php if (!empty($fetchInv['order_narration'])): ?>
                        <div class="text-muted" style="font-size: 11px;"><em><?=htmlspecialchars($fetchInv['order_narration'])?></em></div>
                      <?php endif; ?>
                      <?php if (!empty($fetchInv['freight']) && floatval($fetchInv['freight']) > 0): ?>
                        <div class="text-muted" style="font-size: 11px;">Freight: <?=number_format($fetchInv['freight'])?> RS</div>
                      <?php endif; ?>
                    </td>
                    <td class="text-right font-weight-bold">
                      <?=$inv_total_qty > 0 ? number_format($inv_total_qty, 2) : '-'?>
                    </td>
                    <td class="text-right font-weight-bold">
                      <?=$inv_rate_display?>
                    </td>
                    <td class="text-right font-weight-bold">
                      <?=$inv_subtotal > 0 ? number_format($inv_subtotal) : '-'?>
                    </td>
                    <td class="text-center font-weight-bold">
                      <?=$inv_tax_rate > 0 ? ($inv_tax_rate + 0) . '%' : '-'?>
                    </td>
                    <td class="text-right font-weight-bold">
                      <?=$inv_tax_amount > 0 ? number_format($inv_tax_amount) : '-'?>
                    </td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-right">
                      <?php if ($row_debit > 0): ?>
                        <span class="text-primary font-weight-bold" style="font-size: 14px;"><?=number_format($row_debit)?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right">
                      <?php if ($row_credit > 0): ?>
                        <span class="text-success font-weight-bold" style="font-size: 14px;"><?=number_format($row_credit)?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right font-weight-bold" style="font-size: 14px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                      <?=number_format($running_bal)?>
                    </td>
                  </tr>
                  <?php

                elseif ($row['transaction_from'] == "voucher"):
                  $invoice_type2 = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT * FROM vouchers WHERE transaction_id1 = '{$row['transaction_id']}' OR transaction_id2 = '{$row['transaction_id']}' LIMIT 1"));
                  $v_id = !empty($invoice_type2['voucher_id']) ? $invoice_type2['voucher_id'] : $row['transaction_id'];

                  $sum_vi_amt = 0;
                  $sum_vi_wht = 0;
                  $v_pos = [];
                  $v_fbrs = [];
                  $v_wht_pcts = [];

                  if (!empty($invoice_type2['voucher_id'])) {
                      $q_vi = mysqli_query($dbc, "SELECT vi.*, ts.fbr_invoice_no FROM voucher_invoices vi LEFT JOIN tax_sales ts ON (vi.invoice_id = ts.invoice_no OR vi.invoice_id = ts.tax_sale_id OR vi.invoice_id = ts.fbr_invoice_no) WHERE vi.voucher_id = '{$invoice_type2['voucher_id']}' ORDER BY vi.id ASC");
                      if ($q_vi && mysqli_num_rows($q_vi) > 0) {
                          while ($vi_row = mysqli_fetch_assoc($q_vi)) {
                              $v_inv_amt = floatval(str_replace(',', '', $vi_row['invoice_amount'] ?? 0));
                              $v_tax = floatval(str_replace(',', '', $vi_row['tax_amount'] ?? 0));
                              $sum_vi_amt += $v_inv_amt;
                              $sum_vi_wht += $v_tax;
                              if (!empty($vi_row['party_po'])) {
                                  $v_pos[] = trim($vi_row['party_po']);
                              }
                              $fbr_val = !empty($vi_row['fbr_invoice_no']) ? trim($vi_row['fbr_invoice_no']) : (!empty($vi_row['invoice_id']) ? trim($vi_row['invoice_id']) : '');
                              if (!empty($fbr_val) && !in_array($fbr_val, $v_fbrs)) {
                                  $v_fbrs[] = $fbr_val;
                              }
                              if (!empty($vi_row['tax_percent']) && floatval($vi_row['tax_percent']) > 0) {
                                  $pct_str = ($vi_row['tax_percent'] + 0) . '%';
                                  if (!in_array($pct_str, $v_wht_pcts)) {
                                      $v_wht_pcts[] = $pct_str;
                                  }
                              }
                          }
                      }
                  }

                  $v_total_raw = (float)($row['debit'] ?? 0);
                  $v_final_debit = ($sum_vi_amt > 0) ? max($v_total_raw, $sum_vi_amt) : $v_total_raw;

                  $sr++;
                  $total_debit += $v_final_debit;
                  $total_wht += $sum_vi_wht;
                  $running_bal -= ($v_final_debit + $sum_vi_wht);

                  $v_po_display = implode(', ', array_unique(array_filter($v_pos)));
                  $v_fbr_display = implode(', ', array_unique(array_filter($v_fbrs)));
                  $v_wht_pct_display = implode(', ', array_unique(array_filter($v_wht_pcts)));
                  ?>
                  <tr>
                    <td class="text-center font-weight-bold"><?=$sr?></td>
                    <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($row['transaction_date']))?></td>
                    <td class="text-center">
                      <strong class="text-success font-weight-bold">VOU #<?=$v_id?></strong>
                    </td>
                    <td class="text-center font-weight-bold">
                      <?=htmlspecialchars($v_fbr_display ?: '-')?>
                    </td>
                    <td class="text-center font-weight-bold">
                      <?=htmlspecialchars($v_po_display ?: '-')?>
                    </td>
                    <td class="text-center">-</td>
                    <td>
                      <div class="item-title text-success font-weight-bold">Payment Received</div>
                      <?php if (!empty($invoice_type2['td_check_no'])): ?>
                        <div class="text-muted mt-1" style="font-size: 11px;">
                          Cheque #: <strong><?=htmlspecialchars($invoice_type2['td_check_no'])?></strong>
                          <?php if (!empty($invoice_type2['voucher_bank_name'])): ?> &bull; Bank: <?=htmlspecialchars($invoice_type2['voucher_bank_name'])?><?php endif; ?>
                          <?php if (!empty($invoice_type2['td_check_date'])): ?> &bull; Date: <?=htmlspecialchars($invoice_type2['td_check_date'])?><?php endif; ?>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($invoice_type2['voucher_hint'])): ?>
                        <div class="text-muted mt-1" style="font-size: 11px;"><em><?=htmlspecialchars($invoice_type2['voucher_hint'])?></em></div>
                      <?php elseif (!empty($row['transaction_remarks'])): ?>
                        <div class="text-muted mt-1" style="font-size: 11px;"><?=str_replace("=", "<br/>", $row['transaction_remarks'])?></div>
                      <?php endif; ?>
                    </td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center">
                      <?php if ($sum_vi_wht > 0): ?>
                        <div class="text-danger font-weight-bold" style="font-size: 13px;">
                          <?=number_format($sum_vi_wht)?>
                        </div>
                        <?php if (!empty($v_wht_pct_display)): ?>
                          <small class="text-muted font-weight-bold"><?=$v_wht_pct_display?></small>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right">
                      <?php if ($v_final_debit > 0): ?>
                        <span class="text-primary font-weight-bold" style="font-size: 14px;">
                          <?=number_format($v_final_debit)?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right"><span class="text-muted">-</span></td>
                    <td class="text-right font-weight-bold" style="font-size: 14px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                      <?=number_format($running_bal)?>
                    </td>
                  </tr>
                  <?php

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
                    <td class="text-center font-weight-bold">-</td>
                    <td class="text-center">-</td>
                    <td><?=str_replace("=", "<br/>", $row['transaction_remarks'])?></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-center"><span class="text-muted">-</span></td>
                    <td class="text-right">
                      <?php if ($row_debit > 0): ?>
                        <span class="text-primary font-weight-bold" style="font-size: 14px;"><?=number_format($row_debit)?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right">
                      <?php if ($row_credit > 0): ?>
                        <span class="text-success font-weight-bold" style="font-size: 14px;"><?=number_format($row_credit)?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right font-weight-bold" style="font-size: 14px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                      <?=number_format($running_bal)?>
                    </td>
                  </tr>
                  <?php
                endif;
              endwhile;
            else:
            ?>
              <tr>
                <td colspan="16" class="text-center py-4 text-muted">No Transaction Found</td>
              </tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <!-- <tr style="background: #e9ecef; font-weight: 700; border-top: 2px solid #454d55;">
              <td colspan="7" class="text-right font-weight-bold" style="font-size: 13px;">Totals:</td>
              <td class="text-right font-weight-bold" style="font-size: 13px;"><?= $tot_qty > 0 ? number_format($tot_qty, 2) : '-' ?></td>
              <td class="text-center font-weight-bold" style="font-size: 13px;">-</td>
              <td class="text-right font-weight-bold" style="font-size: 13px;"><?= $tot_subtotal > 0 ? number_format($tot_subtotal) : '-' ?></td>
              <td class="text-center">-</td>
              <td class="text-right font-weight-bold" style="font-size: 13px;"><?= $tot_tax_amt > 0 ? number_format($tot_tax_amt) : '-' ?></td>
              <td class="text-center font-weight-bold text-danger" style="font-size: 13px;"><?= $total_wht > 0 ? number_format($total_wht) : '-' ?></td>
              <td class="text-right font-weight-bold text-primary" style="font-size: 14px;"><?= number_format($total_debit) ?></td>
              <td class="text-right font-weight-bold text-success" style="font-size: 14px;"><?= number_format($total_credit) ?></td>
              <td class="text-right font-weight-bold" style="font-size: 14px; color: <?= $running_bal > 0 ? '#dc3545' : '#28a745' ?>;"><?= number_format($running_bal) ?></td>
            </tr> -->
            <tr>
              <td colspan="12"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Opening Balance</td>
              <td colspan="2" class="h4 text-success font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($opening_balance)?></td>
            </tr>
            <tr>
              <td colspan="12"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Total Debits</td>
              <td colspan="2" class="h4 text-info font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($total_debit)?></td>
            </tr>
            <tr>
              <td colspan="12"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Total WHT</td>
              <td colspan="2" class="h4 text-danger font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($total_wht)?></td>
            </tr>
            <tr>
              <td colspan="12"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Total Credits</td>
              <td colspan="2" class="h4 text-warning font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($total_credit)?></td>
            </tr>
            <tr>
              <td colspan="12"></td>
              <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Closing Balance</td>
              <td colspan="2" class="h4 font-weight-bold mb-0 <?=$running_bal > 0 ? 'text-danger' : 'text-success'?>" style="vertical-align: middle;"><?=number_format($running_bal)?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
<?php 
          else:
            // ==========================================
            // PREVIOUS DESIGN & LOGIC FOR SUPPLIER, BANK, ETC.
            // ==========================================
            if (!empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
                  $sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_add_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' ";
                   $opening_sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_from='voucher' AND transaction_add_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' ORDER BY transaction_id ASC LIMIT 1 ";
                   $date_comment="<h6>FROM :".$_REQUEST['from_date']." TO :".$_REQUEST['to_date']."</h6>";
            }else if (!empty($_REQUEST['from_date']) AND empty($_REQUEST['to_date'])) {
                  $sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_add_date = '".$_REQUEST['from_date']."' ";
                  $opening_sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_from='voucher' AND transaction_add_date='".$_REQUEST['from_date']."' ORDER BY transaction_id ASC LIMIT 1 ";
                  $date_comment="<h6>Date :".$_REQUEST['from_date']."</h6>";
            }else{
                     $opening_sql = "SELECT * FROM transactions WHERE customer_id='$customer' AND transaction_from='voucher' ORDER BY transaction_id ASC LIMIT 1 ";
                 $date_comment="<h6>Overall</h6>";
                  $sql = "SELECT * FROM transactions WHERE customer_id='$customer' ";
            }

            $result = mysqli_query($dbc, $sql);
?>
  <center style="width: 100%;margin-top: -5px;"><?=$date_comment?></center>
  <div class="row">
    <div class="col-12">
      <table class="table table-bordered table-striped" style="width: 100%">
        <thead>
          <tr>
            <th>Transaction #</th>
            <th>Date</th>
            <?php if (isset($_POST['fullledger'])): ?>
              <th>Transfer From</th>
            <?php endif ?>
            <th>Remarks</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Balance</th>
            <?php if (isset($_POST['fullledger'])): ?>
              <th>Remaining</th>
              <th>Extra</th>
            <?php endif ?>
          </tr>
        </thead>
        <tbody>
          <?php 
          $temp=$check_remaing_balance=$show_rem_bal=$total_debit=$total_credit=0;
          $debitTotal = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(debit) AS debitTotal FROM transactions WHERE customer_id = '$customer'"));
          $check_remaing_balance=$debitTotal['debitTotal'] ?? 0;
          if ( mysqli_num_rows($result) > 0) :
            while($row = mysqli_fetch_array($result)):
              @$total_debit += (float)($row['debit'] ?? 0);
              $invoice_type=$comment='';
              $remaing_amount=0;
              $remaing_balance1 = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT sum(debit-credit) AS Nettotal FROM transactions WHERE customer_id = '$customer'"));
              $remaing_balance=$remaing_balance1['Nettotal'] ?? 0;

              if ($row['transaction_type']=="credit_sale") {
                 $check_remaing_balance=$check_remaing_balance-$row['credit'];
                 
                $invoice_id=filter_var($row['transaction_remarks'], FILTER_SANITIZE_NUMBER_INT);
                 $fetchinvoive = fetchRecord($dbc,"orders","order_id",$invoice_id);
                  $invoice_type=$fetchinvoive['credit_sale_type'] ?? '';
                  if ($check_remaing_balance<0) {
                        $Date = date('Y-m-d');
                        $now = strtotime($Date);
                        if ($invoice_type=="15days" AND ($fetchinvoive['payment_status'] ?? 0)==0) {
                          $sale_Type = '15';
                          $next_date= date('Y-m-d', strtotime(($fetchinvoive['order_date'] ?? date('Y-m-d')). ' + 15 days'));
                        }elseif($invoice_type=="30days" AND ($fetchinvoive['payment_status'] ?? 0)==0){
                          $sale_Type = '30';
                          $next_date= date('Y-m-d', strtotime(($fetchinvoive['order_date'] ?? date('Y-m-d')). ' + 30 days'));
                        }elseif($invoice_type=="5days" AND ($fetchinvoive['payment_status'] ?? 0)==0){
                          $sale_Type = '30';
                          $next_date= date('Y-m-d', strtotime(($fetchinvoive['order_date'] ?? date('Y-m-d')). ' + 5 days'));
                        }
                        else{
                          $sale_Type = 'special';
                          $next_date='special';
                        }
                        $remaing_amount=$fetchinvoive['due'] ?? 0;
                        if($next_date != 'special'){
                          $your_date = strtotime($next_date);
                          $datediff =$your_date- $now;
                          $total_days= round($datediff / (60 * 60 * 24));
                          if ($total_days>0) {
                            $comment='<span class="text-warning">'.(($sale_Type-$total_days)).'/'.$sale_Type.'<br/>'.$total_days.' days left </span>('.$next_date.')';
                          }else{
                            $comment='<span class="text-danger">'.abs($total_days-$sale_Type).'/'.$sale_Type.'  Expired </span>';
                          }
                        }
                        $show_rem_bal=abs($check_remaing_balance);
                  }else{
                       $show_rem_bal=0;
                       $comment='<span class="text-success">Paid </span>';
                  } 
              }elseif($row['transaction_from']=="voucher"){
                $show_rem_bal=0;
                $fetchinvoive = fetchRecord($dbc,"orders","order_id",$row['transaction_id']);
                $invoice_type=$row['transaction_type'];
               
                $invoice_type2 = mysqli_fetch_assoc(mysqli_query($dbc,"SELECT * FROM vouchers WHERE transaction_id1 = '$row[transaction_id]' "));
                if(!empty($invoice_type2['voucher_id'])){
                  $invoice_type =  $invoice_type2['voucher_id'];
                }
              }
              @$total_credit+= (float)($row['credit'] ?? 0);
              $row_debit = (float)($row['debit'] ?? 0);
              $row_credit = (float)($row['credit'] ?? 0);
              $cur_temp_calc = ((int)$row['credit']-(int)$row['debit'])+(int)$temp;

            if ($row_debit != 0 || $row_credit != 0):
              ?>
              <tr>
                <td><?=$row['transaction_id']?></td>
                <td><?=date('D, d-M-Y',strtotime($row['transaction_date']))?></td>
                <?php if (isset($_POST['fullledger'])): ?>
                  <td><?=$row['transaction_from']?> (<?=@$invoice_type?>)</td>
                <?php endif ?>
                <td><?= str_replace("=","<br/>",$row['transaction_remarks']);?></td>
                <td class="text-primary h6"><?=@number_format($row['debit'])?></td>
                <td class="text-success h6 font-weight-bolder"><?=@number_format($row['credit'])?></td>
                <?php if ($check_remaing_balance<0 AND $row['transaction_from']=="invoice"): ?>
                  <td class="text-danger"><?=number_format($cur_temp_calc)?></td>
                <?php elseif ($row['transaction_from']=="voucher"): ?>
                  <td class="text-info"><?=number_format($cur_temp_calc)?></td>
                <?php else: ?>
                  <td class="text-success"><?=number_format($cur_temp_calc)?></td>
                <?php endif ?>
                <?php if (isset($_POST['fullledger'])): ?>
                  <td class=" font-weight-bolder"><?=@number_format($show_rem_bal)?></td>
                  <td><?=$comment?></td>
                <?php endif; ?>
              </tr>
              <?php
            endif;

            $temp=((int)$row['credit']-(int)$row['debit'])+$temp;
            ?>
            <?php endwhile; ?>

            <?php 
              $open_b_res = mysqli_query($dbc,$opening_sql);
              $open_b = $open_b_res ? mysqli_fetch_assoc($open_b_res) : null;
              if (empty($open_b)) {
                $opening_balance = 0;
              } elseif ((float)($open_b['debit'] ?? 0) == 0) {
                $opening_balance = (float)($open_b['credit'] ?? 0);
              }else{
                $opening_balance = (float)($open_b['debit'] ?? 0);
              }
            ?>
            <tr>
              <?php if (isset($_POST['fullledger'])): ?>
                <td colspan="3"></td>
              <?php else: ?>
                <td colspan="2"></td>
              <?php endif; ?> 
              <td colspan="3" align="right">  Opening Balance </td>
              <td colspan="4" class='h3 text-success'><?=$opening_balance?></td>
            </tr>
            <tr>
              <?php if (isset($_POST['fullledger'])): ?>
                <td colspan="3"></td>
              <?php else: ?>
                <td colspan="2"></td>
              <?php endif; ?> 
              <td colspan="3" align="right">Total Debits</td>
              <td colspan="4" class='h3 text-info'><?=number_format((float)$total_debit)?></td>
            </tr>
            <tr>
              <?php if (isset($_POST['fullledger'])): ?>
                <td colspan="3"></td>
              <?php else: ?>
                <td colspan="2"></td>
              <?php endif; ?> 
              <td colspan="3" align="right">Total Credits</td>
              <td colspan="4" class='h3 text-warning'><?=number_format((float)$total_credit)?></td>
            </tr>
            <tr>
              <?php if (isset($_POST['fullledger'])): ?>
                <td colspan="3"></td>
              <?php else: ?>
                <td colspan="2"></td>
              <?php endif; ?> 
              <td colspan="3" align="right">Closing Balance</td>
              <?php if ($temp<=0): ?>
                <td colspan="4" class='h3 text-danger'><?=number_format((float)$temp)?></td>
              <?php else: ?>
                <td colspan="4" class='h3 text-success'><?=number_format((float)$temp)?></td>
              <?php endif ?>
            </tr>
          </tbody>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center">No Transaction Found</td>
            </tr>
          <?php endif; ?>
          </table>
        </div>
      </div>
<?php 
          endif; // end if customer vs non-customer
        } // end if (!empty($customer))
?>

 
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