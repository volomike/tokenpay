<?php

class TokenPay {

public static function createGateway($sCodeName) {
	$sCodeName = strtolower($sCodeName);
	require_once('../vendor/' . $sCodeName . '/Gateway.php');
	$oGateway = new TokGateway();
	return $oGateway;
}




} // end class
