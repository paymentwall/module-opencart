<?php

if (!class_exists('Paymentwall_Config'))
    require_once DIR_SYSTEM . '/thirdparty/paymentwall-php/lib/paymentwall.php';

class ModelPaymentPaymentwall extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('payment/paymentwall');
        $method_data = array();
        if ($this->config->get('paymentwall_status') && $total > 0) {
            $method_data = array(
                'code' => 'paymentwall',
                'title' => $this->language->get('text_title'),
                'sort_order' => $this->config->get('paymentwall_sort_order'),
                'terms' => ''
            );
        }

        return $method_data;
    }

    public function initConfig()
    {
        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => $this->config->get('paymentwall_key'),
            'private_key' => $this->config->get('paymentwall_secret')
        ));
    }

    /**
     * @param $order
     */
    public function callDeliveryApi($order, $ref)
    {
        if ($this->config->get('paymentwall_delivery')) {
            // Delivery Confirmation
            $delivery = new Paymentwall_GenerericApiObject('delivery');
            return $delivery->post($this->prepareDeliveryData($order, $ref));
        }
        return array();
    }

    /**
     * @param $order
     * @param $ref
     * @return array
     */

    private function prepareDeliveryData($order, $ref)
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

    public function generateWidget($orderInfo, $customer, $successUrl)
    {
        // Init Paymentwall configs
        $this->initConfig();

        $successUrl = $successUrl
                    ? $successUrl
                    : $this->config->get('paymentwall_success_url');
        $total = $orderInfo['currency_value'] > 0 ? $orderInfo['total'] * $orderInfo['currency_value'] : $orderInfo['total']; // when currency_value <= 0 changes to 1

        $widget = new Paymentwall_Widget(
            !empty($customer->getId()) ? $customer->getId() : $_SERVER["REMOTE_ADDR"],
            $this->config->get('paymentwall_widget'),
            array(
                new Paymentwall_Product(
                    $orderInfo['order_id'],
                    $total, 
                    $orderInfo['currency_code'],
                    'Order #' . $orderInfo['order_id']
                )
            ),
            array_merge(
                array(
                    'success_url' => $successUrl,
                    'integration_module' => 'opencart',
                    'test_mode' => $this->config->get('paymentwall_test')
                ),
                $this->getUserProfileData($orderInfo)
            ));

        return $widget->getHtmlCode(array(
            'width' => '100%',
            'height' => 600,
            'frameborder' => 0
        ));
    }

    private function getUserProfileData($orderInfo)
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
            'email' => $orderInfo['email'],
        );
    }
}
