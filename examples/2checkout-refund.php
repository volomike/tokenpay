<?php

error_reporting(E_ALL);
ini_set('display_errors','On');
header('Content-Type: text/plain');

require_once('../base/TokenPay.php');

$sChargeID = '9195555348558'; // CHANGE ME
$nAmount = 100.50; // CHANGE ME
$sUsername = 'user'; // CHANGE ME
$sPassword = 'password'; // CHANGE ME

$o = TokenPay::createGateway('2checkout');
$o->setUsername($sUsername); // note this is unique to 2CO -- and it's only necessary for refunds
$o->setPassword($sPassword); // note this is unique to 2CO -- and it's only necessary for refunds
$o->setDebugMode(TRUE); // whether to send output of sending and receiving to the screen
$o->setTestMode(TRUE);
$oResponse = $o->refundCharge($sChargeID,$nAmount); // HERE WE GO!
if ($oResponse->isSuccess) {
	$sTransID = $oResponse->TransID;
	die('SUCCESS: ' . $sTransID);
} else {
	$sError = $oResponse->FailMessage;
	$nError = $oResponse->FailCode;
	die('FAIL: ' . $sError . ' (' . $nError . ')');
}

