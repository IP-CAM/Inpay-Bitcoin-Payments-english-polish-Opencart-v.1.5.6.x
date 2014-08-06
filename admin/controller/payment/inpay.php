<?php

class ControllerPaymentInpay extends Controller
{
    private $error = array();

    public function index ()
    {
        $this->load->language('payment/inpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('inpay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_all_zones'] = $this->language->get('text_all_zones');
        $this->data['text_yes'] = $this->language->get('text_yes');
        $this->data['text_no'] = $this->language->get('text_no');

        $this->data['entry_api_key'] = $this->language->get('entry_api_key');
        $this->data['entry_secret_key'] = $this->language->get('entry_secret_key');
        $this->data['entry_test_mode'] = $this->language->get('entry_test_mode');

        $this->data['entry_lang'] = $this->language->get('entry_lang');
        $this->data['entry_order_status'] = $this->language->get('entry_order_status');
        $this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        $this->data['tab_general'] = $this->language->get('tab_general');

        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        if (isset($this->error['api_key'])) {
            $this->data['error_api_key'] = $this->error['api_key'];
        } else {
            $this->data['error_api_key'] = '';
        }

        if (isset($this->error['secret_key'])) {
            $this->data['error_secret_key'] = $this->error['secret_key'];
        } else {
            $this->data['error_secret_key'] = '';
        }

        $this->document->breadcrumbs = array();

        $this->document->breadcrumbs[] = array(
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'text'      => $this->language->get('text_home'),
            'separator' => FALSE
        );

        $this->document->breadcrumbs[] = array(
            'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'text'      => $this->language->get('text_payment'),
            'separator' => ' :: '
        );

        $this->document->breadcrumbs[] = array(
            'href'      => $this->url->link('payment/inpay', 'token=' . $this->session->data['token'], 'SSL'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: '
        );

        $this->data['action'] = $this->url->link('payment/inpay', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['api_key'])) {
            $this->data['api_key'] = $this->request->post['api_key'];
        } else {
            $this->data['api_key'] = $this->config->get('api_key');
        }

        if (isset($this->request->post['secret_key'])) {
            $this->data['secret_key'] = $this->request->post['secret_key'];
        } else {
            $this->data['secret_key'] = $this->config->get('secret_key');
        }

        if (isset($this->request->post['test_mode'])) {
            $this->data['test_mode'] = $this->request->post['test_mode'];
        } else {
            $this->data['test_mode'] = $this->config->get('test_mode');
        }

        if (isset($this->request->post['inpay_order_status_id'])) {
            $this->data['inpay_order_status_id'] = $this->request->post['inpay_order_status_id'];
        } else {
            $this->data['inpay_order_status_id'] = $this->config->get('inpay_order_status_id');
        }

        $this->load->model('localisation/order_status');

        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['inpay_geo_zone_id'])) {
            $this->data['inpay_geo_zone_id'] = $this->request->post['inpay_geo_zone_id'];
        } else {
            $this->data['inpay_geo_zone_id'] = $this->config->get('inpay_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['inpay_status'])) {
            $this->data['inpay_status'] = $this->request->post['inpay_status'];
        } else {
            $this->data['inpay_status'] = $this->config->get('inpay_status');
        }

        if (isset($this->request->post['inpay_sort_order'])) {
            $this->data['inpay_sort_order'] = $this->request->post['inpay_sort_order'];
        } else {
            $this->data['inpay_sort_order'] = $this->config->get('inpay_sort_order');
        }

        $this->template = 'payment/inpay.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
    }

    private function validate ()
    {
        if (!$this->user->hasPermission('modify', 'payment/inpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }
        if (!$this->request->post['secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }

        if (!$this->error) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

?>