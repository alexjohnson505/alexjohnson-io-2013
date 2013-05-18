<?php


class StripePayment extends Model
{
    protected $token;
    public $amount;

    //the stripe api requires the amount in cents
    public $amount_in_cents;

    function init($amount, $post_params, $invoice = null)
    {
        //there is nothing to do if we don't have the stripe token
        if (!isset($_POST['stripeToken'])) {
            $this->set_error('amount', 'Invalid Token');
            return false;
        }

        $this->load_library('stripe/Stripe');

        // set your secret key: remember to change this to your live secret key in production
        // see your keys here https://manage.stripe.com/account
        Stripe::setApiKey(get_config('payments.stripe.secret_key'));

        // get the credit card details submitted by the form
        $this->token = $post_params['stripeToken'];

        $this->amount = $amount;
        $this->amount_in_cents = $this->amount * 100;

        return true;
    }


    function submit()
    {
        try {
            // create the charge on Stripe's servers - this will charge the user's card
            $result = Stripe_Charge::create(array(
                    "amount" => $this->amount_in_cents, // amount in cents, again
                    "currency" => "usd",
                    "card" => $this->token,
                    "description" => get_config('company.name')) //todo:set the invoice number
            );

            //make sure we have a valid stripe charge object
            if (!($result instanceof Stripe_Charge)) {
                $this->set_error('amount', 'There was an error charging the card');
                return false;
            } else return $result->id;

        } catch (Stripe_AuthenticationError $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $this->set_error('amount', 'There was an error charging the card');
            return false;
        }
        catch(Stripe_CardError $e) {
            // Since it's a decline, Stripe_CardError will be caught
            $body = $e->getJsonBody();
            $err  = $body['error'];

            $this->set_error('amount',  $err['message']);
            return false;
        } catch (Stripe_InvalidRequestError $e) {

            // Invalid parameters were supplied to Stripe's API
            $this->set_error('amount', 'Invalid request');
            return false;

        } catch (Stripe_ApiConnectionError $e) {
            // Network communication with Stripe failed
            $this->set_error('amount', 'Unable to connect');
            return false;

        } catch (Stripe_Error $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            $this->set_error('amount', 'There was an error charging the card');
            return false;

        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            $this->set_error('amount', 'There was an error. Please contact an admin');
            return false;

        }

    }

    function get(){
        echo "hii";
        //override the default get function, since there is currently no functionality in the app to view a payment
        //or list payments
    }
}