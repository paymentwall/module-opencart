<?php

if (!class_exists('Paymentwall_Config'))
    require_once DIR_SYSTEM . '/thirdparty/paymentwall-php/lib/paymentwall.php';

class ModelPaymentPaymentwall extends Model
{
    /**
     * @param $address
     * @param $total
     * @return array
     */
    public function getMethod($address, $total)
    {
        $this->load->language('payment/paymentwall');
        $method_data = array();
        if ($this->config->get('paymentwall_status') && $total > 0) {
            $method_data = array(
                'code' => 'paymentwall',
                'title' => $this->language->get('text_title'),
                'sort_order' => $this->config->get('paymentwall_sort_order')
            );
        }

        return $method_data;
    }

    /**
     * @param bool $pingback
     */
    public function initConfig($pingback = false)
    {
        Paymentwall_Config::getInstance()->set(array(
            'api_type' => Paymentwall_Config::API_GOODS,
            'public_key' => $this->config->get('paymentwall_key'),
            'private_key' => $this->config->get('paymentwall_secret')
        ));
    }

    /**
     * @param $order
     * @return array
     */
    public function callDeliveryApi($order, $refId)
    {
        if ($this->config->get('paymentwall_delivery')) {
            // Delivery Confirmation
            $delivery = new Paymentwall_GenerericApiObject('delivery');
            return $delivery->post($this->prepareDeliveryData($order, $refId));
        }
        return [];
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
     * @param $customer
     * @param $successUrl
     * @return string
     */
    public function generateWidget($orderInfo, $customer, $successUrl)
    {
        $successUrl = $this->config->get('paymentwall_success_url')
            ? $this->config->get('paymentwall_success_url')
            : $successUrl;

        $widget = new Paymentwall_Widget(
            $customer->getId() ? $customer->getId() : $orderInfo['email'],
            $this->config->get('paymentwall_widget'),
            array(
                new Paymentwall_Product(
                    $orderInfo['order_id'],
                    $orderInfo['currency_value'] > 0
                        ? ($orderInfo['total'] * $orderInfo['currency_value'])
                        : $orderInfo['total'], // when currency_value <= 0 changes to 1
                    $orderInfo['currency_code'],
                    'Order #' . $orderInfo['order_id']
                )
            ),
            array_merge(
                array(
                    'success_url' => $successUrl,
                    'email' => $orderInfo['email'],
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
            'email' => $orderInfo['email'],
        );
    }
}
