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
        $this->language->load('payment/paymentwall');
        $this->getPaymentModel()->initConfig();

        $orderId = @$this->session->data['order_id'];
        $orderInfo = $this->getCheckoutOrderModel()->getOrder($orderId);
        $products = $this->cart->getProducts();

        if (!empty($orderInfo)) {
            $this->cart->clear();
            unset($this->session->data['order_id']);
        } else {
            // Redirect to shopping cart
            $this->redirect($this->url->link('checkout/cart', '', 'SSL'));
        }

        $cartProducts = reset($products);

        if (!empty($cartProducts['recurring'])) {
            $this->load->model('checkout/recurring');
            $profileDesciption = $this->getProfileDescription($cartProducts);
            $this->model_checkout_recurring->create($cartProducts, $orderInfo['order_id'], $profileDesciption);
        }

        $this->document->setTitle($this->language->get('widget_title'));

        $this->data['breadcrumbs'] = array();
        $this->data['breadcrumbs'][] = array(
            'href' => $this->url->link('common/home'),
            'text' => $this->language->get('text_home'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'href' => '#',
            'text' => $this->language->get('widget_title'),
            'separator' => $this->language->get('text_separator')
        );

        $this->data['widget_title'] = $this->language->get('widget_title');
        $this->data['widget_notice'] = $this->language->get('widget_notice');
        $this->data['iframe'] = $this->getPaymentModel()->generateWidget(
            $orderInfo,
            $this->customer,
            $this->url->link('checkout/success', '', 'SSL'),
            $cartProducts
        );

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

    /**
     * @return Profile description
     */
    protected function getProfileDescription($product)
    {
        $this->language->load('checkout/cart');
        $profile_description = '';

        $frequencies = array(
            'day' => $this->language->get('text_day'),
            'week' => $this->language->get('text_week'),
            'semi_month' => $this->language->get('text_semi_month'),
            'month' => $this->language->get('text_month'),
            'year' => $this->language->get('text_year'),
        );

        if ($product['recurring_trial']) {
                $recurring_price = $this->currency->format($this->tax->calculate($product['recurring_trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')));
            $profile_description = sprintf($this->language->get('text_trial_description'), $recurring_price, $product['recurring_trial_cycle'], $frequencies[$product['recurring_trial_frequency']], $product['recurring_trial_duration']) . ' ';
        }

        $recurring_price = $this->currency->format($this->tax->calculate($product['recurring_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')));

        if ($product['recurring_duration']) {
            $profile_description .= sprintf($this->language->get('text_payment_description'), $recurring_price, $product['recurring_cycle'], $frequencies[$product['recurring_frequency']], $product['recurring_duration']);
        } else {
            $profile_description .= sprintf($this->language->get('text_payment_until_canceled_description'), $recurring_price, $product['recurring_cycle'], $frequencies[$product['recurring_frequency']], $product['recurring_duration']);
        }

        return $profile_description;
    }

    /**
     * @return ModelPaymentPaymentwall
     */
    protected function getPaymentModel()
    {
        if(!$this->model_payment_paymentwall){
            $this->load->model('payment/paymentwall');
        }
        return $this->model_payment_paymentwall;
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
