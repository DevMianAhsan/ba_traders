
<?php include_once 'includes/head.php'; 
$invoice =  fetchRecord($dbc,"invoices","invoice_id",$_REQUEST['invoice']);
$saleorder =  fetchRecord($dbc,"purchase_order","po_partypo",$invoice['party_po']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page-container {
      padding: 30px;
    }

    @font-face {
      font-family: tahoma;
      src: url(./fonts/Tahoma\ Regular\ font.ttf);
    }

    @font-face {
      font-family: tahomabold;
      src: url(./fonts/TAHOMAB0.TTF);
    }

    * {
      margin: 0;
      padding: 0;
      font-family: tahoma;
    }

    .top-sec {
      display: flex;
      margin-top: 1rem;
      justify-content: space-between;
    }

    table {
      border-collapse: collapse;
      text-align: center;
      border: 1px solid black;
      width: 100%;
      column-gap: 80px;

    }

    th,td {
      border: 2px solid black;
      padding: 5px 0px;
    }

    thead th {
      background-color: #AFCDEA;
      padding: 10px 0px !important;
    }

    tbody {
      border-left: 2px solid black;
    }

    tfoot th {
      padding-top: 5px;
      
    }


    .order-det {
      display: flex;
      justify-content: space-between;
      margin-top: 4rem;
    }




    .order-n-d p {
      border: 1px solid #111111;
      text-align: right;
      margin-top: 8px;
      font-weight: bold;
    }

    .order-n-d h4 {
      font-weight: bold;
      margin-top: 2px;
    }







    .sign-sec h4 {
      margin-top: 5rem;
      border: 1px solid black;
      font-weight: bold;
      width: 180px;
      text-align: center;
      padding: 4px 0;
    }
  </style>
</head>

<body>
  <div class="page-container">
    <div class="top-sec">
      <div class="com-det">
        <h2><?=strtoupper($get_company['name'])?></h2>
      </div>
      <div>
        <img style="width: 150px;"
          src="img/logo/<?=$get_company['logo']?>"
          alt="">
      </div>
      <div class="com-det">
        <h1>INVOICE</h1>
        <p style="text-align: right;"> Original</p>

      </div>
    </div>
    <div class="order-det">
      <div class="order-data" style="width: 70%;">
        <div style="padding-top: 2rem">
          <h4> Sold To:</h4>
          <h3> 
          	<?php
          	 echo    $newString = str_replace(',', '<br/>', strtoupper($saleorder['po_consumer']));
          	?>
          </h3>
        </div>
      </div>
      <div class="order-n-d">
        <h4>Challan Number</h4>
        <p><?=$invoice['invoice_id']?></p>
        <h4>Challan Date</h4>
        <p><?=date('D, d-m-Y',strtotime($invoice['invoice_date']))?></p>
      </div>
    </div>
    <div class="top-sec ">
      <h4>REMARKS: <span></span></h4>
      <h5><?=$saleorder['po_remarks']?></h5>
    </div>
    <div class="table-sec">
      <table>
        <thead>
          <tr>
            <th>PO #</th>
            <th>Challan #</th>
            <th style="width: 40%;">Item Description</th>
            <th>Quantity</th>
            <th>Rate</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
        	<?php
        	$res = json_decode($invoice['invoice_details']);
          $qtytotal = 0;
          $grandtotal = 0;
        	//print_r($res);
        	 $data =  $res->data;
         
        	for ($i=0; $i <  count($data) ; $i++) { 
        		$dcDetails = mysqli_query($dbc,"SELECT * FROM orders INNER JOIN order_item ON orders.order_id = order_item.order_id
              iNNER JOIN product ON product.product_id = order_item.product_id
               iNNER JOIN brands ON brands.brand_id = product.brand_id
                iNNER JOIN categories ON categories.categories_id = product.category_id
             WHERE orders.order_id = '".$data[$i]->dc_no."'");
        		while($r = mysqli_fetch_assoc($dcDetails)):
        			//print_r($r);
        	?>
        	<tr>
        		<td><?=$data[$i]->party_po?></td>
          <td><?=$data[$i]->dc_no?></td>
          <td><?=ucfirst($r['product_name'])?> <?=ucfirst($r['categories_name'])?> <?=ucfirst($r['brand_name'])?>  </td>
          <td><?=number_format($r['quantity'])?></td>
          <td><?=number_format($r['rate'],2)?></td>
          <td><?=number_format($r['total'],2)?></td>
        	</tr>
        	<?php
          $qtytotal +=$r['quantity'];
          $grandtotal += $r['total'];
       endwhile;
    }
        	?>
          <!-- <td></td>
          <td>2110</td>
          <td>39X27X3X8CM 7MM PVC POLY BAG WITH
            POCKET (18X13) CM</td>
          <td>1,650</td>
          <td>30.25</td>
          <td>49,912.50</td> -->
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" style="text-align: right; padding-right: 5px;">TOTAL:</th>
            <th><?=number_format($qtytotal)?></th>
            <th></th>
            <th><?=number_format($grandtotal,2)?></th>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="sign-sec">
      <h4>Signature</h4>
    </div>

  </div>
</body>

</html>