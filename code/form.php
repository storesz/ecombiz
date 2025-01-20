<?php


$posted = array();

if(!empty($_POST))
{
  foreach($_POST as $vr => $value) 
  {    
    $posted[$vr] = $value; 
  }
}

$amount = $posted['amount'];
$firstname = $posted['firstname'];
$email = $posted['email'];
$phone = $posted['phone'];
$address1 = $posted['address1'];
$city = $posted['city'];
$state = $posted['state'];
$zipcode = $posted['zipcode'];
$size = $posted['size'];
$quantity = $posted['quantity'];
$pg = $posted['pg'];

$txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);

if($pg == "DC")
  {
    $key = "xpeODu"; 
    $salt = "PIuKOUT8";
    $servicepro = "";
    $gateway = "payubiz";
  }

if($pg == "NB" || $pg == "CC")
  {
    $key = "ekROVjFf"; 
    $salt = "tMTdXuMknH";
    $servicepro = "payu_paisa";
    $payment_gateway = "payumoney";
  }
if($pg == "CASH")
  {
    $gateway = "paytm";
  }

$array1 = preg_split( '/_/', $posted['productinfo']);
$productinfo = $array1[0];
$design_id = $array1[1].'-'.$size;

$item_rate = 380;
$item_discount = 0;
$taxable_value = ($item_rate*$quantity)-$item_discount;
if (strpos($state, 'Andhra') !== false) 
{
  $cgst_rate = 2.5;
  $cgst_amount = $taxable_value*$cgst_rate;
  $sgst_rate = 2.5;
  $sgst_amount = $taxable_value*$sgst_rate;
  $igst_rate = 0;
  $igst_amount = $taxable_value*$igst_rate;
}
else
{
  $cgst_rate = 0;
  $cgst_amount = $taxable_value*$cgst_rate;
  $sgst_rate = 0;
  $sgst_amount = $taxable_value*$sgst_rate;
  $igst_rate = 5;
  $igst_amount = $taxable_value*$igst_rate;
}
$total_amount = $taxable_value+$cgst_amount+$sgst_amount+$igst_amount;



 $dsn = getenv('MYSQL_DSN');
 $user = getenv('MYSQL_USER');
 $password = getenv('MYSQL_PASSWORD');
 if (!isset($dsn, $user) || false === $password)
 {
  throw new Exception('Set MYSQL_DSN, MYSQL_USER, and MYSQL_PASSWORD environment variables');
 }
 $db = new PDO($dsn, $user, $password);

 $params = array(':txnid' => $txnid,':customer_id' => $phone,':design_id' => $design_id,':product_id' => $productinfo,':item_qty' => $quantity,':item_rate' => $item_rate,':item_discount' => $item_discount,':taxable_value' => $taxable_value,':cgst_rate' => $cgst_rate,':cgst_amount' => $cgst_amount,':sgst_rate' => $sgst_rate,':sgst_amount' => $sgst_amount,':igst_rate' => $igst_rate,':igst_amount' => $igst_amount,':total_amount' => $total_amount,':payment_mode' => $pg,':payment_gateway' => $gateway, ':customer_email' => $email, ':customer_name' => $firstname, ':customer_address' => $address1, ':customer_city' => $city, ':customer_state' => $state, ':customer_pincode' => $zipcode);
 
 $db->exec("SET time_zone='Asia/Kolkata';");


 $statement = $db->prepare("INSERT INTO sales VALUES (:txnid, DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s'), '', '', :customer_id, :design_id, :product_id, :item_qty, :item_rate, :item_discount, :taxable_value, :cgst_rate, :cgst_amount, :sgst_rate, :sgst_amount, :igst_rate, :igst_amount, :total_amount, :payment_mode, :payment_gateway)");
 
 $statement->execute($params);

 $statement = $db->prepare("INSERT INTO sales VALUES (:customer_email, :customer_id, :customer_name, :customer_address, :customer_city, :customer_state, :customer_pincode)");
 
 $statement->execute($params);

 $db = null;
 


$udf1 = $quantity;
$udf2 = $design_id;
$udf3 = $address1;
$udf4 = $city.', '.$state.' - '.$zipcode;

$action = "https://secure.payu.in/_payment";
$response = "http://localhost/response.php";
$surl = $response;
$furl = $response;
$curl = $response;


$hash_string = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|||||||' . $salt;
$hash = strtolower(hash('sha512', $hash_string));



?>
<html>
  <head>
  <script>
    var hash = '<?php echo $hash ?>';
    function submitPayuForm()
    {
      if(hash == '') 
      {
        return;
      }
      var payuForm = document.forms.payuForm;
      //payuForm.submit();
    }

  </script>

<style type="text/css"> 
.page-loader-wrap {
    background-color: #a5c339;
    height: 100%;
    width: 100%;
    position: fixed;
    z-index: 99999;
    top: 0;
    left: 0;
}
.page-loader-wrap .loader-content {
    max-width: 320px;
    width: 100%;
    margin-right: auto;
    margin-left: auto;
    margin-top: 80px;
    text-align: center;
}
.page-loader-wrap .loading-text {
    font-size: 20px !important;
    font-size: 1.25rem;
    color: #fff;
    margin-bottom: 10px;
 font: 100%/1.5em "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
    text-rendering: optimizeLegibility;
    -webkit-font-smoothing: antialiased;
}

.pageloader {
    background: url(https://media.payumoney.com/media/images/payment/payment/pageloader.gif) no-repeat;
    display: inline-block;
    height: 16px;
    width: 105px;
}
</style>
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1747208625544950'); // Insert your pixel ID here.
fbq('track', 'PageView');
fbq('track', 'InitiateCheckout');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1747208625544950&ev=PageView&noscript=1"
/></noscript>
  </head>
  <body onload="submitPayuForm()">
   <div id="main-loader" class="page-loader-wrap hide">
      <div class="loader-content">
         <h4 class="loading-text">Please Wait. Loading...</h4>
         <span class="pageloader"></span>
      </div>
   </div>
   <h2>PayU Form</h2>
   <br/>
   <?php if($formError) { ?>
   <span style="color:red">Please fill all mandatory fields.</span>
   <br/>
   <br/>
   <?php } ?>
   <form action="<?php echo $action; ?>" method="post" name="payuForm" >
      <input type="hidden" name="key" value="<?php echo $key ?>" />
      <input type="hidden" name="hash" value="<?php echo $hash ?>"/>
      <input type="hidden" name="txnid" value="<?php echo $txnid ?>" />
      <input type="hidden" name="surl" value="<?php echo $surl ?>" />   
      <input type="hidden" name="furl" value="<?php echo $furl ?>" />
      <input type="hidden" name="amount" value="<?php echo $amount ?>" />
      <input type="hidden" name="firstname" value="<?php echo $firstname ?>" />
      <input type="hidden" name="email" value="<?php echo $email ?>" />
      <input type="hidden" name="phone" value="<?php echo $phone ?>" />
      <input type="hidden" name="productinfo" value="<?php echo $productinfo ?>" />
      <input type="hidden" name="curl" value="<?php echo $curl ?>" />
      <input type="hidden" name="address1" value="<?php echo $address1 ?>" />
      <input type="hidden" name="city" value="<?php echo $city ?>" />
      <input type="hidden" name="state" value="<?php echo $state ?>" />
      <input type="hidden" name="zipcode" value="<?php echo $zipcode ?>" />
      <input type="hidden" name="udf1" value="<?php echo $udf1 ?>" />
      <input type="hidden" name="udf2" value="<?php echo $udf2 ?>" />
      <input type="hidden" name="udf3" value="<?php echo $udf3  ?>" />
      <input type="hidden" name="udf4" value="<?php echo $udf4  ?>" />
      <input type="hidden" name="drop_category" value="CASH" />
      <input type="hidden" name="pg" value="<?php echo $pg ?>" />
      <input type="hidden" name="service_provider" value="<?php echo $servicepro ?>" />
      <input type="hidden" type="submit" value="Submit" />
    </form>
</body>
</html>
