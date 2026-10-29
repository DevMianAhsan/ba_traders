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
                      <div class="col-sm-2 text-right">From Account</div>
                      <div class="col-sm-4">
                        <div class="input-group mb-3">
                          <select class="form-control" id="voucher_from_account" onchange="getBalance(this.value,'from_account_bl'); checkCustomerRole(false);" name="voucher_from_account" aria-label="Username" aria-describedby="basic-addon1">
                            <option value="">Select Account</option>
                          <?php   
                          $q = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_status = 1 ORDER BY customer_type ASC, customer_name ASC");
                          $prev_type = null;
                          while($r = mysqli_fetch_assoc($q)):
                              if ($prev_type !== $r['customer_type']):
                                  if ($prev_type !== null): ?>
                                      </optgroup>
                                  <?php endif; ?>
                                  <optgroup label="<?=htmlspecialchars(ucfirst($r['customer_type']))?>">
                                  <?php $prev_type = $r['customer_type'];
                              endif;
                          ?>       
                              <option data-type="<?=htmlspecialchars(strtolower($r['customer_type']))?>" <?=@($voucher['customer_id1']==$r['customer_id'])?"selected":""?> value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                          <?php endwhile; 
                          if ($prev_type !== null): ?>
                              </optgroup>
                          <?php endif; ?>
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
                        <div class="input-group">
                          <input type="text" oninput="onDebitInput(this)" onblur="formatInputOnBlur(this); sameValue(this.value,'#voucher_credit1');" required name="voucher_debit" id="voucher_debit" value="<?=!empty($voucher['voucher_amount']) ? number_format(floatval($voucher['voucher_amount'])) : ''?>" class="form-control font-weight-bold" placeholder="0">
                          <div class="input-group-append">
                            <span class="input-group-text font-weight-bold">RS</span>
                          </div>
                        </div>
                      </div>
                    </div>  <!-- end of formgr0up -->
                   
                    <div class="form-group row">
                      
                      <div class="col-sm-2 text-right">To Account</div>
                      <div class="col-sm-4">
                        <div class="input-group mb-3">
                          <select class="form-control" id="voucher_to_account" name="voucher_to_account" onchange="getBalance(this.value,'to_account_bl');" aria-label="Username" aria-describedby="basic-addon1">
                            <option value="">Select Account</option>
                          <?php   
                          $q = mysqli_query($dbc, "SELECT * FROM customers WHERE customer_status = 1 ORDER BY customer_type ASC, customer_name ASC");
                          $prev_type = null;
                          while($r = mysqli_fetch_assoc($q)):
                              if ($prev_type !== $r['customer_type']):
                                  if ($prev_type !== null): ?>
                                      </optgroup>
                                  <?php endif; ?>
                                  <optgroup label="<?=htmlspecialchars(ucfirst($r['customer_type']))?>">
                                  <?php $prev_type = $r['customer_type'];
                              endif;
                          ?>       
                              <option data-type="<?=htmlspecialchars(strtolower($r['customer_type']))?>" <?=@($voucher['customer_id2']==$r['customer_id'])?"selected":""?> value="<?=$r['customer_id']?>"><?=$r['customer_name']?></option>
                          <?php endwhile; 
                          if ($prev_type !== null): ?>
                              </optgroup>
                          <?php endif; ?>
                          </select>
                          <div class="input-group-prepend">
                          <span class="input-group-text" id="basic-addon1">Balance :<span id="to_account_bl">0</span> </span>
                        </div>
                      </div>
                      </div>
                      <div class="col-sm-2 text-right">Credit
                      </div>

                      <div class="col-sm-4">
                        <div class="input-group">
                          <input type="text" readonly value="<?=!empty($voucher['voucher_amount']) ? number_format(floatval($voucher['voucher_amount'])) : ''?>" id="voucher_credit1" name="voucher_credit" class="form-control font-weight-bold" required placeholder="0">
                          <div class="input-group-append">
                            <span class="input-group-text font-weight-bold">RS</span>
                          </div>
                        </div>
                      </div>
                    
                    </div>
                    <!-- Invoices Section (Only shown if From Account role is customer) -->
                    <div class="form-group row mb-2" id="voucher_invoices_section" style="display:none;">
                      <div class="col-sm-2 text-right">Invoices</div>
                      <div class="col-sm-10">
                        <table class="table table-sm table-bordered mb-1" id="voucher_invoices_table" style="background:#fff;">
                          <thead class="thead-light">
                            <tr>
                              <th style="width: 22%; padding: 4px 8px;">Invoice #</th>
                              <th style="width: 11%; padding: 4px 8px;">Party PO</th>
                              <th style="width: 18%; padding: 4px 8px;">Total Amount</th>
                              <th style="width: 12%; padding: 4px 8px;">WHT %</th>
                              <th style="width: 16%; padding: 4px 8px;">WHT Amount</th>
                              <th style="width: 16%; padding: 4px 8px;">Amount Received <small class="text-muted">(After WHT)</small></th>
                              <th style="width: 5%; padding: 4px 8px; text-align: center;">
                                <button type="button" class="btn btn-sm btn-admin py-0 px-2" style="font-size:11px;" onclick="addInvoiceRow()" title="Add another invoice">
                                  <i class="fa fa-plus"></i> Add
                                </button>
                              </th>
                            </tr>
                          </thead>
                          <tbody id="voucher_invoices_tbody">
                            <?php
                            $existing_invoices = [];
                            if (isset($_REQUEST['id']) && !empty($voucher['voucher_id'])) {
                              $v_id = $voucher['voucher_id'];
                              $q_v_inv = mysqli_query($dbc, "SELECT vi.*, COALESCE(c.customer_name, c2.customer_name) AS customer_name FROM voucher_invoices vi LEFT JOIN tax_sales ts ON (vi.invoice_id = ts.invoice_no OR vi.invoice_id = ts.tax_sale_id) LEFT JOIN customers c ON ts.customer_id = c.customer_id LEFT JOIN vouchers v ON vi.voucher_id = v.voucher_id LEFT JOIN customers c2 ON v.customer_id1 = c2.customer_id WHERE vi.voucher_id = '$v_id'");
                              while ($row_vi = mysqli_fetch_assoc($q_v_inv)) {
                                $existing_invoices[] = $row_vi;
                              }
                            }
                            if (!empty($existing_invoices)):
                              foreach ($existing_invoices as $ei):
                            ?>
                            <tr class="v_inv_row">
                              <td>
                                <input type="text" class="form-control form-control-sm" name="invoice_id[]" value="<?=$ei['invoice_id']?>" readonly>
                              </td>
                              <td>
                                <input type="text" name="inv_party_po[]" class="form-control form-control-sm v_inv_po" value="<?=$ei['party_po']?>" readonly placeholder="PO">
                              </td>
                              <td>
                                <div class="input-group input-group-sm">
                                  <input type="text" name="invoice_amount[]" value="<?=!empty($ei['invoice_amount']) ? number_format(floatval($ei['invoice_amount'])) : ''?>" class="form-control form-control-sm v_inv_amount" readonly placeholder="0" onblur="formatInputOnBlur(this)" oninput="onTotalAmountChange(this)">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <div class="input-group input-group-sm">
                                  <input type="number" step="any" min="0" max="100" name="tax_percent[]" value="<?=(isset($ei['tax_percent']) && $ei['tax_percent'] !== '') ? $ei['tax_percent'] : '5.5'?>" class="form-control form-control-sm v_inv_tax_percent" placeholder="5.5" oninput="onTaxPercentChange(this)">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">%</span>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <div class="input-group input-group-sm">
                                  <input type="text" name="tax_amount[]" value="<?=!empty($ei['tax_amount']) ? number_format(floatval($ei['tax_amount'])) : '0'?>" class="form-control form-control-sm v_inv_tax text-danger" placeholder="0" onblur="formatInputOnBlur(this)" oninput="onTaxAmountChange(this)">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <div class="input-group input-group-sm">
                                  <input type="text" name="received_amount[]" value="<?=!empty($ei['received_amount']) ? number_format(floatval($ei['received_amount'])) : ''?>" class="form-control form-control-sm v_inv_received font-weight-bold text-success" placeholder="0" onblur="formatInputOnBlur(this)" oninput="onRowReceivedChange(this)">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeInvoiceRow(this)" title="Remove"><i class="fa fa-trash"></i></button>
                              </td>
                            </tr>
                            <?php
                              endforeach;
                            else:
                            ?>
                            <tr class="v_inv_row">
                              <td>
                                <div class="input-group input-group-sm">
                                  <select class="form-control form-control-sm v_inv_select" name="invoice_id[]" onchange="onRowInvoiceChange(this)">
                                    <option value="">-- Select Invoice --</option>
                                    <?php
                                    $selected_cust_id = !empty($voucher['customer_id1']) ? $voucher['customer_id1'] : 0;
                                    if (!empty($selected_cust_id)):
                                      $q_initial_inv = mysqli_query($dbc, "SELECT ts.*, c.customer_name FROM tax_sales ts LEFT JOIN customers c ON ts.customer_id = c.customer_id WHERE ts.customer_id = '$selected_cust_id' ORDER BY ts.tax_sale_id DESC");
                                      while ($r_inv = mysqli_fetch_assoc($q_initial_inv)):
                                        $inv_num = !empty($r_inv['invoice_no']) ? $r_inv['invoice_no'] : $r_inv['tax_sale_id'];
                                        $pName = !empty($r_inv['po_no']) ? ' (PO: ' . htmlspecialchars($r_inv['po_no']) . ')' : '';
                                      ?>
                                      <option value="<?=$inv_num?>" data-customer="<?=htmlspecialchars($r_inv['customer_name'] ?? '')?>" data-po="<?=htmlspecialchars($r_inv['po_no'] ?? '')?>" data-total="<?=$r_inv['grand_total']?>" data-due="<?=$r_inv['grand_total']?>">
                                        Invoice #<?=$inv_num?><?=$pName?> - Due: <?=number_format($r_inv['grand_total'])?>
                                      </option>
                                      <?php endwhile;
                                    endif;
                                    ?>
                                  </select>
                                  <input type="text" class="form-control form-control-sm v_inv_manual" placeholder="Inv #" style="display:none;" onkeyup="onManualInvChange(this)">
                                  <div class="input-group-append">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="toggleManualRow(this)" title="Type invoice number manually" style="font-size:11px;">
                                      <i class="fa fa-pencil-alt"></i>
                                    </button>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <input type="text" name="inv_party_po[]" class="form-control form-control-sm v_inv_po" readonly placeholder="PO">
                              </td>
                              <td>
                                <div class="input-group input-group-sm">
                                  <input type="text" name="invoice_amount[]" class="form-control form-control-sm v_inv_amount" readonly placeholder="0" onblur="formatInputOnBlur(this)" oninput="onTotalAmountChange(this)">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <div class="input-group input-group-sm">
                                  <input type="number" step="any" min="0" max="100" name="tax_percent[]" class="form-control form-control-sm v_inv_tax_percent" value="5.5" placeholder="5.5" oninput="onTaxPercentChange(this)">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">%</span>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <div class="input-group input-group-sm">
                                  <input type="text" name="tax_amount[]" class="form-control form-control-sm v_inv_tax text-danger" placeholder="0" onblur="formatInputOnBlur(this)" oninput="onTaxAmountChange(this)">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <div class="input-group input-group-sm">
                                  <input type="text" name="received_amount[]" class="form-control form-control-sm v_inv_received font-weight-bold text-success" placeholder="0" onblur="formatInputOnBlur(this)" oninput="onRowReceivedChange(this)">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeInvoiceRow(this)" title="Remove"><i class="fa fa-trash"></i></button>
                              </td>
                            </tr>
                            <?php endif; ?>
                          </tbody>
                          <tfoot style="background: #f8f9fa;">
                            <tr style="font-weight: 600;">
                              <td colspan="2" class="text-right" style="padding: 6px 8px; vertical-align: middle;">Total:</td>
                              <td style="padding: 4px 8px;">
                                <div class="input-group input-group-sm">
                                  <input type="text" id="v_inv_total_sum" class="form-control form-control-sm font-weight-bold" readonly style="background:#e9ecef;" value="0">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td></td>
                              <td style="padding: 4px 8px;">
                                <div class="input-group input-group-sm">
                                  <input type="text" id="v_inv_tax_sum" class="form-control form-control-sm font-weight-bold text-danger" readonly style="background:#e9ecef;" value="0">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td style="padding: 4px 8px;">
                                <div class="input-group input-group-sm">
                                  <input type="text" id="v_inv_received_sum" class="form-control form-control-sm font-weight-bold text-success" readonly style="background:#e9ecef;" value="0">
                                  <div class="input-group-append">
                                    <span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>
                                  </div>
                                </div>
                              </td>
                              <td></td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                  <div class="form-group ">
                       <div class="row">
                         
                      <div class="col-sm-2 text-right">Hint
                      </div>
                      <div class="col-sm-10 mx-auto">
                        <input type="text" name="voucher_hint" class="form-control" value="<?=@$voucher['voucher_hint']?>" >
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
                      <th>Invoices</th>
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
                          <td>
                            <?php
                            $q_vi_list = mysqli_query($dbc, "SELECT invoice_id, received_amount FROM voucher_invoices WHERE voucher_id = '".$r['voucher_id']."'");
                            $vi_badges = [];
                            while ($vi_row = mysqli_fetch_assoc($q_vi_list)) {
                              $vi_badges[] = '<span class="badge badge-info">#' . $vi_row['invoice_id'] . ' (' . number_format(floatval($vi_row['received_amount'])) . ')</span>';
                            }
                            echo !empty($vi_badges) ? implode(' ', $vi_badges) : '<span class="text-muted">-</span>';
                            ?>
                          </td>
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

<script type="text/javascript">
var customerInvoicesData = {};
var invoiceOptionsHtml = '<option value="">-- Select Invoice --</option>';

function checkCustomerRole(isInit) {
  var $fromSel = $('#voucher_from_account');
  var $opt = $fromSel.find(':selected');
  var role = ($opt.data('type') || '').toString().toLowerCase();
  var accountId = $fromSel.val();
  var prevAccountId = $fromSel.data('prev-account');
  
  if (role === 'customer' && accountId) {
    $('#voucher_invoices_section').show();
    $('#voucher_invoices_section :input').prop('disabled', false);
    
    // Clear previous customer selections if user switches customer
    if (!isInit && prevAccountId && prevAccountId !== accountId) {
      $('#voucher_invoices_tbody tr').each(function(idx) {
        if (idx === 0) {
          $(this).find('.v_inv_select').val('');
          $(this).find('.v_inv_po').val('');
          $(this).find('.v_inv_amount').val('');
          $(this).find('.v_inv_received').val('').data('base-val', 0);
          $(this).find('.btn_deduct_tax').removeClass('active btn-info text-white').addClass('btn-outline-info');
        } else {
          $(this).remove();
        }
      });
      calcVoucherInvoicesTotal();
    }
    
    $fromSel.data('prev-account', accountId);
    loadCustomerInvoices(accountId);
  } else {
    $('#voucher_invoices_section').hide();
    $('#voucher_invoices_section :input').prop('disabled', true);
  }
}

function onAccountChange(accountId) {
  checkCustomerRole(false);
}

function loadCustomerInvoices(customerId) {
  var voucherId = $('input[name="voucher_id"]').val() || 0;
  
  if (!customerId || customerId === 'all') {
    invoiceOptionsHtml = '<option value="">-- Select Invoice --</option>';
    $('.v_inv_select').html(invoiceOptionsHtml);
    return;
  }
  
  $.ajax({
    url: 'php_action/custom_action.php',
    type: 'POST',
    data: { get_customer_invoices: customerId, voucher_id: voucherId },
    dataType: 'json',
    success: function(res) {
      if (res.sts === 'success') {
        customerInvoicesData = {};
        var options = '<option value="">-- Select Invoice --</option>';
        
        if (res.data && res.data.length > 0) {
          for (var i = 0; i < res.data.length; i++) {
            var inv = res.data[i];
            customerInvoicesData[inv.invoice_id] = inv;
            var poText = inv.party_po ? ' (PO: ' + inv.party_po + ')' : '';
            options += '<option value="' + inv.invoice_id + '" data-customer="' + (inv.customer_name || '') + '" data-po="' + (inv.party_po || '') + '" data-total="' + inv.grand_total + '" data-due="' + inv.due + '">';
            options += 'Invoice #' + inv.invoice_id + poText + ' - Due: ' + Number(inv.due).toLocaleString();
            options += '</option>';
          }
        } else {
          options += '<option value="" disabled>No invoices found for this customer</option>';
        }
        
        invoiceOptionsHtml = options;
        
        // Update all select dropdowns
        $('.v_inv_select').each(function() {
          var curVal = $(this).val();
          $(this).html(invoiceOptionsHtml);
          if (curVal && customerInvoicesData[curVal]) {
            $(this).val(curVal);
          }
        });
      }
    }
  });
}

function cleanNumber(val) {
  if (val === undefined || val === null || val === '') return 0;
  var cleaned = val.toString().replace(/,/g, '').trim();
  var num = parseFloat(cleaned);
  return isNaN(num) ? 0 : num;
}

function formatNumberWithCommas(val) {
  if (val === undefined || val === null || val === '') return '';
  var numStr = val.toString().replace(/,/g, '').trim();
  var parts = numStr.split('.');
  var integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  if (parts.length > 1) {
    return integerPart + '.' + parts[1];
  }
  return integerPart;
}

function formatInputOnBlur(input) {
  var num = cleanNumber($(input).val());
  if (num > 0 || $(input).val().trim() !== '') {
    $(input).val(formatNumberWithCommas(num));
  }
}

function onTaxPercentChange(input) {
  var $row = $(input).closest('tr');
  var totalVal = cleanNumber($row.find('.v_inv_amount').val());
  var pctVal = parseFloat($(input).val()) || 0;
  var tax = parseFloat((totalVal * (pctVal / 100)).toFixed(2));
  var netReceived = parseFloat((totalVal - tax).toFixed(2));
  if (netReceived < 0) netReceived = 0;
  $row.find('.v_inv_tax').val(formatNumberWithCommas(tax));
  $row.find('.v_inv_received').val(formatNumberWithCommas(netReceived));
  calcVoucherInvoicesTotal();
}

function onTaxAmountChange(input) {
  var $row = $(input).closest('tr');
  var totalVal = cleanNumber($row.find('.v_inv_amount').val());
  var taxVal = cleanNumber($(input).val());
  if (totalVal > 0) {
    var pct = parseFloat(((taxVal / totalVal) * 100).toFixed(2));
    var netReceived = parseFloat((totalVal - taxVal).toFixed(2));
    if (netReceived < 0) netReceived = 0;
    $row.find('.v_inv_tax_percent').val(pct);
    $row.find('.v_inv_received').val(formatNumberWithCommas(netReceived));
  }
  calcVoucherInvoicesTotal();
}

function onTotalAmountChange(input) {
  var $row = $(input).closest('tr');
  var totalVal = cleanNumber($(input).val());
  var pctVal = parseFloat($row.find('.v_inv_tax_percent').val()) || 0;
  var tax = parseFloat((totalVal * (pctVal / 100)).toFixed(2));
  var netReceived = parseFloat((totalVal - tax).toFixed(2));
  if (netReceived < 0) netReceived = 0;
  $row.find('.v_inv_tax').val(formatNumberWithCommas(tax));
  $row.find('.v_inv_received').val(formatNumberWithCommas(netReceived));
  calcVoucherInvoicesTotal();
}

function onRowReceivedChange(input) {
  var $input = $(input);
  $input.data('base-val', cleanNumber($input.val()));
  calcVoucherInvoicesTotal();
}

function addInvoiceRow() {
  var rowHtml = '<tr class="v_inv_row">' +
    '<td>' +
      '<div class="input-group input-group-sm">' +
        '<select class="form-control form-control-sm v_inv_select" name="invoice_id[]" onchange="onRowInvoiceChange(this)">' +
          invoiceOptionsHtml +
        '</select>' +
        '<input type="text" class="form-control form-control-sm v_inv_manual" placeholder="Inv #" style="display:none;" onkeyup="onManualInvChange(this)">' +
        '<div class="input-group-append">' +
          '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="toggleManualRow(this)" title="Type invoice number manually" style="font-size:11px;">' +
            '<i class="fa fa-pencil-alt"></i>' +
          '</button>' +
        '</div>' +
      '</div>' +
    '</td>' +
    '<td>' +
      '<input type="text" name="inv_party_po[]" class="form-control form-control-sm v_inv_po" readonly placeholder="PO">' +
    '</td>' +
    '<td>' +
      '<div class="input-group input-group-sm">' +
        '<input type="text" name="invoice_amount[]" class="form-control form-control-sm v_inv_amount" readonly placeholder="0" onblur="formatInputOnBlur(this)" oninput="onTotalAmountChange(this)">' +
        '<div class="input-group-append">' +
          '<span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>' +
        '</div>' +
      '</div>' +
    '</td>' +
    '<td>' +
      '<div class="input-group input-group-sm">' +
        '<input type="number" step="any" min="0" max="100" name="tax_percent[]" class="form-control form-control-sm v_inv_tax_percent" value="5.5" placeholder="5.5" oninput="onTaxPercentChange(this)">' +
        '<div class="input-group-append">' +
          '<span class="input-group-text" style="font-size:11px; padding: 2px 6px;">%</span>' +
        '</div>' +
      '</div>' +
    '</td>' +
    '<td>' +
      '<div class="input-group input-group-sm">' +
        '<input type="text" name="tax_amount[]" class="form-control form-control-sm v_inv_tax text-danger" placeholder="0" onblur="formatInputOnBlur(this)" oninput="onTaxAmountChange(this)">' +
        '<div class="input-group-append">' +
          '<span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>' +
        '</div>' +
      '</div>' +
    '</td>' +
    '<td>' +
      '<div class="input-group input-group-sm">' +
        '<input type="text" name="received_amount[]" class="form-control form-control-sm v_inv_received font-weight-bold text-success" placeholder="0" onblur="formatInputOnBlur(this)" oninput="onRowReceivedChange(this)">' +
        '<div class="input-group-append">' +
          '<span class="input-group-text" style="font-size:11px; padding: 2px 6px;">RS</span>' +
        '</div>' +
      '</div>' +
    '</td>' +
    '<td class="text-center">' +
      '<button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeInvoiceRow(this)" title="Remove"><i class="fa fa-trash"></i></button>' +
    '</td>' +
  '</tr>';
  
  $('#voucher_invoices_tbody').append(rowHtml);
}

function toggleManualRow(btn) {
  var $cell = $(btn).closest('td');
  var $row = $(btn).closest('tr');
  var $select = $cell.find('.v_inv_select');
  var $input = $cell.find('.v_inv_manual');
  var $poInput = $row.find('.v_inv_po');
  var $amountInput = $row.find('.v_inv_amount');
  var $icon = $(btn).find('i');
  
  if ($select.is(':visible')) {
    $select.hide().removeAttr('name');
    $input.show().attr('name', 'invoice_id[]').focus();
    $poInput.prop('readonly', false);
    $amountInput.prop('readonly', false);
    $icon.removeClass('fa-pencil-alt').addClass('fa-list');
  } else {
    $input.hide().removeAttr('name');
    $select.show().attr('name', 'invoice_id[]');
    $poInput.prop('readonly', true);
    $amountInput.prop('readonly', true);
    $icon.removeClass('fa-list').addClass('fa-pencil-alt');
    onRowInvoiceChange($select[0]);
  }
}

function onManualInvChange(input) {
  calcVoucherInvoicesTotal();
}

function onRowInvoiceChange(sel) {
  var $row = $(sel).closest('tr');
  var invId = $(sel).val();
  
  if (!invId) {
    $row.find('.v_inv_po').val('');
    $row.find('.v_inv_amount').val('');
    $row.find('.v_inv_tax').val('0');
    $row.find('.v_inv_received').val('').data('base-val', 0);
    calcVoucherInvoicesTotal();
    return;
  }
  
  var $opt = $(sel).find(':selected');
  var po = $opt.data('po') || '';
  var total = cleanNumber($opt.data('total'));
  var pct = parseFloat($row.find('.v_inv_tax_percent').val()) || 5.5;
  var tax = parseFloat((total * (pct / 100)).toFixed(2));
  var netReceived = parseFloat((total - tax).toFixed(2));
  if (netReceived < 0) netReceived = 0;
  
  $row.find('.v_inv_po').val(po);
  $row.find('.v_inv_amount').val(formatNumberWithCommas(total));
  $row.find('.v_inv_tax').val(formatNumberWithCommas(tax));
  $row.find('.v_inv_received').val(formatNumberWithCommas(netReceived)).data('base-val', netReceived);
  
  calcVoucherInvoicesTotal();
}

function removeInvoiceRow(btn) {
  var rowCount = $('#voucher_invoices_tbody tr').length;
  if (rowCount > 1) {
    $(btn).closest('tr').remove();
  } else {
    var $row = $(btn).closest('tr');
    $row.find('.v_inv_select').val('');
    $row.find('.v_inv_manual').val('');
    $row.find('.v_inv_po').val('');
    $row.find('.v_inv_amount').val('');
    $row.find('.v_inv_tax_percent').val('5.5');
    $row.find('.v_inv_tax').val('0');
    $row.find('.v_inv_received').val('').data('base-val', 0);
  }
  calcVoucherInvoicesTotal();
}

function onDebitInput(input) {
  var rawVal = $(input).val();
  var num = cleanNumber(rawVal);
  var formatted = num > 0 ? formatNumberWithCommas(num) : rawVal;
  $('#voucher_credit1').val(formatted);
}

function calcVoucherInvoicesTotal() {
  var sumTotal = 0;
  var sumTax = 0;
  var sumReceived = 0;
  var hasActiveInvoices = false;
  
  $('.v_inv_row').each(function() {
    var invId = $(this).find('input[name="invoice_id[]"]').val() || $(this).find('select[name="invoice_id[]"]').val();
    var totalAmt = cleanNumber($(this).find('.v_inv_amount').val());
    var taxAmt = cleanNumber($(this).find('.v_inv_tax').val());
    var recAmt = cleanNumber($(this).find('.v_inv_received').val());
    
    if (invId) {
      sumTotal += totalAmt;
      sumTax += taxAmt;
      sumReceived += recAmt;
      if (totalAmt > 0 || recAmt > 0) {
        hasActiveInvoices = true;
      }
    }
  });
  
  $('#v_inv_total_sum').val(formatNumberWithCommas(sumTotal));
  $('#v_inv_tax_sum').val(formatNumberWithCommas(sumTax));
  $('#v_inv_received_sum').val(formatNumberWithCommas(sumReceived));
  
  if (hasActiveInvoices) {
    var finalAmount = sumTotal > 0 ? sumTotal : sumReceived;
    if (finalAmount > 0) {
      var formattedVal = formatNumberWithCommas(finalAmount);
      $('#voucher_debit').val(formattedVal).data('base-val', finalAmount);
      $('#voucher_credit1').val(formattedVal);
    }
  } else {
    var $fromSel = $('#voucher_from_account');
    var role = ($fromSel.find(':selected').data('type') || '').toString().toLowerCase();
    if (role === 'customer') {
      $('#voucher_debit').val('').data('base-val', 0);
      $('#voucher_credit1').val('');
    }
  }
}

function resetVoucherInvoices() {
  $('#voucher_invoices_tbody').empty();
  addInvoiceRow();
}

$(document).ready(function() {
  var $fromSel = $('#voucher_from_account');
  var $opt = $fromSel.find(':selected');
  var role = ($opt.data('type') || '').toString().toLowerCase();
  var accountId = $fromSel.val();
  var hasExistingInvoices = <?= (!empty($existing_invoices) ? 'true' : 'false') ?>;
  
  if ((role === 'customer' && accountId) || hasExistingInvoices) {
    $('#voucher_invoices_section').show();
    $('#voucher_invoices_section :input').prop('disabled', false);
    loadCustomerInvoices(accountId);
    if ($('#voucher_invoices_tbody tr').length === 0) {
      addInvoiceRow();
    } else {
      calcVoucherInvoicesTotal();
    }
  } else {
    $('#voucher_invoices_section').hide();
    $('#voucher_invoices_section :input').prop('disabled', true);
  }
});
</script>