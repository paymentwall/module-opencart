<?php

class ControllerPaymentBrick extends Controller
{
    public function index()
    {
        $this->language->load('payment/brick');
        $this->prepareTranslationData();
        $this->getPaymentModel()->initConfig();

        $this->data['public_key'] = Paymentwall_Config::getInstance()->getPublicKey();
        $this->data['months'] = array();
        for ($i = 1; $i <= 12; $i++) {
            $this->data['months'][] = array(
                'text' => sprintf('%02d', $i),
                'value' => sprintf('%02d', $i)
            );
        }

        $today = getdate();
        $this->data['year_expire'] = array();
        for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
            $this->data['year_expire'][] = array(
                'text' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
                'value' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i))
            );
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/brick.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/brick.tpl';
        } else {
            $this->template = 'default/template/payment/brick.tpl';
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
     * Validate Brick request
     */
    public function validate()
    {
        $this->load->model('setting/setting');
        $this->language->load('payment/brick');

        $orderId = @$this->session->data['order_id'];
        $session = $this->session;

        $json = array(
            'status' => 'error',
            'message' => '',
            'redirect' => false
        );

        if (empty($orderId) || !isset($this->request->post['cc_brick_token'])) {

            $json['message'] = "Oops, Something went wrong. Please try again!";

        } elseif ($orderInfo = $this->getCheckoutOrderModel()->getOrder($session->data['order_id'])) {

            $this->getPaymentModel()->initConfig();
            $charge = $this->getPaymentModel()->createChargePayment($orderInfo, $this->request->post);
            $rawResponse = json_decode($charge->getRawResponseData(), true);
            $response = json_decode($charge->getPublicData(), true);

            if ($charge->isSuccessful()) {

                $this->getCheckoutOrderModel()->confirm(
                    $orderId,
                    $this->config->get('config_order_status_id')
                );

                if ($charge->isCaptured()) {
                    $json['message'] = $this->language->get('text_order_processed');
                } elseif ($charge->isUnderReview()) {
                    $this->data['message'] = $this->language->get('text_order_under_review');
                }

                $json['status'] = 'success';
                $json['redirect'] = $this->url->link('checkout/success');
            } elseif (!empty($rawResponse['secure'])) {
                $json['status'] = '3ds';
                $json['message'] = $this->language->get('text_3ds_step');
                $json['secure'] = $rawResponse['secure']['formHTML'];
            } else {
                $json['message'] = $response['error']['message'];
            }

        } else {
            $this->data['message'] = $this->language->get('text_order_invalid');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * @return ModelPaymentBrick
     */
    protected function getPaymentModel()
    {
        if (!$this->model_payment_brick) {
            $this->load->model('payment/brick');
        }
        return $this->model_payment_brick;
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

    protected function prepareTranslationData()
    {
        $this->data['text_credit_card'] = $this->language->get('text_credit_card');
        $this->data['text_start_date'] = $this->language->get('text_start_date');
        $this->data['text_wait'] = $this->language->get('text_wait');
        $this->data['text_loading'] = $this->language->get('text_loading');
        $this->data['entry_cc_number'] = $this->language->get('entry_cc_number');
        $this->data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
        $this->data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['text_click_here'] = $this->language->get('text_click_here');
        $this->data['text_3ds_step'] = $this->language->get('text_3ds_step');
    }
}
