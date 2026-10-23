<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; 
if (isset($_REQUEST['id'])) {
 $voucher=fetchRecord($dbc,"vouchers", "voucher_id",base64_decode($_REQUEST['id']));

}
?>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text text-center"><?=ucwords(str_replace('_', ' ', $_REQUEST['act']))?></b>
           
                <?php if (@$userPrivileges['nav_add']==1 || $fetchedUserRole=="admin"): ?>
                 <a  href="<?=$getpage?>" class="btn btn-admin float-right btn-sm hide"> Add New</a>
                  <?php endif ?>  
              </div>
            </div>
           
            <div class="card-body">
              <div class="row">
                
              <?php if ($_REQUEST['act']=="general_voucher"){ ?>
                
                     
                  
                   <div class="col-sm-12">
                    <form action="php_action/custom_action.php" method="POST" id="voucher_general_fm">
                    <div class="form-group row">
                      <div class="col-sm-2 text-right">
                        Date
                      </div>
                      <div class="col-sm-4">
                        <?php if (isset($_REQUEST['id'])): ?>
                    <input type="date" class="form-control" name="new_voucher_date" value="<?=@$voucher['voucher_date']?>">

                          <?php else: ?>  
                    <input type="date" class="form-control" name="new_voucher_date" value="<?=date('Y-m-d')?>">
                          
                        <?php endif ?>
                    <input type="hidden" class="form-control" name="voucher_id" value="<?=@$voucher['voucher_id']?>">
                     <input type="hidden" class="form-control" name="voucher_group" value="general_voucher">
                      </div>

                      <div class="col-sm-2 text-right">
                        Type
                      </div>
                      <div class="col-sm-4">
                          <select class="form-control"  name="voucher_type" >
                            <option <?=@($voucher['voucher_type']=="general_voucher")?"checked":""?> value="general_voucher">General Voucher</option>
                            <option <?=@($voucher['voucher_type']=="payment_clearance")?"checked":""?> value="payment_clearance ">Payment Clearance </option>
                            <option <?=@($voucher['voucher_type']=="transferring")?"checked":""?> value="transferring">Transferring</option>
                          </select>
                          
                        </div>
                      </div>
                    
                    
                    <div class="form-group row">
                      <div class="col-sm-2 text-right">To Account</div>
                      <div class="col-sm-4">
                        <div class="input-group mb-3">
                          <select class="form-control"  id="voucher_from_account" onchange="getBalance(this.value,'from_account_bl')" name="voucher_from_account" aria-label="Username" aria-describedby="basic-addon1" id="">
                            <option value="">Select Account</option>
                           

                          <?php   $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 ORDER BY customer_type ASC  ");
                          $type2='';
                            while($r=mysqli_fetch_assoc($q)):
                                $type=$r['customer_type'];
                             ?>       
                             <?php if ($type!=$type2): ?>
                               <optgroup label="<?=$r['customer_type']?>">
                             <?php endif ?>
                                   
                                  <option  <?=@($voucher['customer_id1']==$r['customer_id'])?"selected":""?> value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                                       
                                   <?php if ($type!=$type2): ?>
                               </optgroup>
                             <?php endif ?>
                            <?php    $type2=$r['customer_type']; endwhile; ?>
                          
                        
                          </select>
                          <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance :<span  id="from_account_bl">0</span> </span>
                        </div>
                      </div>
                      </div>
                      <div class="col-sm-2 text-right">
                        Debit
                      </div>

                      <div class="col-sm-4">
                        <input type="number" onkeyup="sameValue(this.value,'#voucher_credit1')" min="0" required name="voucher_debit" value="<?=@$voucher['voucher_amount']?>" class="form-control">
                      </div>
                    </div>  <!-- end of formgr0up -->
                   
                    <div class="form-group row">
                      
                      <div class="col-sm-2 text-right">From Account</div>
                      <div class="col-sm-4">
                        <div class="input-group mb-3">
                          <select class="form-control" id="voucher_to_account" name="voucher_to_account" onchange="getBalance(this.value,'to_account_bl')" aria-label="Username" aria-describedby="basic-addon1">
                            <option value="">Select Account</option>
                           

                          <?php   $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 ORDER BY customer_type ASC ");
                          $type2='';
                            while($r=mysqli_fetch_assoc($q)):
                                $type=$r['customer_type'];
                             ?>       
                             <?php if ($type!=$type2): ?>
                               <optgroup label="<?=$r['customer_type']?>">
                             <?php endif ?>
                                   
                                  <option <?=@($voucher['customer_id2']==$r['customer_id'])?"selected":""?> value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                                       
                                   <?php if ($type!=$type2): ?>
                               </optgroup>
                             <?php endif ?>
                            <?php    $type2=$r['customer_type']; endwhile; ?>
                          
                        
                          </select>
                          <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance :<span id="to_account_bl">0</span> </span>
                        </div>
                      </div>
                      </div>
                      <div class="col-sm-2 text-right">Credit
                      </div>

                      <div class="col-sm-4">
                        <input type="text" readonly value="<?=@$voucher['voucher_amount']?>" id="voucher_credit1" name="voucher_credit" class="form-control" required>
                      </div>
                    
                    </div>
                  <div class="form-group ">
                       <div class="row">
                         
                      <div class="col-sm-2 text-right">Hint
                      </div>
                      <div class="col-sm-10 mx-auto">
                        <input type="text" name="voucher_hint" class="form-control" value="<?=@$voucher['voucher_hint']?>" required>
                      </div>
                    
                       </div>
                     </div>
                      <div class="form-group row">
                      <div class="col-sm-2 text-right">DD/ Check No.</div>
                      <div class="col-sm-4">
                        <input type="text" class="form-control" name="td_check_no" value="<?=@$voucher['td_check_no']?>">
                      </div>
                      <div class="col-sm-2 text-right">Bank Name</div>

                      <div class="col-sm-4">
                        <input type="text" autocomplete="off" value="<?=@$voucher['voucher_bank_name']?>" id="voucher_bank_name" name="voucher_bank_name" class="form-control" list="bank_list">
                         <datalist id="bank_list">
                       
                          <?php
                            $q=mysqli_query($dbc,"SELECT DISTINCT voucher_bank_name from vouchers WHERE voucher_type='general_voucher' ");
                                 while($r=mysqli_fetch_assoc($q)){
                          ?>
                              <option   value="<?=$r['voucher_bank_name']?>"><?=$r['voucher_bank_name']?></option>
                         <?php   } ?>
            
                        </datalist>
                      </div>
                     </div>
                         <div class="form-group row">
                      <div class="col-sm-2 text-right">DD/ Check Date</div>
                      <div class="col-sm-4">
                        <input type="date" class="form-control" name="td_check_date" value="<?=@$voucher['td_check_date']?>">
                      </div>

                      <div class="col-sm-2 text-right">Type</div>
                      <div class="col-sm-4">
                        <input autocomplete="off" type="text" class="form-control" name="check_type" value="<?=@$voucher['check_type']?>" list="check_type_list">
                        <datalist id="check_type_list">
                       
                          <?php
                            $q=mysqli_query($dbc,"SELECT DISTINCT check_type from vouchers WHERE voucher_type='general_voucher' ");
                                 while($r=mysqli_fetch_assoc($q)){
                          ?>
                              <option   value="<?=$r['check_type']?>"><?=$r['check_type']?></option>
                         <?php   } ?>
            
                        </datalist>

                      </div>
                     
                     </div>
                    <hr>
                    <div class="row">
                      <div class="col-sm-2 offset-10">
                         <?php if (@$userPrivileges['nav_add']==1 || $fetchedUserRole=="admin" AND !isset($_REQUEST['id']) ): ?>
                      <button class="btn btn-admin " type="submit" id="voucher_general_btn">Save  </button>
                    <?php endif ?>
                     <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND isset($_REQUEST['id'])): ?>
                      <button class="btn btn-admin " type="submit" id="voucher_general_btn">Update  </button>
                    <?php endif ?>
                    </div>
                    </div>
                  </form>

                </div>
              <?php }elseif ($_REQUEST['act']=="expense_voucher") {
                ?>
               <div class="col-sm-12">

                    <form action="php_action/custom_action.php" method="POST" id="voucher_expense_fm">
                    <div class="form-group row">
                      <div class="col-sm-2 text-right">
                        Date
                      </div>
                      <div class="col-sm-4">
                        <?php if (isset($_REQUEST['id'])): ?>
                    <input type="date" class="form-control" name="new_voucher_date" value="<?=@$voucher['voucher_date']?>">

                          <?php else: ?>  
                    <input type="date" class="form-control" name="new_voucher_date" value="<?=date('Y-m-d')?>">
                          
                        <?php endif ?>
                    <input type="hidden" class="form-control" name="voucher_id" value="<?=@$voucher['voucher_id']?>">
                    <input type="hidden" class="form-control" name="voucher_group" value="expense_voucher">
                     
                      </div>
                      <div class="col-sm-2 text-right">
                        Type
                      </div>
                      <div class="col-sm-4">
                          <select class="form-control"  name="voucher_type" >
                            <?php $q=get($dbc,"expenses WHERE expense_status=1 ");
                                  while ($r=mysqli_fetch_assoc($q)) {
                                    # code...
                                 
                               ?>
                             <option <?=@($voucher['voucher_type']==$r['expense_id'])?"checked":""?> value="<?=$r['expense_id']?>"><?=strtoupper($r['expense_name'])?></option>
                           <?php } ?>
                                
                          </select>
                          
                        </div>
                    </div>
                    <div class="form-group row">
                      <div class="col-sm-2 text-right">From Account</div>
                      <div class="col-sm-4">
                        <div class="input-group mb-3">
                          <select class="form-control" onchange="getBalance(this.value,'from_account_exp')" id="voucher_from_account" name="voucher_from_account" aria-label="Username" aria-describedby="basic-addon1">
                            <option value="">Select Account</option>
                           

                          <?php    $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 ORDER BY customer_type ASC ");
                          $type2='';
                            while($r=mysqli_fetch_assoc($q)):
                                $type=$r['customer_type'];
                             ?>       
                             <?php if ($type!=$type2): ?>
                               <optgroup label="<?=$r['customer_type']?>">
                             <?php endif ?>
                                   
                                  <option <?=@($voucher['customer_id1']==$r['customer_id'])?"selected":""?> value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                                       
                                   <?php if ($type!=$type2): ?>
                               </optgroup>
                             <?php endif ?>
                            <?php    $type2=$r['customer_type']; endwhile; ?>
                          </select>
                          <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance : <span id="from_account_exp">0</span> </span>
                        </div>
                      </div>
                      </div>
                      <div class="col-sm-2 text-right">
                        Debit
                      </div>

                      <div class="col-sm-4">
                        <input type="number" onkeyup="sameValue(this.value,'#voucher_credit')" min="0" name="voucher_debit" class="form-control" value="<?=@$voucher['voucher_amount']?>" required>
                      </div>
                    </div>  <!-- end of formgr0up -->
                    <div class="form-group row">
                      
                      
                      <div class="col-sm-2 text-right">To Account</div>
                      <div class="col-sm-4">
                        <div class="input-group mb-3">
                          <select class="form-control" onchange="getBalance(this.value,'to_account_exp')" id="voucher_to_account" name="voucher_to_account" aria-label="Username" aria-describedby="basic-addon1">
                            <option value="">Select Account</option>
                           

                          <?php   $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 AND customer_type ='expense' ");
                          $type2='';
                            while($r=mysqli_fetch_assoc($q)):
                                $type=$r['customer_type'];
                             ?>       
                             <?php if ($type!=$type2): ?>
                               <optgroup label="<?=$r['customer_type']?>">
                             <?php endif ?>
                                   
                                  <option <?=@($voucher['customer_id2']==$r['customer_id'])?"selected":""?> value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                                       
                                   <?php if ($type!=$type2): ?>
                               </optgroup>
                             <?php endif ?>
                            <?php    $type2=$r['customer_type']; endwhile; ?>
                          
                        
                          </select>
                          <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance : <span id="to_account_exp">0</span> </span>
                        </div>
                      </div>
                      </div>
                      <div class="col-sm-2 text-right">                        Credit
                      </div>

                      <div class="col-sm-4">
                        <input type="text" readonly  value="<?=@$voucher['voucher_amount']?>" id="voucher_credit" name="voucher_credit" class="form-control">
                      </div>
                    
                    
                    </div>
                    <div class="form-group row">
                      <div class="col-sm-2 text-right">Hint
                      </div>
                      <div class="col-sm-10">
                        <input type="text" name="voucher_hint"  class="form-control" value="<?=@$voucher['voucher_hint']?>">
                      </div>
                    </div>
                   
                    <hr>
                    <div class="row">
                      <div class="col-sm-2 offset-10">
                      <button class="btn btn-admin " type="submit" id="voucher_expense_btn">Save  </button>
                    </div>
                    </div>
                  </form>

                
                        </div>
                <?php }elseif ($_REQUEST['act']=="single_voucher") {
                ?>
                  <div class="col-sm-12">


                    <form action="php_action/custom_action.php" method="POST" id="voucher_single_fm">
                    <div class="form-group row">
                      <div class="col-sm-2 text-right">
                        Date
                      </div>
                      <div class="col-sm-4">
                        <?php if (isset($_REQUEST['id'])): ?>
                    <input type="date" class="form-control" name="new_sin_voucher_date" value="<?=@$voucher['voucher_date']?>">

                          <?php else: ?>  
                    <input type="date" class="form-control" name="new_sin_voucher_date" value="<?=date('Y-m-d')?>">
                          
                        <?php endif ?>
                    <input type="hidden" class="form-control" name="voucher_id" value="<?=@$voucher['voucher_id']?>">
                    <input type="hidden" class="form-control" name="voucher_group" value="single_voucher">
                     
                      </div>
                      <div class="col-sm-2 text-right">Account</div>
                      <div class="col-sm-4">
                        <div class="input-group mb-3">
                          <select class="form-control" required onchange="getBalance(this.value,'account_sing')" name="voucher_from_account" aria-label="Username" aria-describedby="basic-addon1">
                            <option value="">Select Account</option>
                           

                          <?php  
                          $transactions=fetchRecord($dbc,"transactions", "transaction_id", $voucher['transaction_id1']);
        
                            $q=mysqli_query($dbc,"SELECT * FROM customers WHERE customer_status =1 ORDER BY customer_type ASC ");
                          $type2='';
                            while($r=mysqli_fetch_assoc($q)):
                                $type=$r['customer_type'];
                             ?>       
                             <?php if ($type!=$type2): ?>
                               <optgroup label="<?=$r['customer_type']?>">
                             <?php endif ?>
                                   
                                  <option <?=@($voucher['customer_id1']==$r['customer_id'])?"selected":""?> value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                                       
                                   <?php if ($type!=$type2): ?>
                               </optgroup>
                             <?php endif ?>
                            <?php    $type2=$r['customer_type']; endwhile; ?>
                          </select>
                          <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance : <span id="account_sing">0</span> </span>
                        </div>
                      </div>
                      </div>
                    </div>
                    <div class="form-group row">
                       <div class="col-sm-2 text-right">                        Credit
                      </div>

                      <div class="col-sm-4">
                        <input type="number" onkeyup="readonlyIt(this.value,'voucher_sin_debit')"   value="<?=@$transactions['credit']?>" id="voucher_sin_credit" name="voucher_credit" class="form-control" required>
                      </div>
                      <div class="col-sm-2 text-right">
                        Debit
                      </div>

                      <div class="col-sm-4">
                        <input type="number" onkeyup="readonlyIt(this.value,'voucher_sin_credit')" min="0" name="voucher_debit"  id="voucher_sin_debit" class="form-control" value="<?=@$transactions['debit']?>">
                      </div>
                    </div>  <!-- end of formgr0up -->
                  
                    <div class="form-group row">
                      <div class="col-sm-2 text-right">Hint
                      </div>
                      <div class="col-sm-10">
                        <input type="text" name="voucher_hint"  class="form-control" value="<?=@$voucher['voucher_hint']?>">
                      </div>
                    </div>
                   
                    <hr>
                    <div class="row">
                      <div class="col-sm-2 offset-10">
                      <button class="btn btn-admin " type="submit" id="voucher_single_btn">Save  </button>
                    </div>
                    </div>
                  </form>

                
                        
                  </div>
                   
                    
              <?php }else{ ?>  <!-- add --------------- -->
              <div class="col-sm-12">                
                <table class="table  dataTable" id="voucher_expense_tb">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>From Account</th>
                      <th>To Account</th>
                      <th>Amount</th>
                      <th>Hint</th>
                      <th>Voucher Type</th>
                      <th>Date</th>
                      <th>By</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody> 
                      <?php   $q=mysqli_query($dbc,"SELECT * FROM vouchers ORDER BY voucher_id DESC LIMIT 200");
                      $c=0;
                        while ($r=mysqli_fetch_assoc($q)) { $c++;
                      @$customer_id1=fetchRecord($dbc,"customers", "customer_id", $r['customer_id1'])['customer_name'];
                     @$customer_id2=fetchRecord($dbc,"customers", "customer_id", $r['customer_id2'])['customer_name'];
                       $username=fetchRecord($dbc,"users", "user_id", $r['addby_user_id'])['username'];
                     


                       ?>
                       <tr>
                          <td><?=$r['voucher_id']?></td>
                          <td><?=$customer_id1?></td>
                          <td><?=@$customer_id2?></td>
                          <td><?=$r['voucher_amount']?></td>
                          <td><?=$r['voucher_hint']?></td>
                          <td><?=$r['voucher_group']?></td>
                          <td><?=$r['voucher_date']?></td>
                          <td><?=$username?></td>
                          <td>
                          <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin"): ?>
                            <form action="voucher.php" method="POST">
                              <input type="hidden" name="id" value="<?=base64_encode($r['voucher_id'])?>">
                              <input type="hidden" name="act" value="<?=$r['voucher_group']?>">
                              <button type="submit" class="btn m-1 btn-admin btn-sm" >Edit</button>
                            </form>
                            

                          <?php   endif ?>
                            <a onclick="getVoucherPrint(`<?=base64_encode($r['voucher_id'])?>`)" href="#"  class="btn btn-admin btn-sm m-1">Print</a>
                          <?php if (@$userPrivileges['nav_delete']==1 || $fetchedUserRole=="admin"): ?>

                             <a href="#" onclick="deleteAlert('<?=$r['voucher_id']?>','vouchers','voucher_id','voucher_expense_tb')" class="btn btn-admin2 btn-sm m-1">Delete</a>
                          <?php   endif ?>
                          </td>
                       </tr>
                     <?php  } ?>
                  </tbody>  
                </table>
                </div>

              <?php   } ?>
            
              </div>
            </div>
          </div>
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>