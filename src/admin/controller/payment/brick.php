<?php

class ControllerPaymentBrick extends Controller
{
    private $error = array();

    // Generate default configs
    public function install() {
        $this->load->model('setting/setting');
        $defaultConfigs = $this->model_setting_setting->getSetting('config');
        $this->model_setting_setting->editSetting('brick', array(
            'brick_under_review_status' => 1, // Pending
            'brick_complete_status' => $defaultConfigs['config_complete_status_id'],
            'brick_test_mode' => 0,
            'brick_status' => 1,
        ));
    }

    public function uninstall(){
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('brick');
    }

    /**
     * Index action
     */
    public function index()
    {
        $this->load->model('setting/setting');
        $this->load->model('payment/brick');
        $this->load->language('payment/brick');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('brick', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->document->setTitle($this->language->get('heading_title'));
        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['text_edit'] = $this->language->get('text_edit');
        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_all_zones'] = $this->language->get('text_all_zones');
        $this->data['text_yes'] = $this->language->get('text_yes');
        $this->data['text_no'] = $this->language->get('text_no');
        $this->data['text_authorization'] = $this->language->get('text_authorization');
        $this->data['text_sale'] = $this->language->get('text_sale');

        $this->data['entry_public_key'] = $this->language->get('entry_public_key');
        $this->data['entry_private_key'] = $this->language->get('entry_private_key');
        $this->data['entry_public_test_key'] = $this->language->get('entry_public_test_key');
        $this->data['entry_private_test_key'] = $this->language->get('entry_private_test_key');
        $this->data['entry_complete_status'] = $this->language->get('entry_complete_status');
        $this->data['entry_under_review_status'] = $this->language->get('entry_under_review_status');
        $this->data['entry_test_mode'] = $this->language->get('entry_test_mode');

        $this->data['entry_transaction'] = $this->language->get('entry_transaction');
        $this->data['entry_total'] = $this->language->get('entry_total');
        $this->data['entry_order_status'] = $this->language->get('entry_order_status');
        $this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $this->data['entry_active'] = $this->language->get('entry_active');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        $this->data['statuses'] = $this->model_payment_brick->getAllStatuses();
        $this->data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

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
                'href' => $this->url->link('payment/brick', 'token=' . $this->session->data['token'], 'SSL'),
                'separator' => ' :: '
            )
        );

        $this->data['action'] = $this->url->link('payment/brick', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['brick_public_key'] = $this->checkPostRequestIsset(
            'brick_public_key',
            $this->config->get('brick_public_key'));

        $this->data['brick_private_key'] = $this->checkPostRequestIsset(
            'brick_private_key',
            $this->config->get('brick_private_key'));

        $this->data['brick_public_test_key'] = $this->checkPostRequestIsset(
            'brick_public_test_key',
            $this->config->get('brick_public_test_key'));

        $this->data['brick_private_test_key'] = $this->checkPostRequestIsset(
            'brick_private_test_key',
            $this->config->get('brick_private_test_key'));

        $this->data['brick_complete_status'] = $this->checkPostRequestIsset(
            'brick_complete_status',
            $this->config->get('brick_complete_status'));

        $this->data['brick_under_review_status'] = $this->checkPostRequestIsset(
            'brick_under_review_status',
            $this->config->get('brick_under_review_status'));

        $this->data['brick_test_mode'] = $this->checkPostRequestIsset(
            'brick_test_mode',
            $this->config->get('brick_test_mode'));

        $this->data['brick_status'] = $this->checkPostRequestIsset(
            'brick_status',
            $this->config->get('brick_status'));

        $this->template = 'payment/brick.tpl';
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
        if (!$this->user->hasPermission('modify', 'payment/brick')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return empty($this->error);
    }
}
