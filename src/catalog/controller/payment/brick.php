<?php

class ControllerPaymentBrick extends Controller
{
    public function index()
    {
        $this->language->load('payment/brick');
        $this->load->model('payment/brick');

        $this->getPaymentModel()->initConfig();

        $data = $this->prepareTranslationData();

        $data['public_key'] = Paymentwall_Config::getInstance()->getPublicKey();

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/brick.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/brick.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/brick.tpl', $data);
        }
    }

    protected function prepareTranslationData()
    {
        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $months[] = array(
                'text' => sprintf('%02d', $i),
                'value' => sprintf('%02d', $i)
            );
        }

        $today = getdate();
        $yearExpire = array();
        for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
            $yearExpire[] = array(
                'text' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
                'value' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i))
            );
        }

        return array(
            'text_credit_card' => $this->language->get('text_credit_card'),
            'text_start_date' => $this->language->get('text_start_date'),
            'text_wait' => $this->language->get('text_wait'),
            'text_loading' => $this->language->get('text_loading'),

            'entry_cc_number' => $this->language->get('entry_cc_number'),
            'entry_cc_expire_date' => $this->language->get('entry_cc_expire_date'),
            'entry_cc_cvv2' => $this->language->get('entry_cc_cvv2'),
            'button_confirm' => $this->language->get('button_confirm'),
            'text_click_here' => $this->language->get('text_click_here'),
            'text_3ds_step' => $this->language->get('text_3ds_step'),
            
            'months' => $months,
            'year_expire' => $yearExpire
        );
    }

    /**
     * Validate Brick request
     */
    public function validate()
    {
        $this->load->model('checkout/order');
        $this->load->model('payment/brick');
        $this->load->model('account/activity');
        $this->load->model('setting/setting');
        $this->language->load('payment/brick');

        $defaultConfigs = $this->model_setting_setting->getSetting('config');
        $data = array(
            'status' => 'error',
            'message' => '',
            'redirect' => false
        );

        if (!isset($this->session->data['order_id']) || !isset($this->request->post['cc_brick_token']) || !isset($this->request->post['cc_brick_token'])) {
            $data['message'] = "Oops, Something went wrong. Please try again!";
        } elseif ($orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id'])) {

            if ($this->customer->isLogged()) {
                $activity_data = array(
                    'customer_id' => $this->customer->getId(),
                    'name' => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
                    'order_id' => $this->session->data['order_id']
                );

                $this->model_account_activity->addActivity('order_account', $activity_data);
            } else {
                $activity_data = array(
                    'name' => $this->session->data['guest']['firstname'] . ' ' . $this->session->data['guest']['lastname'],
                    'order_id' => $this->session->data['order_id']
                );
                $this->model_account_activity->addActivity('order_guest', $activity_data);
            }
            
            $this->getPaymentModel()->initConfig();
            $charge = $this->getPaymentModel()->createChargePayment($orderInfo, $this->request->post);
            $rawResponse = json_decode($charge->getRawResponseData(), true);
            $response = json_decode($charge->getPublicData(), true);

            if ($charge->isSuccessful() && empty($rawResponse['secure'])) {
                if ($charge->isCaptured()) {
                    $data['message'] = $this->language->get('text_order_processed');
                } elseif ($charge->isUnderReview()) {
                    $data['message'] = $this->language->get('text_order_under_review');
                }

                $data['status'] = 'success';
                $data['redirect'] = $this->url->link('checkout/success');
            } elseif(!empty($rawResponse['secure']['formHTML'])) {
                $data['status'] = '3ds';
                $data['message'] = $this->language->get('text_3ds_step');
                $data['secure'] = $rawResponse['secure']['formHTML'];
            } else {
                $data['message'] = $response['error']['message'];
            }
        } else {
            $data['message'] =  $this->language->get('text_order_invalid');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
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
}
