<?php

error_reporting(E_ALL);
ini_set('display_errors','On');
header('Content-Type: text/plain');

require_once('../base/TokenPay.php');

$sChargeID = '9f4bhx'; // CHANGE ME
$nAmount = '0.00'; // CHANGE ME

$sBraintreePublicKey = '525555c8qcf2wq5p'; // CHANGE ME
$sBraintreePrivateKey = '7b955556e4bbf8a6f03cb7ace0e812ba'; // CHANGE ME
$sBraintreeMerchantID = 'g325555m5h27zxdb'; // CHANGE ME

$o = TokenPay::createGateway('braintree');
$o->setPrivateKey($sBraintreePrivateKey); // could also use setAPIKey()
$o->setPublicKey($sBraintreePublicKey); // unique to Braintree
$o->setMerchantID($sBraintreeMerchantID); // unique to Braintree
$o->setDebugMode(TRUE); // whether to send output of sending and receiving to the screen
$o->setTestMode(TRUE); // use the sandbox
// Note if $nAmount < 1, is null or empty string, then it won't be sent to the server and will use the charge ID for a full
// refund, instead.
// Note that currency is not specified.
$oResponse = $o->refundCharge($sChargeID,$nAmount); // HERE WE GO!
if ($oResponse->isSuccess) {
	$sTransID = $oResponse->TransID;
	die('SUCCESS: ' . $sTransID);
} else {
	$sError = $oResponse->FailMessage;
	$nError = $oResponse->FailCode;
	die('FAIL: ' . $sError . ' (' . $nError . ')');
}


