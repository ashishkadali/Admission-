 <?php

 //it contains payment services


include '../../db_connect.php';
include '../../ma_config.php';
include'../../communication/class.phpmailer.php';


//this service is to send the checksum value
 if(array_key_exists('getPgMsg', $_POST))
 {
   
$email = $_POST['email'];
$appno = $_POST['application_no'];
$apl_name = $_POST['fullname'];
$address = "";
$mobileno = $_POST['mobile_no'];
$exam_type = $_POST['exam_type'];
// $amount ="1000";
 $trackidapp = $appno;
$transportalID = "";
$transportalPassword = "";
$currencyCode = "";
$langID = "";
$actionCode = "";


if ($stmt = $mysqli->prepare("SELECT * from paymentGatewayDetails WHERE examType = '".$exam_type."' ")) { 
      $stmt->execute();
      $stmt->store_result();
      //changed by hari id added
      $stmt->bind_result($id,$exam_type, $amount, $transportalID, $transportalPassword, $currencyCode, $langID, $actionCode); 
      $stmt->fetch();
}

    
if ($stmt = $mysqli->prepare("SELECT count(*) as totaltrans from onlineTransactions WHERE email = ?")) { 
      $stmt->bind_param('s', $email); 
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($totaltrans); 
      $stmt->fetch();
        
        $totaltrans = $totaltrans + 1;
        $num_padded = sprintf("%03d", $totaltrans);
        $trackidapp = $trackidapp.$num_padded;
        //$_SESSION['transappno'] = $trackidapp;
}

if ($stmt = $mysqli->prepare("SELECT address_line1, address_line2, place_town, city, pincode, mobile_no FROM ma_user_profile WHERE email = ?")) 
{ 
    $stmt->bind_param('s', $email); 
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($address_line1, $address_line2, $place_town, $city, $pincode, $mobile_no); 
    $stmt->fetch();
    $address = preg_replace('/[^a-zA-Z0-9]/ ', '', $address_line1)." ".preg_replace('/[^a-zA-Z0-9]/ ', '', $address_line2)." ".preg_replace('/[^a-zA-Z0-9]/ ', '', $place_town)." ".preg_replace('/[^a-zA-Z0-9]/ ', '', $city)." ".$pincode;
    $mobileno = $mobile_no;
}



$trackIdResponse = "Customer Redirected to PG, response pending";
$dbPaymentID = "Not Recieved";
$dbresponse = "Payment Request Initiated";
$dbtxnRef = "";
$dbtxnTranID = "";
$dbtxnError = "";
$date = date_default_timezone_set('Asia/Kolkata');
$dbtransDate = date("m/d/y G.i:s", time());
$MerchantID = "CIHL";
$SecurityID = "cihl";
$TypeField1 = "R";
$postedApp =$exam_type;
$amount = $amount.'.00';
//$amount = '1.00'; //testing
$checksomprovidedbybilldesk = 'wY0iv7Ktr7qa';   
$params = $MerchantID.'|'.$trackidapp.'|NA|'.$amount.'|NA|NA|NA|INR|NA|'.$TypeField1.'|'.$SecurityID.'|NA|NA|F|'.$apl_name.'|'.$email.'|'.$mobile_no.'|'.$appno.'|'.$postedApp.'|NA|NA|https://msitprogram.net/admissions/authentication/GatServices/pg_dump.php';
$checksum = hash_hmac('sha256',$params,$checksomprovidedbybilldesk, false);
$checksum_value = strtoupper($checksum);
$dataWithCheckSumValue = $params."|".$checksum_value;
$result=array('result'=>"Success",'checksum'=>$dataWithCheckSumValue);

$msg = $dataWithCheckSumValue; 
$url = "https://pgi.billdesk.com/pgidsk/PGIMerchantPayment";

if ($insert_stmt = $mysqli->prepare("INSERT INTO onlineTransactions(
                    email, appno, appType, trackID, 
                    amount, paymentID, response, 
                    request, txnRef, txnTranID, txnError, 
                    transDate, trackIdResponse) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) 
{    
    $insert_stmt->bind_param('sssssssssssss', 
                $email, $appno, $postedApp, $trackidapp, 
                $amount, $dbPaymentID, $dbresponse, 
                $params, $dbtxnRef, $dbtxnTranID, $dbtxnError, 
                $dbtransDate, $trackIdResponse ); 
    $insert_stmt->execute();
    $insert_stmt->close();
}
else
{
  $result=array('result'=>"failure",'msg'=>"REQ001-Not able to create online transaction");
}
echo json_encode($result);
 }
 else if(array_key_exists('payment_history', $_POST))
    { 
    $email=$_POST['email'];
   
    $i=0;
    $query="select * from onlineTransactions where email='".$email."'";
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->execute();
        while ($stmt->fetch()) {
            $i = 1;
        }
    }
    if($i == 1)
    {
      $data=array();
        
          $sql ="SELECT appno, appType, trackID, amount, paymentID, response, txnTranID, transDate, trackIdResponse from onlineTransactions WHERE email = '".$email."'";
             $sql1= mysqli_query($con,$sql);
              $index=0;
               while ($row = mysqli_fetch_assoc($sql1))
               {
                    $data[$index]=$row;
                    $index++;
                    //$data=$row;
                }
                $result=array('result'=>"Success",'msg'=>"Your Transcation History",'data'=>$data);
            
        }
    else{
        $result=array('result'=>"failure",'msg'=>"You have not made any Online transactions.");
    }
    echo json_encode($result);
 }

 //subbimsion

 else  if (array_key_exists('payment_submission', $_POST)){
  //echo "string";
  $response = array();
$response = explode("|", $_REQUEST['msg']); 
$trackidapp = $_POST['transappno'];
  $email=$_POST['email'];

if($response[14] == '0300' )
{
  $ResMerchantID  =  $response[0];
  $ResTrackID  =  $response[1];
  $ResPaymentId =  $response[2] ; 
  $ResTranId =  'M-'.$response[3] ; 
  $ResAmount   =    $response[4] ; 
  $date = date_default_timezone_set('Asia/Kolkata');
  $ResPosdate  =    date('Y-m-d', strtotime($response[13]));
  $dbtransDate =  date('Y-m-d', strtotime($response[13])); 
  $bd_payment_status = $response[14] ;
  $ResErrorNo = $response[14];            //Error Number
  $apl_name      =   $response[16];
  $email =  $response[17];   
  $mobile =  $response[18];  
  $appl_id      =   $response[19] ;
  $appl_type      =   $response[20] ;
  $ResResult =      $response[24] ; 
  $txnDate = date("m/d/y G.i:s", time());
  $date = date_default_timezone_set('Asia/Kolkata');
  $dbtransDate = date("m/d/y G.i:s", time());
  
  $trackidapp = $ResTrackID;
  
  if ($stmt = $mysqli->prepare("SELECT email, appno, amount, paymentID, response, txnRef, txnTranID, txnError, trackIdResponse from onlineTransactions where trackID = ?")) {
    $stmt->bind_param('s', $trackidapp); 
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($txn_email, $txn_appno, $txn_amount, $txn_paymentId, $txn_response, $txn_ref, $txn_tranId, $txn_error, $txn_trackIdResponse); 
    $stmt->fetch();
 
    //echo "----------".$trackidapp;
    
      if ($insert_stmt = $mysqli->prepare("INSERT INTO paymentDetails(ResPaymentId, ResTrackID,appl_id, apl_name,ResErrorNo, ResResult, ResPosdate, ResTranId, ResRef, ResAmount, email, mobile, bd_payment_status, txnDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)")) 
      {    
        $insert_stmt->bind_param('ssssssssssssss', $ResPaymentId, $ResTrackID, $appl_id, $apl_name, $ResErrorNo, $ResResult, $ResPosdate, $ResTranId, $ResRef, $ResAmount, $email, $mobile, $bd_payment_status, $txnDate); 
        $insert_stmt->execute();
        $insert_stmt->close();
        //echo "0000000000000000".$appl_id;
        // error_log("SUC005-inserted success payment into paymentDetails \n", 3, "paymentlog.txt");
        $payment_status = "yes";
        $payment_type = "ONLINE";
        if ($insert_stmt = $mysqli->prepare("update onlineTransactions set paymentID=?, response=?, txnTranID=?, txnError=?, trackIdResponse = 'SUCCESS' where email = ? and trackID = ?")) {         
          $insert_stmt->bind_param('ssssss', $ResPaymentId, $ResResult, $ResTranId, $ResErrorNo, $email,$trackidapp ); 
          $insert_stmt->execute();
          $insert_stmt->close();
          // echo "trackidapp".$trackidapp;
          // echo "email".$email;
          // error_log("SUC006-updated success payment into onlineTransactions \n", 3, "paymentlog.txt");
        }
        // else
        // {
        //   error_log("ERR007-Not able to update success payment in onlineTransactions \n", 3, "paymentlog.txt");
        // }'
        // echo "2342342342342342342342342".$appl_type;
        if($appl_type=="WALKIN")
        {
        $updateQueryText = "update walkinApplications set paymentType = ?, paymentStatus = ? where walkinAppNo = ?";
       //echo ">>>>>>>>>>>>>>W";
       }
        if($appl_type=="GAT")
        {
        	//echo "-----------G";
           $updateQueryText = "update gatApplications set paymentType = ?, paymentStatus = ? where gatAppNo = ?";
       }
        if ($insert_stmt = $mysqli->prepare($updateQueryText)) 
        {         
          $insert_stmt->bind_param('sss',  $payment_type, $payment_status, $appl_id ); 
          $insert_stmt->execute();
          $insert_stmt->close();
          $result=array('result'=>"Success",'msg'=>"Your Payment is Successfully Done for'".$appl_id."'");
           // echo "Application num".$appl_id;
           // echo "paymentStatus".$payment_status;
           // echo "payment_type".$payment_type;
         // error_log("SUC007-updated success application status \n", 3, "paymentlog.txt");
            
          $mail = new PHPMailer(); 
          $mail->IsSMTP(); 
          $mail->SMTPAuth   = true;                  
          $mail->SMTPSecure = "ssl"; 
          $mail->Host       = gethostbyname("smtp.gmail.com"); //"smtp.gmail.com";      
          $mail->Port       = 465;                  
          $mail->Username   = "*******";
          $mail->Password   = "*******";           
          $mail->SMTPKeepAlive = true;
          $mail->Timeout =100000000;
          $mail->From       = "msitadmissions@gmail.com"; 
          $mail->FromName   = HEADERTEXT;
          $mail->WordWrap = 40;                               
          $mail->IsHTML(true);                              
          $mail->Subject  =  HEADERTEXT." Payment Details"; 
          $bodyHtmlText = '<html xmlns="http://www.w3.org/1999/xhtml">
           <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <meta name="viewport" content="initial-scale=1.0" />
            <meta name="format-detection" content="telephone=no" />
            <title></title>
            <style type="text/css">
            body {
              width: 100%;
              margin: 0;
              padding: 0;
              -webkit-font-smoothing: antialiased;
            }
            @media only screen and (max-width: 600px) {
              table[class="table-row"] {
                float: none !important;
                width: 98% !important;
                padding-left: 20px !important;
                padding-right: 20px !important;
              }
              table[class="table-row-fixed"] {
                float: none !important;
                width: 98% !important;
              }
              table[class="table-col"], table[class="table-col-border"] {
                float: none !important;
                width: 100% !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                table-layout: fixed;
              }
              td[class="table-col-td"] {
                width: 100% !important;
              }
              table[class="table-col-border"] + table[class="table-col-border"] {
                padding-top: 12px;
                margin-top: 12px;
                border-top: 1px solid #E8E8E8;
              }
              table[class="table-col"] + table[class="table-col"] {
                margin-top: 15px;
              }
              td[class="table-row-td"] {
                padding-left: 0 !important;
                padding-right: 0 !important;
              }
              table[class="navbar-row"] , td[class="navbar-row-td"] {
                width: 100% !important;
              }
              img {
                max-width: 100% !important;
                display: inline !important;
              }
              img[class="pull-right"] {
                float: right;
                margin-left: 11px;
                max-width: 125px !important;
                padding-bottom: 0 !important;
              }
              img[class="pull-left"] {
                float: left;
                margin-right: 11px;
                max-width: 125px !important;
                padding-bottom: 0 !important;
              }
              table[class="table-space"], table[class="header-row"] {
                float: none !important;
                width: 98% !important;
              }
              td[class="header-row-td"] {
                width: 100% !important;
              }
            }
            @media only screen and (max-width: 480px) {
              table[class="table-row"] {
                padding-left: 16px !important;
                padding-right: 16px !important;
              }
            }
            @media only screen and (max-width: 320px) {
              table[class="table-row"] {
                padding-left: 12px !important;
                padding-right: 12px !important;
              }
            }
            @media only screen and (max-width: 458px) {
              td[class="table-td-wrap"] {
                width: 100% !important;
              }
            }
            </style>
           </head>
           <body style="font-family: Arial, sans-serif; font-size:13px; color: #444444; min-height: 200px;" bgcolor="#E4E6E9" leftmargin="0" topmargin="0" marginheight="0" marginwidth="0">
           <table width="100%" height="100%" bgcolor="#E4E6E9" cellspacing="0" cellpadding="0" border="0">
           <tr><td width="100%" align="center" valign="top" bgcolor="#E4E6E9" style="background-color:#E4E6E9; min-height: 200px;">
          <table><tr><td class="table-td-wrap" align="center" width="458"><table class="table-space" height="18" style="height: 18px; font-size: 0px; line-height: 0; width: 450px; background-color: #e4e6e9;" width="450" bgcolor="#E4E6E9" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-space-td" valign="middle" height="18" style="height: 18px; width: 450px; background-color: #e4e6e9;" width="450" bgcolor="#E4E6E9" align="left">&nbsp;</td></tr></tbody></table>
          <table class="table-space" height="8" style="height: 8px; font-size: 0px; line-height: 0; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-space-td" valign="middle" height="8" style="height: 8px; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" align="left">&nbsp;</td></tr></tbody></table>

          <table class="table-row" width="450" bgcolor="#FFFFFF" style="table-layout: fixed; background-color: #ffffff;" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-row-td" style="font-family: Arial, sans-serif; line-height: 19px; color: #444444; font-size: 13px; font-weight: normal; padding-left: 36px; padding-right: 36px;" valign="top" align="left">
            <table class="table-col" align="left" width="378" cellspacing="0" cellpadding="0" border="0" style="table-layout: fixed;"><tbody><tr><td class="table-col-td" width="378" style="font-family: Arial, sans-serif; line-height: 19px; color: #444444; font-size: 13px; font-weight: normal; width: 378px;" valign="top" align="left">
            <table class="header-row" width="378" cellspacing="0" cellpadding="0" border="0" style="table-layout: fixed;"><tbody><tr><td class="header-row-td" width="378" style="font-family: Arial, sans-serif; font-weight: normal; line-height: 19px; color: #478fca; margin: 0px; font-size: 18px; padding-bottom: 10px; padding-top: 15px;" valign="top" align="left">Congratulations!!!</td></tr></tbody></table>
            <div style="font-family: Arial, sans-serif; line-height: 20px; color: #444444; font-size: 13px;">
              <b style="color: #777777;">We have received your payment of Rs.'.$ResAmount.'.<br/>
              Application No: '.$appl_id.'<br/>
              App Track ID: '.$ResTrackID.'<br/>
              Payment ID: '.$ResPaymentId.'<br/>
              Transaction ID: '.$ResTranId.'<br/>
              Trasaction Date: '.$txnDate.'<br/>
              </b>
              <br/><br/>
              Thank you for Payment!
            </div>
            </td></tr></tbody></table>
          </td></tr></tbody></table>
            
          <table class="table-space" height="12" style="height: 12px; font-size: 0px; line-height: 0; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-space-td" valign="middle" height="12" style="height: 12px; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" align="left">&nbsp;</td></tr></tbody></table>
          <table class="table-space" height="12" style="height: 12px; font-size: 0px; line-height: 0; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-space-td" valign="middle" height="12" style="height: 12px; width: 450px; padding-left: 16px; padding-right: 16px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" align="center">&nbsp;<table bgcolor="#E8E8E8" height="0" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td bgcolor="#E8E8E8" height="1" width="100%" style="height: 1px; font-size:0;" valign="top" align="left">&nbsp;</td></tr></tbody></table></td></tr></tbody></table>
          <table class="table-space" height="16" style="height: 16px; font-size: 0px; line-height: 0; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-space-td" valign="middle" height="16" style="height: 16px; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" align="left">&nbsp;</td></tr></tbody></table>

          <table class="table-space" height="6" style="height: 6px; font-size: 0px; line-height: 0; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-space-td" valign="middle" height="6" style="height: 6px; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" align="left">&nbsp;</td></tr></tbody></table>

          <table class="table-row-fixed" width="450" bgcolor="#FFFFFF" style="table-layout: fixed; background-color: #ffffff;" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-row-fixed-td" style="font-family: Arial, sans-serif; line-height: 19px; color: #444444; font-size: 13px; font-weight: normal; padding-left: 1px; padding-right: 1px;" valign="top" align="left">
            <table class="table-col" align="left" width="448" cellspacing="0" cellpadding="0" border="0" style="table-layout: fixed;"><tbody><tr><td class="table-col-td" width="448" style="font-family: Arial, sans-serif; line-height: 19px; color: #444444; font-size: 13px; font-weight: normal;" valign="top" align="left">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="table-layout: fixed;"><tbody><tr><td width="100%" align="center" bgcolor="#f5f5f5" style="font-family: Arial, sans-serif; line-height: 24px; color: #bbbbbb; font-size: 13px; font-weight: normal; text-align: center; padding: 9px; border-width: 1px 0px 0px; border-style: solid; border-color: #e3e3e3; background-color: #f5f5f5;" valign="top">
              <a href="#" style="color: #428bca; text-decoration: none; background-color: transparent;">MSIT Admissions &copy; '.YEARTEXT.'</a>
            </td></tr></tbody></table>
            </td></tr></tbody></table>
          </td></tr></tbody></table>
          <table class="table-space" height="1" style="height: 1px; font-size: 0px; line-height: 0; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-space-td" valign="middle" height="1" style="height: 1px; width: 450px; background-color: #ffffff;" width="450" bgcolor="#FFFFFF" align="left">&nbsp;</td></tr></tbody></table>
          <table class="table-space" height="36" style="height: 36px; font-size: 0px; line-height: 0; width: 450px; background-color: #e4e6e9;" width="450" bgcolor="#E4E6E9" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="table-space-td" valign="middle" height="36" style="height: 36px; width: 450px; background-color: #e4e6e9;" width="450" bgcolor="#E4E6E9" align="left">&nbsp;</td></tr></tbody></table></td></tr></table>
          </td></tr>
           </table>
           </body>
           </html>';  
        
          $mail->Body = $bodyHtmlText;
          $mail->AddAddress($email); 
          if($mail->Send())
          {
            $mail->ClearAddresses();
           // error_log("SUC008-successfully sent payment email \n", 3, "paymentlog.txt");
          }
          // else
          // {
          //   error_log("ERR008-Not able to send payment success email \n", 3, "paymentlog.txt");
          // }
          // header("Location:http://msitprogram.net/admissions/payment.php?successMsg=Your transaction is Successfull. Status:".$ResResult);
        }
        // else
        // {
        //   error_log("ERR009-Not able to update application status \n", 3, "paymentlog.txt");
        // }
      }
      // else
      // {
      //   error_log("ERR010-Not able to insert success payment into paymentDetails \n", 3, "paymentlog.txt");
      // }
     
  }
  // else
  // {
  //   error_log("ERR011-Not able to retrieve the transaction details on success payment \n", 3, "paymentlog.txt");
  // }
}
else 
{
  $ResTrackID  =  $response[1];
  $ResPaymentId =  $response[2] ; 
  $ResAmount   =    $response[4] ; 
  $date = date_default_timezone_set('Asia/Kolkata');
  $ResPosdate  =    date('Y-m-d', strtotime($response[13]));
  $dbtransDate =  date('Y-m-d', strtotime($response[13])); 
  $bd_payment_status = $response[14] ;
  $ResErrorNo = $response[14];            //Error Number
  $apl_name      =   $response[16];
  $email =  $response[17];   
  $mobile =  $response[18];  
  $appl_id      =   $response[19] ;
  $ResResult =      $response[24] ; 
  $txnDate = date("m/d/y G.i:s", time());
  $date = date_default_timezone_set('Asia/Kolkata');
  $dbtransDate = date("m/d/y G.i:s", time());

  if ($insert_stmt = $mysqli->prepare("INSERT INTO paymentDetailsFailed(ResPaymentId, appl_id, ResTrackID,apl_name,ResErrorNo, ResResult, ResPosdate, ResTranId, ResRef, ResAmount, email, mobile, bd_payment_status, txnDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)")) 
  {    
    $insert_stmt->bind_param('ssssssssssssss', $ResPaymentId, $appl_id, $ResTrackID, $apl_name, $ResErrorNo, $ResResult, $ResPosdate, $ResTranId, $ResRef, $ResAmount, $email, $mobile, $bd_payment_status, $txnDate); 
    $insert_stmt->execute();
    $insert_stmt->close();    
    //error_log("SUC009-inserted failed payment into paymentDetailsFailed \n", 3, "paymentlog.txt");
    if ($insert_stmt = $mysqli->prepare("update onlineTransactions set paymentID = ?, response = ?, txnError = ?, trackIdResponse = 'FAILED' where email = ? and trackID = ?")) {         
      $insert_stmt->bind_param('sssss', $ResPaymentId, $ResResult, $ResErrorNo, $email,$trackidapp ); 
      $insert_stmt->execute();
      $insert_stmt->close();
      $result=array('result'=>"Failure",'msg'=>"We can not do your payment for'".$appl_id."' please try after sometime");
  //    error_log("SUC010-updated failed payment into onlineTransactions \n", 3, "paymentlog.txt");
    }
    // else
    // {
    //   error_log("ERR012-Not able to update failed payment in onlineTransactions \n", 3, "paymentlog.txt");
    // }
  }
  // else
  // {
  //   error_log("ERR013-Not able to insert failed payment in paymentDetailsFailed \n", 3, "paymentlog.txt");
  // }
  // header("Location:http://msitprogram.net/admissions/payment.php?errorMsg=Your transaction is denied. Status:".$ResResult);
}
echo json_encode($result);

//error_log("END_PAYMENT_RESPONSE\n\n", 3, "paymentlog.txt");

 }
