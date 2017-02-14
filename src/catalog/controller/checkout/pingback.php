<?php

class ControllerCheckoutPingback extends Controller
{
    protected $paymentModel;

    const DEFAULT_PINGBACK_RESPONSE_SUCCESS = "OK";
    const TRANSACTION_CREATED = 0;
    const TRANSACTION_COMPLETED = 1;
    const TRANSACTION_CANCELED = 5;
    const TRANSACTION_SKIPPED = 3;
    const RECURRING_PEDING = 6;
    const RECURRING_ACTIVE = 2;
    const RECURRING_CANCELED = 4;
    
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
            $this->getCheckoutOrderModel()->confirm($order['order_id'], $defaultConfigs['config_order_status_id']);
        }

        if ($pingback->validate()) {
            $referenceId = $pingback->getReferenceId();
            $transactionStatus = self::TRANSACTION_CREATED;
            $recurringStatus = self::RECURRING_PEDING;

            if ($pingback->isDeliverable()) {
                $transactionStatus = self::TRANSACTION_COMPLETED;
                $recurringStatus = self::RECURRING_ACTIVE;

                $this->getPaymentModel()->callDeliveryApi($order, $referenceId);

                $this->getCheckoutOrderModel()->update(
                    $orderId,
                    $this->config->get($order['payment_code'] . '_complete_status'),
                    'Order approved!, Transaction Id: #' . $referenceId,
                    true
                );

            } elseif ($pingback->isCancelable()) {
                $transactionStatus = self::TRANSACTION_CANCELED;
                $recurringStatus = self::RECURRING_CANCELED;

                $this->getCheckoutOrderModel()
                    ->update($orderId, $this->config->get($order['payment_code'] .'_cancel_status'), 'Order canceled!', true);
            } elseif ($pingback->isUnderReview()) {
                $this->getCheckoutOrderModel()
                    ->update($orderId, $this->config->get($order['payment_code'] .'_under_review_status'), 'The order is under review!', true);
            }

            $this->updateRecurringOrder($order, $referenceId, $transactionStatus, $recurringStatus);

            echo self::DEFAULT_PINGBACK_RESPONSE_SUCCESS;
        } else {
            echo $pingback->getErrorSummary();
        }
    }

    public function updateRecurringOrder($orderInfo, $ref, $transactionStatus, $recurringStatus)
    {
        try {
            $orderRecurring = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_recurring` WHERE `order_id` = {$orderInfo['order_id']} ORDER BY order_recurring_id DESC LIMIT 1")->row;
            if (!empty($orderRecurring)) {
                $this->load->model('checkout/recurring');
                $orderRecurringId = $orderRecurring['order_recurring_id'];
                if (!empty($orderRecurring['profile_reference'])) {
                    $orderRecurring['name'] = $orderRecurring['product_name'];
                    $orderRecurring['quantity'] = $orderRecurring['product_quantity'];
                    $orderRecurring['description'] = $orderRecurring['profile_description'];
                    $orderRecurring['recurring_trial'] = $orderRecurring['trial'];
                    $orderRecurring['recurring_trial_cycle'] = $orderRecurring['trial_cycle'];
                    $orderRecurring['recurring_trial_duration'] = $orderRecurring['trial_duration'];
                    $orderRecurring['recurring_trial_price'] = $orderRecurring['trial_price'];

                    $orderRecurringId = $this->model_checkout_recurring->create($orderRecurring, $orderInfo['order_id'], '');
                }
                $this->model_checkout_recurring->addReference($orderRecurringId, $ref);

                $amount = $orderInfo['currency_value'] > 0
                    ? ($orderInfo['total'] * $orderInfo['currency_value'])
                    : $orderInfo['total'];
                $this->updateRecurringTransaction($orderRecurringId, $amount, $transactionStatus, $recurringStatus);
            }
        } catch (Exception $ex) {}
    }

    protected function updateRecurringTransaction($order_recurring_id, $amount, $transactionStatus, $recurringStatus) {
        try {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "order_recurring_transaction` SET `order_recurring_id` = {$order_recurring_id}, `created` = NOW(), `amount` = {$amount}, `type` = {$transactionStatus}");
            $this->db->query("UPDATE `" . DB_PREFIX . "order_recurring` SET `status` = {$recurringStatus} WHERE `order_recurring_id` = {$order_recurring_id} LIMIT 1");
        } catch (Exception $ex) {}
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
}
