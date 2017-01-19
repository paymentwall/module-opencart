<?php
if (!class_exists('Paymentwall_Config'))
    require_once DIR_SYSTEM . '/thirdparty/paymentwall-php/lib/paymentwall.php';

class ModelPaymentBrick extends Model
{
    /**
     * @param $address
     * @param $total
     * @return array
     */
    public function getMethod($address, $total)
    {
        $this->load->language('payment/brick');
        $method_data = array();
        if ($this->config->get('brick_status') && $total > 0) {
            $method_data = array(
                'code' => 'brick',
                'title' => $this->language->get('text_title'),
                'sort_order' => $this->config->get('brick_sort_order'),
                'terms' => ''
            );
        }

        return $method_data;
    }

    /**
     * @param bool $pingback
     */
    public function initConfig($pingback = false)
    {
        if ($pingback) {
            Paymentwall_Config::getInstance()->set(array(
                'api_type' => Paymentwall_Config::API_GOODS,
                'private_key' => $this->config->get('brick_secret_key')
            ));
        } else {
            Paymentwall_Config::getInstance()->set(array(
                'api_type' => Paymentwall_Config::API_GOODS,
                'public_key' => $this->config->get('brick_test_mode')
                    ? $this->config->get('brick_public_test_key')
                    : $this->config->get('brick_public_key'),
                'private_key' => $this->config->get('brick_test_mode')
                    ? $this->config->get('brick_private_test_key')
                    : $this->config->get('brick_private_key')
            ));
        }
    }

    /**
     * @param $orderInfo
     * @param $request
     * @return Paymentwall_Charge
     */
    public function createChargePayment($orderInfo, $request)
    {
        $charge = new Paymentwall_Charge();
        $charge->create(array_merge(
            $this->prepareCardInfo($orderInfo, $request),
            $this->getUserProfileData($orderInfo)
        ));
        return $charge;
    }

    /**
     * @param $order
     * @return array
     */
    public function callDeliveryApi($order, $refId)
    {
        if ($this->config->get('brick_delivery')) {
            // Delivery Confirmation
            $delivery = new Paymentwall_GenerericApiObject('delivery');
            return $delivery->post($this->prepareDeliveryData($order, $refId));
        }
        return [];
    }

    protected function prepareCardInfo($orderInfo, $request)
    {
        $data = array(
            'email' => $orderInfo['email'],
            'amount' => number_format($orderInfo['total'], 2, '.', ''),
            'currency' => $orderInfo['currency_code'],
            'token' => $request['cc_brick_token'],
            'fingerprint' => $request['cc_brick_fingerprint'],
            'plan' => $orderInfo['order_id'],
            'description' => 'Order #' . $orderInfo['order_id']
        );

        if (!empty($request['cc_brick_secure_token'])) {
            $data['secure_token'] = $request['cc_brick_secure_token'];
        }

        if (!empty($request['cc_brick_charge_id'])) {
            $data['charge_id'] = $request['cc_brick_charge_id'];
        }

        return $data;
    }

    /**
     * @param $order
     * @return array
     */
    protected function prepareDeliveryData($order, $ref)
    {
        return array(
            'payment_id' => $ref,
            'type' => 'digital',
            'status' => 'delivered',
            'estimated_delivery_datetime' => date('Y/m/d H:i:s'),
            'estimated_update_datetime' => date('Y/m/d H:i:s'),
            'refundable' => 'yes',
            'details' => 'Item will be delivered via email by ' . date('Y/m/d H:i:s'),
            'shipping_address[email]' => $order['email'],
            'shipping_address[firstname]' => $order['shipping_firstname'],
            'shipping_address[lastname]' => $order['shipping_lastname'],
            'shipping_address[country]' => $order['shipping_country'],
            'shipping_address[street]' => $order['shipping_address_1'],
            'shipping_address[state]' => $order['shipping_zone'],
            'shipping_address[phone]' => $order['telephone'],
            'shipping_address[zip]' => $order['shipping_postcode'],
            'shipping_address[city]' => $order['shipping_city'],
            'reason' => 'none',
            'is_test' => $this->config->get('paymentwall_test') ? 1 : 0,
        );
    }

    /**
     * @param $orderInfo
     * @return array
     */
    protected function getUserProfileData($orderInfo)
    {
        return array(
            'customer[city]' => $orderInfo['payment_city'],
            'customer[state]' => $orderInfo['payment_zone'],
            'customer[address]' => $orderInfo['payment_address_1'],
            'customer[country]' => $orderInfo['payment_iso_code_2'],
            'customer[zip]' => $orderInfo['payment_postcode'],
            'customer[username]' => $orderInfo['customer_id'] ? $orderInfo['customer_id'] : $orderInfo['email'],
            'customer[firstname]' => $orderInfo['payment_firstname'],
            'customer[lastname]' => $orderInfo['payment_lastname'],
            'custom[integration_module]' => 'opencart',
            'email' => $orderInfo['email'],
        );
    }

}