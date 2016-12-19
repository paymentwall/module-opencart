<?php

class ControllerPaymentPaymentwall extends Controller
{
    private $error = array();

    // Generate default configs
    public function install()
    {
        $this->load->model('setting/setting');
        $defaultConfigs = $this->model_setting_setting->getSetting('config');
        $this->model_setting_setting->editSetting('paymentwall', array(
            'paymentwall_complete_status' => $defaultConfigs['config_complete_status_id'],
            'paymentwall_cancel_status' => 7,
            'paymentwall_test' => 0,
            'paymentwall_delivery' => 1,
            'paymentwall_status' => 1,
        ));
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('paymentwall');
    }

    /**
     * Index action
     */
    public function index()
    {
        $this->load->model('setting/setting');
        $this->load->language('payment/paymentwall');
        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('paymentwall', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->prepareTranslationData();
        $this->prepareViewData();

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

    protected function prepareViewData()
    {
        $this->load->model('payment/paymentwall');
        $this->data['statuses'] = $this->model_payment_paymentwall->getAllStatuses();
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

        $this->data['paymentwall_key'] = $this->getPostData('paymentwall_key', $this->config->get('paymentwall_key'));
        $this->data['paymentwall_secret'] = $this->getPostData('paymentwall_secret', $this->config->get('paymentwall_secret'));
        $this->data['paymentwall_widget'] = $this->getPostData('paymentwall_widget', $this->config->get('paymentwall_widget'));
        $this->data['paymentwall_complete_status'] = $this->getPostData('paymentwall_complete_status', $this->config->get('paymentwall_complete_status'));
        $this->data['paymentwall_cancel_status'] = $this->getPostData('paymentwall_cancel_status', $this->config->get('paymentwall_cancel_status'));
        $this->data['paymentwall_under_review_status'] = $this->getPostData('paymentwall_under_review_status', $this->config->get('paymentwall_under_review_status'));
        $this->data['paymentwall_test'] = $this->getPostData('paymentwall_test', $this->config->get('paymentwall_test'));
        $this->data['paymentwall_delivery'] = $this->getPostData('paymentwall_delivery', $this->config->get('paymentwall_delivery'));
        $this->data['paymentwall_success_url'] = $this->getPostData('paymentwall_success_url', $this->config->get('paymentwall_success_url'));
        $this->data['paymentwall_status'] = $this->getPostData('paymentwall_status', $this->config->get('paymentwall_status'));
        $this->data['paymentwall_sort_order'] = $this->getPostData('paymentwall_sort_order', $this->config->get('paymentwall_sort_order'));
    }

    /**
     * Prepare translation data
     */
    protected function prepareTranslationData()
    {
        $language = $this->language;
        $this->data['heading_title'] = $language->get('heading_title');

        $this->data['text_paymentwall_register'] = $language->get('text_paymentwall_register');
        $this->data['text_enabled'] = $language->get('text_enabled');
        $this->data['text_disabled'] = $language->get('text_disabled');
        $this->data['text_all_zones'] = $language->get('text_all_zones');
        $this->data['text_yes'] = $language->get('text_yes');
        $this->data['text_no'] = $language->get('text_no');
        $this->data['text_authorization'] = $language->get('text_authorization');
        $this->data['text_sale'] = $language->get('text_sale');
        $this->data['text_pingback_url'] = HTTPS_CATALOG . 'index.php?route=checkout/pingback';

        $this->data['entry_key'] = $language->get('entry_key');
        $this->data['entry_secret'] = $language->get('entry_secret');
        $this->data['entry_widget'] = $language->get('entry_widget');
        $this->data['entry_transaction'] = $language->get('entry_transaction');
        $this->data['entry_order_status'] = $language->get('entry_order_status');
        $this->data['entry_status'] = $language->get('entry_status');
        $this->data['entry_sort_order'] = $language->get('entry_sort_order');
        $this->data['entry_complete_status'] = $language->get('entry_complete_status');
        $this->data['entry_cancel_status'] = $language->get('entry_cancel_status');
        $this->data['entry_test'] = $language->get('entry_test');
        $this->data['entry_delivery'] = $language->get('entry_delivery');
        $this->data['entry_success_url'] = $language->get('entry_success_url');
        $this->data['entry_active'] = $language->get('entry_active');
        $this->data['entry_pingback_url'] = $language->get('entry_pingback_url');
        $this->data['entry_under_review_status'] = $language->get('entry_under_review_status');

        $this->data['button_save'] = $language->get('button_save');
        $this->data['button_cancel'] = $language->get('button_cancel');

        $this->data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $this->data['error_key'] = isset($this->error['key']) ? $this->error['key'] : '';
        $this->data['error_secret'] = isset($this->error['secret']) ? $this->error['secret'] : '';
        $this->data['error_widget'] = isset($this->error['widget']) ? $this->error['widget'] : '';
    }
}
