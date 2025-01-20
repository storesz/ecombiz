<?php

$curl = curl_init();

$sender = "TSHIRT";
$route = "4";
$mobiles = "91"."7373370770";
$authkey = "170174AD5RqE09y59945a7c";
$country = "91";
$message = "Your tshirt order delivery has been postponed to (on or before) 27th October. Delayed due to heavy demand. Sorry for inconvenience.";

//$message = "Your T-shirt Order no.9052 is pending. Try again by clicking the link : https://goo.gl/gqiJgV";

//$message = "Your Order no. 3085 has been placed successfully. T-shirt will be dispatched tomorrow and will be delivered by 9-10th October";


/*$message = "Your order has been placed successfully. T-shirt will be dispatched tonight and will be delivered by 10-12th January";

/*Your T-shirt order is successfully dispatched (via DTDC). You will receive it by 9th December.*/
/*
$con = mysqli_connect('localhost', 'root', 'stranger', 'ecomindia');
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }
$sql="SELECT *,FORMAT(igst_amount, 2) as igst,FORMAT(cgst_amount, 2) as cgst,FORMAT(sgst_amount, 2) as sgst, concat(RIGHT(design_id,5),' - ',SUBSTRING_INDEX(product_id,'-',-1)) as prd, DATE_FORMAT(t.invoice_date,'%d-%m-%Y') AS dt FROM txn t INNER JOIN cust c ON t.txnid=c.txnid WHERE t.status ='success'";
$result=mysqli_query($con,$sql);

*/
curl_setopt_array($curl, array(
  CURLOPT_URL => "http://api.msg91.com/api/sendhttp.php?sender=".$sender."&route=".$route."&mobiles=".$mobiles."&authkey=".$authkey."&country=".$country."&message=".$message,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_SSL_VERIFYHOST => 0,
  CURLOPT_SSL_VERIFYPEER => 0,
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}
?>