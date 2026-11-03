 <!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 

if (!empty($_REQUEST['edit_order_id'])) {
    $fetchOrder = fetchRecord($dbc, "invoices", "invoice_id", base64_decode($_REQUEST['edit_order_id']));
    if (!empty($fetchOrder['transaction_id'])) {
        $fetchTransaction = fetchRecord($dbc, "transactions", "transaction_id", $fetchOrder['transaction_id']);
    }
    $fetchCustomer = !empty($fetchOrder['customer_id']) ? fetchRecord($dbc, "customers", "customer_id", $fetchOrder['customer_id']) : null;
    $customer_address_val = !empty($fetchOrder['customer_address']) ? $fetchOrder['customer_address'] : (!empty($fetchCustomer['customer_fbr_address']) ? $fetchCustomer['customer_fbr_address'] : ($fetchCustomer['customer_address'] ?? ''));
    $inv_po_date_val = '';
    if (!empty($fetchOrder['party_po'])) {
        $po_rec = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT po_date FROM purchase_order WHERE po_partypo = '".mysqli_real_escape_string($dbc, $fetchOrder['party_po'])."' LIMIT 1"));
        $inv_po_date_val = $po_rec['po_date'] ?? '';
    }
}
 
?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
  
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text pb-3"><?=!empty($_REQUEST['edit_order_id']) ? "Edit Commercial Invoice" : "Commercial Invoice"?></b>
           
             
                 <a href="invoice.php" class="btn btn-admin float-right btn-sm ml-2">Add New</a>
                 <a href="invoice_list.php" class="btn btn-admin2 float-right btn-sm"><i class="fe fe-list"></i> Invoice List</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">
            <form action="php_action/custom_action.php" method="POST" id="sale_order_fm">
              <input type="hidden" name="product_order_id" value="<?=!isset($_REQUEST['edit_order_id'])?"":base64_decode($_REQUEST['edit_order_id'])?>">
            <div class="row form-group">
              <input type="hidden" name="page_type" id="page_type" value="invoice">
              <div class="col-md-2">
                <label>Invoice ID#</label>
                <?php $result = mysqli_query($dbc,"SHOW TABLE STATUS LIKE 'invoices'");
                $data = mysqli_fetch_assoc($result);
                $next_increment = $data['Auto_increment']; ?>
                <input type="text" name="next_increment" id="next_increment" value="<?=@empty($_REQUEST['edit_order_id'])?$next_increment:$fetchOrder['invoice_id']?>" readonly class="form-control">
              </div>
              <div class="col-md-2">
                <label>Invoice Date</label>
                <input type="date" name="order_date" id="order_date" value="<?=@empty($_REQUEST['edit_order_id'])?date('Y-m-d'):$fetchOrder['invoice_date']?>" <?=(!empty($inv_po_date_val)?'min="'.htmlspecialchars($inv_po_date_val).'"':'')?>  class="form-control">
              </div>
              <input type="hidden" name="credit_sale_type" value="<?=@$fetchOrder['credit_sale_type'] ?: '30days'?>" id="credit_sale_type">
              <div class="col-sm-4">
                <label>Customer Account</label>
                <div class="input-group">
                  <select class="form-control searchableSelect" onchange="getBalance(this.value,'customer_account_exp')" name="invoice_client_name" id="credit_order_client_name" required aria-label="Username" aria-describedby="basic-addon1">
                    <option value="">Customer Account</option>
                    <?php
                      $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type='customer' ORDER BY customer_name ASC");
                      while($r=mysqli_fetch_assoc($q)){
                        $cust_addr_opt = !empty($r['customer_fbr_address']) ? $r['customer_fbr_address'] : $r['customer_address'];
                    ?>
                      <option <?=@($fetchOrder['customer_id']==$r['customer_id'] || @$fetchOrder['customer_account']==$r['customer_id'])?"selected":""?> data-id="<?=$r['customer_id']?>" data-contact="<?=$r['customer_phone']?>" data-address="<?=htmlspecialchars($cust_addr_opt)?>" value="<?=$r['customer_name']?>"><?=getCustomerDisplayName($r['customer_name'], $r['customer_phone'])?></option>
                    <?php } ?>
                  </select>
                  <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">Balance : <span id="customer_account_exp">0</span></span>
                  </div>
                </div>
                <input type="hidden" name="customer_account" id="customer_account" value="<?=@$fetchOrder['customer_id'] ?? @$fetchOrder['customer_account']?>">
                <input type="hidden" name="client_contact" id="client_contact" value="<?=@$fetchOrder['client_contact']?>">
                <input type="hidden" name="R_Limit" id="R_LimitInput" />
              </div>
              <div class="col-sm-4">
                <label>Party PO</label>
                <div class="input-group">
                  <select id="party_po" name="party_po" class="form-control searchableSelect">
                    <?php if (!empty($fetchOrder['customer_id'])): ?>
                      <option value="">Select Party PO</option>
                      <?php
                        $cid = intval($fetchOrder['customer_id']);
                        $pos_list = [];
                        $qp = mysqli_query($dbc, "SELECT DISTINCT po_partypo, po_paymentdays, po_date FROM purchase_order WHERE po_customer = '$cid' ORDER BY po_id DESC");
                        while ($rp = mysqli_fetch_assoc($qp)) {
                          $p = trim($rp['po_partypo']);
                          if ($p !== '') {
                            $pos_list[$p] = ['days' => $rp['po_paymentdays'] ?? '30', 'date' => trim($rp['po_date'] ?? '')];
                          }
                        }
                        $qo = mysqli_query($dbc, "SELECT DISTINCT party_po, credit_sale_type FROM orders WHERE customer_account = '$cid' AND party_po IS NOT NULL AND party_po != ''");
                        while ($ro = mysqli_fetch_assoc($qo)) {
                          $p = trim($ro['party_po']);
                          if ($p !== '' && !isset($pos_list[$p])) {
                            $pos_list[$p] = ['days' => !empty($ro['credit_sale_type']) ? intval($ro['credit_sale_type']) : '30', 'date' => ''];
                          }
                        }
                        if (!empty($fetchOrder['party_po']) && !isset($pos_list[$fetchOrder['party_po']])) {
                          $pos_list[$fetchOrder['party_po']] = ['days' => '30', 'date' => ''];
                        }
                        foreach ($pos_list as $po_val => $po_info):
                          $sel = (!empty($fetchOrder['party_po']) && $fetchOrder['party_po'] == $po_val) ? 'selected' : '';
                      ?>
                        <option data-paymentdays="<?=$po_info['days']?>" data-date="<?=htmlspecialchars($po_info['date'])?>" value="<?=$po_val?>" <?=$sel?>><?=$po_val?></option>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <option value="">Select Customer first</option>
                    <?php endif; ?>
                  </select>
                  <div class="input-group-prepend">
                    <button type="button" class="btn-sm btn-admin float-right" onclick="getpoDetails(true)">
                      <span class="fa fa-eye"></span>
                    </button>
                    <button type="button" class="btn-sm btn-admin float-right" onclick="addpoDetails()">
                      <span class="fa fa-plus"></span>
                    </button>
                  </div>
                </div>
              </div>
            </div> <!-- end row 1 -->
            <div class="row form-group">
              <div class="col-sm-6">
                <label>Address</label>
                <input type="text" name="customer_address" id="customer_address" value="<?=htmlspecialchars($customer_address_val ?? '')?>" class="form-control" placeholder="Address">
              </div>
              <div class="col-sm-6">
                <label>Remarks</label>
                <input type="text" autocomplete="off" name="order_narration" id="order_narration" value="<?=@$fetchOrder['order_narration'] ?: @$fetchTransaction['transaction_remarks']?>" class="form-control" placeholder="Remarks">
              </div>
            </div> <!-- end of form-group -->
             
              <div class="row">
                <div class="col-12">
                  
                  <table class="table  saleTable" id="myDiv"> 
                    <thead class="table-bordered" style="background: <?=$base_color?>">
                      <tr>
                        <th>Party PO</th>
                        <th>Delivery Challan</th>
                        <th>Amount</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody class="table table-bordered" id="invoice_list">
                    <?php if (isset($_REQUEST['edit_order_id']) && !empty($fetchOrder['invoice_details'])): 
                      $invDetails = json_decode($fetchOrder['invoice_details'], true);
                      if (!empty($invDetails['data'])):
                        foreach ($invDetails['data'] as $k => $item):
                          $rowId = $k + 1;
                      ?>
                      <?php
                          $item_po_date = '';
                          if (!empty($item['party_po'])) {
                            $po_item_q = mysqli_query($dbc, "SELECT po_date FROM purchase_order WHERE po_partypo = '".mysqli_real_escape_string($dbc, $item['party_po'])."' LIMIT 1");
                            if ($po_item_r = mysqli_fetch_assoc($po_item_q)) {
                              $item_po_date = trim($po_item_r['po_date']);
                            }
                          }
                      ?>
                      <tr id="product_idN_<?=$rowId?>" data-po-date="<?=htmlspecialchars($item_po_date)?>">
                        <input type="hidden" class="row_po_date" value="<?=htmlspecialchars($item_po_date)?>">
                        <input type="hidden" id="product_rate_<?=$rowId?>" name="party_po[]" value="<?=$item['party_po']?>">
                        <input type="hidden" id="product_rate_<?=$rowId?>" name="dc_no[]" value="<?=$item['dc_no']?>">
                        <input type="hidden" id="product_totalrate_<?=$rowId?>" name="po_amount[]" value="<?=$item['po_amount']?>">
                        <td><?=$item['party_po']?></td>
                        <td><a target="_blank" href="print_sale.php?type=order&id=<?=$item['dc_no']?>"><?=$item['dc_no']?></a></td>
                        <td><?=$item['po_amount']?></td>
                        <td>
                          <button type="button" onclick="removeByid('#product_idN_<?=$rowId?>')" class="fa fa-trash text-danger"></button>
                        </td>
                      </tr>
                      <?php 
                        endforeach;
                      endif;
                    endif; ?>
                  </tbody>
                 
           <tfoot>
              <tr>
                <td class="table-bordered"> Sub Total :</td>
                <td class="table-bordered">
                  <span id="product_total_amount"><?=@$fetchOrder['total_amount']?></span>
                  <input type="hidden" name="total_amount" id="total_amount" value="<?=@$fetchOrder['total_amount']?>">
                </td>
                <td class="table-bordered"> <strong>Tax :</strong></td>
                <td class="table-bordered" id="getDiscount">
                  <div class="form-group row mb-0 align-items-center">
                    <div class="col-sm-4">
                      <input onkeyup="getinvoiceTotal()" type="number" id="ordered_discount" class="form-control form-control-sm" value="<?=@isset($fetchOrder['discount']) ? $fetchOrder['discount'] : '0'?>" min="0" max="100" name="ordered_discount" >
                    </div>
                    <div class="col-sm-4">
                      <div class="custom-control custom-switch" >
                        <input type="checkbox" class="custom-control-input" id="with_tax" <?=!empty($fetchOrder['discount']) && floatval($fetchOrder['discount']) > 0 ? "checked" : ""?>>
                        <label class="custom-control-label" for="with_tax">With Tax</label>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <input onkeyup="getinvoiceTotal()" type="number" id="freight" class="form-control form-control-sm " placeholder="Freight" value="<?=@isset($fetchOrder['freight']) ? $fetchOrder['freight'] : ''?>" min="0" name="freight" >
                    </div>
                  </div>
                </td>
              </tr>
              <tr>
                <td colspan="2" class="border-none" ></td>
                <td class="table-bordered"> <strong>Grand Total :</strong> 
                  <input type="hidden" name="grand_total" id="grand_total" value="<?=@$fetchOrder['grand_total']?>">
                </td>
                <td class="table-bordered" id="product_grand_total_amount"><?=@$fetchOrder['grand_total']?></td>
              </tr>
            </tfoot>
           </table>
                </div>
              </div>
                <div class="row">
                  <div class="col-sm-6 offset-6">
                    
                  <button class="btn btn-admin float-right " name="sale_order_btn" value="print" type="submit" id="sale_order_btn"><?=!empty($_REQUEST['edit_order_id']) ? "Update and Print" : "Save and Print"?></button>
                
                  </div>
                </div>
            </form>
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
     
    </div> <!-- .wrapper -->
    
  </body>
</html>

<?php include_once 'includes/foot.php'; ?>

<script type="text/javascript">
  $(document).ready(function() {
    if (typeof getinvoiceTotal === 'function') {
      getinvoiceTotal();
    }
    if (typeof syncPoDateValidation === 'function') {
      syncPoDateValidation(false);
    }
    $('#credit_order_client_name').on('change', function() {
      var addr = $(this).find(':selected').data('address') || '';
      $('#customer_address').val(addr);
    });
    $('#party_po').on('change', function() {
      var days = $(this).find(':selected').data('paymentdays');
      if (days) {
        var dStr = String(days).replace(/[^0-9]/g, '');
        if (dStr) $('#credit_sale_type').val(dStr + 'days');
      }
    });
  });
</script>


<?php
if (!empty($_REQUEST['edit_order_id'])) {
?>
<script type="text/javascript">
  var custid = $("#customer_account").val();
 
  //alert(custid);
  getBalance(custid,'customer_account_exp');

 

</script>
<?php
}
  


?>

<div class="modal fade" id="myModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Party PO Details</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            
            <!-- Modal body -->
            <div class="modal-body">
                <table class="table" id="TablicaRacuni">
                    <thead>
                        <tr>
                            <th>Product ID </th>
                            <th>Product Name </th>
                            <th>Required Quantity</th>
                            <th>Delivered Quantity</th>
                            <th>Remaining Quantity</th>

                        </tr>
                    </thead>
                    <tbody id="party_poDetails"  >
                        
                        <!-- Add more rows as needed -->
                    </tbody>
                </table>
            </div>
            
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



