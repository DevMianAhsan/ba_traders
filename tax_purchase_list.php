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
                <b class="text-center card-text">Purchase Tax Invoice List</b>
                <a href="monthly_tax_return.php" class="btn btn-admin float-right btn-sm ml-2"><i class="fe fe-book-open"></i> Monthly Tax Return</a>
                <a href="tax_purchase.php" class="btn btn-admin2 float-right btn-sm"><i class="fe fe-plus"></i> New Purchase Invoice</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">
                <table class="table  dataTableAsc" id="view_orders_tb">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Invoice #</th>
                      <th>Date</th>
                      <th>Supplier</th>
                      <th>Total Qty</th>
                      <th>Value Excl Taxes</th>
                      <th>Sales Tax</th>
                      <th>Grand Total</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody> 
                      <?php   
                      $q = mysqli_query($dbc, "SELECT tp.*, c.customer_name AS supplier_name, c.customer_strn AS supplier_strn, c.customer_phone AS supplier_phone FROM tax_purchases tp LEFT JOIN customers c ON c.customer_id = tp.supplier_id ORDER BY tp.tax_purchase_id DESC LIMIT 500");
                      $c = 0;
                      while ($row = mysqli_fetch_assoc($q)) { 
                        $c++;
                      ?>
                       <tr>
                          <td><?=$c?></td>
                          <td><?=$row['invoice_no'] ?: $row['tax_purchase_id']?></td>
                          <td><?=$row['invoice_date']?></td>
                          <td>
                            <?=htmlspecialchars($row['supplier_name'] ?? '')?>
                            <?php if (!empty($row['supplier_phone'])): ?>
                              <br/><small class="text-muted"><?=htmlspecialchars($row['supplier_phone'])?></small>
                            <?php elseif (!empty($row['supplier_strn'])): ?>
                              <br/><small class="text-muted">STRN: <?=htmlspecialchars($row['supplier_strn'])?></small>
                            <?php endif; ?>
                          </td>
                          <td><?=number_format(floatval($row['total_qty']), 2)?></td>
                          <td><?=number_format(floatval($row['total_value_excl_tax']), 2)?></td>
                          <td><span class="text-danger font-weight-bold"><?=number_format(floatval($row['total_sales_tax']), 2)?></span></td>
                          <td><span class="text-success font-weight-bold"><?=number_format(floatval($row['invoice_amount']), 2)?></span></td>
                          <td>
                            <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                              <a href="tax_purchase.php?edit_id=<?=$row['tax_purchase_id']?>" class="btn btn-admin btn-sm m-1">Edit</a>
                            <?php endif; ?>

                            <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>
                              <a href="#" onclick="deleteAlert('<?=$row['tax_purchase_id']?>','tax_purchases','tax_purchase_id','view_orders_tb')" class="btn btn-danger btn-sm m-1">Delete</a>
                            <?php endif; ?>

                            <a target="_blank" href="print_tax_purchase.php?id=<?=$row['tax_purchase_id']?>" class="btn btn-admin2 btn-sm m-1">Print</a>
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
function deleteTaxPurchase(id, invNo) {
  deleteAlert(id, 'tax_purchases', 'tax_purchase_id', 'view_orders_tb');
}
</script>
