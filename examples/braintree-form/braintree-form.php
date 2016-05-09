<?php

// PLEASE SEE THE var clientToken variable below -- you must modify in order to use.

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.3.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
<script src="https://js.braintreegateway.com/js/braintree-2.23.0.min.js"></script>
<style type="text/css">.hideme{display:none;}</style>
</head>
<body>
<div class="container-fluid">
<p>&nbsp;</p>
<div class="row">
<div class="col col-md-offset-1 col-md-5">
<div class="panel panel-primary">
	<div class="panel-heading">Sample Credit Card Form</div>
	<div class="panel-body">
		<form accept-charset="UTF-8" action="../braintree-charge.php" enctype="multipart/form-data" id="checkout" method="post" novalidate="novalidate">
		<p>Normally other fields would go here like billing, shipping, product choice and options, etc.</p>

			<div class="form-group">
				<label class="control-label" for="ccname">Cardholder Name</label>
				<input class="form-control" id="ccname" data-braintree-name="cardholder_name" type="text" value="John Doe" />
			</div>

			<div class="form-group">
				<label class="control-label" for="ccn">Credit Card Number</label>
				<input class="form-control" id="ccn" data-braintree-name="number" type="text" value="4111 1111 1111 1111" />
			</div>
			
			<div class="form-group">
				<label class="control-label" for="ccexp">Expiration (MM/YY)</label>
				<input class="form-control" id="ccexp"  data-braintree-name="expiration_date" type="text" value="12/18" />
			</div>

			<div class="form-group">
				<label class="control-label" for="cvv">CVV/CVC</label>
				<input class="form-control" id="cvv"  data-braintree-name="cvv" type="text" value="100" />
			</div>
			
			<button id="btnPurchase" class="btn btn-success">Purchase</button>
			
	</form>

	<p>&nbsp;</p>
	<div id="error" class="alert alert-warning alert-dismissible hideme" role="alert">
	  <button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	  <span id="errormsg"></span>
	</div>


	</div>
</div><!-- panel -->
</div><!-- col -->
</div><!-- row -->
</div><!-- container -->
<script type="text/javascript">

function invalidForm(){
	// use the Stripe or Braintree credit card form validator and any other form validations you want here
    // Braintree: https://github.com/braintree/card-validator
    // Stripe: https://github.com/stripe/jquery.payment
    // return a string value of the problem
	return '';
}

jQuery(document).ready(function(){

	$('FORM#checkout').append('<input type="hidden" id="token" name="token" />');
	var clientToken = 'sandbox_dh555f3_z3ts5555z4spkzzw'; // generate this once from the Tokenization feature in the dashboard
    braintree.setup(clientToken, 'custom', {
    	id:'checkout',
    	onPaymentMethodReceived: function (paymentMethod) { // Braintree's docs fail to mention this happens only on a form submit
    		$('#btnPurchase').addClass('disabled').attr('disabled');
    		var sErr = invalidForm();
			if (sErr) {
				$('#errormsg').html(sErr);
				$('#error').show();
				$('#btnPurchase').removeClass('disabled').removeAttr('disabled');
				return false;
			} // else...
			$('#token').val(paymentMethod.nonce);
			$('FORM#checkout').submit();
			return true;
    	}
    });
	
	$('.alert BUTTON.close').click(function(){
		$(this).parent().hide();
	});

});

</script>
</body>
</html>

