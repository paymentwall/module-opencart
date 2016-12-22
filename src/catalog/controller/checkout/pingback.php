<?php

class ControllerCheckoutPingback extends Controller
{
    const DEFAULT_PINGBACK_RESPONSE_SUCCESS = "OK";

    public function index()
    {
        $this->load->model('setting/setting');

        $request = $this->request->request;
        $defaultConfigs = $this->model_setting_setting->getSetting('config');
        $orderId = $this->getOrderIdFromRequest($request);
        $order = $this->getCheckoutOrderModel()->getOrder($orderId);

        if (!$order) {
            die('Order invalid!');
        }

        // Init payment configs for pingback handle
        $this->loadPaymentModel($order['payment_code']);
        $this->getPaymentModel()->initConfig(true);

        $pingback = new Paymentwall_Pingback($this->request->get, $this->getRealIpAddress($this->request->server));

        // Confirm order if status is null
        if (!$order['order_status']) {
            $this->getCheckoutOrderModel()->addOrderHistory($order['order_id'], $defaultConfigs['config_order_status_id'], '', true);
        }

        if ($pingback->validate()) {
            if ($pingback->isDeliverable()) {
                $this->getPaymentModel()->callDeliveryApi($order, $pingback->getReferenceId());
                if ($order['order_status_id'] != $this->config->get($order['payment_code'] . '_complete_status')) {
                    $this->getCheckoutOrderModel()->addOrderHistory($pingback->getProduct()->getId(), $this->config->get($order['payment_code'] .'_complete_status'), 'Order approved!, Transaction Id: #' . $pingback->getReferenceId(), true);
                }
            } elseif ($pingback->isCancelable()) {
                if ($order['order_status_id'] != $this->config->get($order['payment_code'] . '_cancel_status')) {
                    $this->getCheckoutOrderModel()->addOrderHistory($pingback->getProduct()->getId(), $this->config->get($order['payment_code'] .'_cancel_status'), 'Order Cancel !', true);
                }
            } elseif ($pingback->isUnderReview()) {
                if ($order['order_status_id'] != $this->config->get($order['payment_code'] . '_under_review_status')) {
                    $this->getCheckoutOrderModel()->addOrderHistory($pingback->getProduct()->getId(), $this->config->get($order['payment_code'] .'_under_review_status'), 'The order is under review !', true);
                }
            }

            echo self::DEFAULT_PINGBACK_RESPONSE_SUCCESS;
        } else {
            echo $pingback->getErrorSummary();
        }

    }

    /**
     * @param $server
     * @return string
     */
    public function getRealIpAddress($server)
    {
        if (!empty($server['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip = $server['HTTP_CLIENT_IP'];
        } elseif (!empty($server['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip = $server['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $server['REMOTE_ADDR'];
        }

        // Validate Ip
        if (!(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))) {
            return $server['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * @return ModelPaymentBrick | ModelPaymentPaymentwall
     */
    protected function getPaymentModel()
    {
        return $this->paymentModel;
    }

    /**
     * @param $paymentCode
     * @return mixed
     */
    protected function loadPaymentModel($paymentCode)
    {
        if (!in_array($paymentCode, ['brick', 'paymentwall'])) {
            die('Payment method is invalid!');
        }

        $this->load->model('payment/' . $paymentCode);
        $modelName = 'model_payment_' . $paymentCode;
        $this->paymentModel = $this->{$modelName};

        return $this->paymentModel;
    }

    /**
     * @param $request
     * @return mixed
     */
    protected function getOrderIdFromRequest($request)
    {
        return @$request['goodsid'];
    }

    /**
     * @return ModelCheckoutOrder
     */
    protected function getCheckoutOrderModel()
    {
        if (!$this->model_checkout_order) {
            $this->load->model('checkout/order');
        }
        return $this->model_checkout_order;
    }

}
