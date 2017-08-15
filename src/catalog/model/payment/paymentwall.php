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
    public function generateWidget($orderInfo, $customer, $successUrl, $cartProducts)
    {
        $successUrl = $this->config->get('paymentwall_success_url')
            ? $this->config->get('paymentwall_success_url')
            : $successUrl;

        $this->load->model('catalog/product');

        $widget = new Paymentwall_Widget(
            $customer->getId() ? $customer->getId() : $orderInfo['email'],
            $this->config->get('paymentwall_widget'),
            array(
                $this->getProduct($orderInfo, $cartProducts, $hasTrial)
            ),
            array_merge(
                array(
                    'success_url' => $successUrl,
                    'email' => $orderInfo['email'],
                    'integration_module' => 'opencart',
                    'test_mode' => $this->config->get('paymentwall_test'),
                    'hide_post_trial_good' => $hasTrial ? 1 : 0,
                ),
                $this->getUserProfileData($orderInfo)
            ));

        return $widget->getHtmlCode(array(
            'width' => '100%',
            'height' => 600,
            'frameborder' => 0
        ));
    }

    public function getProduct($orderInfo, $cartProducts, &$hasTrial)
    {
        $price = $orderInfo['currency_value'] > 0
            ? ($orderInfo['total'] * $orderInfo['currency_value'])
            : $orderInfo['total'];
        $periodType = null;
        $recurringDuration = null;
        $trialProduct = null;
        $typeProduct = Paymentwall_Product::TYPE_SUBSCRIPTION;
        $hasTrial = false;

        if (count($cartProducts['quantity']) > 1) {
            $typeProduct = Paymentwall_Product::TYPE_FIXED;
        }

        if ($typeProduct == Paymentwall_Product::TYPE_SUBSCRIPTION) {
            $subScriptionProduct = $cartProducts;
            $recurringDuration = $subScriptionProduct['recurring_duration'];
            $periodType = $this->getPeriodType($subScriptionProduct['recurring_frequency'], $recurringDuration);

            if ($subScriptionProduct['recurring_trial']) {
                $hasTrial = true;
                $recurringTrialDuration = $subScriptionProduct['recurring_trial_duration'];
                $periodTrialType = $this->getPeriodType($subScriptionProduct['recurring_trial_frequency'], $recurringTrialDuration);

                $trialProduct = new Paymentwall_Product(
                    $orderInfo['order_id'],
                    $price,
                    $orderInfo['currency_code'],
                    $subScriptionProduct['name'],
                    $typeProduct,
                    $recurringTrialDuration,
                    $periodTrialType,
                    true
                );
            }
        }

        return new Paymentwall_Product(
            $orderInfo['order_id'],
            $price,
            $orderInfo['currency_code'],
            'Order #' . $orderInfo['order_id'],
            $typeProduct,
            $recurringDuration,
            $periodType,
            ($typeProduct == Paymentwall_Product::TYPE_SUBSCRIPTION) ? true : false,
            $trialProduct
        );
    }

    protected function getPeriodType($recurringFrequency, &$recurringDuration)
    {
        switch ($recurringFrequency) {
            case 'day':
                $periodType = Paymentwall_Product::PERIOD_TYPE_DAY;
                break;
            case 'week':
                $periodType = Paymentwall_Product::PERIOD_TYPE_WEEK;
                break;
            case 'semi_month':
                $periodType = Paymentwall_Product::PERIOD_TYPE_WEEK;
                $recurringDuration = $recurringDuration * 2; //2 weeks
                break;
            case 'month':
                $periodType = Paymentwall_Product::PERIOD_TYPE_MONTH;
                break;
            case 'year':
                $periodType = Paymentwall_Product::PERIOD_TYPE_YEAR;
                break;
            default:
                break;
        }

        return $periodType;
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

    public function recurringPayments() 
    {
        /*
         * Used by the checkout to state the module
         * supports recurring profiles.
         */
        return true;
    }
}
