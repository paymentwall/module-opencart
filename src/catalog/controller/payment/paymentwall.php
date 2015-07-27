<?php

class ControllerPaymentPaymentwall extends Controller
{
    const ORDER_PENDING_STATUS_ID = 1;

    public function index()
    {
        $this->language->load('payment/paymentwall');
        $this->load->model('payment/paymentwall');
        $this->load->model('checkout/order');

        $data['text_credit_card'] = $this->language->get('text_credit_card');
        $data['text_start_date'] = $this->language->get('text_start_date');
        $data['text_issue'] = $this->language->get('text_issue');
        $data['text_wait'] = $this->language->get('text_wait');

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $data['orderId'] = $orderInfo['order_id'];

        // Update order status to pending
        if (!$orderInfo['order_status']) {
            $this->model_checkout_order->addOrderHistory($orderInfo['order_id'], self::ORDER_PENDING_STATUS_ID);
        }

        // Generate Widget
        $data['url']['iframe'] = $this->generateWidget($orderInfo);

        $data['button_confirm'] = $this->language->get('button_confirm');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/paymentwall.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/paymentwall.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/paymentwall.tpl', $data);
        }
    }

    private function generateWidget($orderInfo)
    {
        $this->load->model('payment/paymentwall');
        // Init Paymentwall configs
        $this->model_payment_paymentwall->initPaymentwallConfig();

        $widget = new Paymentwall_Widget(
            $orderInfo['email'],
            $this->config->get('paymentwall_widget'),
            array(
                new Paymentwall_Product(
                    $orderInfo['order_id'],
                    $orderInfo['total'] * $orderInfo['currency_value'],
                    $orderInfo['currency_code'],
                    'Order #' . $orderInfo['order_id']
                )
            ),
            array_merge(
                array(
                    'success_url' => $this->url->link('checkout/success'),
                    'email' => $orderInfo['email'],
                    'integration_module' => 'opencart',
                    'test_mode' => $this->config->get('paymentwall_test')
                ),
                $this->getUserProfileData($orderInfo)
            ));

        return $widget->getHtmlCode(array(
            'width' => '100%',
            'height' => 400,
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
            'customer[username]' => $orderInfo['customer_id'] ? $orderInfo['customer_id'] : $_SERVER['REMOTE_ADDR'],
            'customer[firstname]' => $orderInfo['payment_firstname'],
            'customer[lastname]' => $orderInfo['payment_lastname'],
            'email' => $orderInfo['email'],
        );
    }
}
