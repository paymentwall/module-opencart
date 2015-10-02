<?php

class ControllerPaymentPaymentwall extends Controller
{

    public function index()
    {
        $this->language->load('payment/paymentwall');

        $this->data['pay_via_paymentwall'] = $this->language->get('pay_via_paymentwall');
        $this->data['widget_link'] = $this->url->link('payment/paymentwall/widget', '', 'SSL');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/paymentwall.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/paymentwall.tpl';
        } else {
            $this->template = 'default/template/payment/paymentwall.tpl';
        }

        $this->render();
    }

    public function widget()
    {
        $this->load->model('checkout/order');
        $this->load->model('payment/paymentwall');
        $this->language->load('payment/paymentwall');

        $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if (isset($this->session->data['order_id'])) {
            $this->cart->clear();
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['guest']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
            unset($this->session->data['totals']);

        } else {
            // Redirect to shopping cart
            $this->redirect($this->url->link('checkout/cart'));
        }

        $this->document->setTitle($this->language->get('widget_title'));

        $this->data['breadcrumbs'] = array();
        $this->data['breadcrumbs'][] = array(
            'href' => $this->url->link('common/home'),
            'text' => $this->language->get('text_home'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('widget_title'),
            'separator' => $this->language->get('text_separator')
        );

        $this->data['widget_title'] = $this->language->get('widget_title');
        $this->data['widget_notice'] = $this->language->get('widget_notice');
        $this->data['iframe'] = $this->generateWidget($orderInfo);

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/paymentwall_widget.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/paymentwall_widget.tpl';
        } else {
            $this->template = 'default/template/payment/paymentwall_widget.tpl';
        }

        $this->children = array(
            'common/column_left',
            'common/column_right',
            'common/content_top',
            'common/content_bottom',
            'common/footer',
            'common/header'
        );

        $this->response->setOutput($this->render());
    }

    private function generateWidget($orderInfo)
    {
        // Init Paymentwall configs
        $this->model_payment_paymentwall->initPaymentwallConfig();

        $widget = new Paymentwall_Widget(
            $this->customer->getId() ? $this->customer->getId() : $_SERVER["REMOTE_ADDR"],
            $this->config->get('paymentwall_widget'),
            array(
                new Paymentwall_Product(
                    $orderInfo['order_id'],
                    $orderInfo['currency_value'] > 0 ? $orderInfo['total'] * $orderInfo['currency_value'] : $orderInfo['total'], // when currency_value <= 0 changes to 1
                    $orderInfo['currency_code'],
                    'Order #' . $orderInfo['order_id']
                )
            ),
            array_merge(
                array(
                    'success_url' => $this->config->get('paymentwall_success_url'),
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
