TokenPay 2Checkout Connector 1.0
--------------------------------

Some things to note about 2Checkout:

1. Charges use the API key, but refunds do not. For refunds, you have to create a new user and then give it Admin API and API Update
permissions, and then use the user/pass technique in that API, rather than the API key. Note you can't use your existing user
account -- you must use a separate one. Also note that this new user account will be blocked from being able to use the admin
interface, oddly. So, that new user will be API access only. This is kind of quirky, IMHO, and is one reason why Stripe is still
the best thing for developers.

2. Always turn Demo Mode to Off on the dashboard, both in the sandbox and in production. Otherwise, the API won't work on charges 
for some odd reason. Again, another quirk that Stripe doesn't have.

3. Never lose your sandbox password. Currently, 2Checkout does not support sandbox password resets, and they don't say that anywhere
in their documentation!

4. On refunds, 2Checkout requires that it is passed some explanation for the refund, and a refund category. We assume they review
these on your account and that it may affect your risk level with them. This connector hard-codes these like so:

```
'comment' => 'Customer decided the item did not meet expectations and it was within their refund period.',
'category' => 2 // did not like item
```

If you need to change that, then you have a couple choices: (a) change it in this connector, or (b) edit the connector so that you
can pass those items as extra parameters with either a setXXX class method and a protected property that you can read and use during
the refund, or pass them as extra parameters on the refund class methods.

5. 2Checkout refunds use setUsername() and setPassword() instead of setAPIKey(). Again, that's quirky IMHO.

6. 2Checkout charges require extra class methods: setSellerID() and setMerchantOrderID().

7. 2Checkout charges require passing correct Billing info. Note that you actually don't need to pass a phone number in this API,
but 2Checkout requires that this parameter be sent, anyway, even if empty.

8. 2Checkout charges do not store shipping info because this API marks the item as intangible. Instead, the API stores this
information as custom metadata. It's not a big deal whether you mark the item as intangible or tangible as far as the payment
gateway is concerned -- just as long as your own application logic reflects that the item is tangible or not.

9. Note that the official 2Checkout PHP lib uses strlen() on strings submitted through their API and doesn't take into account
UTF-8 characters. Our version uses mb_strlen(), instead, to fix that.

