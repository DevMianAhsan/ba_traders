<!DOCTYPE html>
<html lang="en">
<?php  
include_once 'includes/head.php';

$userid = isset($_REQUEST['customer']) ? trim($_REQUEST['customer']) : '';
$customers = fetchRecord($dbc, "customers", "customer_id", $userid);

if (!$customers) {
    die("<div class='container p-4'><div class='alert alert-danger'>Customer / Supplier record not found.</div></div>");
}

$is_supplier = (($customers['customer_type'] ?? '') == 'supplier');
$bal_res = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT SUM(CAST(credit AS DECIMAL(15,2))) as tot_cr, SUM(CAST(debit AS DECIMAL(15,2))) as tot_dr FROM transactions WHERE customer_id='$userid'"));

if ($is_supplier) {
    $customer_blance_remain = floatval($bal_res['tot_dr'] ?? 0) - floatval($bal_res['tot_cr'] ?? 0);
} else {
    $customer_blance_remain = floatval($bal_res['tot_cr'] ?? 0) - floatval($bal_res['tot_dr'] ?? 0);
}

// Fetch Recent Transactions for this Account
$q_recent = mysqli_query($dbc, "SELECT * FROM transactions WHERE customer_id = '$userid' ORDER BY transaction_date DESC, transaction_id DESC LIMIT 15");
$recent_txs = [];
while ($rtx = mysqli_fetch_assoc($q_recent)) {
    $recent_txs[] = $rtx;
}
// Sort ascending for chronological reading
$recent_txs = array_reverse($recent_txs);

// Report header variables
$report_title = $is_supplier ? 'SUPPLIER PENDING PAYABLE STATEMENT' : 'CUSTOMER PENDING BALANCE STATEMENT';
$report_customer = $customers;
$report_duration = "As of " . date('d-M-Y');

// Helper for number to words
function amountToWords($num) {
    $num = abs(floatval($num));
    $ones = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    ];
    $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    ];
    if ($num == 0) return 'Zero Rupees Only';

    $n = intval($num);
    $out = '';
    if ($n >= 10000000) {
        $crore = intval($n / 10000000);
        $out .= amountToWords($crore) . ' Crore ';
        $n %= 10000000;
    }
    if ($n >= 100000) {
        $lakh = intval($n / 100000);
        $out .= amountToWords($lakh) . ' Lakh ';
        $n %= 100000;
    }
    if ($n >= 1000) {
        $th = intval($n / 1000);
        $out .= amountToWords($th) . ' Thousand ';
        $n %= 1000;
    }
    if ($n >= 100) {
        $h = intval($n / 100);
        $out .= $ones[$h] . ' Hundred ';
        $n %= 100;
    }
    if ($n > 0) {
        if ($n < 20) {
            $out .= $ones[$n] . ' ';
        } else {
            $t = intval($n / 10);
            $o = $n % 10;
            $out .= $tens[$t] . ' ' . $ones[$o] . ' ';
        }
    }
    return trim($out) . ' Rupees Only';
}
?>
<body class="bg-white">
  <div class="container py-3">

    <!-- Action Toolbar (Hidden on Print) -->
    <div class="d-flex justify-content-between align-items-center d-print-none mb-3">
      <a href="dues_report.php" class="btn btn-outline-secondary">
        <i class="fe fe-arrow-left mr-1"></i> Back to Dues Report
      </a>
      <div>
        <button onclick="window.print();" class="btn btn-primary font-weight-bold shadow-sm mr-1">
          <i class="fe fe-printer mr-1"></i> Print Statement Slip
        </button>
        <button onclick="window.close();" class="btn btn-secondary">
          <i class="fe fe-x mr-1"></i> Close
        </button>
      </div>
    </div>

    <!-- Unified Report Header -->
    <?php include 'includes/report_header.php'; ?>

    <!-- Main Balance Highlight Box -->
    <div class="balance-card mb-4 p-3" style="border: 2px solid #000; border-radius: 6px; background-color: #fdfdfd;">
      <div class="row align-items-center">
        <div class="col-sm-7">
          <div class="small text-muted font-weight-bold text-uppercase">
            <?=$is_supplier ? 'Total Outstanding Payable Amount' : 'Total Outstanding Pending Due Amount'?>
          </div>
          <div class="h2 font-weight-bolder mb-1" style="color: <?=$customer_blance_remain > 0 ? '#b30000' : '#1b6330'?>;">
            Rs. <?=number_format($customer_blance_remain)?>
          </div>
          <div class="small font-italic text-dark">
            <strong>In Words:</strong> <?=amountToWords($customer_blance_remain)?>
          </div>
        </div>
        <div class="col-sm-5 text-sm-right mt-2 mt-sm-0">
          <?php if ($customer_blance_remain > 0): ?>
            <span class="badge badge-due p-2" style="font-size: 13px;">
              <i class="fe fe-alert-circle mr-1"></i> <?= $is_supplier ? 'PENDING TO PAY' : 'PAYMENT PENDING RECOVERY' ?>
            </span>
          <?php elseif ($customer_blance_remain < 0): ?>
            <span class="badge badge-advance p-2" style="font-size: 13px;">
              <i class="fe fe-info mr-1"></i> ADVANCE BALANCE
            </span>
          <?php else: ?>
            <span class="badge badge-cleared p-2" style="font-size: 13px;">
              <i class="fe fe-check-circle mr-1"></i> CLEARED / NIL BALANCE
            </span>
          <?php endif; ?>
          <div class="small text-muted mt-2">
            Statement Generated: <strong><?=date('d-M-Y h:i A')?></strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions Table -->
    <?php if (!empty($recent_txs)): ?>
      <div class="mb-3">
        <div class="font-weight-bold text-uppercase small text-dark mb-2">
          <strong>Recent Statement Transactions:</strong>
        </div>
        <table class="table table-bordered table-sm text-center mb-0" style="width: 100%;">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th style="width: 110px;">Date</th>
              <th style="width: 110px;">Ref #</th>
              <th style="text-align: left;">Particulars / Remarks</th>
              <th style="width: 120px;" class="text-right">Debit (Rs)</th>
              <th style="width: 120px;" class="text-right">Credit (Rs)</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $i = 0;
            foreach ($recent_txs as $tx):
              $i++;
            ?>
              <tr>
                <td><?=$i?></td>
                <td><?=date('d-M-Y', strtotime($tx['transaction_date']))?></td>
                <td class="font-weight-bold">#<?=$tx['transaction_id']?></td>
                <td style="text-align: left;">
                  <?=htmlspecialchars(str_replace('=', ' ', $tx['transaction_remarks'] ?: 'Account Entry'))?>
                  <?php if (!empty($tx['transaction_from'])): ?>
                    <span class="badge badge-light border ml-1"><?=htmlspecialchars($tx['transaction_from'])?></span>
                  <?php endif; ?>
                </td>
                <td class="text-right font-weight-bold text-primary">
                  <?=floatval($tx['debit']) > 0 ? number_format($tx['debit']) : '-'?>
                </td>
                <td class="text-right font-weight-bold text-success">
                  <?=floatval($tx['credit']) > 0 ? number_format($tx['credit']) : '-'?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- Note & Computerized Disclaimer -->
    <div class="p-2 mb-4" style="background: #f8f9fa; border: 1px dashed #999; font-size: 11.5px;">
      <strong>Note:</strong> This is a computerized pending balance statement issued on behalf of <strong><?=htmlspecialchars($comp_name)?></strong>. Please contact our accounts department if you find any discrepancies.
    </div>

    <!-- Signatures Block -->
    <div class="print-signature-row">
      <div class="print-sig-col">
        <div class="print-sig-line"></div>
        <div class="print-sig-title">Prepared By</div>
        <div class="print-sig-sub">Accounts Officer</div>
      </div>
      <div class="print-sig-col">
        <div class="print-sig-line"></div>
        <div class="print-sig-title">Verified By</div>
        <div class="print-sig-sub">Chief Accountant</div>
      </div>
      <div class="print-sig-col">
        <div class="print-sig-line"></div>
        <div class="print-sig-title">Customer / Receiver</div>
        <div class="print-sig-sub">Signature &amp; Stamp</div>
      </div>
    </div>

  </div>
</body>
</html>
