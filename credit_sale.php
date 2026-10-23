 <!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 

if (!empty($_REQUEST['edit_order_id'])) {
    # code...
    $fetchOrder=fetchRecord($dbc,"orders","order_id",base64_decode($_REQUEST['edit_order_id']));
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
                <b class="text-center card-text pb-3">Delivery Challan</b>
           
             
                 <a href="#" onclick="reload_page()" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">
            <form action="php_action/custom_action.php" method="POST" id="sale_order_fm">
              <input type="hidden" name="product_order_id" value="<?=!isset($_REQUEST['edit_order_id'])?"":base64_decode($_REQUEST['edit_order_id'])?>">
            <div class="row form-group">
              <input type="hidden" name="payment_type" id="payment_type" value="credit_sale">
              <div class="col-md-1">
                <label>Order ID#</label>
                <?php $result = mysqli_query($dbc,"
    SHOW TABLE STATUS LIKE 'orders'
");
$data = mysqli_fetch_assoc($result);
$next_increment = $data['Auto_increment']; ?>
                <input type="text" name="next_increment" id="next_increment" value="<?=@empty($_REQUEST['edit_order_id'])?$next_increment:$fetchOrder['order_id']?>" readonly class="form-control">
              </div>
              <div class="col-md-2">
                <label>Order Date</label>
              
               
                   <input type="text" name="order_date" id="order_date" value="<?=@empty($_REQUEST['edit_order_id'])?date('Y-m-d'):$fetchOrder['order_date']?>" readonly class="form-control">
              </div>
              <input type="hidden" name="credit_sale_type" value="<?=@$credit_sale_type?>" id="credit_sale_type">
                      <div class="col-sm-3">
                        <label>Customer Account</label>
                        <div class="input-group">
                       
                          <select class="form-control" onchange="getBalance(this.value,'customer_account_exp')" name="credit_order_client_name" id="credit_order_client_name" required aria-label="Username" aria-describedby="basic-addon1">
                       <option value="">Customer Account</option>
                          <?php
                            $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type='customer'");
                                 while($r=mysqli_fetch_assoc($q)){
                          ?>
                              <option <?=@($fetchOrder['customer_account']==$r['customer_id'])?"selected":""?> data-id="<?=$r['customer_id']?>" data-contact="<?=$r['customer_phone']?>"  value="<?=$r['customer_name']?>"><?=$r['customer_name']?></option>
                         <?php   } ?>
                    </select>
                                              
                                              <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance : <span id="customer_account_exp">0</span>
                          <!-- <span class="input-group-text" id="basic-addon1">Limit : <span id="customer_Limit">0</span> </span>
                           <span class="input-group-text" id="basic-addon1">R Limit : <span id="R_Limit">0</span> </span> -->
                        </div>    
</div>
                      
               
                    <input type="hidden" name="customer_account" id="customer_account" value="<?=@$fetchOrder['customer_account']?>" >
                    <input type="hidden" name="client_contact" id="client_contact" value="<?=@$fetchOrder['client_contact']?>" >
                    <input type="hidden" name="R_Limit" id="R_LimitInput" />

                   </div>

                      <div class="col-sm-2">
                       
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
                 </div>
                 </div>
                   </div>


                
                   <div class="col-sm-2">
                     <label>Vehicle NO </label>
                      <input type="text" id="vehicle_no" value="<?=@$fetchOrder['vehicle_no']?>" class="form-control" autocomplete="off" name="vehicle_no" list="vehicle_no_list" >
                    <datalist id="vehicle_no_list">
                          <?php
                            $q=mysqli_query($dbc,"SELECT DISTINCT vehicle_no FROM orders");
                                 while($r=mysqli_fetch_assoc($q)){
                          ?>
                              <option   value="<?=$r['vehicle_no']?>"><?=$r['vehicle_no']?></option>
                         <?php   } ?>
                    </datalist>
                   </div>

                 

                    <div class="col-sm-2">
                 <label>Remarks</label>
                    <input type="text" autocomplete="off" name="order_narration" id="order_narration" value="<?=@$fetchOrder['order_narration']?>" class="form-control" >
                  
                   </div>

              </div> <!-- end of form-group -->
              <div class="form-group row">
                    <div class="col-6 col-md-2">
                      <label>Product Code</label>
                      <input type="text"  name="product_code" autocomplete="off" id="get_product_code" class="form-control" readonly>
                    </div>
                       <div class="col-6 col-md-2">
                        <label>Products</label>
                        <input type="hidden" id="add_pro_type" value="add">  
                         <select class="form-control searchableSelect" id="get_product_name"  name="product_id"   >
                              <option value="">Select Party PO</option>
                          <?php
                          if (isset($_REQUEST['edit_order_id'])): 
                             $result=mysqli_query($dbc,"SELECT * FROM product WHERE status=1 ");
                             while($row=mysqli_fetch_array($result)){ 
                             $getBrand=fetchRecord($dbc,"brands","brand_id",$row['brand_id']);
                             $getCat=fetchRecord($dbc,"categories","categories_id",$row['category_id']);
                          ?>

                      <option data-price="<?=$row["current_rate"]?>"  <?=empty($r['product_id'])?"":"selected"?>  value="<?=$row["product_id"]?>"  >
                        <?=$row["product_name"]?> |  <?=@$getBrand["brand_name"]?>(<?=@$getCat["categories_name"]?>) </option>
                          <?php
                        }
                        endif;
                          ?>
                     
                  </select>
                  <span  class="text-center w-100" id="instockQty"></span>
                      </div>
                      

                        <div class="col-6 col-sm-1 col-md-1 ">
                          <label>Price</label>
                         <input type="number" <?=($_SESSION['user_role']=="admin")?"":"readonly"?> min="0" class="form-control" id="get_product_price" >
                      </div>
                      <div class="col-6 col-sm-2 col-md-2">
                        <label>Quantity</label>
                         <input type="text" class="form-control" id="get_product_quantity" value="1"  min="1" name="quantity" >
                      </div>

                      <div class="col-6 col-sm-2 col-md-2">
                        <label>Package</label>
                         <input type="text" class="form-control" id="get_product_package" value="1"  min="1" name="quantity" >
                      </div>


                      <div class="col-6 col-sm-1 col-md-1">
                        <label>P.PKG</label>
                         <input type="text" class="form-control" id="get_product_p_pckg" value="1"  min="1" name="quantity" >
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
                       
                       
                         <th>Package</th>
                         <th>P.PKG</th>
                        <th>Quantity</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody class="table table-bordered" id="purchase_product_tb">
                    <?php if (isset($_REQUEST['edit_order_id'])): 
                      $q=mysqli_query($dbc,"SELECT  product.*,brands.*,order_item.* FROM order_item INNER JOIN product ON product.product_id=order_item.product_id INNER JOIN brands ON product.brand_id=brands.brand_id   WHERE order_item.order_id='".base64_decode($_REQUEST['edit_order_id'])."'");
                      
                      while ($r=mysqli_fetch_assoc($q)) {
                      
                      ?>
                 <tr id="product_idN_<?=$r['product_id']?>">
                <input type="hidden" data-price="<?=$r['rate']?>"  data-quantity="<?=$r['quantity']?>" id="product_ids_<?=$r['product_id']?>" class="product_ids" name="product_ids[]" value="<?=$r['product_id']?>">
                <input type="hidden" id="product_quantites_<?=$r['product_id']?>" name="product_quantites[]" value="<?=$r['quantity']?>">
                <input type="hidden" id="rate_mm_<?=$r['product_id']?>" name="rate_mm[]" value="<?=$r['rate_mm']?>">
                <input type="hidden" id="product_rate_<?=$r['product_id']?>" name="product_rates[]" value="<?=$r['rate']?>">
                <input type="hidden" id="product_totalrate_<?=$r['product_id']?>" name="product_totalrates[]" value="<?=$r['rate']?>">

                  <input type="hidden" id="package_<?=$r['product_id']?>" name="package[]" value="<?=$r['pckg']?>">

                    <input type="hidden" id="ppackage_<?=$r['product_id']?>" name="p_package[]" value="<?=$r['p_pckg']?>">
                <td><?=$r['product_code']?></td>
                <td><?=$r['product_name']?></td>
                  <td><?=$r['pckg']?></td>
                 <td><?=$r['p_pckg']?></td>
                 <td><?=$r['quantity']?></td>
               
                <td>
               
            <button type="button" onclick="removeByid(`#product_idN_<?=$r['product_id']?>`)" class="fa fa-trash text-danger" href="#" ></button>
                  <button type="button" onclick="editByid(<?=$r['product_id']?>,`<?=$r['product_code']?>`,<?=$r['rate']?>,<?=$r['quantity']?>,<?=$r['pckg']?>,<?=$r['p_pckg']?>)" class="fa fa-edit text-success ml-2 "  ></button>
                  
            </td>
                </tr>
                    <?php } endif ?>
                  </tbody>
                 
           <tfoot>
              <tr class="">
                        <td colspan="2" ></td>
                       
                        <td class="table-bordered"> Sub Total :</td>
                        <td class="table-bordered" id="product_total_amount"><?=@$fetchOrder['total_amount']?></td>
                        <td class="table-bordered"> Tax % :</td>
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
              <tr class="">
                  <td colspan="2"class="border-none" ></td>
                  <td class="table-bordered"> <strong>Grand Total :</strong> </td>
                  <td class="table-bordered" id="product_grand_total_amount"><?=@$fetchOrder['grand_total']?></td>
                  <td class="table-bordered ">Paid :</td>
                  <td class="table-bordered">
                    <div class="form-group row  d-none">
                      <div class="col-sm-6">
                        <input type="text" min="0" class="form-control form-control-sm" id="paid_ammount" required onkeyup="getRemaingAmount()" name="paid_ammount" value=" <?=@isset($fetchOrder['paid'])?$fetchOrder['paid']:"0"?>">
                      </div>
                      <div class="col-sm-6">
                         <div class="custom-control custom-switch" >
                        <input type="checkbox" class="custom-control-input" id="full_payment_check">
                        <label class="custom-control-label" for="full_payment_check">Full Payment</label>
                      </div>
                      </div>
                    </div>
                  </td>
               </tr>
               <tr class="">
                  <td colspan="2"class="border-none" ></td>
                   <td class="table-bordered">Remaing Amount :</td>
                  <td class="table-bordered"><input type="number" class="form-control form-control-sm" id="remaining_ammount" required readonly name="remaining_ammount" value="<?=@$fetchOrder['due']?>">
                  </td>
                  <td class="table-bordered">Account :</td>
                  <td class="table-bordered ">

                    <div class="input-group d-none">
                    <select class="form-control" onchange="getBalance(this.value,'payment_account_bl')" name="payment_account" id="payment_account" aria-label="Username" aria-describedby="basic-addon1">
               
                     <?php 
                      if($_SESSION['user_role'] == 'admin' ){
                      $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type='bank'");
                    }else{
                      $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_id = 
                        '$UserData[cash_account]'");
                    }
                      while($r=mysqli_fetch_assoc($q)): ?>
                        <option <?=@($fetchOrder['payment_account']==$r['customer_id'])?"selected":""?>   value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                      <?php endwhile; ?>  
                    </select>
                          <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance : <span id="payment_account_bl">0</span> </span>
                        </div>
                      </div>
                  </td>

                        
                        
                   
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



