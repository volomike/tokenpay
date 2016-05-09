TokenPay Braintree Connector 1.0
--------------------------------

Some things to note about Braintree:

1. Braintree doesn't require Billing or Shipping. This connector stores those as metadata on the charge. However, it does require
cardholder name, which it gets from billingName. So, you'll need to include a Billing block as demonstrated in the examples.

2. Braintree has a super finicky Javascript client API for generating that token. Because of this confusion, we included an example
charge form so that you can see the proper way to do validation before a form submit. They also have some naming differences -- 
you must create a token, then use it to create a nonce, then submit the nonce, and that's a bit different than other payment
gateways that have you use a public key and credit fields to create a token, and then submit the token. So, once the nonce arrives
at the server, we refer to it as "the token" in order to be consistent with other TokenPay gateways, but really it's a "nonce".

More strangeness in that Javascript client API is documented here:

http://stackoverflow.com/a/37014825/105539
http://stackoverflow.com/a/37022494/105539



