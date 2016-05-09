TokenPay Stripe Connector 1.0
-----------------------------

Some things to note about Stripe:

1. Stripe doesn't require Billing or Shipping. This connector stores those as metadata on the charge.

2. Stripe ignores setTestMode(). Instead, your API keys delineate whether you are test mode or not.


