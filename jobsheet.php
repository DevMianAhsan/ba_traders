<!DOCTYPE html>
<html lang="en">

<head>

    <?php
include_once "includes/head.php";

$po_order =   $order=fetchRecord($dbc,"purchase_order","po_id",$_REQUEST['id']);

    ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JOb sheet</title>
  <link rel="stylesheet" href="style.css">
  <style>


* {
  margin: 0;
  padding: 0;
  font-family: Tahoma, Arial, sans-serif;
}

.top-sec {
  display: flex;
  margin-top: 2rem;
  justify-content: space-between;
}

.page-heading {
  display: flex;
  margin-top: 1rem;
}

.top-sec .com-det h3 {
  width: 8rem;
}
.top-sec .com-det span {
  border: none;
  border-bottom: 2px solid black;
  font-weight: bold;
  width: 7rem;
}

table {
  border-collapse: collapse;
  text-align: center;
  border: 1px solid black;
  margin: 4rem 1rem 0 1rem;
}
.table-sec thead th {
  border: 1px solid black;
  padding: 6px 10px;
  font-weight: bold;
}
.table-sec tbody th {
  padding: 10px;
  border-bottom: 1px solid black;
}
.table-sec tbody th {
  padding: 10px;
  border-bottom: 1px solid black;
}
.table-sec thead {
  background-color: #cdcbcbb6;
}
.page-note{
  margin-top: 1rem;
}
.page-note p {
  border-bottom: 1px solid #111111;
  padding: 10px;
  font-size : 20px;
  font-weight: bold;
}
@media print {
    body {
        font-family: Tahoma, sans-serif; /* Change the font for printing */
        background-color: transparent; /* Set a transparent background for printing */
        color: #000000; /* Change text color for printing */
    }

    .table-sec thead {
  background-color: #cdcbcbb6;
}
 


  </style>
</head>

<body>
  <div class="page-container" style="padding: 20px">
    <div class="top-sec">
      <div class="com-det">
        <h1><?=$get_company['name']?></h1>
        <div class="page-heading" style="margin-top: 2rem;">
          <h4>Order Number </h4>
           <span style="margin-left: 20px"> <?=$po_order['po_id']?></span>

        </div>
        <div class="page-heading">
          <h4>Party Po</h4>
          <span style="margin-left: 80px"><?=$po_order['po_partypo']?></span>
        </div>
      </div>
      <div class="">
        <img src="img/logo/<?=$get_company['logo']?>" style="width: 200px">
      </div>
      <div class="com-det">
        <h2>Job Sheet</h2>
        <div class="page-heading" style="margin-top: 2rem;">
          <h4>Date</h4>
          <span style=";margin-left: 95px"><?=$po_order['po_date']?></span>

        </div>

        <div class="page-heading">
          <h4>Target Date</h4>
          <span style="margin-left: 20px"><?=$po_order['po_targetdate']?></span>
        </div>
      </div>

    </div>
    <div class="table-sec">
      <table>
        <thead>
          <tr>
            <th style="width:10%">Party Ref</th>
            
            <th style="width:60%">Item Description</th>
            <th>Uom</th>
            <th style="width:1%">Qty</th>
          </tr>
        </thead>
        <tbody>
            <?php
            $q1 = mysqli_query($dbc,"SELECT * FROM purchase_order_item 
                INNER JOIN product ON product.product_id = purchase_order_item.product_id
                WHERE po_id = '$po_order[po_id]'
                ");
            while($r = mysqli_fetch_assoc($q1)): 
            ?>
          <tr>
            <th></th>
            <th><?=$r['product_name']?></th>
            
            <th>PCS</th>
            <th><?=$r['quantity']?></th>
            
          </tr>
          <?php
      endwhile;
          ?>
        </tbody>
      </table>
    </div>
    <div class="page-footer">
      <div class="page-note">
        <h2>Insturction</h2>
        <p ><?=$po_order['po_jobdesc'] ?> </p>
      
      </div>
      <div class="page-note">
        <h2>Remarks</h2>
        <p> <?=$po_order['po_remarks'] ?> </p>
        
      </div>
    </div>
  </div>
</body>

</html>

<script type="text/javascript">
    window.print()
</script>