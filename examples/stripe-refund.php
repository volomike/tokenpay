<?php

error_reporting(E_ALL);
ini_set('display_errors','On');
header('Content-Type: text/plain');

require_once('../base/TokenPay.php');

$sStripeSecretKey = 'YFLVs5555hCnnyuD4HAViGD15Xne7444'; // CHANGE ME
$sChargeID = 'ch_5555r0vd0zaB0F'; // CHANGE ME
$nAmount = 100.50; // CHANGE ME

$o = TokenPay::createGateway('stripe');
$o->setAPIKey($sStripeSecretKey);
$o->setDebugMode(TRUE); // whether to send output of sending and receiving to the screen
$o->setTestMode(TRUE); // note, stripe ignores this b/c it is set by API key, instead
$oResponse = $o->refundCharge($sChargeID,$nAmount); // HERE WE GO!
if ($oResponse->isSuccess) {
	$sTransID = $oResponse->TransID;
	die('SUCCESS: ' . $sTransID);
} else {
	$sError = $oResponse->FailMessage;
	$nError = $oResponse->FailCode;
	die('FAIL: ' . $sError . ' (' . $nError . ')');
}

