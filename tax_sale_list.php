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
                <b class="text-center card-text">Sales Tax Invoice List</b>
                <a href="monthly_tax_return.php" class="btn btn-admin float-right btn-sm ml-2"><i class="fe fe-book-open"></i> Monthly Tax Return</a>
                <a href="tax_sale_report.php" class="btn btn-admin float-right btn-sm ml-2"><i class="fe fe-file-text"></i> Payment & Due Report</a>
                <a href="po_tax_report.php" class="btn btn-warning float-right btn-sm ml-2 text-dark font-weight-bold" title="Check Pending & Invoiced Sales Tax by PO"><i class="fe fe-alert-triangle"></i> Pending PO Tax Report</a>
                <a href="tax_sale.php" class="btn btn-admin2 float-right btn-sm"><i class="fe fe-plus"></i> New Tax Invoice</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">
                <table class="table  dataTable" id="view_orders_tb">
                  <thead>
                    <tr>
                      <!-- <th>#</th> -->
                      <th>ID #</th>
                      <th>Date</th>
                      <th>PO #</th>
                      <th>Customer Details</th>
                      <th>Grand Total</th>
                      <th>Paid Amount</th>
                      <th>WHT Tax</th>
                      <th>Due Balance</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody> 
                      <?php   
                      $q = mysqli_query($dbc, "SELECT ts.*, c.customer_name, c.customer_phone, c.customer_strn FROM tax_sales ts LEFT JOIN customers c ON c.customer_id = ts.customer_id ORDER BY ts.tax_sale_id DESC");
                      $c = 0;
                      while ($r = mysqli_fetch_assoc($q)) { 
                        $c++;
                        $party_po = trim($r['po_no'] ?? '');
                        $inv_no = trim($r['invoice_no'] ?? '');
                        $ts_id = $r['tax_sale_id'];
                        $cust_id = $r['customer_id'];

                        $inv_no_escaped = mysqli_real_escape_string($dbc, $inv_no);
                        $ts_id_escaped = mysqli_real_escape_string($dbc, $ts_id);

                        $fbr_no = trim($r['fbr_invoice_no'] ?? '');
                        $fbr_no_escaped = mysqli_real_escape_string($dbc, $fbr_no);

                        $inv_clause = "(vi.invoice_id = '$ts_id_escaped'";
                        if (!empty($inv_no)) {
                          $inv_clause .= " OR vi.invoice_id = '$inv_no_escaped'";
                        }
                        if (!empty($fbr_no)) {
                          $inv_clause .= " OR vi.invoice_id = '$fbr_no_escaped'";
                        }
                        $inv_clause .= ")";

                        if (!empty($party_po)) {
                          $po_escaped = mysqli_real_escape_string($dbc, $party_po);
                          $q_vi_paid = mysqli_query($dbc, "
                            SELECT 
                              SUM(CAST(vi.invoice_amount AS DECIMAL(15,2))) AS total_paid,
                              SUM(CAST(COALESCE(vi.tax_amount, 0) AS DECIMAL(15,2))) AS total_wht
                            FROM voucher_invoices vi
                            LEFT JOIN vouchers v ON vi.voucher_id = v.voucher_id
                            WHERE $inv_clause
                               OR ((vi.invoice_id IS NULL OR vi.invoice_id = '' OR vi.invoice_id = '0') 
                                   AND vi.party_po = '$po_escaped' 
                                   AND (v.customer_id1 = '$cust_id' OR v.customer_id1 IS NULL OR v.customer_id1 = '' OR v.customer_id1 = '0'))
                          ");
                        } else {
                          $q_vi_paid = mysqli_query($dbc, "
                            SELECT 
                              SUM(CAST(vi.invoice_amount AS DECIMAL(15,2))) AS total_paid,
                              SUM(CAST(COALESCE(vi.tax_amount, 0) AS DECIMAL(15,2))) AS total_wht
                            FROM voucher_invoices vi
                            WHERE $inv_clause
                          ");
                        }
                        $r_vi_paid = mysqli_fetch_assoc($q_vi_paid);
                        $paid_amount = !empty($r_vi_paid['total_paid']) ? floatval($r_vi_paid['total_paid']) : 0;
                        $wht_amount  = !empty($r_vi_paid['total_wht']) ? floatval($r_vi_paid['total_wht']) : 0;
                        $grand_total = floatval($r['grand_total']);
                        $due_diff = $grand_total - ($paid_amount + $wht_amount);
                      ?>
                       <tr>
                          <!-- <td><?=$c?></td> -->
                          <td>
                            <strong><?=$r['invoice_no'] ?: $r['tax_sale_id']?></strong>
                            <?php if (!empty($r['fbr_invoice_no'])): ?>
                              <br><small class="text-primary font-weight-bold" title="FBR Invoice Number">FBR: <?=htmlspecialchars($r['fbr_invoice_no'])?></small>
                            <?php endif; ?>
                          </td>
                          <td><?=$r['invoice_date']?></td>
                          <td><strong><?=htmlspecialchars($r['po_no'] ?? '')?></strong></td>
                          <td>
                            <?=htmlspecialchars($r['customer_name'] ?? '')?><br/>
                            <?=htmlspecialchars($r['customer_phone'] ?: ($r['customer_strn'] ? 'STRN: ' . $r['customer_strn'] : ''))?>
                          </td>
                          <td><?=number_format($grand_total)?></td>
                          <td><span class="text-success font-weight-bold"><?=number_format($paid_amount)?></span></td>
                          <td>
                            <?php if ($wht_amount > 0): ?>
                              <span class="font-weight-bold" style="color: #6f42c1;"><?=number_format($wht_amount)?></span>
                            <?php else: ?>
                              <span class="text-muted">0</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($due_diff > 0.01): ?>
                              <span class="text-danger font-weight-bold"><?=number_format($due_diff)?></span>
                            <?php elseif ($due_diff < -0.01): ?>
                              <span class="badge badge-info font-weight-bold" title="Paid Rs <?=number_format($paid_amount)?> + WHT Rs <?=number_format($wht_amount)?> exceeds Invoice Total Rs <?=number_format($grand_total)?>">Overpaid (+<?=number_format(abs($due_diff))?>)</span>
                            <?php else: ?>
                              <span class="badge badge-success font-weight-bold">Paid</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                              <a href="tax_sale.php?edit_id=<?=$r['tax_sale_id']?>" class="btn btn-admin btn-sm m-1">Edit</a>
                            <?php endif; ?>

                            <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>
                              <a href="#" onclick="deleteAlert('<?=$r['tax_sale_id']?>','tax_sales','tax_sale_id','view_orders_tb')" class="btn btn-danger btn-sm m-1">Delete</a>
                            <?php endif; ?>

                            <a target="_blank" href="print_tax_sale.php?id=<?=$r['tax_sale_id']?>" class="btn btn-admin2 btn-sm m-1">Print</a>
                          </td>
                       </tr>
                     <?php  } ?>
                  </tbody>  
                </table>
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>

<?php include_once 'includes/foot.php'; ?>
<script type="text/javascript" src="js/panelV2.js"></script>
<script>
function deleteTaxSale(id, invNo) {
  deleteAlert(id, 'tax_sales', 'tax_sale_id', 'view_orders_tb');
}
</script>
