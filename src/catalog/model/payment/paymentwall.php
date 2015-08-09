<?php

if (!class_exists('Paymentwall_Config'))
    require_once DIR_SYSTEM . '/thirdparty/paymentwall-php/lib/paymentwall.php';

class ModelPaymentPaymentwall extends Model
{
    public function getMethod($address, $total)
    {
        $method_data = array();
        
        $this->load->language('payment/paymentwall');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('paymentwall_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if (
            !($this->config->get('paymentwall_total') > $total)
            || !$this->config->get('paymentwall_geo_zone_id')
            || $query->num_rows
        ) {
            $method_data = array(
                'code' => 'paymentwall',
                'title' => $this->language->get('text_title'),
                'sort_order' => $this->config->get('paymentwall_sort_order'),
                'terms' => ''
            );
        }

        return $method_data;
    }

    public function initPaymentwallConfig()
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
            $response = $delivery->post($this->prepareDeliveryData($order, $ref));
        }
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
}
