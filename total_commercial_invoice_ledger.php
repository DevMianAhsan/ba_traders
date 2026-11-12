<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 

$customer = $_REQUEST['customer_id'] ?? '';
$from_date = $_REQUEST['from_date'] ?? '';
$to_date = $_REQUEST['to_date'] ?? '';
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
      font-size: 11px !important;
    }
    .ledger-table {
      width: 100% !important;
      border-collapse: collapse !important;
    }
    .ledger-table thead th {
      background-color: #222 !important;
      color: #fff !important;
      border: 1px solid #000 !important;
      font-size: 10.5px !important;
      padding: 4px 2px !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .ledger-table tbody td, .ledger-table tfoot td {
      border: 1px solid #000 !important;
      font-size: 10px !important;
      padding: 4px 2px !important;
      color: #000 !important;
    }
    .print-header {
      margin-bottom: 15px !important;
    }
  }
</style>
<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>
    <main role="main" class="main-content">
      <div class="container-fluid">
        <div class="card shadow">
          <div class="card-header card-bg d-print-none">
            <div class="row align-items-center">
              <div class="col-sm-6">
                <h4 class="mb-0 font-weight-bold text-white">Total Commercial Invoice Ledger</h4>
                <small class="text-white-50">Customer statement for Combined Commercial Invoices (All DCs Included) & Voucher Payments</small>
              </div>
              <div class="col-sm-6 text-right">
                <a href="commercial_invoice_ledger.php" class="btn btn-sm btn-info ml-1"><i class="fe fe-list"></i> DC Ledger</a>
                <a href="invoice.php" class="btn btn-sm btn-admin ml-1"><i class="fe fe-plus"></i> New Commercial Inv</a>
                <a href="invoice_list.php" class="btn btn-sm btn-outline-secondary text-white ml-1"><i class="fe fe-list"></i> Inv List</a>
              </div>
            </div>
          </div>

          <div class="card-body">
            <!-- Filter Form -->
            <form action="total_commercial_invoice_ledger.php" method="POST" class="d-print-none mb-4">
              <div class="row align-items-end">
                <div class="form-group col-md-4 col-sm-6 mb-2">
                  <label class="font-weight-bold">Customer Account <span class="text-danger">*</span></label>
                  <select required class="form-control searchableSelect" id="ledger_customer_id" name="customer_id" autofocus="true">
                    <option value="">-- Select Customer Account --</option>
                    <?php
                    $q_cust = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_status = 1 AND customer_type = 'customer' ORDER BY customer_name ASC");
                    while ($r_c = mysqli_fetch_assoc($q_cust)) {
                      $sel = ($customer == $r_c['customer_id']) ? 'selected' : '';
                      echo "<option value='{$r_c['customer_id']}' {$sel}>" . htmlspecialchars(getCustomerDisplayName($r_c['customer_name'], $r_c['customer_phone'] ?? '')) . "</option>";
                    }
                    ?>
                  </select>
                </div>

                <div class="form-group col-md-2 col-sm-3 mb-2">
                  <label class="font-weight-bold">From Date</label>
                  <input type="date" name="from_date" value="<?=htmlspecialchars($from_date)?>" class="form-control">
                </div>

                <div class="form-group col-md-2 col-sm-3 mb-2">
                  <label class="font-weight-bold">To Date</label>
                  <input type="date" name="to_date" value="<?=htmlspecialchars($to_date)?>" class="form-control">
                </div>

                <div class="form-group col-md-4 col-sm-12 mb-2 text-right">
                  <button class="btn btn-admin mr-1" name="search_ledger" type="submit">
                    <i class="fe fe-search"></i> Search Ledger
                  </button>
                  <?php if (!empty($customer)): ?>
                    <button class="btn btn-primary" type="button" onclick="window.print();">
                      <i class="fe fe-printer"></i> Print Statement
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </form>

            <?php
            if (!empty($customer)):
              $fetchCustomer = fetchRecord($dbc, "customers", "customer_id", $customer);

              // 1. Calculate Opening Balance if from_date is set
              $opening_balance = 0;
              $date_comment = "OVERALL STATEMENT";

              if (!empty($from_date) && !empty($to_date)) {
                $date_comment = "FROM: " . date('d-M-Y', strtotime($from_date)) . " &nbsp; TO: " . date('d-M-Y', strtotime($to_date));
              } elseif (!empty($from_date)) {
                $date_comment = "FROM: " . date('d-M-Y', strtotime($from_date)) . " ONWARDS";
              } elseif (!empty($to_date)) {
                $date_comment = "UP TO: " . date('d-M-Y', strtotime($to_date));
              }

              if (!empty($from_date)) {
                // Invoices prior to from_date
                $q_pre_inv = mysqli_query($dbc, "SELECT SUM(CAST(grand_total AS DECIMAL(15,2))) AS pre_credit FROM invoices WHERE customer_id = '$customer' AND invoice_date < '$from_date'");
                $r_pre_inv = mysqli_fetch_assoc($q_pre_inv);
                $pre_credit = floatval($r_pre_inv['pre_credit'] ?? 0);

                // Vouchers prior to from_date
                $pre_debit = 0;
                $q_pre_v = mysqli_query($dbc, "
                  SELECT DISTINCT v.* 
                  FROM vouchers v
                  LEFT JOIN voucher_invoices vi ON v.voucher_id = vi.voucher_id
                  WHERE (v.customer_id1 = '$customer' 
                     OR vi.invoice_id IN (SELECT invoice_id FROM invoices WHERE customer_id = '$customer'))
                    AND v.voucher_date < '$from_date'
                ");
                while ($pv = mysqli_fetch_assoc($q_pre_v)) {
                  $pv_id = $pv['voucher_id'];
                  $q_pvi = mysqli_query($dbc, "SELECT * FROM voucher_invoices WHERE voucher_id = '$pv_id'");
                  if ($q_pvi && mysqli_num_rows($q_pvi) > 0) {
                    while ($pvi_row = mysqli_fetch_assoc($q_pvi)) {
                      $inv_settle = floatval(str_replace(',', '', $pvi_row['invoice_amount'] ?? 0));
                      $tax_settle = floatval(str_replace(',', '', $pvi_row['tax_amount'] ?? 0));
                      $pre_debit += ($inv_settle + $tax_settle);
                    }
                  } else {
                    $pre_debit += floatval(str_replace(',', '', $pv['voucher_amount'] ?? 0));
                  }
                }
                $opening_balance = $pre_credit - $pre_debit;
              }

              // 2. Fetch Invoices in period
              $inv_where = "customer_id = '$customer'";
              if (!empty($from_date) && !empty($to_date)) {
                $inv_where .= " AND invoice_date BETWEEN '$from_date' AND '$to_date'";
              } elseif (!empty($from_date)) {
                $inv_where .= " AND invoice_date >= '$from_date'";
              } elseif (!empty($to_date)) {
                $inv_where .= " AND invoice_date <= '$to_date'";
              }

              $q_invoices = mysqli_query($dbc, "SELECT * FROM invoices WHERE $inv_where ORDER BY invoice_date ASC, invoice_id ASC");
              $entries = [];

              while ($inv = mysqli_fetch_assoc($q_invoices)) {
                $items = [];
                $inv_total_qty = 0;
                $prod_names = [];
                $inv_pos = [];
                $inv_rates = [];
                if (!empty($inv['party_po'])) {
                  $inv_pos[] = trim($inv['party_po']);
                }

                $invDetails = json_decode($inv['invoice_details'], true);
                if (!empty($invDetails['data']) && is_array($invDetails['data'])) {
                  foreach ($invDetails['data'] as $d) {
                    if (!empty($d['party_po'])) {
                      $inv_pos[] = trim($d['party_po']);
                    }
                    if (!empty($d['dc_no'])) {
                      $dc_no = intval($d['dc_no']);
                      $q_items = mysqli_query($dbc, "SELECT oi.*, p.product_name, p.product_code, p.product_unit 
                        FROM order_item oi 
                        LEFT JOIN product p ON oi.product_id = p.product_id 
                        WHERE oi.order_id = '$dc_no'");
                      if ($q_items) {
                        while ($it = mysqli_fetch_assoc($q_items)) {
                          $item_qty = floatval($it['quantity']);
                          $item_rate = floatval($it['rate']);
                          $inv_total_qty += $item_qty;
                          if ($item_rate > 0) {
                            $inv_rates[] = number_format($item_rate, 2);
                          }
                          $pname = !empty($it['product_name']) ? trim($it['product_name']) : ('Product #' . $it['product_id']);
                          if (!empty($pname) && !in_array($pname, $prod_names)) {
                            $prod_names[] = $pname;
                          }
                          $items[] = [
                            'dc_no' => $dc_no,
                            'party_po' => $d['party_po'] ?? $inv['party_po'],
                            'product_name' => $pname,
                            'product_code' => $it['product_code'] ?? '',
                            'product_unit' => $it['product_unit'] ?? '',
                            'quantity' => $item_qty,
                            'rate' => $item_rate,
                            'total' => floatval($it['total'] ?? ($item_qty * $item_rate))
                          ];
                        }
                      }
                    }
                  }
                }

                $inv_total_amt = floatval($inv['total_amount']);
                $inv_disc = floatval($inv['discount']);
                $inv_tax_amt = ($inv_total_amt * ($inv_disc / 100));
                $po_display = implode(', ', array_unique(array_filter($inv_pos)));
                $inv_rate_display = !empty($inv_rates) ? implode(', ', array_unique($inv_rates)) : '-';

                $entries[] = [
                  'type' => 'invoice',
                  'date' => $inv['invoice_date'],
                  'sort_time' => strtotime($inv['invoice_date'] . ' 00:00:00'),
                  'id' => $inv['invoice_id'],
                  'fbr_no' => '',
                  'party_po' => $po_display,
                  'days' => $inv['credit_sale_type'],
                  'items' => $items,
                  'prod_names' => $prod_names,
                  'total_qty' => $inv_total_qty,
                  'rate_display' => $inv_rate_display,
                  'total_amount' => $inv_total_amt,
                  'discount' => $inv_disc,
                  'tax_amount' => $inv_tax_amt,
                  'freight' => floatval($inv['freight']),
                  'grand_total' => floatval($inv['grand_total']),
                  'narration' => $inv['order_narration'],
                  'credit' => floatval($inv['grand_total']),
                  'debit' => 0,
                  'wht_percent' => 0,
                  'wht_amount' => 0,
                ];
              }

              // 3. Fetch Vouchers in period
              $v_sql = "
                SELECT DISTINCT v.* 
                FROM vouchers v
                LEFT JOIN voucher_invoices vi ON v.voucher_id = vi.voucher_id
                WHERE (v.customer_id1 = '$customer' 
                   OR vi.invoice_id IN (SELECT invoice_id FROM invoices WHERE customer_id = '$customer'))
              ";
              if (!empty($from_date) && !empty($to_date)) {
                $v_sql .= " AND v.voucher_date BETWEEN '$from_date' AND '$to_date'";
              } elseif (!empty($from_date)) {
                $v_sql .= " AND v.voucher_date >= '$from_date'";
              } elseif (!empty($to_date)) {
                $v_sql .= " AND v.voucher_date <= '$to_date'";
              }
              $v_sql .= " ORDER BY v.voucher_date ASC, v.voucher_id ASC";

              $q_v = mysqli_query($dbc, $v_sql);
              while ($v = mysqli_fetch_assoc($q_v)) {
                $v_id = $v['voucher_id'];
                $q_vi = mysqli_query($dbc, "SELECT vi.*, ts.fbr_invoice_no FROM voucher_invoices vi LEFT JOIN tax_sales ts ON (vi.invoice_id = ts.invoice_no OR vi.invoice_id = ts.tax_sale_id OR vi.invoice_id = ts.fbr_invoice_no) WHERE vi.voucher_id = '$v_id' ORDER BY vi.id ASC");
                
                $sum_vi_amt = 0;
                $sum_vi_wht = 0;
                $v_pos = [];
                $v_fbrs = [];
                $v_wht_pcts = [];

                if ($q_vi && mysqli_num_rows($q_vi) > 0) {
                  while ($vi = mysqli_fetch_assoc($q_vi)) {
                    $inv_amt = floatval(str_replace(',', '', $vi['invoice_amount'] ?? 0));
                    $tax = floatval(str_replace(',', '', $vi['tax_amount'] ?? 0));
                    $sum_vi_amt += $inv_amt;
                    $sum_vi_wht += $tax;
                    if (!empty($vi['party_po'])) {
                      $v_pos[] = trim($vi['party_po']);
                    }
                    $fbr_val = !empty($vi['fbr_invoice_no']) ? trim($vi['fbr_invoice_no']) : (!empty($vi['invoice_id']) ? trim($vi['invoice_id']) : '');
                    if (!empty($fbr_val) && !in_array($fbr_val, $v_fbrs)) {
                      $v_fbrs[] = $fbr_val;
                    }
                    if (!empty($vi['tax_percent']) && floatval($vi['tax_percent']) > 0) {
                      $pct_str = ($vi['tax_percent'] + 0) . '%';
                      if (!in_array($pct_str, $v_wht_pcts)) {
                        $v_wht_pcts[] = $pct_str;
                      }
                    }
                  }
                }

                $v_raw_amt = floatval(str_replace(',', '', $v['voucher_amount'] ?? 0));
                $v_final_debit = ($sum_vi_amt > 0) ? max($v_raw_amt, $sum_vi_amt) : $v_raw_amt;

                $v_po_display = implode(', ', array_unique(array_filter($v_pos)));
                $v_fbr_display = implode(', ', array_unique(array_filter($v_fbrs)));
                $v_wht_pct_display = implode(', ', array_unique(array_filter($v_wht_pcts)));

                $entries[] = [
                  'type' => 'voucher',
                  'date' => $v['voucher_date'],
                  'sort_time' => strtotime($v['voucher_date'] . ' 12:00:00'),
                  'id' => $v['voucher_id'],
                  'fbr_no' => $v_fbr_display,
                  'party_po' => $v_po_display,
                  'days' => '',
                  'items' => [],
                  'prod_names' => [],
                  'total_qty' => 0,
                  'rate_display' => '-',
                  'total_amount' => 0,
                  'discount' => 0,
                  'tax_amount' => 0,
                  'voucher_hint' => $v['voucher_hint'],
                  'check_no' => $v['td_check_no'],
                  'bank_name' => $v['voucher_bank_name'],
                  'check_date' => $v['td_check_date'],
                  'check_type' => $v['check_type'],
                  'credit' => 0,
                  'debit' => $v_final_debit,
                  'wht_percent' => $v_wht_pct_display,
                  'wht_amount' => $sum_vi_wht,
                ];
              }

              // 4. Sort entries chronologically: same date shows invoice first, then voucher
              usort($entries, function($a, $b) {
                if ($a['date'] != $b['date']) {
                  return strcmp($a['date'], $b['date']);
                }
                $order_a = ($a['type'] == 'invoice') ? 1 : 2;
                $order_b = ($b['type'] == 'invoice') ? 1 : 2;
                if ($order_a != $order_b) {
                  return $order_a <=> $order_b;
                }
                return $a['id'] <=> $b['id'];
              });
            ?>

            <!-- Unified Centered Report Header -->
            <?php
            $report_title = "TOTAL COMMERCIAL INVOICE STATEMENT";
            $report_customer = $fetchCustomer;
            $report_duration = $date_comment;
            include 'includes/report_header.php';
            ?>

            <!-- Main Clean Ledger Table -->
            <div class="table-responsive">
              <table class="table table-bordered ledger-table mb-2">
                <thead>
                  <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 8%;">Date</th>
                    <th style="width: 7%;">Ref #</th>
                    <th style="width: 6%;">PO #</th>
                    <th style="width: 6%;">Terms</th>
                    <th style="width: 17%; text-align: left;">Description</th>
                    <th style="width: 7%; text-align: center;">QTY<br/>(WT)</th>
                    <th style="width: 6%; text-align: right;">Rate</th>
                    <th style="width: 7%; text-align: right;">Amount (Rs)</th>
                    <th style="width: 5%; text-align: center;">Tax %</th>
                    <th style="width: 6%; text-align: right;">Tax Amount (Rs)</th>
                    <th style="width: 6%; text-align: center;">WHT Tax</th>
                    <th style="width: 7%; text-align: right;">Debit (Rs)</th>
                    <th style="width: 7%; text-align: right;">Credit (Rs)</th>
                    <th style="width: 8%; text-align: right;">Balance (Rs)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $running_bal = $opening_balance;
                  $tot_credit = 0;
                  $tot_debit = 0;
                  $tot_wht = 0;
                  $tot_qty = 0;
                  $tot_subtotal = 0;
                  $tot_tax_amt = 0;
                  $sr = 0;

                  // Render Opening Balance row if date filter applies
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

                  if (!empty($entries)):
                    foreach ($entries as $e):
                      $sr++;
                      $tot_credit += $e['credit'];
                      $tot_debit += $e['debit'];
                      $tot_wht += $e['wht_amount'];
                      $tot_qty += $e['total_qty'];
                      $tot_subtotal += $e['total_amount'];
                      $tot_tax_amt += $e['tax_amount'];
                      
                      $running_bal += ($e['credit'] - $e['debit'] - $e['wht_amount']);
                  ?>
                  <tr>
                    <!-- # -->
                    <td class="text-center font-weight-bold"><?=$sr?></td>

                    <!-- Date -->
                    <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($e['date']))?></td>

                    <!-- Ref # -->
                    <td class="text-center">
                      <?php if ($e['type'] == 'invoice'): ?>
                        <strong class="text-primary font-weight-bold">INV #<?=$e['id']?></strong>
                      <?php else: ?>
                        <strong class="text-success font-weight-bold">VOU #<?=$e['id']?></strong>
                      <?php endif; ?>
                    </td>

                    <!-- Party PO -->
                    <td class="text-center font-weight-bold">
                      <?=htmlspecialchars($e['party_po'] ?: '-')?>
                    </td>

                    <!-- Terms / Days -->
                    <td class="text-center">
                      <?=htmlspecialchars($e['days'] ?: '-')?>
                    </td>

                    <!-- Description -->
                    <td>
                      <?php if ($e['type'] == 'invoice'): ?>
                        <div class="item-title font-weight-bold"><?=!empty($e['prod_names']) ? htmlspecialchars(implode(', ', $e['prod_names'])) : 'Commercial Invoice #'.$e['id']?></div>
                        <?php if (!empty($e['narration'])): ?>
                          <div class="text-muted mt-1" style="font-size: 11px;"><em><?=htmlspecialchars($e['narration'])?></em></div>
                        <?php endif; ?>
                        <?php if (!empty($e['freight']) && floatval($e['freight']) > 0): ?>
                          <div class="text-muted" style="font-size: 11px;">Freight: <?=number_format($e['freight'])?> RS</div>
                        <?php endif; ?>
                      <?php else: ?>
                        <!-- Voucher Details -->
                        <div class="item-title text-success font-weight-bold">Payment Received</div>

                        <?php if (!empty($e['check_no'])): ?>
                          <div class="text-muted mt-1" style="font-size: 11px;">
                            Cheque #: <strong><?=htmlspecialchars($e['check_no'])?></strong>
                            <?php if (!empty($e['bank_name'])): ?> &bull; Bank: <?=htmlspecialchars($e['bank_name'])?><?php endif; ?>
                            <?php if (!empty($e['check_date'])): ?> &bull; Date: <?=htmlspecialchars($e['check_date'])?><?php endif; ?>
                          </div>
                        <?php endif; ?>

                        <?php if (!empty($e['voucher_hint'])): ?>
                          <div class="text-muted mt-1" style="font-size: 11px;"><em><?=htmlspecialchars($e['voucher_hint'])?></em></div>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>

                    <!-- QTY (WT) -->
                    <td class="text-right font-weight-bold">
                      <?=$e['total_qty'] > 0 ? number_format($e['total_qty'], 2) : '-'?>
                    </td>

                    <!-- Rate -->
                    <td class="text-right font-weight-bold">
                      <?=$e['type'] == 'invoice' ? ($e['rate_display'] ?: '-') : '-'?>
                    </td>

                    <!-- Amount (Rs) -->
                    <td class="text-right font-weight-bold">
                      <?=$e['total_amount'] > 0 ? number_format($e['total_amount']) : '-'?>
                    </td>

                    <!-- Tax % -->
                    <td class="text-center font-weight-bold">
                      <?=$e['discount'] > 0 ? ($e['discount'] + 0) . '%' : '-'?>
                    </td>

                    <!-- Tax Amount (Rs) -->
                    <td class="text-right font-weight-bold">
                      <?=$e['tax_amount'] > 0 ? number_format($e['tax_amount']) : '-'?>
                    </td>

                    <!-- WHT Tax -->
                    <td class="text-center">
                      <?php if ($e['type'] == 'voucher' && $e['wht_amount'] > 0): ?>
                        <div class="text-danger font-weight-bold" style="font-size: 13px;">
                          <?=number_format($e['wht_amount'])?>
                        </div>
                        <?php if (!empty($e['wht_percent'])): ?>
                          <small class="text-muted font-weight-bold"><?=$e['wht_percent']?></small>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- Debit (Rs) -->
                    <td class="text-right">
                      <?php if ($e['debit'] > 0): ?>
                        <span class="text-primary font-weight-bold" style="font-size: 14px;">
                          <?=number_format($e['debit'])?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- Credit (Rs) -->
                    <td class="text-right">
                      <?php if ($e['credit'] > 0): ?>
                        <span class="text-success font-weight-bold" style="font-size: 14px;">
                          <?=number_format($e['credit'])?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- Balance (Rs) -->
                    <td class="text-right font-weight-bold" style="font-size: 14px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                      <?=number_format($running_bal)?>
                    </td>
                  </tr>
                  <?php
                    endforeach;
                  else:
                  ?>
                  <tr>
                    <td colspan="15" class="text-center py-4 text-muted">
                      <i class="fe fe-info fe-16 mr-1"></i> No Commercial Invoices or Vouchers found for this Customer Account in the selected period.
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="11"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Opening Balance</td>
                    <td colspan="2" class="h4 text-success font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($opening_balance)?></td>
                  </tr>
                  <tr>
                    <td colspan="11"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Total Debits</td>
                    <td colspan="2" class="h4 text-info font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($tot_debit)?></td>
                  </tr>
                  <tr>
                    <td colspan="11"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Total WHT</td>
                    <td colspan="2" class="h4 text-danger font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($tot_wht)?></td>
                  </tr>
                  <tr>
                    <td colspan="11"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Total Credits</td>
                    <td colspan="2" class="h4 text-warning font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($tot_credit)?></td>
                  </tr>
                  <tr>
                    <td colspan="11"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 14px; vertical-align: middle;">Closing Balance</td>
                    <?php
                      $closing_type = '';
                      if ($running_bal < 0) {
                        $closing_type = ' dr';
                      } elseif ($running_bal > 0) {
                        $closing_type = ' cr';
                      }
                    ?>
                    <td colspan="2" class="h4 font-weight-bold mb-0 <?=$running_bal > 0 ? 'text-danger' : 'text-success'?>" style="vertical-align: middle;"><?=number_format(abs($running_bal)) . $closing_type?></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <?php else: ?>
              <div class="alert alert-info text-center font-weight-bold py-4 my-3">
                <i class="fe fe-info fe-24 d-block mb-2"></i>
                Please select a Customer Account above and click <strong>Search Ledger</strong> to view the Total Commercial Invoice Statement.
              </div>
            <?php endif; ?>

          </div> <!-- .card-body -->
        </div> <!-- .card -->
      </div> <!-- .container-fluid -->
    </main>
  </div> <!-- .wrapper -->
</body>
</html>
<?php include_once 'includes/foot.php'; ?>
