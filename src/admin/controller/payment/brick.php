<?php

class ControllerPaymentBrick extends Controller
{
    private $error = array();

    /**
     * Install script
     */
    public function install()
    {
        $this->load->model('setting/setting');
        $defaultConfigs = $this->model_setting_setting->getSetting('config');
        $this->model_setting_setting->editSetting('brick', array(
            'brick_under_review_status' => 1, // Pending
            'brick_complete_status' => $defaultConfigs['config_complete_status_id'],
            'brick_test_mode' => 0,
            'brick_delivery' => 1,
            'brick_status' => 1,
        ));
    }

    /**
     * Uninstall script
     */
    public function uninstall()
    {
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

        $this->prepareTranslationData();
        $this->prepareViewData();

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
    protected function getPostData($key, $default)
    {
        return isset($this->request->post[$key]) ? $this->request->post[$key] : $default;
    }

    /**
     * Validator
     * @return bool
     */
    protected function validate()
    {
        $post = $this->request->post;
        if (!$this->user->hasPermission('modify', 'payment/brick')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$post['brick_public_key']) {
            $this->error['key'] = $this->language->get('error_key');
        }

        if (!$post['brick_private_key']) {
            $this->error['private'] = $this->language->get('error_private');
        }

        if (!$post['brick_secret_key']) {
            $this->error['secret'] = $this->language->get('error_secret');
        }

        return empty($this->error);
    }

    /**
     * Prepare view data
     */
    protected function prepareViewData()
    {
        $this->document->setTitle($this->language->get('heading_title'));

        $this->data['statuses'] = $this->model_payment_brick->getAllStatuses();
        $this->data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $this->data['error_key'] = isset($this->error['key']) ? $this->error['key'] : '';
        $this->data['error_private'] = isset($this->error['private']) ? $this->error['private'] : '';
        $this->data['error_secret'] = isset($this->error['secret']) ? $this->error['secret'] : '';

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

        $this->data['brick_public_key'] = $this->getPostData('brick_public_key', $this->config->get('brick_public_key'));
        $this->data['brick_private_key'] = $this->getPostData('brick_private_key', $this->config->get('brick_private_key'));
        $this->data['brick_public_test_key'] = $this->getPostData('brick_public_test_key', $this->config->get('brick_public_test_key'));
        $this->data['brick_private_test_key'] = $this->getPostData('brick_private_test_key', $this->config->get('brick_private_test_key'));
        $this->data['brick_secret_key'] = $this->getPostData('brick_secret_key', $this->config->get('brick_secret_key'));

        $this->data['brick_complete_status'] = $this->getPostData('brick_complete_status', $this->config->get('brick_complete_status'));
        $this->data['brick_under_review_status'] = $this->getPostData('brick_under_review_status', $this->config->get('brick_under_review_status'));

        $this->data['brick_test_mode'] = $this->getPostData('brick_test_mode', $this->config->get('brick_test_mode'));
        $this->data['brick_status'] = $this->getPostData('brick_status', $this->config->get('brick_status'));
        $this->data['brick_sort_order'] = $this->getPostData('brick_sort_order', $this->config->get('brick_sort_order'));
    }

    /**
     * Prepare translation data
     */
    protected function prepareTranslationData()
    {
        $language = $this->language;
        $this->data['heading_title'] = $language->get('heading_title');

        $this->data['text_brick_register'] = $language->get('text_brick_register');
        $this->data['text_enabled'] = $language->get('text_enabled');
        $this->data['text_disabled'] = $language->get('text_disabled');
        $this->data['text_yes'] = $language->get('text_yes');
        $this->data['text_no'] = $language->get('text_no');
        $this->data['text_pingback_url'] = HTTPS_CATALOG . 'index.php?route=checkout/pingback';

        $this->data['entry_public_key'] = $language->get('entry_public_key');
        $this->data['entry_private_key'] = $language->get('entry_private_key');
        $this->data['entry_public_test_key'] = $language->get('entry_public_test_key');
        $this->data['entry_private_test_key'] = $language->get('entry_private_test_key');
        $this->data['entry_paymentwall_secret_key'] = $language->get('entry_paymentwall_secret_key');
        $this->data['entry_pingback_url'] = $language->get('entry_pingback_url');
        $this->data['entry_complete_status'] = $language->get('entry_complete_status');
        $this->data['entry_under_review_status'] = $language->get('entry_under_review_status');
        $this->data['entry_test_mode'] = $language->get('entry_test_mode');

        $this->data['entry_order_status'] = $language->get('entry_order_status');
        $this->data['entry_status'] = $language->get('entry_status');
        $this->data['entry_sort_order'] = $language->get('entry_sort_order');
        $this->data['entry_active'] = $language->get('entry_active');

        $this->data['button_save'] = $language->get('button_save');
        $this->data['button_cancel'] = $language->get('button_cancel');
    }
}
