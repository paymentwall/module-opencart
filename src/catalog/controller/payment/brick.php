<?php

class ControllerPaymentBrick extends Controller
{
    public function index()
    {
        $this->language->load('payment/brick');
        $this->load->model('payment/brick');

        $this->model_payment_brick->initBrickConfig();
        $this->data['text_credit_card'] = $this->language->get('text_credit_card');
        $this->data['text_start_date'] = $this->language->get('text_start_date');
        $this->data['text_wait'] = $this->language->get('text_wait');
        $this->data['text_loading'] = $this->language->get('text_loading');

        $this->data['entry_cc_number'] = $this->language->get('entry_cc_number');
        $this->data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
        $this->data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
        $this->data['button_confirm'] = $this->language->get('button_confirm');
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
        $this->load->model('checkout/order');
        $this->load->model('payment/brick');
        $this->load->model('setting/setting');
        $this->language->load('payment/brick');

        $json = array(
            'status' => 'error',
            'message' => '',
            'redirect' => false
        );

        if (!isset($this->session->data['order_id']) || !isset($this->request->post['cc_brick_token']) || !isset($this->request->post['cc_brick_token'])) {
            $json['message'] = "Oops, Something went wrong. Please try again!";
        } elseif ($orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id'])) {

            $this->model_payment_brick->initBrickConfig();

            $charge = new Paymentwall_Charge();
            $charge->create(array_merge(
                $this->prepareCardInfo($orderInfo),
                $this->getUserProfileData($orderInfo)
            ));
            $response = $charge->getPublicData();

            if ($charge->isSuccessful()) {

                $this->model_checkout_order->confirm(
                    $this->session->data['order_id'],
                    $this->config->get('config_order_status_id')
                );

                if ($charge->isCaptured()) {
                    $this->model_checkout_order->update(
                        $this->session->data['order_id'],
                        $this->config->get('brick_complete_status'),
                        'The order approved, Transaction ID: #' . $charge->getId(),
                        true
                    );
                    $json['message'] = $this->language->get('text_order_processed');
                } elseif ($charge->isUnderReview()) {
                    $this->model_checkout_order->update(
                        $this->session->data['order_id'],
                        $this->config->get('brick_under_review_status'),
                        'The order is under review!',
                        true
                    );
                    $this->data['message'] = $this->language->get('text_order_under_review');
                }

                $json['status'] = 'success';
                $json['redirect'] = $this->url->link('checkout/success');
            } else {
                $response = json_decode($response, true);
                $json['message'] = $response['error']['message'];
            }

        } else {
            $this->data['message'] =  $this->language->get('text_order_invalid');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
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

    private function prepareCardInfo($orderInfo)
    {
        $post = $this->request->post;
        return array(
            'email' => $orderInfo['email'],
            'amount' => number_format($orderInfo['total'], 2, '.', ''),
            'currency' => $orderInfo['currency_code'],
            'token' => $post['cc_brick_token'],
            'fingerprint' => $post['cc_brick_fingerprint'],
            'description' => 'Order #' . $orderInfo['order_id']
        );
    }
}
