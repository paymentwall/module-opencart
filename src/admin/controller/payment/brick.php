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
            'brick_cancel_status' => 7, // Cancel
            'brick_complete_status' => $defaultConfigs['config_complete_status_id'],
            'brick_test_mode' => 0,
            'brick_status' => 1, // Pending
            'brick_sort_order' => 1
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
            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $translationData = $this->prepareTranslationData();
        $viewData = $this->prepareViewData();
        $data = array_merge($translationData, $viewData);

        $this->response->setOutput($this->load->view('payment/brick.tpl', $data));
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    private function getPostData($key, $default)
    {
        return isset($this->request->post[$key]) ? $this->request->post[$key] : $default;
    }

    protected function prepareTranslationData()
    {
        $this->document->setTitle($this->language->get('heading_title'));
        return array(
            'heading_title' => $this->language->get('heading_title'),

            'text_edit' => $this->language->get('text_edit'),
            'text_enabled' => $this->language->get('text_enabled'),
            'text_disabled' => $this->language->get('text_disabled'),
            'text_yes' => $this->language->get('text_yes'),
            'text_no' => $this->language->get('text_no'),

            'entry_public_key' => $this->language->get('entry_public_key'),
            'entry_private_key' => $this->language->get('entry_private_key'),
            'entry_public_test_key' => $this->language->get('entry_public_test_key'),
            'entry_private_test_key' => $this->language->get('entry_private_test_key'),
            'entry_secret_key' => $this->language->get('entry_secret_key'),
            'entry_complete_status' => $this->language->get('entry_complete_status'),
            'entry_under_review_status' => $this->language->get('entry_under_review_status'),
            'entry_cancel_status' => $this->language->get('entry_cancel_status'),
            'entry_test' => $this->language->get('entry_test'),
            'entry_delivery' => $this->language->get('entry_delivery'),

            'entry_transaction' => $this->language->get('entry_transaction'),
            'entry_total' => $this->language->get('entry_total'),
            'entry_order_status' => $this->language->get('entry_order_status'),
            'entry_geo_zone' => $this->language->get('entry_geo_zone'),
            'entry_status' => $this->language->get('entry_status'),
            'entry_sort_order' => $this->language->get('entry_sort_order'),
            'entry_active' => $this->language->get('entry_active'),

            'button_save' => $this->language->get('button_save'),
            'button_cancel' => $this->language->get('button_cancel')
        );
    }

    protected function prepareViewData()
    {
        $data['statuses'] = $this->model_payment_brick->getAllStatuses();
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        $data['breadcrumbs'] = array(
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

        $data['action'] = $this->url->link('payment/brick', 'token=' . $this->session->data['token'], 'SSL');
        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        $data['brick_public_key'] = $this->getPostData(
            'brick_public_key',
            $this->config->get('brick_public_key'));

        $data['brick_private_key'] = $this->getPostData(
            'brick_private_key',
            $this->config->get('brick_private_key'));

        $data['brick_public_test_key'] = $this->getPostData(
            'brick_public_test_key',
            $this->config->get('brick_public_test_key'));

        $data['brick_private_test_key'] = $this->getPostData(
            'brick_private_test_key',
            $this->config->get('brick_private_test_key'));

        $data['brick_secret_key'] = $this->getPostData(
            'brick_secret_key',
            $this->config->get('brick_secret_key'));

        $data['brick_complete_status'] = $this->getPostData(
            'brick_complete_status',
            $this->config->get('brick_complete_status'));

        $data['brick_under_review_status'] = $this->getPostData(
            'brick_under_review_status',
            $this->config->get('brick_under_review_status'));

        $data['brick_cancel_status'] = $this->getPostData(
            'brick_cancel_status',
            $this->config->get('brick_cancel_status'));

        $data['brick_test_mode'] = $this->getPostData(
            'brick_test_mode',
            $this->config->get('brick_test_mode'));

        $data['brick_status'] = $this->getPostData(
            'brick_status',
            $this->config->get('brick_status'));

        $data['brick_sort_order'] = $this->getPostData(
            'brick_sort_order',
            $this->config->get('brick_sort_order'));

        $data['brick_delivery'] = $this->getPostData(
            'brick_delivery',
            $this->config->get('brick_delivery'));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $data;
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
