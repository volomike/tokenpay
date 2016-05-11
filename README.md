# TokenPay
Version 0.03

### Currently in Beta

The central purpose of this community PHP project is to produce almost a single PHP API that addresses multiple token-based payment 
gateways for charges and refunds. What it does not try to do is every aspect of eCommerce APIs -- not even subscriptions. The 
other main purpose is to make something super easy to install, use immediately, extend easily, and with readable code and no
heavy, slow framework dependencies.

Another major reason for this project is because the payment gateways often have poor documentation, and because often the official
PHP libs are inferior, have poor error handling, and contain more than what you may actually need. Some gateways are even unusable
unless you file a support ticket, such as finding out that sandbox password resets are not possible on a particular gateway, or
unless you turn off something like a Demo Mode.

The ulterior desire, however, is to encourage all payment gateways to simplify and be more developer-friendly like Stripe, and to
especiallly use token-based payments in their API offerings.

Where this project deviates from OmniPay is that the code is easier to follow, doesn't require a heavy framework dependency (it's
straight PHP), and is easier to install, use, and extend. Also, OmniPay supports more API features that we find superfluous. 
OmniPay also supports non-token-based charges, while this one does not.

Currently supported gateways:

* Stripe
* 2Checkout
* BrainTree (owned by PayPal)

Stripe innovated in the eCommerce world with the introduction of token-based payments. Others such as PayPal, Braintree, and 
2Checkout have followed suit. This is quite an innovation. So, for PCI compliance, businesses never store credit cards when they do
charges from their website. Instead, before an order form is submitted, an AJAX call in Javascript is made to request a token for 
the form's credit card detail. This token is then passed back to the business webserver. From there, it is authenticated back to the 
payment gateway with some kind of HTTP POST and response.

The desirable thing now, however, is consistency. Developers want a consistent interface (for the most part) that speaks to all
the token-based payment systems. So, in a sense (with some minor exceptions), they can write code once and point it to multiple
gateways. This is very useful so that you get a bit more fault tolerance in your cart, and can work on a dispute with one payment
gateway while still processing customers through your other payment gateways.

If you want subscriptions, then you're better off sending an annual email for renewal and doing another charge because automatic 
subscriptions can increase your risk of chargebacks and potentially trigger more account reviews. Also by not having cards on file
for subscriptions, your PCI compliance issues go down.

This API currently does not take into account taxation. (I highly recommend a non-sales-tax area such as Delaware for your
company.)

Please note that each vendor folder may have their own README.md for extra things to note per that platform.

In practice it's a good idea to make your payment forms use multiple payment gateways, round robin, on transactions. It's also a
good idea to ping the Javascript API of each payment gateway before permitting use of it -- if the API doesn't respond back within
like 4 seconds, then skip it and use another payment gateway. (Just note that your server ping may get flagged as spam and be
blocked, so consider doing this on the client browser via jQuery/AJAX.) By doing this, your cart/checkout pages provide more uptime.
It also lets you work with a payment gateway on a dispute (where you can remove it from a customer mix) and yet still receive money
on the other payment gateways. Last but not least, some payment gateways may withhold funds and by using more payment gateways, you
have the ability to access some of your funds while working on a dispute with one payment gateway. So, the more payment gateways you
use, the better. This is especially true if you are needing to pay a third-party.

#General Usage

The following is general usage, but alters only slightly for each vendor. Review the README.md for each vendor for specific extra
things you will have to do for each vendor.

Charges
-------

Your credit card form needs to generate a token. Each vendor has their own Javascript way to generate this. 

After a successful charge, you should also store the $sTransID, $sPrice, and $sCurrency in your database because you will need this
for refunds via the API. Note that the $sTransID becomes the $sChargeID when doing refunds.

We also recommend that if you store the data in a database, that you use something like AES (or better) encryption to
store PII (Personaly Identifiable Information) such as email, phone, and street1 values. This can also improve your PCI audit
score, should you require a PCI audit. And, like the payment gateways will tell you -- never do an HTTP POST of the credit card
information on the HTML form. This is because it will trigger a PCI requirement and audit on your end, and is the reason why they
pass you a token in the first place.

Note that some vendors require that you pass Billing, while some do not. Also, the Shipping is optional. Some vendors may store
the billing and/or shipping information as custom metadata, while others do not.


```php
require_once('tokenpay/base/TokenPay.php');
$o = TokenPay::createGateway('vendor');
$o->setDebugMode(TRUE); // whether to send output of sending and receiving to the screen
$o->setAPIKey($sAPIKey);
$o->setTestMode(TRUE); // use the sandbox
$o->setBilling(array(
	'billingName' => $sName,
	'billingAddress1' => $sStreet1,
	'billingAddress2' => $sStreet2,
	'billingCity' => $sCity,
	'billingState' => $sState,
	'billingPostcode' => $sZip,
	'billingCountry' => $sCountry,
	'billingPhone' => $sPhone,
	'email' => $sEmail
));
$o->setShipping(array(
	'shippingName' => $sSName,
	'shippingAddress1' => $sSStreet1,
	'shippingAddress2' => $sSStreet2,
	'shippingCity' => $sSCity,
	'shippingState' => $sSState,
	'shippingPostcode' => $sSZip,
	'shippingCountry' => $sSCountry,
	'shippingPhone' => $sSPhone
));
$o->setCharge(array(
	'amount' => $sPrice,
	'currency' => $sCurrency,
	'token' => $sToken,
	'description' => $sDesc
));
$oResponse = $o->chargeCard();
if ($oResponse->isSuccess) {
	$sTransID = $oResponse->TransID;
	die('SUCCESS: ' . $sTransID);
} else {
	$sError = $oResponse->FailMessage;
	$nError = $oResponse->FailCode;
	die('FAIL: ' . $sError . ' (' . $nError . ')');
}
```

Refunds
-------

If you weren't storing the $sTransID (which becomes the $sChargeID here) and $sPrice (amount) of the charge, then you will need to
do so in order to be able to use this API for refunds. Note that some vendors may also want you to pass the $sCurrency, too.


```php
require_once('tokenpay/base/TokenPay.php');
$o = TokenPay::createGateway('vendor');
$o->setDebugMode(TRUE); // whether to send output of sending and receiving to the screen
$o->setAPIKey($sAPIKey);
$o->setTestMode(TRUE); // use the sandbox
$oResponse = $o->refundCharge($sChargeID,$nAmount);
if ($oResponse->isSuccess) {
	$sTransID = $oResponse->TransID;
	die('SUCCESS: ' . $sTransID);
} else {
	$sError = $oResponse->FailMessage;
	$nError = $oResponse->FailCode;
	die('FAIL: ' . $sError . ' (' . $nError . ')');
}
```

Subscriptions
-------------

TokenPay will not support subscriptions. We recommend avoiding using the API for these because:

* it often increases your risk level with the payment gateway
* may cause the payment gateway to require you to do a PCI audit
* increases your chargeback risk

A better solution is to do an annual charge and then have your code issue an email annually for the customer to click to be 
charged again.

Note that OmniPay also does not support Subscriptions.

#Extra Notes

1. Note that we do a lightweight SSL verification by default. You can turn this off with setVerifySSL(FALSE). Should you need a 
stronger version of the SSL verification, then change the vendor package before the curl_exec() call so that you follow the
proper fix from this blog post:

http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/

...which recommends also adding CURLOPT_SSL_VERIFYHOST (set to 2) and CURLOPT_CAINFO, passing a CA certificate.

Also, if you're having trouble with your API, turning off SSL verification temporarily can help you determine if that's the cause.

#Contributing

See the CONTRIBUTING.md

#Roadmap

1. Suggest ways to make this better without making it harder to install, harder to use, harder to extend, and harder to read, and
without adding framework dependencies -- keep it native PHP.

2. Improve error handling.

3. Add more payment gateways.

4. Vote on new features and add them in.

