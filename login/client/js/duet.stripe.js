var DUET = DUET || {};
var stripeNS = DUET.stripe = {};

stripeNS.stripeDeferred = {};

stripeNS.$stripeForm = $('<br/>');

stripeNS.stripeInit = function($form){
    stripeNS.$stripeForm = $form;

    // this identifies your website in the createToken call below
    Stripe.setPublishableKey(DUET.config.stripe_publishable_key);
};

stripeNS.stripeHandleSubmit = function(params){
    stripeNS.stripeDeferred = new $.Deferred();

    stripeNS.$stripeForm.find('[type=submit]').attr('disabled', 'disabled');

    Stripe.createToken({
        number:params.cardNumber,
        cvc:params.cardCvc,
        exp_month:params.cardExpiryMonth,
        exp_year:params.cardExpiryYear
    }, stripeNS.stripeResponseHandler);

    return stripeNS.stripeDeferred;
};

stripeNS.stripeResponseHandler = function(status, response){

    if (response.error) {
        // show the errors on the form
        stripeNS.$stripeForm.find("#payment-errors").append(DUET.error(response.error.message));
        $('html, body').animate({
            scrollTop:stripeNS.$stripeForm.find("#payment-errors").offset().top
        }, 200);
        stripeNS.$stripeForm.find('[type=submit]').removeAttr("disabled");
        stripeNS.stripeDeferred.reject();
    } else {

        // token contains id, last4, and card type
        var token = response['id'];
        // insert the token into the form so it gets submitted to the server
        stripeNS.$stripeForm.append("<input type='hidden' name='stripeToken' value='" + token + "'/>");

        stripeNS.stripeDeferred.resolve(token);
    }

    return false;
};