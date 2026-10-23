<!DOCTYPE html>
<html lang="en">
<?php include_once 'includes/head.php'; ?>
<style type="text/css">
  thead tr th{
    font-size: 19px !important;
    font-weight: bolder !important;
    color: #000 !important;
  }
 tbody tr th ,tbody tr td,tbody tr th span,tfoot tr th{
    font-size: 18px !important;
    font-weight: bolder !important;
      color: #000 !important;
  }
    
</style>
  <body class="horizontal light  ">
    <div class="wrapper">
  <?php include_once 'includes/header.php'; ?>
      <main role="main" class="main-content">
        <div class="container-fluid">
          <div class="card">
            <div class="card-header card-bg" align="center">

            <div class="row">
              <div class="col-12 mx-auto h4">
                <b class="text-center card-text">Trail Balance</b>
           
             
              </div>
            </div>
  
          </div>
           <div class="card-body">


  
    



    <form action="" method="post" class="d-print-none" >



      <div class="row  ">



      <div class="form-group col-sm-3"></div><!-- group -->
  <div class="form-group col-sm-3 ">



        <label for="">From Date</label>



        <input type="date" name="from_date" class="form-control">



                                             

                                            

      </div>
  <div class="form-group col-sm-3 ">



        <label for="">To Date</label>



        <input type="date" name="to_date" class="form-control">



                                             

                                            

      </div>

      



      <div class="form-group col-sm-3 d-print-none" >



        <br />



        <button class="mt-2 btn btn-admin float-right" name="genealledger" type="submit">Search</button>
        <button class="mt-2 btn btn-admin2 float-right" onclick="window.print();" style="margin-right: 15px;">Print Report</button>


      </div><!-- group -->



      </div>



    </form>



  
  


  <?php 







  
      



?>
  <div class="row">
    <div class="col-12">
      <table class="table table-bordered table-striped" style="width: 100%">
        



    



      <?php



      //echo  DateFormat($f_date , '%Y-%m-%d');



       ?>



       <center>



        <?php if (isset($_REQUEST['from_date'])): ?>
          <h4 class="d-inline"><b>From :</b><?=@$_REQUEST['from_date']?></h4>
        <h4 class="d-inline ml-3"><b>To :</b><?=@$_REQUEST['to_date']?></h4>
        <?php endif ?>
     

       </center>
<thead>
     <tr>



        <th>Account</th>



        <th>Debit</th>

        <th>Credit</th>

      


        <!-- <th>Test</th> -->



      </tr>

</thead>


    <tbody>
<?php
$net_debit=$net_credit=0;
$customersQ = mysqli_query($dbc,"SELECT * FROM customers WHERE  customer_status=1 ");
while ($customerR=mysqli_fetch_assoc($customersQ)):
 
        $customer=$customerR['customer_id'];

    if (!empty($_REQUEST['from_date']) AND !empty($_REQUEST['to_date'])) {
      $sql = "SELECT * FROM transactions WHERE  customer_id='$customer' AND transaction_add_date BETWEEN '".$_REQUEST['from_date']."' AND '".$_REQUEST['to_date']."' ";
    }else if (!empty($_REQUEST['from_date']) AND empty($_REQUEST['to_date'])) {
      $sql = "SELECT * FROM transactions WHERE  customer_id='$customer' AND transaction_add_date = '".$_REQUEST['from_date']."' ";
    }else{
    
      $sql = "SELECT * FROM transactions WHERE  customer_id='$customer' ";

    }
  $result = mysqli_query($dbc, $sql);



   $total_debit=$total_credit=$temp=0;

  if ( mysqli_num_rows($result) > 0) :
    while($row = mysqli_fetch_array($result)):
      @$total_debit += $row['debit'];
      @$total_credit+= $row['credit'];
      $remaing_balance = (@(int)$row['credit']-@(int)$row['debit'])+$temp;
      $temp=(@(int)$row['credit']-@(int)$row['debit'])+$temp; 

 endwhile;
  else:
  $total_debit=0;
  $total_credit=0;

  endif;
  $net_debit+=$total_debit;
  $net_credit+=$total_credit;
   ?>
      <tr>
        <td><?=$customerR['customer_name']?></td>

    


        <td class="text-primary font-weight-bold"><?=$total_debit?></td>



        <td class="text-success font-weight-bold"><?=$total_credit?></td>
      </tr>

 <?php    endwhile; ?>
    </tbody>
    <tfoot>
      
    <tr >





        <th>Total</th>



        <th><span class="badge badge-primary p-2 font-weight-bold"><?=$net_debit?></span></th>



        <th><span class="badge badge-success p-2 font-weight-bold"><?=$net_credit?></span> </th>

    

      </tr>


    </tfoot>




   

<hr/>










      </table>
    </div>
  </div>


 
 



      


























<script>



  $( function() {



    var dateFormat = "yy-mm-dd";



      from = $( "#from" )



        .datepicker({



          changeMonth: true,



          numberOfMonths: 1,



          dateFormat : "yy-mm-dd",



        })



        .on( "change", function() {



          to.datepicker( "option", "minDate", getDate( this ) );



        }),



      to = $( "#to" ).datepicker({



        changeMonth: true,



        numberOfMonths: 1,



        dateFormat : "yy-mm-dd",



      })



      .on( "change", function() {



        from.datepicker( "option", "maxDate", getDate( this ) );



      });







    function getDate( element ) {



      var date;



      try {



        date = $.datepicker.parseDate( dateFormat, element.value );



      } catch( error ) {



        date = null;



      }







      return date;



    }



  } );



  </script>
           </div>
          </div> <!-- .row -->
        </div> <!-- .container-fluid -->
       
      </main> <!-- main -->
    </div> <!-- .wrapper -->
    
  </body>
</html>
<?php include_once 'includes/foot.php'; ?>