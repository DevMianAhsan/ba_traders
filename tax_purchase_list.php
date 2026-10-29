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
                  <i class="fe fe-list mr-2"></i>Sales Tax Invoices (Purchase List)
                </h4>
                <small class="text-light">Manage and print supplier tax purchase invoices for BA TRADERS</small>
              </div>
              <div class="col-sm-6 text-right">
                <a href="tax_purchase.php" class="btn btn-light btn-sm shadow-sm font-weight-bold">
                  <i class="fe fe-plus mr-1"></i>Create New Purchase Invoice
                </a>
              </div>
            </div>
          </div>
          
          <div class="card-body">
            <div class="table-responsive">
              <table id="taxPurchasesTable" class="table table-bordered table-hover dataTable w-100">
                <thead class="thead-dark text-center font-weight-bold">
                  <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>S.Tax Reg No.</th>
                    <th>GD Number</th>
                    <th>Total Qty</th>
                    <th>Value Excl Taxes</th>
                    <th>Sales Tax</th>
                    <th>I.Tax 236G</th>
                    <th>Inv. Amt</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $q = mysqli_query($dbc, "SELECT tp.*, c.customer_name AS supplier_name, c.customer_strn AS supplier_strn FROM tax_purchases tp LEFT JOIN customers c ON c.customer_id = tp.supplier_id ORDER BY tp.tax_purchase_id DESC");
                  while ($row = mysqli_fetch_assoc($q)):
                  ?>
                  <tr>
                    <td class="text-center font-weight-bold text-danger"><?=$row['invoice_no']?></td>
                    <td class="text-center"><?=date('d-M-Y', strtotime($row['invoice_date']))?></td>
                    <td class="font-weight-bold text-dark"><?=$row['supplier_name']?></td>
                    <td class="text-center"><small class="badge badge-light border"><?=$row['supplier_strn'] ?: 'N/A'?></small></td>
                    <td class="text-center font-weight-bold"><?=$row['gd_number'] ?: '-'?></td>
                    <td class="text-right"><?=number_format($row['total_qty'], 3)?></td>
                    <td class="text-right"><?=number_format($row['total_value_excl_tax'], 2)?></td>
                    <td class="text-right text-danger font-weight-bold"><?=number_format($row['total_sales_tax'], 2)?></td>
                    <td class="text-right"><?=number_format($row['itax_236g_amount'], 2)?></td>
                    <td class="text-right text-success font-weight-bold h6 mb-0"><?=number_format($row['invoice_amount'], 2)?></td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <a href="print_tax_purchase.php?id=<?=$row['tax_purchase_id']?>" target="_blank" class="btn btn-outline-primary" title="Print Invoice">
                          <i class="fe fe-printer"></i>
                        </a>
                        <a href="tax_purchase.php?edit_id=<?=$row['tax_purchase_id']?>" class="btn btn-outline-info" title="Edit Invoice">
                          <i class="fe fe-edit"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger" title="Delete Invoice" onclick="deleteTaxPurchase(<?=$row['tax_purchase_id']?>, '<?=$row['invoice_no']?>')">
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
      if ($.fn.DataTable.isDataTable('#taxPurchasesTable')) {
        $('#taxPurchasesTable').DataTable().destroy();
      }
      $('#taxPurchasesTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
      });
    });

    function deleteTaxPurchase(id, invNo) {
      Swal.fire({
        title: 'Delete Purchase Invoice?',
        text: 'Are you sure you want to delete Tax Purchase Invoice #' + invNo + '?',
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
              table: 'tax_purchases',
              row: 'tax_purchase_id'
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
