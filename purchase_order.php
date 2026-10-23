 <!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 

if (!empty($_REQUEST['edit_order_id'])) {
    # code...
  //echo base64_decode($_REQUEST['edit_order_id']);

    $fetchOrder=fetchRecord($dbc,"purchase_order","po_id",base64_decode($_REQUEST['edit_order_id']));
    //print_r($fetchOrder);
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
                <b class="text-center card-text pb-3">Sale Order</b>
           
             
                 <a href="#" onclick="reload_page()" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">
            <form action="php_action/custom_action.php" method="POST" id="sale_order_fm">
              <input type="hidden" name="product_order_id" value="<?=!isset($_REQUEST['edit_order_id'])?"":base64_decode($_REQUEST['edit_order_id'])?>">
            <div class="row form-group">
              <input type="hidden" name="payment_type" id="payment_type" value="credit_purchase">
              <div class="col-md-1">
                <label>PO ID#</label>
                <?php $result = mysqli_query($dbc,"
    SHOW TABLE STATUS LIKE 'purchase_order'
");
$data = mysqli_fetch_assoc($result);
$next_increment = $data['Auto_increment']; ?>
                <input type="text" name="next_increment" id="next_increment" value="<?=@empty($_REQUEST['edit_order_id'])?$next_increment:$fetchOrder['po_id']?>" readonly class="form-control">
              </div>
              <div class="col-md-2">
                <label>PO Date</label>
              
               
                   <input type="text" name="order_date" id="order_date" value="<?=@empty($_REQUEST['edit_order_id'])?date('Y-m-d'):$fetchOrder['po_date']?>" readonly class="form-control">
              </div>
              <input type="hidden" name="credit_sale_type" value="<?=@$credit_sale_type?>" id="credit_sale_type">
                      <div class="col-sm-3">
                        <label>Customer Account</label>
                        <div class="input-group">       

                          <select class="form-control" onchange="getBalance(this.value,'customer_account_exp')" name="purchase_order_client_name" id="credit_order_client_name" required aria-label="Username" aria-describedby="basic-addon1">
                       <option value="">Customer Account</option>
                          <?php
                            $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type='customer'");
                                 while($r=mysqli_fetch_assoc($q)){
                          ?>
                              <option <?=@($fetchOrder['po_customer']==$r['customer_id'])?"selected":""?> data-id="<?=$r['customer_id']?>" data-contact="<?=$r['customer_phone']?>"  value="<?=$r['customer_name']?>"><?=$r['customer_name']?></option>
                         <?php   } ?>
                    </select>
                                              
                                              <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance : <span id="customer_account_exp">0</span>
                          <!-- <span class="input-group-text" id="basic-addon1">Limit : <span id="customer_Limit">0</span> </span>
                           <span class="input-group-text" id="basic-addon1">R Limit : <span id="R_Limit">0</span> </span> -->
                        </div>    
</div>
                      
               
                    <input type="hidden" name="customer_account" id="customer_account" value="<?=@$fetchOrder['po_customer']?>" >
                    <input type="hidden" name="client_contact" id="client_contact" value="<?=@$fetchOrder['po_customer_no']?>" >
                    <input type="hidden" name="R_Limit" id="R_LimitInput" />

                   </div>
                

                   <div class="col-sm-2">
                     <label>Party STM </label>
                      <input type="text" id="party_stm" value="<?=@$fetchOrder['po_partystm']?>" class="form-control" autocomplete="off" name="party_stm" onchange="PartyPOorSTM('po_partystm',this.value, 'party_stm')"  >
                   
                   </div>

                    <div class="col-sm-2">
                     <label>Party PO</label>
                      <input type="text" id="party_po1" value="<?=@$fetchOrder['po_partypo']?>" class="form-control" autocomplete="off" name="party_po" required onchange="PartyPOorSTM('po_partypo',this.value, 'party_po')">
                   
                   </div>

                    <div class="col-sm-2">
                 <label>Remarks</label>
                    <input type="text" autocomplete="off" name="order_narration" id="order_narration" value="<?=@$fetchOrder['po_remarks']?>" class="form-control" >
                  
                   </div>

              </div> <!-- end of form-group -->

               <div class="form-group row">
                  <div class="col-6 col-md-3">
                      <label>Payment Days</label>
                      <input type="number" name="payment_days" autocomplete="off" id="payment_days" class="form-control" list="paymentDays" value="<?=@$fetchOrder['po_paymentdays']?>">
                        <datalist id="paymentDays">
                          <option value="30">
                          <option value="45">
                          <option value="60">
                        </datalist>
                     </div>


                      <div class="col-6 col-md-3">
                      <label>Target date</label>
                      <input type="date" name="target_date" autocomplete="off" id="target_date" class="form-control" value="<?=@$fetchOrder['po_targetdate']?>" >
                        
                     </div>

                      <div class="col-6 col-md-3">
                      <label>Sold To (Consumer) <span>use (,) New Line</span></label>
                      <input type="text" name="party_consumer"  id="party_consumer" class="form-control" value="<?=@$fetchOrder['po_consumer']?>">
                        
                     </div>



                     <div class="col-6 col-md-3">
                      <label>Job Card Description</label>
                      <input type="text" name="jobcard_desc" autocomplete="off" id="jobcard_desc" class="form-control" value="<?=@$fetchOrder['po_jobdesc']?>">
                        
                     </div>



               </div>

              <!-- New row -->
              <div class="form-group row">
                    <div class="col-6 col-md-2">
                      <label>Product Code</label>
                      <input type="text"  name="product_code" autocomplete="off" id="get_product_code" class="form-control">
                    </div>
                       <div class="col-6 col-md-2">
                        <label>Products</label>
                        <input type="hidden" id="add_pro_type" value="add">  
                         <select class="form-control searchableSelect" id="get_product_name"  name="product_id"   >
                              <option value="">Select Product</option>
                          <?php
                            $result=mysqli_query($dbc,"SELECT * FROM product WHERE status=1 ");
                             while($row=mysqli_fetch_array($result)){ 
                             $getBrand=fetchRecord($dbc,"brands","brand_id",$row['brand_id']);
                             $getCat=fetchRecord($dbc,"categories","categories_id",$row['categories_id']);
                          ?>
                    
                      <option data-price="<?=$row["current_rate"]?>"  <?=empty($r['product_id'])?"":"selected"?>  value="<?=$row["product_id"]?>"  >
                        <?=$row["product_name"]?> |  <?=@$getBrand["brand_name"]?>(<?=@$getCat["categories_name"]?>) </option>

                      <?php   } ?>
                  </select>
                  <span  class="text-center w-100" id="instockQty"></span>
                      </div>

                         <div class="col-6 col-sm-2 col-md-2 ">
                        <label>Rate/MM</label>
                         <input type="number" class="form-control" id="get_product_mm" value="1"  min="1" name="quantity" step="0.5" onkeyup="applyFormula()"  >
                      </div>

                        <div class="col-6 col-sm-2 col-md-2">
                          <label>Price</label>
                         <input type="number" <?=($_SESSION['user_role']=="admin")?"":"readonly"?> min="0" class="form-control" id="get_product_price" >
                      </div>
                      <div class="col-6 col-sm-2 col-md-2">
                        <label>Quantity</label>
                         <input type="text" class="form-control" id="get_product_quantity" value="1"  min="1" name="quantity" >
                      </div>
                       <div class="col-sm-1">
                        <br>
                         <button type="button" class="btn btn-admin btn-sm mt-2 float-right" id="addProductPurchase"><i class="fa fa-plus"></i> <b>Add</b></button>
                      </div>
                    
              </div>
              <div class="row">
                <div class="col-12">
                  
                  <table class="table  saleTable" id="myDiv"> 
                    <thead class="table-bordered" style="background: <?=$base_color?>">
                      <tr>
                        <th>Code</th>
                        <th>Product Name</th>
                        <th>Rate MM</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody class="table table-bordered" id="purchase_product_tb">
                    <?php if (isset($_REQUEST['edit_order_id'])): 
                      $q=mysqli_query($dbc,"SELECT  product.*,brands.*,purchase_order_item.* FROM purchase_order_item INNER JOIN product ON product.product_id=purchase_order_item.product_id INNER JOIN brands ON product.brand_id=brands.brand_id   WHERE purchase_order_item.po_id='".base64_decode($_REQUEST['edit_order_id'])."'");
                      
                      while ($r=mysqli_fetch_assoc($q)) {
                      
                      ?>
                 <tr id="product_idN_<?=$r['product_id']?>">
                <input type="hidden" data-price="<?=$r['rate']?>"  data-quantity="<?=$r['quantity']?>" id="product_ids_<?=$r['product_id']?>" class="product_ids" name="product_ids[]" value="<?=$r['product_id']?>">
                <input type="hidden" id="product_quantites_<?=$r['product_id']?>" name="product_quantites[]" value="<?=$r['quantity']?>">
                <input type="hidden" id="product_rate_<?=$r['product_id']?>" name="product_rates[]" value="<?=$r['rate']?>">
                <input type="hidden" id="product_totalrate_<?=$r['product_id']?>" name="product_totalrates[]" value="<?=$r['rate']?>">
                <td><?=$r['product_code']?></td>
                <td><?=$r['product_name']?></td>
                <td></td>
                 <td><?=$r['rate']?></td>
                 <td><?=$r['quantity']?></td>
                <td><?=(double)$r['rate']*(double)$r['quantity']?></?></td>
                <td>
               
            <button type="button" onclick="removeByid(`#product_idN_<?=$r['product_id']?>`)" class="fa fa-trash text-danger" href="#" ></button>
                  <button type="button" onclick="editByid(<?=$r['product_id']?>,`<?=$r['product_code']?>`,<?=$r['rate']?>,<?=$r['quantity']?>)" class="fa fa-edit text-success ml-2 "  ></button>
                  
            </td>
                </tr>
                    <?php } endif ?>
                  </tbody>
                 
           <tfoot>
              <tr>
                        <td colspan="3" ></td>
                       
                        <td class="table-bordered"> Sub Total :</td>
                        <td class="table-bordered" id="product_total_amount"><?=@$fetchOrder['total_amount']?></td>
                        <td class="table-bordered">   <strong>Tax :</strong> </td>
                         <td class="table-bordered" id="getDiscount">
                            <div class="form-group row">
                      <div class="col-sm-4">
                          <input onkeyup="getOrderTotal()" type="number" id="ordered_discount" class="form-control form-control-sm" value="<?=@empty($_REQUEST['edit_order_id'])?"0":$fetchOrder['discount']?>" min="0" max="100" name="ordered_discount" >
                        </div>
                          <div class="col-sm-4">
                           <div class="custom-control custom-switch" >
                        <input type="checkbox" class="custom-control-input" id="with_tax">
                        <label class="custom-control-label" for="with_tax">With Tax</label>
                      </div>
                    </div>
                    <div class="col-sm-4">
                       <input onkeyup="getOrderTotal()" type="number" id="freight" class="form-control form-control-sm " placeholder="Freight" value="<?=@$fetchOrder['freight']?>" min="0" name="freight" >
                    </div>
                  </div>
                        </td>
                      </tr>

                      <tr>
                  <td colspan="3"class="border-none" ></td>
                  <td class="table-bordered"> <strong>Grand Total :</strong> </td>
                  <td class="table-bordered" id="product_grand_total_amount" colspan="3"><?=@$fetchOrder['grand_total']?></td>
                  
               </tr>
              
               
              
            </tfoot>
           </table>
                </div>
              </div>
                <div class="row">
                  <div class="col-sm-6 offset-6">
                    
                  <button class="btn btn-admin float-right " name="sale_order_btn" value="print" type="submit" id="sale_order_btn">Save and Print</button>
                
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