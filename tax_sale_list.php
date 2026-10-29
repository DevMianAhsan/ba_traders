<!DOCTYPE html>
<html lang="en">
<?php 
include_once 'includes/head.php'; 
?>
<body class="horizontal light">
  <div class="wrapper">
    <?php include_once 'includes/header.php'; ?>
    
    <main role="main" class="main-content">
      <div class="container-fluid">
        <div class="card shadow">
          <div class="card-header card-bg text-white">
            <div class="row align-items-center">
              <div class="col-sm-6">
                <h4 class="mb-0 text-white font-weight-bold">
                  <i class="fe fe-list mr-2"></i>Sales Tax Invoices (Sale List)
                </h4>
                <small class="text-light">Manage and print system generated sales tax invoices</small>
              </div>
              <div class="col-sm-6 text-right">
                <a href="tax_sale.php" class="btn btn-light btn-sm shadow-sm font-weight-bold">
                  <i class="fe fe-plus mr-1"></i>Create New Sale Invoice
                </a>
              </div>
            </div>
          </div>
          
          <div class="card-body">
            <div class="table-responsive">
              <table id="taxSalesTable" class="table table-bordered table-hover dataTable w-100">
                <thead class="thead-dark text-center font-weight-bold">
                  <tr>
                    <th>SR. #</th>
                    <th>Date</th>
                    <th>Messers (Buyer)</th>
                    <th>ST. Reg. No.</th>
                    <th>P.O #</th>
                    <th>FBR Inv #</th>
                    <th>Total Qty</th>
                    <th>Value Excl. Tax</th>
                    <th>Sales Tax</th>
                    <th>Grand Total</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $q = mysqli_query($dbc, "SELECT ts.*, c.customer_name, c.customer_strn FROM tax_sales ts LEFT JOIN customers c ON c.customer_id = ts.customer_id ORDER BY ts.tax_sale_id DESC");
                  while ($row = mysqli_fetch_assoc($q)):
                  ?>
                  <tr>
                    <td class="text-center font-weight-bold text-danger"><?=$row['invoice_no']?></td>
                    <td class="text-center"><?=date('d-M-Y', strtotime($row['invoice_date']))?></td>
                    <td class="font-weight-bold text-dark"><?=$row['customer_name']?></td>
                    <td class="text-center"><small class="badge badge-light border"><?=$row['customer_strn'] ?: 'N/A'?></small></td>
                    <td class="text-center font-weight-bold"><?=$row['po_no'] ?: '-'?></td>
                    <td class="text-center small"><?=$row['fbr_invoice_no'] ? substr($row['fbr_invoice_no'], 0, 16) . '...' : '-'?></td>
                    <td class="text-right"><?=number_format($row['total_qty'], 3)?></td>
                    <td class="text-right"><?=number_format($row['total_value_excl_tax'], 2)?></td>
                    <td class="text-right text-danger font-weight-bold"><?=number_format($row['total_tax_amount'], 2)?></td>
                    <td class="text-right text-success font-weight-bold h6 mb-0"><?=number_format($row['grand_total'], 2)?></td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <a href="print_tax_sale.php?id=<?=$row['tax_sale_id']?>" target="_blank" class="btn btn-outline-primary" title="Print Invoice">
                          <i class="fe fe-printer"></i>
                        </a>
                        <a href="tax_sale.php?edit_id=<?=$row['tax_sale_id']?>" class="btn btn-outline-info" title="Edit Invoice">
                          <i class="fe fe-edit"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger" title="Delete Invoice" onclick="deleteTaxSale(<?=$row['tax_sale_id']?>, '<?=$row['invoice_no']?>')">
                          <i class="fe fe-trash-2"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <?php include_once 'includes/foot.php'; ?>

  <script>
    $(document).ready(function() {
      if ($.fn.DataTable.isDataTable('#taxSalesTable')) {
        $('#taxSalesTable').DataTable().destroy();
      }
      $('#taxSalesTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
      });
    });

    function deleteTaxSale(id, invNo) {
      Swal.fire({
        title: 'Delete Invoice?',
        text: 'Are you sure you want to delete Tax Sale Invoice #' + invNo + '?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: 'php_action/ajax_deleteData.php',
            type: 'POST',
            data: {
              delete_bymanually: id,
              table: 'tax_sales',
              row: 'tax_sale_id'
            },
            dataType: 'json',
            success: function(resp) {
              if (resp.sts === 'success') {
                Swal.fire('Deleted!', resp.msg, 'success').then(() => {
                  location.reload();
                });
              } else {
                Swal.fire('Error', resp.msg || 'Could not delete invoice', 'error');
              }
            },
            error: function(xhr, status, err) {
              Swal.fire('Error', 'Server error: ' + err, 'error');
            }
          });
        }
      });
    }
  </script>
</body>
</html>
