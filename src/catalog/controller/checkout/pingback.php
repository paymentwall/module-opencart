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
        $order = $this->model_checkout_order->getOrder($pingback->getProduct()->getId());

        if (!$order) {
            die('Order invalid!');
        }

        if ($pingback->validate()) {

            if ($pingback->isDeliverable()) {
                $this->model_payment_paymentwall->callDeliveryApi($order, $pingback->getReferenceId());
                $this->model_checkout_order->addOrderHistory($pingback->getProduct()->getId(), $this->config->get('paymentwall_complete_status'));
            } elseif ($pingback->isCancelable()) {
                $this->model_checkout_order->addOrderHistory($pingback->getProduct()->getId(), $this->config->get('paymentwall_cancel_status'));
            }

            echo self::DEFAULT_PINGBACK_RESPONSE_SUCCESS;
        } else {
            echo $pingback->getErrorSummary();
        }

    }

}
