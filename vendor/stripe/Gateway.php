<?php

// Copyright (c) 2016, Volo, LLC of South Carolina, USA

require_once('../base/AbstractGateway.php');

class TokGateway extends AbstractTokGateway {

// stripe uses same URL whether in test mode or live mode -- it's the keys that change that define either way
protected $URLTest = 'https://api.stripe.com/v1';
protected $URLLive = 'https://api.stripe.com/v1';

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
	$asFields['amount'] = $nAmount;
	$asFields['amount'] = intval($asFields['amount'] * 100); // Stripe wants a positive integer value in cents
	// do the http connection with the data
	$sURL .= "/charges/$sChargeID/refunds";
	$sFields = http_build_query($asFields);
	$asOpt = array(
		CURLOPT_POSTFIELDS => $sFields,
		CURLOPT_URL => $sURL,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_HTTPHEADER => array (
			'X-Stripe-Client-User-Agent: {"bindings_version":"3.12.1","lang":"php","lang_version":"5",' .
			'"publisher":"stripe","uname":"Linux i686"}',
			'User-Agent: Stripe/v1 PhpBindings/3.12.1',
			"Authorization: Bearer $sAPIKey",
			'Content-Type: application/x-www-form-urlencoded'
		),
		CURLE_TOO_MANY_REDIRECTS => 1,
		CURLE_FTP_WEIRD_PASV_REPLY => 80,
		CURLPROTO_SFTP => 1
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
		$oResponse->FailMessage = "Error communicating with Stripe payment system.";
		return $oResponse;
	}
	if ($nResponseCode >= 400) {
		$oResponse->FailCode = $nResponseCode;
	}
	if ($nResponseCode == 400) {
		$sMessage = '';
		@ $oJSON = json_decode($sResponse);
		$sMessage = @ $oJSON->error->message;
		if (strpos($sMessage,'has already been refunded') !== FALSE) {
			$oResponse->FailMessage = "Already refunded.";
			return $oResponse;
		}
		$oResponse->FailMessage = "Error from Stripe payment system. The request was unacceptable, often due to missing a required parameter, or an invalid token.";
		return $oResponse;
	}
	if ($nResponseCode == 401) {
		$oResponse->FailMessage = "Error from Stripe payment system. No valid API key provided.";
		return $oResponse;
	}
	if ($nResponseCode == 402) {
		$oResponse->FailMessage = "Error from Stripe payment system. The parameters were valid but the request failed.";
		return $oResponse;
	}
	if ($nResponseCode == 404) {
		$oResponse->FailMessage = "Error from Stripe payment system. The requested resource doesn't exist.";
		return $oResponse;
	}
	if ($nResponseCode == 409) {
		$oResponse->FailMessage = "Error from Stripe payment system. The request conflicts with another request (perhaps due to using the same idempotent key).";
		return $oResponse;
	}
	if ($nResponseCode == 429) {
		$oResponse->FailMessage = "Error from Stripe payment system. Too many requests hit the API too quickly.";
		return $oResponse;
	}
	if ($nResponseCode >= 500) {
		$oResponse->FailMessage = "Error from Stripe payment system. Unknown error.";
		return $oResponse;
	}
	if (strpos($sResponse,'}') === FALSE) {
		$oResponse->FailMessage = "Error from Stripe payment system. Incomplete JSON.";
		return $oResponse;
	}
	$oJSON = json_decode($sResponse);
if ($bDebugMode) {
echo "\nJSON OBJECT = \n";
print_r($oJSON);
echo "\n";
}
	$bSuccess = @ $oJSON->status;
	$sTransID = @ $oJSON->id;
	$oResponse->isSuccess = (($bSuccess == 'succeeded') and ($sTransID != ''));
	$oResponse->TransID = $sTransID;
	if (!$oResponse->isSuccess) {
		$oResponse->FailMessage = @ $oJSON->error->message;
		$oResponse->FailCode = @ $oJSON->error->code;
	}
	return $oResponse;
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

	$oResponse = (object) array();
	$oResponse->isSuccess = FALSE;
	$oResponse->TransID = NULL;
	$oResponse->FailCode = NULL;
	$oResponse->FailMessage = NULL;
	// translate the Billing, Shipping, and Purchase arrays as necessary for Stripe charges
	$asMeta = array();
	if (is_array($asBilling)) {
		$nCount = count($asBilling);
		if ($nCount > 0) {
			$asMeta = array_merge($asMeta,$asBilling);
		}
	}
	if (is_array($asShipping)) {
		$nCount = count($asShipping);
		if ($nCount > 0) {
			$asMeta = array_merge($asMeta,$asShipping);
		}
	}
	$asFields = array(
		'amount' => @ $asCharge['amount'],
		'currency' => @ $asCharge['currency'],
		'source' => @ $asCharge['token']
	);
	$asFields['amount'] = intval($asFields['amount'] * 100); // Stripe wants a positive integer value in cents
	$sDescTest = @ $asCharge['description'];
	if ($sDescTest) {
		$asFields['description'] = $sDescTest;
	}
	$asFields['metadata'] = $asMeta;
	// do the http connection with the data
	$sURL .= '/charges';
	$sFields = http_build_query($asFields);
	$asOpt = array(
		CURLOPT_POSTFIELDS => $sFields, // use http_build_query
		CURLOPT_URL => $sURL,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_HTTPHEADER => array (
			'X-Stripe-Client-User-Agent: {"bindings_version":"3.12.1","lang":"php","lang_version":"5",' .
			'"publisher":"stripe","uname":"Linux i686"}',
			'User-Agent: Stripe/v1 PhpBindings/3.12.1',
			"Authorization: Bearer $sAPIKey",
			'Content-Type: application/x-www-form-urlencoded'
		),
		CURLE_TOO_MANY_REDIRECTS => 1,
		CURLE_FTP_WEIRD_PASV_REPLY => 80,
		CURLPROTO_SFTP => 1
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
		$oResponse->FailMessage = "Error communicating with Stripe payment system.";
		return $oResponse;
	}
	if ($nResponseCode >= 400) {
		$oResponse->FailCode = $nResponseCode;
	}
	if ($nResponseCode == 400) {
		$oResponse->FailMessage = "Error from Stripe payment system. The request was unacceptable, often due to missing a required parameter, or an invalid token.";
		return $oResponse;
	}
	if ($nResponseCode == 401) {
		$oResponse->FailMessage = "Error from Stripe payment system. No valid API key provided.";
		return $oResponse;
	}
	if ($nResponseCode == 402) {
		$oResponse->FailMessage = "Error from Stripe payment system. The parameters were valid but the request failed.";
		return $oResponse;
	}
	if ($nResponseCode == 404) {
		$oResponse->FailMessage = "Error from Stripe payment system. The requested resource doesn't exist.";
		return $oResponse;
	}
	if ($nResponseCode == 409) {
		$oResponse->FailMessage = "Error from Stripe payment system. The request conflicts with another request (perhaps due to using the same idempotent key).";
		return $oResponse;
	}
	if ($nResponseCode == 429) {
		$oResponse->FailMessage = "Error from Stripe payment system. Too many requests hit the API too quickly.";
		return $oResponse;
	}
	if ($nResponseCode >= 500) {
		$oResponse->FailMessage = "Error from Stripe payment system. Unknown error.";
		return $oResponse;
	}
	if (strpos($sResponse,'}') === FALSE) {
		$oResponse->FailMessage = "Error from Stripe payment system. Incomplete JSON.";
		return $oResponse;
	}
	$oJSON = json_decode($sResponse);
if ($bDebugMode) {
echo "\nJSON OBJECT = \n";
print_r($oJSON);
echo "\n";
}
	$bSuccess = @ $oJSON->status;
	$sTransID = @ $oJSON->id;
	$oResponse->isSuccess = (($bSuccess == 'succeeded') and ($sTransID != ''));
	$oResponse->TransID = $sTransID;
	if (!$oResponse->isSuccess) {
		$oResponse->FailMessage = @ $oJSON->error->message;
		$oResponse->FailCode = @ $oJSON->error->code;
	}
	return $oResponse;
}

} // end TokGateway

