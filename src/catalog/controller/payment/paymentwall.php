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

        if (!empty($orderInfo)) {
            $this->cart->clear();
        } else {
            // Redirect to shopping cart
            $this->redirect($this->url->link('checkout/cart', '', 'SSL'));
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
            $this->url->link('checkout/success', '', 'SSL')
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
     * @return ModelPaymentPaymentwall
     */
    protected function getPaymentModel(){
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
