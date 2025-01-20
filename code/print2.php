<?php

//$con = mysqli_connect('localhost', 'root', 'stranger', 'ecomindia');
$con = mysqli_connect('35.188.94.187:3306', 'root2', 'stranger', 'mydb');

// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }

$sql="SELECT *,FORMAT(igst_amount, 2) as igst,FORMAT(cgst_amount, 2) as cgst,FORMAT(sgst_amount, 2) as sgst, concat(RIGHT(design_id,5),' - ',SUBSTRING_INDEX(product_id,'-',-1)) as prd, DATE_FORMAT(t.invoice_date,'%d-%m-%Y') AS dt FROM txn t  INNER JOIN cust c ON t.txnid=c.txnid WHERE t.status='success'";
$result=mysqli_query($con,$sql);

//<br><b>(CALL TO THE PARTY)</b>
?>
	


<!DOCTYPE html>  
<html>
  <head>
    <style>
    * {
    box-sizing: border-box;
    }
    body {
    margin: 0;
    }
    /* Create two equal columns that floats next to each other */
    .column {
    float: left;
    width: 60%;
    /*padding: 10px;*/
    font-size: 14px;
    }
    /* Clear floats after the columns */
    .row:after {
    content: "";
    display: table;
    clear: both;
    }
    #main
    {
      width:500px;
      text-align: center;
      font-family: arial;
      margin: 0 auto;
    }
    h3
    {
      font-size: 11px;
    font-weight: 100;
    margin: 20px 0px 10px 0px;
    }
    p
    {
      text-align: left;
      margin: 5px;
    }
    .column1
    {
      font-size: 13px;
      width: 20%;
      border-right: 0px solid black !important;
      padding: 5px 0px;
      padding-left: 10px;
    }
    .column2
    {     
      width: 77%;
      font-size: 20px;
      padding-left: 10px;
      line-height: 25px;
      background-image: url(ss.jpg);
    background-repeat: no-repeat;
    background-size: 150px;
    background-position: top right;
    }
    @media print
{
.row {page-break-before:always}
}
    </style>
  </head>
  <body>
    <div id="main">
      <?php $i = 1; while($row =mysqli_fetch_array($result,MYSQLI_ASSOC)) {
     if ($i>=1 && $i<=50){ 
      echo
      '<div class="row">
        <h3> E-India, M Nagar, Kadapa, Andhra Pradesh - 516001-</h3>
        <div class="column column1">
          <p style="font-size:15px">'.$i.'</p>
          <p style="font-size:10px">' .$row["prd"].'</p>
          <p>'.$row["item_qty"].'</p>
          <p>.</p>
          <p>.</p>
          <p>.</p>
          <p>.</p>
          <p>.</p>
          <p>.</p>
          <p>.</p>
        </div>
        <div class="column column2">
          <br/>
          <p style="font-size:16px"><b>To</b></p>
          <p><b>'.$row["customer_name"].'</b></p>
          <p style="/*text-align: justify;*/">'.$row["customer_address"].', '.$row["customer_city"].',<br/>'.$row["customer_state"].' - '.$row["customer_pincode"].'<br/>Phone : '.$row["customer_id"].'</p>
        </div>
      </div>';  } $i++;} ?>
    </div>
    </body>
  </html>