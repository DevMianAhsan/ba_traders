 <!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 

if (!empty($_REQUEST['edit_order_id'])) {
    $fetchOrder = fetchRecord($dbc, "invoices", "invoice_id", base64_decode($_REQUEST['edit_order_id']));
    if (!empty($fetchOrder['transaction_id'])) {
        $fetchTransaction = fetchRecord($dbc, "transactions", "transaction_id", $fetchOrder['transaction_id']);
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
           
             
                 <a href="invoice.php" class="btn btn-admin float-right btn-sm">Add New</a>
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
                <?php $result = mysqli_query($dbc,"
    SHOW TABLE STATUS LIKE 'invoices'
");
$data = mysqli_fetch_assoc($result);
$next_increment = $data['Auto_increment']; ?>
                <input type="text" name="next_increment" id="next_increment" value="<?=@empty($_REQUEST['edit_order_id'])?$next_increment:$fetchOrder['invoice_id']?>" readonly class="form-control">
              </div>
              <div class="col-md-2">
                <label>Invoice Date</label>
              
               
                   <input type="text" name="order_date" id="order_date" value="<?=@empty($_REQUEST['edit_order_id'])?date('Y-m-d'):$fetchOrder['invoice_date']?>" readonly class="form-control">
              </div>
              <input type="hidden" name="credit_sale_type" value="<?=@$credit_sale_type?>" id="credit_sale_type">
                      <div class="col-sm-3">
                        <label>Customer Account</label>
                        <div class="input-group">
                       
                          <select class="form-control" onchange="getBalance(this.value,'customer_account_exp')" name="invoice_client_name" id="credit_order_client_name" required aria-label="Username" aria-describedby="basic-addon1">
                       <option value="">Customer Account</option>
                          <?php
                            $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type='customer'");
                                 while($r=mysqli_fetch_assoc($q)){
                          ?>
                              <option <?=@($fetchOrder['customer_id']==$r['customer_id'] || @$fetchOrder['customer_account']==$r['customer_id'])?"selected":""?> data-id="<?=$r['customer_id']?>" data-contact="<?=$r['customer_phone']?>"  value="<?=$r['customer_name']?>"><?=$r['customer_name']?></option>
                         <?php   } ?>
                    </select>
                                              
                                              <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance : <span id="customer_account_exp">0</span>
                          <!-- <span class="input-group-text" id="basic-addon1">Limit : <span id="customer_Limit">0</span> </span>
                           <span class="input-group-text" id="basic-addon1">R Limit : <span id="R_Limit">0</span> </span> -->
                        </div>    
</div>
                      
               
                    <input type="hidden" name="customer_account" id="customer_account" value="<?=@$fetchOrder['customer_id'] ?? @$fetchOrder['customer_account']?>" >
                    <input type="hidden" name="client_contact" id="client_contact" value="<?=@$fetchOrder['client_contact']?>" >
                    <input type="hidden" name="R_Limit" id="R_LimitInput" />

                   </div>

                     <div class="col-sm-3">
                       
                     <label>Party PO</label>
                      <div class="input-group">
                     <!--  <input type="text" id="voucher_no" value="<?=@$fetchOrder['voucher_no']?>" class="form-control" autocomplete="off" name="voucher_no" required> -->
                     

                     <?php
                      if(@$fetchOrder['party_po']){
                        ?>
                        <input type="text" readonly  id="party_po" name="party_po" value="<?=$fetchOrder['party_po']?>" class="form-control">
                        <?php
                      }else{
                        ?>
                        <select id="party_po" name="party_po" class="form-control">
                       <option > Select Customer first </option>
                     </select>
                      <?php
                    }
                      ?>
                       <div class="input-group-prepend">
                   <button type="button" class="btn-sm btn-admin float-right" onclick="getpoDetails()">
                     <span class="fa fa-eye "  ></span>
                   </button>
                    <button type="button" class="btn-sm btn-admin float-right" onclick="addpoDetails()">
                     <span class="fa fa-plus "  ></span>
                   </button>
                 </div>
                 </div>
                   </div>

                    <div class="col-sm-2">
                 <label>Remarks</label>
                    <input type="text" autocomplete="off" name="order_narration" id="order_narration" value="<?=@$fetchOrder['order_narration'] ?: @$fetchTransaction['transaction_remarks']?>" class="form-control" >
                  
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
                      <tr id="product_idN_<?=$rowId?>">
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



