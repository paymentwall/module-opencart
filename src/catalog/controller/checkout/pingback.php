<?php

class ControllerCheckoutPingback extends Controller
{
    const DEFAULT_PINGBACK_RESPONSE_SUCCESS = "OK";
    public function index()
    {
        $this->load->model('checkout/order');
        $this->load->model('payment/paymentwall');

        // Init Paymentwall configs
        $this->model_payment_paymentwall->initPaymentwallConfig();

        unset($_GET['route']);
        $pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);

        if ($pingback->validate()) {

            if ($pingback->isDeliverable()) {
                $this->model_checkout_order->update($pingback->getProduct()->getId(), $this->config->get('complete_status'));
            } elseif ($pingback->isCancelable()) {
                $this->model_checkout_order->update($pingback->getProduct()->getId(), $this->config->get('cancel_status'));
            }

            echo self::DEFAULT_PINGBACK_RESPONSE_SUCCESS;
        } else {
            echo $pingback->getErrorSummary();
        }

    }

}
