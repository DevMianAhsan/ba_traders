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
                <h4 class="mb-0 font-weight-bold text-dark">Commercial Invoice Ledger</h4>
                <small class="text-muted">Customer statement for Commercial Invoices & Voucher Payments</small>
              </div>
              <div class="col-sm-6 text-right">
                <a href="invoice.php" class="btn btn-sm btn-admin ml-1"><i class="fe fe-plus"></i> New Commercial Inv</a>
                <a href="invoice_list.php" class="btn btn-sm btn-outline-secondary ml-1"><i class="fe fe-list"></i> Inv List</a>
              </div>
            </div>
          </div>

          <div class="card-body">
            <!-- Filter Form -->
            <form action="commercial_invoice_ledger.php" method="POST" class="d-print-none mb-4">
              <div class="row align-items-end">
                <div class="form-group col-md-4 col-sm-6 mb-2">
                  <label class="font-weight-bold">Customer Account <span class="text-danger">*</span></label>
                  <select required class="form-control" id="ledger_customer_id" name="customer_id" autofocus="true">
                    <option value="">-- Select Customer Account --</option>
                    <?php
                    $q_cust = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_status = 1 AND customer_type = 'customer' ORDER BY customer_name ASC");
                    while ($r_c = mysqli_fetch_assoc($q_cust)) {
                      $sel = ($customer == $r_c['customer_id']) ? 'selected' : '';
                      echo "<option value='{$r_c['customer_id']}' {$sel}>" . htmlspecialchars($r_c['customer_name']) . " (" . htmlspecialchars($r_c['customer_phone'] ?? '') . ")</option>";
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
                      $r_amt = floatval(str_replace(',', '', $pvi_row['received_amount'] ?? 0));
                      $t_amt = floatval(str_replace(',', '', $pvi_row['tax_amount'] ?? 0));
                      $tot_settle = $r_amt + $t_amt;
                      if ($tot_settle <= 0) {
                        $tot_settle = floatval(str_replace(',', '', $pvi_row['invoice_amount'] ?? 0));
                      }
                      $pre_debit += $tot_settle;
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
                $invDetails = json_decode($inv['invoice_details'], true);
                if (!empty($invDetails['data']) && is_array($invDetails['data'])) {
                  foreach ($invDetails['data'] as $d) {
                    if (!empty($d['dc_no'])) {
                      $dc_no = intval($d['dc_no']);
                      $q_items = mysqli_query($dbc, "SELECT oi.*, p.product_name, p.product_code, p.product_unit 
                        FROM order_item oi 
                        LEFT JOIN product p ON oi.product_id = p.product_id 
                        WHERE oi.order_id = '$dc_no'");
                      if ($q_items) {
                        while ($it = mysqli_fetch_assoc($q_items)) {
                          $items[] = [
                            'dc_no' => $dc_no,
                            'party_po' => $d['party_po'] ?? $inv['party_po'],
                            'product_name' => $it['product_name'] ?? ('Product #' . $it['product_id']),
                            'product_code' => $it['product_code'] ?? '',
                            'product_unit' => $it['product_unit'] ?? '',
                            'quantity' => floatval($it['quantity']),
                            'rate' => floatval($it['rate']),
                            'total' => floatval($it['total'] ?? ($it['quantity'] * $it['rate']))
                          ];
                        }
                      }
                    }
                  }
                }

                $entries[] = [
                  'type' => 'invoice',
                  'date' => $inv['invoice_date'],
                  'sort_time' => strtotime($inv['invoice_date'] . ' 00:00:00'),
                  'id' => $inv['invoice_id'],
                  'party_po' => $inv['party_po'],
                  'days' => $inv['credit_sale_type'],
                  'items' => $items,
                  'total_amount' => floatval($inv['total_amount']),
                  'discount' => floatval($inv['discount']),
                  'freight' => floatval($inv['freight']),
                  'grand_total' => floatval($inv['grand_total']),
                  'narration' => $inv['order_narration'],
                  'credit' => floatval($inv['grand_total']),
                  'debit' => 0,
                  'wht_percent' => 0,
                  'wht_amount' => 0,
                  'received_amount' => 0,
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
                $q_vi = mysqli_query($dbc, "SELECT * FROM voucher_invoices WHERE voucher_id = '$v_id'");
                $has_breakdown = ($q_vi && mysqli_num_rows($q_vi) > 0);

                if ($has_breakdown) {
                  $sum_vi_settled = 0;
                  while ($vi = mysqli_fetch_assoc($q_vi)) {
                    $rec = floatval(str_replace(',', '', $vi['received_amount'] ?? 0));
                    $tax = floatval(str_replace(',', '', $vi['tax_amount'] ?? 0));
                    $total_settled = $rec + $tax;
                    if ($total_settled <= 0) {
                      $total_settled = floatval(str_replace(',', '', $vi['invoice_amount'] ?? 0));
                    }
                    $sum_vi_settled += $total_settled;

                    $entries[] = [
                      'type' => 'voucher',
                      'date' => $v['voucher_date'],
                      'sort_time' => strtotime($v['voucher_date'] . ' 12:00:00'),
                      'id' => $v['voucher_id'],
                      'invoice_id' => $vi['invoice_id'],
                      'party_po' => $vi['party_po'] ?? '',
                      'days' => '',
                      'items' => [],
                      'voucher_hint' => $v['voucher_hint'],
                      'check_no' => $v['td_check_no'],
                      'bank_name' => $v['voucher_bank_name'],
                      'check_date' => $v['td_check_date'],
                      'check_type' => $v['check_type'],
                      'credit' => 0,
                      'debit' => $rec,
                      'wht_percent' => !empty($vi['tax_percent']) ? $vi['tax_percent'] : 0,
                      'wht_amount' => $tax,
                      'received_amount' => $rec,
                    ];
                  }

                  // If voucher has unallocated balance
                  $v_raw_amt = floatval(str_replace(',', '', $v['voucher_amount'] ?? 0));
                  $rem_v_amt = $v_raw_amt - $sum_vi_settled;
                  if ($rem_v_amt > 0.01) {
                    $entries[] = [
                      'type' => 'voucher',
                      'date' => $v['voucher_date'],
                      'sort_time' => strtotime($v['voucher_date'] . ' 12:00:00'),
                      'id' => $v['voucher_id'],
                      'invoice_id' => '',
                      'party_po' => '',
                      'days' => '',
                      'items' => [],
                      'voucher_hint' => $v['voucher_hint'] ?: 'Unallocated Balance',
                      'check_no' => $v['td_check_no'],
                      'bank_name' => $v['voucher_bank_name'],
                      'check_date' => $v['td_check_date'],
                      'check_type' => $v['check_type'],
                      'credit' => 0,
                      'debit' => $rem_v_amt,
                      'wht_percent' => 0,
                      'wht_amount' => 0,
                      'received_amount' => $rem_v_amt,
                    ];
                  }
                } else {
                  $amt = floatval(str_replace(',', '', $v['voucher_amount'] ?? 0));
                  $entries[] = [
                    'type' => 'voucher',
                    'date' => $v['voucher_date'],
                    'sort_time' => strtotime($v['voucher_date'] . ' 12:00:00'),
                    'id' => $v['voucher_id'],
                    'invoice_id' => '',
                    'party_po' => '',
                    'days' => '',
                    'items' => [],
                    'voucher_hint' => $v['voucher_hint'],
                    'check_no' => $v['td_check_no'],
                    'bank_name' => $v['voucher_bank_name'],
                    'check_date' => $v['td_check_date'],
                    'check_type' => $v['check_type'],
                    'credit' => 0,
                    'debit' => $amt,
                    'wht_percent' => 0,
                    'wht_amount' => 0,
                    'received_amount' => $amt,
                  ];
                }
              }

              // 4. Sort entries chronologically
              usort($entries, function($a, $b) {
                if ($a['sort_time'] == $b['sort_time']) {
                  if ($a['id'] == $b['id']) {
                    return ($a['invoice_id'] ?? 0) <=> ($b['invoice_id'] ?? 0);
                  }
                  return $a['id'] <=> $b['id'];
                }
                return $a['sort_time'] <=> $b['sort_time'];
              });
            ?>

            <!-- Printable Company & Customer Header -->
            <div class="print-header mb-3">
              <div class="row align-items-center">
                <div class="col-sm-2 col-3">
                  <img src="img/logo/<?=$get_company['logo']?>" width="110" class="img-fluid" alt="Company Logo">
                </div>
                <div class="col-sm-6 col-5">
                  <h2 style="color: #930027; font-weight: 800; font-size: 24px; margin-bottom: 2px; text-transform: uppercase;">
                    <?=htmlspecialchars($get_company['name'])?>
                  </h2>
                  <p style="font-weight: 600; font-size: 13px; margin-bottom: 2px;">
                    <i class="fe fe-map-pin d-print-none"></i> <?=htmlspecialchars($get_company['address'])?>
                  </p>
                  <p style="font-weight: 600; font-size: 13px; margin-bottom: 0;">
                    <i class="fe fe-phone d-print-none"></i> PH: <?=htmlspecialchars($get_company['company_phone'])?>
                  </p>
                </div>
                <div class="col-sm-4 col-4 text-right">
                  <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 14px; display: inline-block; text-align: left; min-width: 240px;">
                    <h6 style="color: #930027; font-weight: 800; margin-bottom: 4px; font-size: 13px; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; text-transform: uppercase;">
                      Commercial Statement
                    </h6>
                    <p style="margin-bottom: 2px; font-weight: 700; font-size: 14px;">
                      <strong>Account:</strong> <?=htmlspecialchars(ucwords($fetchCustomer['customer_name'] ?? ''))?>
                    </p>
                    <p style="margin-bottom: 2px; font-size: 12px;">
                      <strong>Phone:</strong> <?=htmlspecialchars($fetchCustomer['customer_phone'] ?? '-')?>
                    </p>
                    <?php if (!empty($fetchCustomer['customer_address'])): ?>
                      <p style="margin-bottom: 0; font-size: 11px; color: #555;">
                        <strong>Address:</strong> <?=htmlspecialchars($fetchCustomer['customer_address'])?>
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="alert alert-secondary py-1 text-center font-weight-bold mt-2 mb-3" style="font-size: 12px; letter-spacing: 0.5px;">
                <?=$date_comment?> &bull; COMMERCIAL INVOICE STATEMENT
              </div>
            </div>

            <!-- Main Clean Ledger Table -->
            <div class="table-responsive">
              <table class="table table-bordered ledger-table mb-2">
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
                  $tot_credit = 0;
                  $tot_debit = 0;
                  $tot_wht = 0;
                  $tot_rec = 0;
                  $sr = 0;

                  // Render Opening Balance row if date filter applies
                  if (!empty($from_date) && $opening_balance != 0):
                  ?>
                  <tr style="background: #fff3cd; font-weight: 700;">
                    <td class="text-center">-</td>
                    <td><?=date('d-M-Y', strtotime($from_date))?></td>
                    <td><strong>OPENING</strong></td>
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

                  if (!empty($entries)):
                    foreach ($entries as $e):
                      $sr++;
                      $tot_credit += $e['credit'];
                      $tot_debit += $e['debit'];
                      $tot_wht += $e['wht_amount'];
                      $tot_rec += $e['received_amount'];
                      $running_bal += ($e['credit'] - $e['debit'] - $e['wht_amount']);
                  ?>
                  <tr>
                    <!-- # -->
                    <td class="text-center font-weight-bold"><?=$sr?></td>

                    <!-- Date -->
                    <td class="text-center font-weight-bold"><?=date('d-M-Y', strtotime($e['date']))?></td>

                    <!-- Ref # (Clean text, NO edit or print buttons) -->
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

                    <!-- Product / Description (Clean, Readable Typography) -->
                    <td>
                      <?php if ($e['type'] == 'invoice'): ?>
                        <?php if (!empty($e['items'])): ?>
                          <?php foreach ($e['items'] as $it): ?>
                            <div class="mb-1">
                              <div class="item-title">
                                <?=htmlspecialchars($it['product_name'])?>
                                <?php if (!empty($it['product_code'])): ?>
                                  <small class="text-muted font-weight-normal">(<?=htmlspecialchars($it['product_code'])?>)</small>
                                <?php endif; ?>
                              </div>
                              <div class="item-meta">
                                Qty: <strong><?=number_format($it['quantity'])?></strong> &times; Rate: <strong><?=number_format($it['rate'])?> RS</strong> = <strong><?=number_format($it['total'])?> RS</strong>
                              </div>
                            </div>
                          <?php endforeach; ?>

                          <?php if (!empty($e['discount']) && floatval($e['discount']) > 0): 
                            $tax_calc = ($e['total_amount'] * ($e['discount'] / 100));
                          ?>
                            <div class="text-muted" style="font-size: 12px; margin-top: 4px;">
                              Subtotal: <?=number_format($e['total_amount'])?> RS &bull; Tax (<?=$e['discount']?>%): <?=number_format($tax_calc)?> RS
                            </div>
                          <?php endif; ?>

                          <?php if (!empty($e['freight']) && floatval($e['freight']) > 0): ?>
                            <div class="text-muted" style="font-size: 12px;">
                              Freight: <?=number_format($e['freight'])?> RS
                            </div>
                          <?php endif; ?>

                        <?php else: ?>
                          <div class="item-title">Commercial Invoice #<?=$e['id']?></div>
                        <?php endif; ?>

                        <?php if (!empty($e['narration'])): ?>
                          <div class="text-muted mt-1" style="font-size: 12px;"><em><?=htmlspecialchars($e['narration'])?></em></div>
                        <?php endif; ?>

                      <?php else: ?>
                        <!-- Voucher Details -->
                        <div class="item-title text-success font-weight-bold">
                          Payment Received
                        </div>

                        <?php if (!empty($e['check_no'])): ?>
                          <div class="text-muted mt-1" style="font-size: 12px;">
                            Cheque #: <strong><?=htmlspecialchars($e['check_no'])?></strong>
                            <?php if (!empty($e['bank_name'])): ?> &bull; Bank: <?=htmlspecialchars($e['bank_name'])?><?php endif; ?>
                            <?php if (!empty($e['check_date'])): ?> &bull; Date: <?=htmlspecialchars($e['check_date'])?><?php endif; ?>
                          </div>
                        <?php endif; ?>

                        <?php if (!empty($e['voucher_hint'])): ?>
                          <div class="text-muted mt-1" style="font-size: 12px;"><em><?=htmlspecialchars($e['voucher_hint'])?></em></div>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>

                    <!-- WHT Tax -->
                    <td class="text-center">
                      <?php if ($e['type'] == 'voucher' && $e['wht_amount'] > 0): ?>
                        <div class="text-danger font-weight-bold" style="font-size: 14px;">
                          <?=number_format($e['wht_amount'])?>
                        </div>
                        <small class="text-muted font-weight-bold"><?=$e['wht_percent']?>%</small>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- Debit (Rs) -->
                    <td class="text-right">
                      <?php if ($e['debit'] > 0): ?>
                        <span class="text-primary font-weight-bold" style="font-size: 15px;">
                          <?=number_format($e['debit'])?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- Credit (Rs) -->
                    <td class="text-right">
                      <?php if ($e['credit'] > 0): ?>
                        <span class="text-success font-weight-bold" style="font-size: 15px;">
                          <?=number_format($e['credit'])?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- Balance (Rs) -->
                    <td class="text-right font-weight-bold" style="font-size: 15px; color: <?=$running_bal > 0 ? '#dc3545' : '#28a745'?>;">
                      <?=number_format($running_bal)?>
                    </td>
                  </tr>
                  <?php
                    endforeach;
                  else:
                  ?>
                  <tr>
                    <td colspan="10" class="text-center py-4 text-muted">
                      <i class="fe fe-info fe-16 mr-1"></i> No Commercial Invoices or Vouchers found for this Customer Account in the selected period.
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="6"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 16px; vertical-align: middle;">Opening Balance</td>
                    <td colspan="2" class="h3 text-success font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($opening_balance)?></td>
                  </tr>
                  <tr>
                    <td colspan="6"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 16px; vertical-align: middle;">Total Debits</td>
                    <td colspan="2" class="h3 text-info font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($tot_debit)?></td>
                  </tr>
                  <tr>
                    <td colspan="6"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 16px; vertical-align: middle;">Total WHT</td>
                    <td colspan="2" class="h3 text-danger font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($tot_wht)?></td>
                  </tr>
                  <tr>
                    <td colspan="6"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 16px; vertical-align: middle;">Total Credits</td>
                    <td colspan="2" class="h3 text-warning font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($tot_credit)?></td>
                  </tr>
                  <tr>
                    <td colspan="6"></td>
                    <td colspan="2" align="right" class="font-weight-bold" style="font-size: 16px; vertical-align: middle;">Closing Balance</td>
                    <?php if ($running_bal <= 0): ?>
                      <td colspan="2" class="h3 text-danger font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($running_bal)?></td>
                    <?php else: ?>
                      <td colspan="2" class="h3 text-success font-weight-bold mb-0" style="vertical-align: middle;"><?=number_format($running_bal)?></td>
                    <?php endif; ?>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Printable Signatures Section -->
            <div class="row mt-5 pt-4 d-none d-print-flex justify-content-between text-center" style="font-size: 12px;">
              <div class="col-3 border-top pt-2">
                <strong>Prepared By</strong>
              </div>
              <div class="col-3 border-top pt-2">
                <strong>Checked By</strong>
              </div>
              <div class="col-3 border-top pt-2">
                <strong>Accountant</strong>
              </div>
              <div class="col-3 border-top pt-2">
                <strong>Customer Signature</strong>
              </div>
            </div>

            <?php else: ?>
              <div class="alert alert-info text-center font-weight-bold py-4 my-3">
                <i class="fe fe-info fe-24 d-block mb-2"></i>
                Please select a Customer Account above and click <strong>Search Ledger</strong> to view the Commercial Invoice Statement.
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
