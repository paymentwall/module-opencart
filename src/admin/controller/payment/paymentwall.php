<?php

class ControllerPaymentPaymentwall extends Controller
{
    private $error = array();

    // Generate default configs
    public function install() {
        $this->load->model('setting/setting');
        $defaultConfigs = $this->model_setting_setting->getSetting('config');
        $this->model_setting_setting->editSetting('paymentwall', array(
            'complete_status' => $defaultConfigs['config_complete_status_id'],
            'cancel_status' => 7,
            'paymentwall_test' => 0,
            'paymentwall_delivery' => 1,
            'paymentwall_status' => 1,
        ));
    }

    public function uninstall(){
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('paymentwall');
    }

    /**
     * Index action
     */
    public function index()
    {
        $this->load->model('setting/setting');
        $this->load->model('payment/paymentwall');
        $this->load->language('payment/paymentwall');
        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting('paymentwall', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_all_zones'] = $this->language->get('text_all_zones');
        $this->data['text_yes'] = $this->language->get('text_yes');
        $this->data['text_no'] = $this->language->get('text_no');
        $this->data['text_authorization'] = $this->language->get('text_authorization');
        $this->data['text_sale'] = $this->language->get('text_sale');

        $this->data['entry_key'] = $this->language->get('entry_key');
        $this->data['entry_secret'] = $this->language->get('entry_secret');
        $this->data['entry_widget'] = $this->language->get('entry_widget');
        $this->data['entry_transaction'] = $this->language->get('entry_transaction');
        $this->data['entry_total'] = $this->language->get('entry_total');
        $this->data['entry_order_status'] = $this->language->get('entry_order_status');
        $this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $this->data['entry_complete_status'] = $this->language->get('entry_complete_status');
        $this->data['entry_cancel_status'] = $this->language->get('entry_cancel_status');
        $this->data['entry_test'] = $this->language->get('entry_test');
        $this->data['entry_delivery'] = $this->language->get('entry_delivery');
        $this->data['entry_success_url'] = $this->language->get('entry_success_url');
        $this->data['entry_active'] = $this->language->get('entry_active');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        $this->data['statuses'] = $this->model_payment_paymentwall->getAllStatuses();

        $this->data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $this->data['error_key'] = isset($this->error['key']) ? $this->error['key'] : '';
        $this->data['error_secret'] = isset($this->error['secret']) ? $this->error['secret'] : '';
        $this->data['error_widget'] = isset($this->error['widget']) ? $this->error['widget'] : '';

        $this->data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
                'separator' => false
            ),
            array(
                'text' => $this->language->get('text_payment'),
                'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
                'separator' => ' :: '
            ),
            array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('payment/paymentwall', 'token=' . $this->session->data['token'], 'SSL'),
                'separator' => ' :: '
            )
        );

        $this->data['action'] = $this->url->link('payment/paymentwall', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['paymentwall_key'] = $this->checkPostRequestIsset(
            'paymentwall_key',
            $this->config->get('paymentwall_key'));

        $this->data['paymentwall_secret'] = $this->checkPostRequestIsset(
            'paymentwall_secret',
            $this->config->get('paymentwall_secret'));

        $this->data['paymentwall_widget'] = $this->checkPostRequestIsset(
            'paymentwall_widget',
            $this->config->get('paymentwall_widget'));

        $this->data['complete_status'] = $this->checkPostRequestIsset(
            'complete_status',
            $this->config->get('complete_status'));

        $this->data['cancel_status'] = $this->checkPostRequestIsset(
            'cancel_status',
            $this->config->get('cancel_status'));

        $this->data['paymentwall_test'] = $this->checkPostRequestIsset(
            'paymentwall_test',
            $this->config->get('paymentwall_test'));

        $this->data['paymentwall_delivery'] = $this->checkPostRequestIsset(
            'paymentwall_delivery',
            $this->config->get('paymentwall_delivery'));

        $this->data['paymentwall_success_url'] = $this->checkPostRequestIsset(
            'paymentwall_success_url',
            $this->config->get('paymentwall_success_url'));

        $this->data['paymentwall_status'] = $this->checkPostRequestIsset(
            'paymentwall_status',
            $this->config->get('paymentwall_status'));

        $this->template = 'payment/paymentwall.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render());
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    private function checkPostRequestIsset($key, $default)
    {
        return isset($this->request->post[$key]) ? $this->request->post[$key] : $default;
    }

    /**
     * Validator
     * @return bool
     */
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'payment/paymentwall')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['paymentwall_key']) {
            $this->error['key'] = $this->language->get('error_key');
        }

        if (!$this->request->post['paymentwall_secret']) {
            $this->error['secret'] = $this->language->get('error_secret');
        }

        if (!$this->request->post['paymentwall_widget']) {
            $this->error['widget'] = $this->language->get('error_widget');
        }

        return empty($this->error);
    }
}
