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
                <b class="text-center card-text">Order</b>
           
             
                 <a href="cash_sale.php" class="btn btn-admin float-right btn-sm">Add New</a>
              </div>
            </div>
  
          </div>
           <div class="card-body">
            <form action="php_action/custom_action.php" method="POST" id="sale_order_fm">
               <input type="hidden" name="product_order_id" value="<?=@empty($_REQUEST['edit_order_id'])?"":base64_decode($_REQUEST['edit_order_id'])?>"> 
              <input type="hidden" name="payment_type" id="payment_type" value="cash_in_hand">

                <div class="row form-group">
              <div class="col-md-4">
                <label>Order Date</label>
               <input type="text" name="order_date" id="order_date" value="<?=@empty($_REQUEST['edit_order_id'])?date('Y-m-d'):$fetchOrder['order_date']?>" readonly class="form-control">
              </div>
              <div class="col-sm-4">
                 <label>Customer Number</label>
                    <input type="number" onchange="getCustomer_name(this.value)" value="<?=@$fetchOrder['client_contact']?>"  min="0" class="form-control" name="client_contact" list="phone" autocomplete="off">
                        <datalist id="phone" >
                          <?php
                            $q=mysqli_query($dbc,"SELECT DISTINCT client_contact from orders");
                                 while($r=mysqli_fetch_assoc($q)){
                          ?>
                              <option   value="<?=$r['client_contact']?>"><?=$r['client_contact']?></option>
                         <?php   } ?>
            
                        </datalist>
                   </div>
                      <div class="col-sm-4">
                 <label>Customer Name</label>
                    <input type="text" id="sale_order_client_name" value="<?=@$fetchOrder['client_name']?>" class="form-control" name="sale_order_client_name" list="client_name" required autocomplete="off">
                    <datalist id="client_name">
                          <?php
                            $q=mysqli_query($dbc,"SELECT DISTINCT client_name  from orders");
                                 while($r=mysqli_fetch_assoc($q)){
                          ?>
                              <option   value="<?=$r['client_name']?>"><?=$r['client_name']?></option>
                         <?php   } ?>
                    </datalist>
                   </div>
                 
              </div> <!-- end of form-group -->
              <div class="form-group row">
                
              <div class="col-sm-6">
                <table class="table table-bordered">
                  <thead>
                  <th>Product</th><th>Rate</th><th>Quantity</th><th>Total</th><th>Action</th>
                  </thead>
                  <tbody id="purchase_product_tb">
                    <?php if (isset($_REQUEST['edit_order_id'])): 
                      $q=mysqli_query($dbc,"SELECT  product.*,brands.*,order_item.* FROM order_item INNER JOIN product ON product.product_id=order_item.product_id INNER JOIN brands ON product.brand_id=brands.brand_id   WHERE order_item.order_id='".base64_decode($_REQUEST['edit_order_id'])."'");
                      
                      while ($r=mysqli_fetch_assoc($q)) {
                      
                      ?>
                 <tr id="product_idN_<?=$r['product_id']?>">
                <input type="hidden" data-price="<?=$r['rate']?>"  data-quantity="<?=$r['quantity']?>" id="product_ids_<?=$r['product_id']?>" class="product_ids" name="product_ids[]" value="<?=$r['product_id']?>">
                <input type="hidden" id="product_quantites_<?=$r['product_id']?>" name="product_quantites[]" value="<?=$r['quantity']?>">
                <input type="hidden" id="product_rate_<?=$r['product_id']?>" name="product_rates[]" value="<?=$r['rate']?>">
                <input type="hidden" id="product_totalrate_<?=$r['product_id']?>" name="product_totalrates[]" value="<?=$r['rate']?>">
                
                <td><?=$r['product_name']?> (<span class="text-success"><?=$r['brand_name']?></span>)</td>
                 <td><?=$r['rate']?></td>
                 <td><?=$r['quantity']?></td>
                <td><?=(double)$r['rate']*(double)$r['quantity']?></?></td>
                <td>
                  <button type="button" onclick="addProductOrder(<?=$r['product_id']?>,<?=$r['quantity']?>,`plus`)" class="fa fa-plus text-success" href="#" ></button>
            <button type="button" onclick="addProductOrder(<?=$r['product_id']?>,<?=$r['quantity']?>,`minus`)" class="fa fa-minus text-warning" href="#" ></button>
            <button type="button" onclick="removeByid(`#product_idN_<?=$r['product_id']?>`)" class="fa fa-trash text-danger" href="#" ></button>
            </td>
                </tr>
                    <?php } endif ?>
                  </tbody>
                </table>

              </div> <!-- end of coulum -->
              <div class="col-sm-6">
                
                 <ul class="nav nav-pills" id="cat_list">
                  <?php $q=mysqli_query($dbc,"SELECT * FROM brands WHERE brand_status=1 ");
                    while ($r=mysqli_fetch_assoc($q)) {

                 ?>
                  <li class="nav-item text-capitalize "><button type="button" onclick="loadProducts(<?=$r['brand_id']?>)" class="btn btn-admin2 m-1 " style="font-size: 14px;" ><?=$r['brand_name']?></button></li>

                 <?php } ?>
                
                 </ul>
                 <hr>
                  <ul class="nav nav-pills mb-2" id="products_list" style="max-height:90px;overflow-y: scroll;">
                    
                  </ul>
                  <hr>
                  <table class="table table-bordered">
                    <tr>
                      <th>Sub Total</th><th id="product_total_amount"><?=@$fetchOrder['total_amount']?></th>
                    </tr>
                    <tr>
                      <th>Discount</th><th id=""><input onkeyup="getOrderTotal()" type="number" id="ordered_discount" class="form-control form-control-sm" value="<?=@empty($_REQUEST['edit_order_id'])?"0":$fetchOrder['discount']?>" min="0" max="100" name="ordered_discount" >
                        </th>
                    </tr>
                    <tr>
                    <th>Grand Total</th><th id="product_grand_total_amount"><?=@$fetchOrder['grand_total']?></th>
                    </tr>
                    <tr>
                      <td class="table-bordered">Paid</td>
                  <td class="table-bordered"><input type="number" class="form-control form-control-sm" id="paid_ammount" required onkeyup="getRemaingAmount()" name="paid_ammount" value="<?=@$fetchOrder['paid']?>"></td>
                    </tr>
                    <tr>
                  <td class="table-bordered">Account :</td>
                  <td class="table-bordered">
                    <select class="form-control" name="payment_account">
                      <option value="">Select Account</option>
                      <?php $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type='bank'");
                      while($r=mysqli_fetch_assoc($q)): ?>
                        <option <?=@($fetchOrder['payment_account']==$r['customer_id'])?"selected":""?>   value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                      <?php endwhile; ?>  
                    </select>
                  </td>
               </tr>
               <tr>
                   <td class="table-bordered">Remaing Amount :</td>
                  <td class="table-bordered"><input type="number" class="form-control form-control-sm" id="remaining_ammount" required readonly name="remaining_ammount" value="<?=@$fetchOrder['due']?>">
                  </td>

               </tr>
                  </table>

              </div> <!-- end of coulum -->
            
              </div>
                <div class="row">
                  <div class="col-sm-6 offset-6">
                    
                  <button class="btn btn-admin float-right " name="sale_order_btn" value="print" type="submit" id="">Save and Print</button>
                
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