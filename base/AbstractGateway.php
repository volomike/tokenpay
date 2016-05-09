<?php

abstract class AbstractTokGateway {

protected $APIKey = '';
protected $TestMode = FALSE; // use the sandbox
protected $DebugMode = FALSE; // print sending and receiving data
protected $VerifySSL = TRUE; // used by curl
protected $Billing = array();
protected $Shipping = array();
protected $Charge = array();

// OVERRIDE THESE PER VENDOR INTERFACE
protected $URLTest = 'https://test-api.example.com/';
protected $URLLive = 'https://live.example.com/';

public function setAPIKey($sKey) {
	$this->APIKey = $sKey;
}

public function setTestMode($bTestMode) {
	$this->TestMode = $bTestMode;
}

public function setDebugMode($bDebugMode) {
	$this->DebugMode = $bDebugMode;
}

public function setBilling($asBilling) {
	$this->Billing = $asBilling;
}

public function setShipping($asShipping) {
	$this->Shipping = $asShipping;
}

public function setCharge($asCharge) {
	$this->Charge = $asCharge;
}

public function setVerifySSL($bVerifySSL) {
	$this->VerifySSL = $bVerifySSL;
}

public function chargeCard() {
	$sAPIKey = $this->APIKey;
	$bTestMode = $this->TestMode;
	$bDebugMode = $this->DebugMode;
	$bVerifySSL = $this->VerifySSL;
	$asBilling = $this->Billing;
	$asShipping = $this->Shipping;
	$asCharge = $this->Charge;
	$sURL = ($bTestMode) ? $this->URLTest : $this->URLLive;
	$oResponse = $this->sendCharge(
		$sURL,
		$sAPIKey,
		$bTestMode,
		$bDebugMode,
		$bVerifySSL,
		$asBilling,
		$asShipping,
		$asCharge
	);
	return $oResponse;
}

// OVERRIDE THIS CLASS METHOD PER VENDOR INTERFACE
public function sendCharge(
	$sURL,
	$sAPIKey,
	$bTestMode,
	$bDebugMode,
	$bVerifySSL,
	$asBilling,
	$asShipping,
	$asCharge
) {

		$oResponse = (object) array();
		$oResponse->isSuccess = FALSE;
		$oResponse->TransID = NULL;
		$oResponse->FailCode = NULL;
		$oResponse->FailMessage = NULL;
		// modify the Billing, Shipping, and Charge arrays per vendor
		// do the http connection with the data
		// parse the response and update our $oResponse object
		return $oResponse;
}

public function refundCharge($sChargeID,$nAmount) {
	$sAPIKey = $this->APIKey;
	$bTestMode = $this->TestMode;
	$bDebugMode = $this->DebugMode;
	$bVerifySSL = $this->VerifySSL;
	$sURL = ($bTestMode) ? $this->URLTest : $this->URLLive;
	$oResponse = $this->sendRefund(
		$sURL,
		$sAPIKey,
		$bTestMode,
		$bDebugMode,
		$bVerifySSL,
		$sChargeID,
		$nAmount
	);
	return $oResponse;
}

// OVERRIDE THIS CLASS METHOD PER VENDOR INTERFACE
public function sendRefund(
	$sURL,
	$sAPIKey,
	$bTestMode,
	$bDebugMode,
	$bVerifySSL,
	$sChargeID,
	$nAmount
) {

		$oResponse = (object) array();
		$oResponse->isSuccess = FALSE;
		$oResponse->TransID = NULL;
		$oResponse->FailCode = NULL;
		$oResponse->FailMessage = NULL;
		// do the http connection with the data
		// parse the response and update our $oResponse object
		return $oResponse;
}

} // end class

