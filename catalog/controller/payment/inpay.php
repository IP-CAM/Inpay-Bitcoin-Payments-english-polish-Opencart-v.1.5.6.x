<?php

class ControllerPaymentInpay extends Controller
{
    protected function index ()
    {
        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['button_back'] = $this->language->get('button_back');

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $api_key = $this->config->get('api_key');
        $secret_key = $this->config->get('secret_key');
        $gateway_url = ($this->config->get('gateway_url') !== "")? $this->config->get('gateway_url') : "https://inpay.pl/api/invoice/create";

        $orderCode = $this->session->data['order_id'];
        $amount = number_format($order_info['total'], 2, '.', '');

        $callbackUrl = $this->url->link('payment/inpay/callback', '', 'SSL');
        $successUrl = $this->url->link('checkout/success');
        $failUrl = $this->url->link('checkout/checkout', '', 'SSL');

        $product = '';
        foreach ($this->cart->getProducts() as $item) {
            $product .= $item['name'] . '; ';
        }
        $product = rtrim($product, '; ');

        $data = array(
            "apiKey" => $api_key,
            "amount" => $amount,
            "currency" => strtoupper($order_info['currency_code']),
            "optData" => '',
            "orderCode" => time() . '_' . $orderCode,
            "description" => $product,
            "customerName" => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
            "customerAddress1" => $order_info['payment_address_1'],
            "customerAddress2" => $order_info['payment_address_2'],
            "customerCity" => $order_info['payment_city'],
            "customerState" => $order_info['payment_zone'],
            "customerZip" => $order_info['payment_postcode'],
            "customerCountry" => $order_info['payment_iso_code_2'],
            "customerEmail" => $order_info['email'],
            "customerPhone" => $order_info['telephone'],
            "callbackUrl" => $callbackUrl,
            "successUrl" => $successUrl,
            "failUrl" => $failUrl,
            "minConfirmations" => ''
        );

        $request = http_build_query($data);

        $result = $this->sendRequest($gateway_url, $request);
        //echo $result;exit;
        $result = json_decode($result);

        if ($result->messageType == 'success') {
            $redirect_url = $result->redirectUrl;
            $this->data['redirect_url'] = $redirect_url;

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/inpay.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/payment/inpay.tpl';
            } else {
                $this->template = 'default/template/payment/inpay.tpl';
            }

            $this->render();
        } elseif ($result->messageType == 'error' && isset($result->message)) {
            $message = $result->message;
            header('HTTP/1.1 400 Payment Request Error');
            exit($message);
        } elseif ($result->messageType == 'error' && isset($result->error->amount)) {
            $message = $result->error->amount;
            header('HTTP/1.1 400 Payment Request Error');
            exit($message);
        } else {
            $message = 'Payment option currently not available, please contact support';
            header('HTTP/1.1 400 Payment Request Error');
            exit($message);
        }
    }

    public function callback ()
    {
        $secret_key = $this->config->get('secret_key');

        $orderCode = isset($_POST['orderCode'])?$_POST['orderCode']:'';
        if($orderCode != ''){
            $tmp = explode('_', $orderCode);
            $order_id = (int)$tmp[1];
            $paid_amount = $_POST['amount'];
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            if (!$order_info) return;

            $order_amount = number_format($order_info['total'], 2, '.', '');

            $apiHash = $_SERVER['HTTP_API_HASH'];
            $query = http_build_query($_POST);
            $hash = hash_hmac("sha512", $query, $secret_key);

            if ($apiHash == $hash && $paid_amount == $order_amount) {
                //success transaction
                $this->load->model('checkout/order');
                $this->model_checkout_order->confirm($order_id, $this->config->get('config_order_status_id'));
                $this->model_checkout_order->update($order_id, $this->config->get('inpay_order_status_id'), 'Invoice Code: ' . $_POST['invoiceCode'], false);
            } else {
                //failed transaction
            }
        }
        exit;
    }

    public function sendRequest ($gateway_url, $request)
    {
        $CR = curl_init();
        curl_setopt($CR, CURLOPT_URL, $gateway_url);
        curl_setopt($CR, CURLOPT_POST, 1);
        curl_setopt($CR, CURLOPT_FAILONERROR, true);
        curl_setopt($CR, CURLOPT_POSTFIELDS, $request);
        curl_setopt($CR, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($CR, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($CR, CURLOPT_FAILONERROR, true);


        //actual curl execution perfom
        $result = curl_exec($CR);
        $error = curl_error($CR);

        // on error - die with error message
        if (!empty($error)) {
            die($error);
        }

        curl_close($CR);

        return $result;
    }
}

?>