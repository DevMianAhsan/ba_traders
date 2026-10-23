<div class="modal fade" id="add_limit_modal" tabindex="-1" role="dialog" aria-labelledby="defaultModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="defaultModalLabel">Add Brand</h5>
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                              </button>
                            </div>
                    
          <div class="modal-body">

                            <form action="php_action/custom_action.php" method="POST" role="form" id="formData1">
                                <div class="msg"></div>
                                <div class="form-group row">
                      <div class="col-sm-2 text-right">DD/ Check No.</div>
                      <div class="col-sm-4">
                        <input type="text" class="form-control" name="td_check_no" id="td_check_no">
                        <input type="hidden" name="LimitCustomer" id="LimitCustomer">
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
                        <input type="date" class="form-control" name="td_check_date" id="td_check_date">
                      </div>

                      <div class="col-sm-2 text-right">Type</div>
                      <div class="col-sm-4">
                        <input autocomplete="off" type="text" class="form-control" name="check_type" id="check_type" list="check_type_list">
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

                     <div class="form-group row">
                      <div class="col-sm-2 text-right">Amount</div>
                      <div class="col-sm-4">
                        <input type="number" class="form-control" name="check_amount" id="check_amount" >
                      </div>

                      <div class="col-sm-2 text-right">Location Info</div>
                      <div class="col-sm-4">
                        <input  type="text" class="form-control" name="location_info" id="location_info" >
                        

                      </div>
                     
                     </div>


                                <hr>
                                <button type="button" class="btn mb-2 btn-secondary" data-dismiss="modal">Close</button>
            
                            <?php if (@$userPrivileges['nav_edit']==1 || $fetchedUserRole=="admin" AND isset($_REQUEST['edit_brand_id'])): ?>
                                <button   class="btn btn-admin2 float-right" id="formData_btn">Update</button>
                                  <?php   endif ?>
                                  <?php if (@$userPrivileges['nav_add']==1 || $fetchedUserRole=="admin" AND !isset($_REQUEST['edit_brand_id'])): ?>
                                <button   class="btn btn-admin float-right" id="formData_btn">Add</button>
                                  <?php   endif ?>
                            </form>
                            
           </div>
        <div class="modal-footer"></div>
                            
                          </div>
                        </div>
                      </div>