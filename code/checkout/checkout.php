<?php

if($_POST)
{
  if(isset($_POST["status"]) || isset($_POST["STATUS"]))
  {
    if(isset($_POST["hash"]))
    {
      $gateway="payu";
      $status = $_POST["status"];
      $amount = $_POST["amount"];     
      $txnid = $_POST["txnid"];
      $phone = $_POST["phone"];
      $array1 = preg_split( '/_/', $_POST['udf2']);
      $sname = $array1[0];
      $pixelid = $array1[1];
      $buymore = 'http://'.$sname.'.stores.zone';
    }
    if(isset($_POST["CHECKSUMHASH"]))
    {
      $gateway="paytm";
      $status = $_POST["STATUS"];
      $amount = $_POST["TXNAMOUNT"];
      $designid = $_POST["ORDERID"];
      $txnid = "INV".rand(10000,99999999);
      $array1 = preg_split( '/_/', $_POST['ORDERID']);
      $phone = substr($array1[0], -10);
    }

    $isValidChecksum = "FALSE";

    if($gateway == "payu")
    {
      $key=$_POST["key"];

      if($key == "xpeODu") { $salt = "PIuKOUT8"; }
      if($key == "ekROVjFf") { $salt = "tMTdXuMknH"; }

      $firstname=$_POST["firstname"];
      $productinfo=$_POST["productinfo"];
      $email=$_POST["email"];
      $udf1=$_POST["udf1"];
      $udf2=$_POST["udf2"];
      $udf3=$_POST["udf3"];
      $udf4=$_POST["udf4"];

      $posted_hash=$_POST["hash"];

      $retHashSeq = $salt.'|'.$status.'|||||||'. $udf4 .'|'. $udf3 .'|'. $udf2 .'|'. $udf1 .'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;

      $hash = hash("sha512", $retHashSeq);

      if ($hash == $posted_hash) 
      {
        $isValidChecksum = "TRUE";
      }
    }

    if($gateway == "paytm")
    {
      function verifychecksum_e($arrayList, $key, $checksumvalue) 
      {
        $arrayList = removeCheckSumParam($arrayList);
        ksort($arrayList);
        $str = getArray2Str($arrayList);
        $paytm_hash = decrypt_e($checksumvalue, $key);
        $salt = substr($paytm_hash, -4);
        $finalString = $str . "|" . $salt;
        $website_hash = hash("sha256", $finalString);
        $website_hash .= $salt;
        $validFlag = "FALSE";
        if ($website_hash == $paytm_hash) {
          $validFlag = "TRUE";
        } else {
          $validFlag = "FALSE";
        }
        return $validFlag;
      }

      function removeCheckSumParam($arrayList) 
      {
        if (isset($arrayList["CHECKSUMHASH"])) {
          unset($arrayList["CHECKSUMHASH"]);
        }
        return $arrayList;
      }

      function getArray2Str($arrayList) 
      {
        $findme   = 'REFUND';
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;  
        foreach ($arrayList as $key => $value) {
          $pos = strpos($value, $findme);
          $pospipe = strpos($value, $findmepipe);
          if ($pos !== false || $pospipe !== false) 
          {
            continue;
          }
          
          if ($flag) {
            $paramStr .= checkString_e($value);
            $flag = 0;
          } else {
            $paramStr .= "|" . checkString_e($value);
          }
        }
        return $paramStr;
      }
      function checkString_e($value) {
        if ($value == 'null')
          $value = '';
        return $value;
      }

      function decrypt_e($crypt, $ky) 
      {
        $crypt = base64_decode($crypt);
        $key = $ky;
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $iv = "@@@@&&&&####$$$$";
        mcrypt_generic_init($td, $key, $iv);
        $decrypted_data = mdecrypt_generic($td, $crypt);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $decrypted_data = pkcs5_unpad_e($decrypted_data);
        $decrypted_data = rtrim($decrypted_data);
        return $decrypted_data;
      }
      function pkcs5_unpad_e($text) 
      {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
          return false;
        return substr($text, 0, -1 * $pad);
      }

     $PAYTM_MERCHANT_KEY = 'flbm#g#3zF1g0mRd';
     $paramList = array();
     $paramList = $_POST;

     $isValidChecksum = verifychecksum_e($paramList, $PAYTM_MERCHANT_KEY, $_POST["CHECKSUMHASH"]); 

    }

    if($isValidChecksum=="TRUE")
    {
      if($status=="success" || $status=="TXN_SUCCESS")
      {
        try
        {
          $dsn = getenv('MYSQL_DSN');
          $user = getenv('MYSQL_USER');
          $password = getenv('MYSQL_PASSWORD');
          if (!isset($dsn, $user) || false === $password)
          {
            throw new Exception('Set MYSQL_DSN, MYSQL_USER, and MYSQL_PASSWORD environment variables');
          }
          $db = new PDO($dsn, $user, $password);
          $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          
          if($status=="TXN_SUCCESS")
          {
            $arry = preg_split( '/_/', $_POST["ORDERID"]);
            $tx = $arry[2];
          }

          $params = array(':txnid' => $txnid);
          $statement = $db->prepare("UPDATE txn SET status='success' WHERE txnid=:txnid");
          $statement->execute($params);

          $db = null;
        }
        catch(PDOException $e)
        {
          echo "Error: " . $e->getMessage();
        }

        if(strlen($phone)==10)
  	    {
    			$msg = "Your Order no. ".rand(1000,10000)." has been placed successfully. T-shirt will be dispatched tomorrow and will be delivered by ".(new DateTime('+6 day'))->format('d')."-".(new DateTime('+7 day'))->format('jS F');
          //$msg = "Your Order no. ".rand(1000,10000)." has been placed successfully. T-shirt will be delivered by 20-21st December";
    			$curl = curl_init();
    			curl_setopt_array($curl, array(
    			  CURLOPT_URL => "http://api.msg91.com/api/sendhttp.php?sender=TSHIRT&route=4&mobiles=".$phone."&authkey=170174AD5RqE09y59945a7c&country=91&message=".$msg,
    			  CURLOPT_RETURNTRANSFER => true,
    			  CURLOPT_ENCODING => "",
    			  CURLOPT_MAXREDIRS => 10,
    			  CURLOPT_TIMEOUT => 30,
    			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    			  CURLOPT_CUSTOMREQUEST => "GET",
    			  CURLOPT_SSL_VERIFYHOST => 0,
    			  CURLOPT_SSL_VERIFYPEER => 0,
    			));
    			curl_exec($curl);
    			curl_close($curl);
        }
      }
    }
  }
  elseif(isset($_POST["size"]))
  {
    $amount = $_POST['amount'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address1 = $_POST['address1'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zipcode = $_POST['zipcode'];
    $size = $_POST['size'];
    $quantity = $_POST['qty'];
    $pg = $_POST['pg'];
    $spid = $_POST['spid'];

    $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
    $array1 = preg_split( '/_/', $_POST['productinfo']);
    $productinfo = $array1[0].'-'.$size;
    $design_id = $array1[1];
    $address1 = str_replace(array("\r", "\n"), ', ', $address1);
    $address1 = str_replace(", ,", ',', $address1);
    $array2 = preg_split( '/_/', $spid);
    $pixelid=$array2[1];
    $response = "http://payment.stores.zone";

    $item_rate = 380;
    $item_discount = 0;
    $taxable_value = ($item_rate*$quantity)-$item_discount;
    if (strpos($state, 'Andhra') !== false) 
    {
      $cgst_rate = 2.5;
      $cgst_amount = $taxable_value*($cgst_rate/100);
      $sgst_rate = 2.5;
      $sgst_amount = $taxable_value*($sgst_rate/100);
      $igst_rate = 0;
      $igst_amount = $taxable_value*($igst_rate/100);
    }
    else
    {
      $cgst_rate = 0;
      $cgst_amount = $taxable_value*($cgst_rate/100);
      $sgst_rate = 0;
      $sgst_amount = $taxable_value*($sgst_rate/100);
      $igst_rate = 5;
      $igst_amount = $taxable_value*($igst_rate/100);
    }
    $total_amount = $taxable_value+$cgst_amount+$sgst_amount+$igst_amount;

    if($pg == "DC") {$gateway = "payubiz";}
    if($pg == "NB") {$gateway = "payubiz";}
    if($pg == "CC") {$gateway = "payubiz";}
    if($pg == "CASH") {$gateway = "paytm";}

    try
    {
      $dsn = getenv('MYSQL_DSN');
      $user = getenv('MYSQL_USER');
      $password = getenv('MYSQL_PASSWORD');
      if (!isset($dsn, $user) || false === $password)
      {
        throw new Exception('Set MYSQL_DSN, MYSQL_USER, and MYSQL_PASSWORD environment variables');
      }
      $db = new PDO($dsn, $user, $password);
      $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $db->exec("SET time_zone='Asia/Kolkata';");

      $params = array(':txnid' => $txnid,':customer_id' => $phone,':design_id' => $design_id,':product_id' => $productinfo,':item_qty' => $quantity,':item_rate' => $item_rate,':item_discount' => $item_discount,':taxable_value' => $taxable_value,':cgst_rate' => $cgst_rate,':cgst_amount' => $cgst_amount,':sgst_rate' => $sgst_rate,':sgst_amount' => $sgst_amount,':igst_rate' => $igst_rate,':igst_amount' => $igst_amount,':total_amount' => $total_amount,':payment_mode' => $pg,':payment_gateway' => $gateway);
      $statement = $db->prepare("INSERT INTO txn VALUES (NULL,:txnid, '', DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s'), '', '', :customer_id, :design_id, :product_id, :item_qty, :item_rate, :item_discount, :taxable_value, :cgst_rate, :cgst_amount, :sgst_rate, :sgst_amount, :igst_rate, :igst_amount, :total_amount, :payment_mode, :payment_gateway)");
      $statement->execute($params);

      $params2 = array(':txnid' => $txnid, ':customer_email' => $email, ':customer_id' => $phone, ':customer_name' => $firstname, ':customer_address' => $address1, ':customer_city' => $city, ':customer_state' => $state, ':customer_pincode' => $zipcode);
      $statement2 = $db->prepare("INSERT INTO cust VALUES (NULL,:txnid, :customer_email, :customer_id, :customer_name, :customer_address, :customer_city, :customer_state, :customer_pincode)");
      $statement2->execute($params2);

      $db = null;
    }
    catch(PDOException $e)
    {
      echo "Error: " . $e->getMessage();
    }


    if ($gateway == "payubiz" || $gateway == "payumoney")
    {
      if($gateway == "payubiz") { $key = "xpeODu"; $salt = "PIuKOUT8"; }
      if($gateway == "payumoney"){ $key = "ekROVjFf"; $salt = "tMTdXuMknH";}

      $udf1 = $quantity;
      $udf2 = $spid;
      $udf3 = $address1;
      $udf4 = $city.', '.$state.' - '.$zipcode;

      $hash_string = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|||||||' . $salt;
      $hash = strtolower(hash('sha512', $hash_string));

      $paramList = array();
      $paramList["key"] = $key;
      $paramList["hash"] = $hash;
      $paramList["txnid"] = $txnid;
      $paramList["amount"] = $amount;
      $paramList["firstname"] = $firstname;
      $paramList["email"] = $email;
      $paramList["phone"] = $phone;
      $paramList["productinfo"] = $productinfo;
      $paramList["address1"] = $address1;
      $paramList["city"] = $city;
      $paramList["state"] = $state;
      $paramList["zipcode"] = $zipcode;
      $paramList["udf1"] = $udf1;
      $paramList["udf2"] = $udf2;
      $paramList["udf3"] = $udf3;
      $paramList["udf4"] = $udf4;
      $paramList["drop_category"] = "WALLET";
      $paramList["pg"] = $pg;
      $paramList["surl"] = $response;
      $paramList["furl"] = $response;
      $paramList["curl"] = $response;
      if($gateway=="payumoney")
      {
       $paramList["service_provider"] = "payu_paisa";
      }

      //$action = "https://secure.payu.in/_payment";
    }

    if($gateway == "paytm")
    {
      function getChecksumFromArray($arrayList, $key, $sort=1) 
      {
        if ($sort != 0) 
        {
          ksort($arrayList);
        }
        $str = getArray2Str($arrayList);
        $salt = generateSalt_e(4);
        $finalString = $str . "|" . $salt;
        $hash = hash("sha256", $finalString);
        $hashString = $hash . $salt;
        $checksum = encrypt_e($hashString, $key);
        return $checksum;
      }

      function getArray2Str($arrayList) 
      {
        $findme   = 'REFUND';
        $findmepipe = '|';
        $paramStr = "";
        $flag = 1;  
        foreach ($arrayList as $key => $value) 
        {
          $pos = strpos($value, $findme);
          $pospipe = strpos($value, $findmepipe);
          if ($pos !== false || $pospipe !== false) 
          {
            continue;
          }
          if ($flag) 
          {
            $paramStr .= checkString_e($value);
            $flag = 0;
          } 
          else 
          {
            $paramStr .= "|" . checkString_e($value);
          }
        }
        return $paramStr;
      }

      function checkString_e($value) 
      {
        if ($value == 'null')
          $value = '';
        return $value;
      }

      function generateSalt_e($length) 
      {
        $random = "";
        srand((double) microtime() * 1000000);
        $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
        $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
        $data .= "0FGH45OP89";
        for ($i = 0; $i < $length; $i++) 
        {
          $random .= substr($data, (rand() % (strlen($data))), 1);
        }
        return $random;
      }

      function encrypt_e($input, $ky) 
      {
        $key = $ky;
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
        $input = pkcs5_pad_e($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        $iv = "@@@@&&&&####$$$$";
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
      }

      function pkcs5_pad_e($text, $blocksize) 
      {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
      }

      $PAYTM_ENVIRONMENT ='PROD';
      $PAYTM_MERCHANT_KEY = 'flbm#g#3zF1g0mRd';
      $PAYTM_MERCHANT_MID = 'EcomIn89568340946518';
      $PAYTM_MERCHANT_WEBSITE = 'EcomInWEB';

      $ORDER_ID = $phone.'_'.$design_id.'_'.$txnid;
      $CUST_ID = $txnid;
      $INDUSTRY_TYPE_ID = "Retail109";
      $CHANNEL_ID = "WEB";
      $TXN_AMOUNT = $amount;

      $paramList = array();
      $paramList["MID"] = $PAYTM_MERCHANT_MID;
      $paramList["ORDER_ID"] = $ORDER_ID;
      $paramList["CUST_ID"] = $CUST_ID;
      $paramList["INDUSTRY_TYPE_ID"] = $INDUSTRY_TYPE_ID;
      $paramList["CHANNEL_ID"] = $CHANNEL_ID;
      $paramList["TXN_AMOUNT"] = $TXN_AMOUNT;
      $paramList["WEBSITE"] = $PAYTM_MERCHANT_WEBSITE;
      $paramList["CALLBACK_URL"] = $response;

      $checkSum = getChecksumFromArray($paramList,$PAYTM_MERCHANT_KEY);

      $action = 'https://secure.paytm.in/oltp-web/processTransaction';

    }
  }
}

?>

<?php if($_POST) : ?>
<html>
  <head>
    <?php if(isset($_POST["status"]) || isset($_POST["STATUS"])) : ?>
      <link href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css' rel='stylesheet'/>
      </head>
      <body>
        <div class="container">
          <div class="row text-center">
            <div class="col-sm-6 col-sm-offset-3">
              <?php if($_POST) : ?>
                <?php if($isValidChecksum=="TRUE") : ?>
                  <?php if($status=="success" || $status=="TXN_SUCCESS") : ?>       
                    <br><br>
                    <h2 style="color:#0fad00">Payment Successful</h2>
                    <img width="100px" src="https://2.bp.blogspot.com/-vEt0_-5kzaE/WXyAyR4btnI/AAAAAAAAAmM/wkO4V5hVJkIt_-rg4nKTeAD00poL9K--wCLcBGAs/s200/checknew%2B%25281%2529.jpg">
                    <h3 style="font-size:15px;font-weight: normal;">Order ID : <?php echo $txnid ?> </h3>
                    <p style="font-size:15px;font-weight: bold;color:#5C5C5C;">Thank you, <?php echo $firstname ?>. You will receive the T-shirt in 8-9 days.</p>
                    <br>
                    <a href="<?php echo $buymore ?>" class="btn btn-success">Buy more</a>
                    <br><br>
                    <script>
                      !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                      n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                      n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                      t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                      document,'script','https://connect.facebook.net/en_US/fbevents.js');
                      fbq('init', '<?php echo $pixelid ?>'); // Insert your pixel ID here.
                      fbq('track', 'PageView');
                      var xy = "<?php echo $amount ?>";
                      fbq('track', 'Purchase', {
                      value: xy,
                      currency: 'INR'
                      });
                    </script>
                  <?php else : ?>
                    <br><br><h2 style="color:red">Payment Failed</h2>
                    <img width="100px" src="https://1.bp.blogspot.com/-Nqlo8xIj3EE/WXx_6BjfwcI/AAAAAAAAAmA/1-dYXYGY80oNecGudr2URBYqMyhZHohKQCLcBGAs/s200/wrong-new.jpg">
                    <h3 style="font-size:15px;font-weight: normal;">Transaction ID : <?php echo $txnid ?> </h3>
                    <br>
                    <h3 style="font-size:15px;font-weight: normal;">If the amount is debited, it will be refunded automatically within few hours </h3>
                    <br>
                    <a href="<?php echo $buymore ?>" class="btn btn-success">Try Again</a>
                    <br><br>
                  <?php endif; ?>
                <?php else : ?>
                  <br><br><h2 style="color:red">Invalid Transaction.</h2>
                  <img width="100px" src="https://1.bp.blogspot.com/-Nqlo8xIj3EE/WXx_6BjfwcI/AAAAAAAAAmA/1-dYXYGY80oNecGudr2URBYqMyhZHohKQCLcBGAs/s200/wrong-new.jpg">
                  <h3 style="font-size:15px;font-weight: normal;">Transaction has been tampered. Please try again</h3>
                  <br>
                  <a href="<?php echo $buymore ?>" class="btn btn-success">Try Again</a>
                  <br><br>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
    <?php elseif(isset($_POST["size"])) : ?>
      <link href="https://file.payumoney.com/images/favicon_index.ico" rel="shortcut icon" type='image/x-icon' sizes="16x16"/>
      <script>
        var hash = '<?php echo $hash ?>';
        function submitPayuForm()
        {
          document.forms.finalform.submit()
          
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
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?php echo $pixelid; ?>'); // Insert your pixel ID here.
        fbq('track', 'PageView');
        fbq('track', 'InitiateCheckout');
      </script>
      <noscript>
        <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=1747208625544950&ev=PageView&noscript=1"/>
      </noscript>

    </head>

    <body onload="submitPayuForm()">
     <div id="main-loader" class="page-loader-wrap hide">
        <div class="loader-content">
           <h4 class="loading-text">Please Wait. Loading...</h4>
           <span class="pageloader"></span>
        </div>
     </div>
     <form action="<?php echo $action; ?>" method="post" name="finalform" >
        <?php
          foreach($paramList as $name => $value) 
          {
            echo '<input type="hidden" name="' . $name .'" value="' . $value . '">';
          }
        ?>
        <?php if($gateway=="paytm") : ?>
        <input type="hidden" name="CHECKSUMHASH" value="<?php echo $checkSum ?>">
        <?php endif; ?>
      </form>
    <?php endif; ?> 
  </body>
</html>
<?php endif; ?> 