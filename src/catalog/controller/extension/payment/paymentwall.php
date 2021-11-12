<?php

class ControllerExtensionPaymentPaymentwall extends Controller
{
    public function index()
    {
        $this->language->load('extension/payment/paymentwall');

        $data['pay_via_paymentwall'] = $this->language->get('pay_via_paymentwall');
        $data['widget_link'] = $this->url->link('extension/payment/paymentwall/widget', '', 'SSL');
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/paymentwall')) {
            return $this->load->view($this->config->get('config_template') . '/template/extension/payment/paymentwall', $data);
        } else {
            return $this->load->view('extension/payment/paymentwall', $data);
        }
    }

    public function widget()
    {   
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/paymentwall');
        $this->language->load('extension/payment/paymentwall');
        $orderInfo = array();
        
        if (!empty($this->session->data['order_id'])) {

            $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            
            $this->cart->clear();
            // Add to activity log
            $this->load->model('account/activity');

            if ($this->customer->isLogged()) {
                $activity_data = array(
                    'customer_id' => $this->customer->getId(),
                    'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
                    'order_id'    => $this->session->data['order_id']
                );
                
                $this->model_account_activity->addActivity('order_account', $activity_data);
            } else {
                $activity_data = array(
                    'name'     => $this->session->data['guest']['firstname'] . ' ' . $this->session->data['guest']['lastname'],
                    'order_id' => $this->session->data['order_id']
                );
                
                $this->model_account_activity->addActivity('order_guest', $activity_data);
            }
            unset($this->session->data['order_id']);
        } else {
            // Redirect to shopping cart
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        $data = $this->prepareViewData($orderInfo, $this->customer);
        
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/paymentwall_widget')) {
            $template = $this->config->get('config_template') . '/template/extension/payment/paymentwall_widget';
        } else { 
            $template = 'extension/payment/paymentwall_widget';
        }
        
        $viewData = $this->load->view($template, $data);
        
        $this->response->setOutput($viewData);
    }

    protected function prepareViewData($orderInfo, $customer)
    {
        $this->document->setTitle($this->language->get('widget_title'));
        $data['breadcrumbs'] = array(
            array(
                'href' => $this->url->link('common/home'),
                'text' => $this->language->get('text_home'),
                'separator' => false
            ),
            array(
                'text' => $this->language->get('widget_title'),
                'separator' => $this->language->get('text_separator'),
                'href' => '#'
            )
        );

        $data['widget_title'] = $this->language->get('widget_title');
        $data['widget_notice'] = $this->language->get('widget_notice');
        $data['iframe'] = $this->model_extension_payment_paymentwall->generateWidget($orderInfo, $customer, $this->config->get('payment_paymentwall_success_url'));

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        return $data;
    }
}
