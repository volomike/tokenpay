<?php

// Copyright (c) 2016, Volo, LLC of South Carolina, USA

require_once('../base/AbstractGateway.php');

class TokGateway extends AbstractTokGateway {

protected $URLTest = 'https://sandbox.2checkout.com/checkout/api/1/XXXX/rs/authService';
protected $URLLive = 'https://www.2checkout.com/checkout/api/1/XXXX/rs/authService';

protected $URLTestRefund = 'https://sandbox.2checkout.com/api/sales/refund_invoice';
protected $URLLiveRefund = 'https://www.2checkout.com/api/sales/refund_invoice';

protected $SellerID = '';
protected $MerchantOrderID = '';
protected $Username = '';
protected $Password = '';

public function setUsername($sUsername) {
	$this->Username = $sUsername;
}

public function setPassword($sPassword) {
	$this->Password = $sPassword;
}

public function setSellerID($sSellerID) {
	$this->SellerID = $sSellerID;
}

public function setMerchantOrderID($sMerchantOrderID) {
	$this->MerchantOrderID = $sMerchantOrderID;
}

public function adjustBilling($asBilling) {
	$asTemp = (object) $asBilling;
	$asBilling = $asTemp;
	$a = array(
		'name' => @ $asBilling->billingName,
		'addrLine1' => @ $asBilling->billingAddress1,
		'addrLine2' => @ $asBilling->billingAddress2,
		'city' => @ $asBilling->billingCity,
		'state' => @ $asBilling->billingState,
		'zipCode' => @ $asBilling->billingPostcode,
		'country' => @ $asBilling->billingCountry,
		'email' => @ $asBilling->billingEmail,
		'phoneNumber' => @ $asBilling->billingPhone
	);
	$sTest = $a['name'];
	if (empty($sTest)) {
		$a['name'] = @ $asBilling->name;
	}
	$sTest = $a['email'];
	if (empty($sTest)) {
		$a['email'] = @ $asBilling->email;
	}
	return $a;
}

public function adjustShipping($asShipping) {
	$asTemp = (object) $asShipping;
	$asShipping = $asTemp;
	$a = array(
		'cust_shippingName' => @ $asShipping->shippingName,
		'cust_shippingAddrLine1' => @ $asShipping->shippingAddress1,
		'cust_shippingAddrLine2' => @ $asShipping->shippingAddress2,
		'cust_shippingCity' => @ $asShipping->shippingCity,
		'cust_shippingState' => @ $asShipping->shippingState,
		'cust_shippingZipCode' => @ $asShipping->shippingPostcode,
		'cust_shippingCountry' => @ $asShipping->shippingCountry,
		'cust_shippingPhoneNumber' => @ $asShipping->shippingPhone
	);
	$sTest = $a['cust_shippingName'];
	if (empty($sTest)) {
		$a['cust_shippingName'] = @ $asShipping->name;
	}
	return $a;
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

	$sSellerID = $this->SellerID;
	$sMerchantOrderID = $this->MerchantOrderID;
	$sMerchantOrderID = (empty($sMerchantOrderID)) ? uniqid() : $sMerchantOrderID;
	$sURL = str_replace('XXXX',$sSellerID,$sURL);

	$oResponse = (object) array();
	$oResponse->isSuccess = FALSE;
	$oResponse->TransID = NULL;
	$oResponse->FailCode = NULL;
	$oResponse->FailMessage = NULL;
	// translate the Billing, Shipping, and Purchase arrays as necessary for 2Checkout charges
	$asFields = array(
		'sellerId' => $sSellerID,
		'merchantOrderId' => $sMerchantOrderID,
		'token' => @ $asCharge['token'],
		'currency' => @ $asCharge['currency'],
		'total' => @ $asCharge['amount'],
	);
	if (is_array($asBilling)) {
		$nCount = count($asBilling);
		if ($nCount > 0) {
			$asFields['billingAddr'] = $this->adjustBilling($asBilling);
		}
	}
	if (is_array($asShipping)) {
		$nCount = count($asShipping);
		if ($nCount > 0) {
			$asShipping = $this->adjustShipping($asShipping);
			$asFields = array_merge($asFields,$asShipping);
		}
	}
	$sDescTest = @ $asCharge['description'];
	if ($sDescTest) {
		$asFields['cust_description'] = $sDescTest;
	}
	$asFields['privateKey'] = $sAPIKey;
	// do the http connection with the data
	$sFields = json_encode($asFields);
	$nLen = mb_strlen($sFields,'UTF-8');
	$asOpt = array (
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HTTPHEADER => 
		array (
			'content-type:application/json',
			"content-length:$nLen",
		),
		CURLOPT_USERAGENT => '2Checkout PHP/0.1.0%s',
		CURLOPT_POSTFIELDS => $sFields,
	);
	$hCurl = curl_init($sURL);
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
		$oResponse->FailMessage = "Error communicating with 2Checkout payment system.";
		return $oResponse;
	}
	if ($nResponseCode >= 400) {
		$oResponse->FailCode = $nResponseCode;
	}
	if ($nResponseCode == 400) {
		@ $oJSON = json_decode($sResponse);
		$sMessage = @ $oJSON->exception->errorMsg;
		$sCode = @ $oJSON->exception->errorCode;
		if (!empty($sCode)) {
			$oResponse->FailCode = $sCode;
			$oResponse->FailMessage = $sMessage;
			return $oResponse;
		}
		$oResponse->FailMessage = "Error from 2Checkout payment system. The request was unacceptable, often due to missing a required parameter, or an invalid token.";
		return $oResponse;
	}
	if ($nResponseCode == 401) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. No valid API key provided.";
		return $oResponse;
	}
	if ($nResponseCode == 402) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. The parameters were valid but the request failed.";
		return $oResponse;
	}
	if ($nResponseCode == 404) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. The requested resource doesn't exist.";
		return $oResponse;
	}
	if ($nResponseCode == 409) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. The request conflicts with another request (perhaps due to using the same idempotent key).";
		return $oResponse;
	}
	if ($nResponseCode == 429) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. Too many requests hit the API too quickly.";
		return $oResponse;
	}
	if ($nResponseCode >= 500) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. Unknown error.";
		return $oResponse;
	}
	if (strpos($sResponse,'}') === FALSE) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. Incomplete JSON.";
		return $oResponse;
	}
	$oJSON = json_decode($sResponse);
if ($bDebugMode) {
echo "\nJSON OBJECT = \n";
print_r($oJSON);
echo "\n";
}
	$bSuccess = @ $oJSON->response->responseCode;
	$sTransID = @ $oJSON->response->orderNumber;
	$oResponse->isSuccess = (($bSuccess == 'APPROVED') and ($sTransID != ''));
	$oResponse->TransID = $sTransID;
	if (!$oResponse->isSuccess) {
		$oResponse->FailMessage = @ $oJSON->exception->errorMsg;
		$oResponse->FailCode = @ $oJSON->exception->errorCode;
	}
	return $oResponse;
}

public function sendRefund(
	$sURL, // ignore in this case, we'll override with a custom one for refunds
	$sAPIKey,
	$bTestMode,
	$bDebugMode,
	$bVerifySSL,
	$sChargeID,
	$nAmount // ignored by 2CO in this case
) {
	$sUser = $this->Username;
	$sPass = $this->Password;
	$bTestMode = $this->TestMode;
	$sURL = ($bTestMode) ? $this->URLTestRefund : $this->URLLiveRefund;

	$oResponse = (object) array();
	$oResponse->isSuccess = FALSE;
	$oResponse->TransID = NULL;
	$oResponse->FailCode = NULL;
	$oResponse->FailMessage = NULL;
	$asFields = array(
		'sale_id' => $sChargeID,
		'comment' => 'Customer decided the item did not meet expectations and it was within their refund period.',
		'category' => 2 // did not like item
	);
	// do the http connection with the data
	$sFields = json_encode($asFields);
	$nLen = strlen($sFields);
	$asOpt = array (
		CURLOPT_HEADER => 0,
		CURLOPT_POST => 0,
		CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
		CURLOPT_USERPWD => "$sUser:$sPass",
		CURLOPT_HTTPHEADER =>  array("Accept: application/json"),
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_USERAGENT => '2Checkout PHP/0.1.0%s',
		CURLOPT_POSTFIELDS => $sFields
	);
	$hCurl = curl_init($sURL);
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
		$oResponse->FailMessage = "Error communicating with 2Checkout payment system.";
		return $oResponse;
	}
	if ($nResponseCode >= 400) {
		$oResponse->FailCode = $nResponseCode;
	}
	if ($nResponseCode == 400) {
		@ $oJSON = json_decode($sResponse);
		$sMessage = @ $oJSON->exception->errorMsg;
		$sCode = @ $oJSON->exception->errorCode;
		if (!empty($sCode)) {
			$oResponse->FailCode = $sCode;
			$oResponse->FailMessage = $sMessage;
			return $oResponse;
		}
		$oResponse->FailMessage = "Error from 2Checkout payment system. The request was unacceptable, often due to missing a required parameter, or an invalid token.";
		return $oResponse;
	}
	if ($nResponseCode == 401) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. No valid API key provided.";
		return $oResponse;
	}
	if ($nResponseCode == 402) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. The parameters were valid but the request failed.";
		return $oResponse;
	}
	if ($nResponseCode == 404) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. The requested resource doesn't exist.";
		return $oResponse;
	}
	if ($nResponseCode == 409) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. The request conflicts with another request (perhaps due to using the same idempotent key).";
		return $oResponse;
	}
	if ($nResponseCode == 429) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. Too many requests hit the API too quickly.";
		return $oResponse;
	}
	if ($nResponseCode >= 500) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. Unknown error.";
		return $oResponse;
	}
	if (strpos($sResponse,'}') === FALSE) {
		$oResponse->FailMessage = "Error from 2Checkout payment system. Incomplete JSON.";
		return $oResponse;
	}
	$oJSON = json_decode($sResponse);
if ($bDebugMode) {
echo "\nJSON OBJECT = \n";
print_r($oJSON);
echo "\n";
}
	$bSuccess = @ $oJSON->response->responseCode;
	$sTransID = @ $oJSON->response->orderNumber;
	$oResponse->isSuccess = (($bSuccess == 'APPROVED') and ($sTransID != ''));
	$oResponse->TransID = $sTransID;
	if (!$oResponse->isSuccess) {
		$oResponse->FailMessage = @ $oJSON->exception->errorMsg;
		$oResponse->FailCode = @ $oJSON->exception->errorCode;
	}
	return $oResponse;

}



} // end TokGateway
