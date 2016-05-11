<?php

// Copyright (c) 2016, Volo, LLC of South Carolina, USA

require_once('../base/AbstractGateway.php');

class TokGateway extends AbstractTokGateway {

protected $URLTest = 'https://api.sandbox.braintreegateway.com:443/merchants/';
protected $URLLive = 'https://api.braintreegateway.com:443/merchants/';

protected $PublicKey = '';
protected $MerchantID = '';

public function setPublicKey($sPublicKey) {
	$this->PublicKey = $sPublicKey;
}

public function setPrivateKey($sPrivateKey) {
	$this->APIKey = $sPrivateKey; // so, we're making setAPIKey and setPrivateKey the same thing for this gateway
}

public function setMerchantID($sMerchantID) {
	$this->MerchantID = $sMerchantID;
}

public function toXML(SimpleXMLElement $object, array $data) {   
	foreach ($data as $key => $value) {
		if (is_array($value)) {
			$new_object = $object->addChild($key);
			$this->toXML($new_object, $value);
		} else {   
			$object->addChild($key, $value);
		}   
	}   
}  

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
	$sToken = @ $asCharge['token'];
	if (empty($sToken)) {
		$sToken = @ $asCharge['nonce'];
	}
	if (empty($sToken)) {
		$sToken = @ $asCharge['payment_method_nonce'];
	}
	if (empty($sToken)) {
		$sToken = @ $asCharge['payment_nonce'];
	}
	$oResponse = (object) array();
	$oResponse->isSuccess = FALSE;
	$oResponse->TransID = NULL;
	$oResponse->FailCode = NULL;
	$oResponse->FailMessage = NULL;
	// translate the Billing, Shipping, and Purchase arrays as necessary for Braintree charges
	$sName = @ $asBilling['billingName'];
	list($sFirstName,$sLastName) = $this->splitName($sName);
	$sPhone = @ $asBilling['billingPhone'];
	if (empty($sPhone)) {
		$sPhone = @ $asBilling['phone'];
	}
	if (empty($sPhone)) {
		$sPhone = @ $asShipping['shippingPhone'];
	}
	if (empty($sPhone)) {
		$sPhone = @ $asShipping['phone'];
	}
	$sEmail = @ $asBilling['billingEmail'];
	if (empty($sEmail)) {
		$sEmail = @ $asBilling['email'];
	}
	if (empty($sEmail)) {
		$sEmail = @ $asShipping['shippingEmail'];
	}
	if (empty($sEmail)) {
		$sEmail = @ $asShipping['email'];
	}
	if (is_array($asBilling)) {
		$nCount = count($asBilling);
		if ($nCount > 0) {
			$asBilling = $this->adjustBilling($asBilling);
		}
	}
	$bHasShipping = FALSE;
	if (is_array($asShipping)) {
		$nCount = count($asShipping);
		if ($nCount > 0) {
			$bHasShipping = TRUE;
			$asShipping = $this->adjustShipping($asShipping);
		}
	}
	$asFields = array(
		'type' => 'sale',
		'amount' => @ $asCharge['amount'],
		'payment-method-nonce' => $sToken,
		'customer' => array(
			'first-name' => $sFirstName,
			'last-name' => $sLastName,
			'phone' => $sPhone,
			'email' => $sEmail
		),
		'billing' => $asBilling
	);
	if ($bHasShipping) {
		$asFields['shipping'] = $asShipping;
	}
	$asFields['options'] = array(
		'submit-for-settlement' => 'true'
	);
	$sDescTest = @ $asCharge['description'];
	if ($sDescTest) {
		$asFields['customFields'] = array(
			'custom-description' => $sDescTest
		);
	}
	// do the http connection with the data
	$sURL .= $this->MerchantID . '/transactions';
	$oXML = new SimpleXMLElement('<transaction/>');
	$this->toXML($oXML,$asFields);
	$sXML = $oXML->asXML();
	$sXML = str_replace('<submit-for-settlement>','<submit-for-settlement type="boolean">',$sXML);
	$asOpt = array(
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_URL => $sURL,
		CURLOPT_ACCEPT_ENCODING => 'gzip',
		CURLOPT_HTTPAUTH => 1,
		CURLOPT_USERPWD => $this->PublicKey . ':' . $this->APIKey,
		CURLOPT_HTTPHEADER => array (
			'Accept: application/xml',
			'Content-Type: application/xml',
			'User-Agent: Braintree PHP Library 3.11.0',
			'X-ApiVersion: 4'
		),
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_POSTFIELDS => $sXML,
		CURLE_FTP_WEIRD_PASV_REPLY => 60
	);
	$hCurl = curl_init();
if ($bDebugMode) {
echo "\nCURL OPTIONS = \n";
print_r($asOpt);
echo "\n";
}
	curl_setopt_array($hCurl,$asOpt);
	curl_setopt($hCurl, CURLOPT_SSL_VERIFYPEER, $bVerifySSL);
	$sResponse = curl_exec($hCurl);
if ($bDebugMode) {
echo "\nRAW RESPONSE = \n$sResponse\n";
}
	$nErr = 0;
	$nResponseCode = 0;
	if ($sResponse === FALSE) {
		$nErr = curl_errno($hCurl);
		$sErr = curl_error($hCurl);
if ($bDebugMode) {
echo "\nCURL ERROR = $sErr ($nErr)\n";
}
		// debug with $nErr or $sErr if you want
	} else {
		$nResponseCode = curl_getinfo($hCurl, CURLINFO_HTTP_CODE);
		$nResponseCode = intval($nResponseCode);
if ($bDebugMode) {
echo "\nCURL RESPONSE CODE = $nResponseCode\n";
}
		// debug with $nResponseCode if you want
	}
	$nResponseCode = intval($nResponseCode);
	curl_close($hCurl);

	// parse the response and update our $oResponse object
	if ($nErr > 0) {
		$oResponse->FailMessage = "Error communicating with Braintree payment system.";
		return $oResponse;
	}
	if ($nResponseCode >= 400) {
		$oResponse->FailCode = $nResponseCode;
	}
	if ($nResponseCode == 400) {
		$oResponse->FailMessage = "Error from Braintree payment system. The request was unacceptable, often due to missing a required parameter, or an invalid token.";
		return $oResponse;
	}
	if ($nResponseCode == 401) {
		$oResponse->FailMessage = "Error from Braintree payment system. No valid API key provided.";
		return $oResponse;
	}
	if ($nResponseCode == 402) {
		$oResponse->FailMessage = "Error from Braintree payment system. The parameters were valid but the request failed.";
		return $oResponse;
	}
	if ($nResponseCode == 404) {
		$oResponse->FailMessage = "Error from Braintree payment system. The requested resource doesn't exist.";
		return $oResponse;
	}
	if ($nResponseCode == 409) {
		$oResponse->FailMessage = "Error from Braintree payment system. The request conflicts with another request (perhaps due to using the same idempotent key).";
		return $oResponse;
	}
	if ($nResponseCode == 429) {
		$oResponse->FailMessage = "Error from Braintree payment system. Too many requests hit the API too quickly.";
		return $oResponse;
	}
	if ($nResponseCode >= 500) {
		$oResponse->FailMessage = "Error from Braintree payment system. Unknown error.";
		return $oResponse;
	}
	if (strpos($sResponse,'</transaction>') === FALSE) {
		$oResponse->FailMessage = "Error from Braintree payment system. Incomplete XML.";
		return $oResponse;
	}
	$oXML = simplexml_load_string($sResponse);
if ($bDebugMode) {
echo "\nXML OBJECT = \n";
print_r($oXML);
echo "\n";
}
	$sStatus = @ $oXML->transaction->status;
	if (empty($sStatus)) {
		$sStatus = $oXML->status;
	}
	$sTransID = @ $oXML->id;
//echo "\n\n\nSTATUS = $sStatus";
//echo "\n\n\nTRANSID = $sTransID";
	if (empty($sTransID)) {
		$oResponse->isSuccess = FALSE;
	} else if (in_array($sStatus, array(
		'settled', 'settlement_confirmed', 'submitted_for_settlement', 'authorized', 'settling', 'settlement_pending'
	))) {
		$oResponse->isSuccess = TRUE;
	} else {
		$oResponse->isSuccess = FALSE;
	}
	$oResponse->TransID = $sTransID;
	if (!$oResponse->isSuccess) {
		$s = @ $oXML->message;
if ($bDebugMode) {
echo "\n\nRAW ERROR MESSAGE = $s\n\n";
}
		if (strpos($s,"\n") !== FALSE) {
			$asParts = explode("\n",$s);
			$s = @ $asParts[1];
		}
		$oResponse->FailMessage = $s;
		$oResponse->FailCode = $this->parseErrorCode($sResponse);
	}
	return $oResponse;
}

function parseErrorCode($s) {
	preg_match('/<code>(.*?)<\/code>/',$s,$asMatches);
	$sCode = @ $asMatches[1];
	if (empty($sCode)) {
		unset($asMatches);
		preg_match('/<processor-response-code>(.*?)<\/processor-response-code>/',$s,$asMatches);
		$sCode = @ $asMatches[1];
	}
	if (empty($sCode)) {
		unset($asMatches);
		preg_match('/<processor-settlement-response-code>(.*?)<\/processor-settlement-response-code>/',$s,$asMatches);
		$sCode = @ $asMatches[1];
	}
	$sCode = (empty($sCode)) ? '0' : $sCode;
	return $sCode;
}

public function adjustBilling($asBilling) {
	$a = array();
	$sName = @ $asBilling['billingName'];
	if (!empty($sName)) {
		list($sFirstName,$sLastName) = $this->splitName($sName);
	} else {
		$sFirstName = @ $asBilling['billingFirstName'];
		$sLastName = @ $asBilling['billingLastName'];
	}
	$a['first-name'] = $sFirstName;
	$a['last-name'] = $sLastName;
	$a['street-address'] = @ $asBilling['billingAddress1'];
	$a['extended-address'] = @ $asBilling['billingAddress2'];
	$a['locality'] = @ $asBilling['billingCity'];
	$a['region'] = @ $asBilling['billingState'];
	$a['postal-code'] = @ $asBilling['billingPostcode'];
	$a['country-code-alpha2'] = $asBilling['billingCountry'];
	return $a;
}

public function adjustShipping($asShipping) {
	$a = array();
	$sName = @ $asShipping['shippingName'];
	if (!empty($sName)) {
		list($sFirstName,$sLastName) = $this->splitName($sName);
	} else {
		$sFirstName = @ $asShipping['shippingFirstName'];
		$sLastName = @ $asShipping['shippingLastName'];
	}
	$a['first-name'] = $sFirstName;
	$a['last-name'] = $sLastName;
	$a['street-address'] = @ $asShipping['shippingAddress1'];
	$a['extended-address'] = @ $asShipping['shippingAddress2'];
	$a['locality'] = @ $asShipping['shippingCity'];
	$a['region'] = @ $asShipping['shippingState'];
	$a['postal-code'] = @ $asShipping['shippingPostcode'];
	$a['country-code-alpha2'] = $asShipping['shippingCountry'];
	return $a;
}

public function splitName($sName) {
	$asParts = explode(' ',$sName);
	$sFirstName = @ $asParts[0];
	$sLastName = @ $asParts[1];
	$sLastName .= ' ' . @ $asParts[2];
	$sLastName .= ' ' . @ $asParts[3];
	$sLastName = trim($sLastName);
	return array($sFirstName,$sLastName);
}

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
	$asFields = array();
	if (intval($nAmount) >= 1) {
$sXML = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<transaction>
 <amount>$nAmount</amount>
</transaction>
EOD;
	} else {
$sXML = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<transaction>
 <amount nil="true"></amount>
</transaction>
EOD;
	}
	// do the http connection with the data
	$sURL .= $this->MerchantID . '/transactions/' . $sChargeID . '/refund';
	$asOpt = array(
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_URL => $sURL,
		CURLOPT_ACCEPT_ENCODING => 'gzip',
		CURLOPT_HTTPAUTH => 1,
		CURLOPT_USERPWD => $this->PublicKey . ':' . $this->APIKey,
		CURLOPT_HTTPHEADER => array (
			'Accept: application/xml',
			'Content-Type: application/xml',
			'User-Agent: Braintree PHP Library 3.11.0',
			'X-ApiVersion: 4'
		),
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_POSTFIELDS => $sXML,
		CURLE_FTP_WEIRD_PASV_REPLY => 60
	);
	$hCurl = curl_init();
if ($bDebugMode) {
echo "\nCURL OPTIONS = \n";
print_r($asOpt);
echo "\n";
}
	curl_setopt_array($hCurl,$asOpt);
	curl_setopt($hCurl, CURLOPT_SSL_VERIFYPEER, $bVerifySSL);
	$sResponse = curl_exec($hCurl);
if ($bDebugMode) {
echo "\nRAW RESPONSE = \n$sResponse\n";
}
	$nErr = 0;
	$nResponseCode = 0;
	if ($sResponse === FALSE) {
		$nErr = curl_errno($hCurl);
		$sErr = curl_error($hCurl);
if ($bDebugMode) {
echo "\nCURL ERROR = $sErr ($nErr)\n";
}
		// debug with $nErr or $sErr if you want
	} else {
		$nResponseCode = curl_getinfo($hCurl, CURLINFO_HTTP_CODE);
		$nResponseCode = intval($nResponseCode);
if ($bDebugMode) {
echo "\nCURL RESPONSE CODE = $nResponseCode\n";
}
		// debug with $nResponseCode if you want
	}
	$nResponseCode = intval($nResponseCode);
	curl_close($hCurl);

	// parse the response and update our $oResponse object
	if ($nErr > 0) {
		$oResponse->FailMessage = "Error communicating with Braintree payment system.";
		return $oResponse;
	}
	if ($nResponseCode >= 400) {
		$oResponse->FailCode = $nResponseCode;
	}
	if ($nResponseCode == 400) {
		$oResponse->FailMessage = "Error from Braintree payment system. The request was unacceptable, often due to missing a required parameter, or an invalid token.";
		return $oResponse;
	}
	if ($nResponseCode == 401) {
		$oResponse->FailMessage = "Error from Braintree payment system. No valid API key provided.";
		return $oResponse;
	}
	if ($nResponseCode == 402) {
		$oResponse->FailMessage = "Error from Braintree payment system. The parameters were valid but the request failed.";
		return $oResponse;
	}
	if ($nResponseCode == 404) {
		$oResponse->FailMessage = "That transaction ID does not exist to be refunded.";
		return $oResponse;
	}
	if ($nResponseCode == 409) {
		$oResponse->FailMessage = "Error from Braintree payment system. The request conflicts with another request (perhaps due to using the same idempotent key).";
		return $oResponse;
	}
	if ($nResponseCode == 429) {
		$oResponse->FailMessage = "Error from Braintree payment system. Too many requests hit the API too quickly.";
		return $oResponse;
	}
	if ($nResponseCode >= 500) {
		$oResponse->FailMessage = "Error from Braintree payment system. Unknown error.";
		return $oResponse;
	}
	if (strpos($sResponse,'</transaction>') === FALSE) {
		$oResponse->FailMessage = "Error from Braintree payment system. Incomplete XML.";
		return $oResponse;
	}
	$oXML = simplexml_load_string($sResponse);
if ($bDebugMode) {
echo "\nXML OBJECT = \n";
print_r($oXML);
echo "\n";
}
	$sStatus = @ $oXML->transaction->status;
	if (empty($sStatus)) {
		$sStatus = $oXML->status;
	}
	$sTransID = @ $oXML->id;
//echo "\n\n\nSTATUS = $sStatus";
//echo "\n\n\nTRANSID = $sTransID";
	if (empty($sTransID)) {
		$oResponse->isSuccess = FALSE;
	} else if (in_array($sStatus, array(
		'settled', 'settlement_confirmed', 'submitted_for_settlement', 'authorized', 'settling', 'settlement_pending'
	))) {
		$oResponse->isSuccess = TRUE;
	} else {
		$oResponse->isSuccess = FALSE;
	}
	$oResponse->TransID = $sTransID;
	if (!$oResponse->isSuccess) {
		$s = @ $oXML->message;
if ($bDebugMode) {
echo "\n\nRAW ERROR MESSAGE = $s\n\n";
}
		if (strpos($s,"\n") !== FALSE) {
			$asParts = explode("\n",$s);
			$s = @ $asParts[1];
		}
		$oResponse->FailMessage = $s;
		$oResponse->FailCode = $this->parseErrorCode($sResponse);
	}
	if ($oResponse->FailCode == '91512') {
		$oResponse->FailMessage = 'Already refunded';
	}
	return $oResponse;
}

} // end TokGateway

