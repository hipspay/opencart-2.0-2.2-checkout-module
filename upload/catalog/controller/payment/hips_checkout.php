<?php
class ControllerPaymentHipsCheckout extends Controller
{
    
    private $error = array();
    public function index()
    {
        
        $this->load->language('payment/hips_checkout');
        $this->load->model('payment/hips_checkout');
        $this->load->model('localisation/country');
        
        if ($this->request->get['route'] == "payment/hips_checkout") {
            $this->document->setTitle($this->language->get('heading_title'));
            
            $data['column_left']    = $this->load->controller('payment/hips_checkout/left');
            $data['column_right']   = $this->load->controller('common/column_right');
            $data['content_top']    = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['column_main']    = $this->load->controller('payment/hips_checkout/main');
            
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');
            
            $data['button_remove'] = $this->language->get('button_remove');
            $data['text_empty']    = $this->language->get('text_empty');
            
            $data['heading_title'] = $this->language->get('text_checkout_title');
            $data['section_left']  = $this->config->get('hips_mode_bar');
            
            if (version_compare(VERSION, '2.2.0.0', '>=')) {
                $this->response->setOutput($this->load->view('payment/hips_checkout.tpl', $data));
            } else {
                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/hips_checkout.tpl')) {
                    $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/hips_checkout.tpl', $data));
                } else {
                    $this->response->setOutput($this->load->view('default/template/payment/hips_checkout.tpl', $data));
                }
            }
            
        }
        
        else {
            $data['text_credit_card'] = $this->language->get('text_credit_card');
            $data['text_start_date']  = $this->language->get('text_start_date');
            $data['text_wait']        = $this->language->get('text_wait');
            $data['text_loading']     = $this->language->get('text_loading');
            
            $data['entry_cc_type']        = $this->language->get('entry_cc_type');
            $data['entry_cc_number']      = $this->language->get('entry_cc_number');
            $data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
            $data['entry_cc_cvv2']        = $this->language->get('entry_cc_cvv2');
            $data['entry_cc_issue']       = $this->language->get('entry_cc_issue');
            
            $data['help_start_date'] = $this->language->get('help_start_date');
            $data['help_issue']      = $this->language->get('help_issue');
            
            $data['button_confirm'] = $this->language->get('button_confirm');
            
            $data['months'] = array();
            
            for ($i = 1; $i <= 12; $i++) {
                $data['months'][] = array(
                    'text' => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)),
                    'value' => sprintf('%02d', $i)
                );
            }
            
            $today = getdate();
            
            $data['year_valid'] = array();
            
            for ($i = $today['year'] - 10; $i < $today['year'] + 1; $i++) {
                $data['year_valid'][] = array(
                    'text' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
                    'value' => strftime('%y', mktime(0, 0, 0, 1, 1, $i))
                );
            }
            
            $data['year_expire'] = array();
            
            for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
                $data['year_expire'][] = array(
                    'text' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
                    'value' => strftime('%y', mktime(0, 0, 0, 1, 1, $i))
                );
            }
            $data['hips_key_public'] = $this->config->get('hips_key_public');
            
            if (version_compare(VERSION, '2.2.0.0', '>=')) {
                return $this->load->view('payment/hipsform.tpl', $data);
            } else {
                return $this->load->view('default/template/payment/hipsform.tpl', $data);
            }
        }
    }
    
    public function main()
    {
        $this->load->language('payment/hips_checkout');
        
        $this->load->model('payment/hips_checkout');
        $this->load->model('localisation/country');
        
        $redirect     = false;
        $html_snippet = '';
        
        $data['heading_title'] = $this->language->get('text_complete_title');
        
        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            unset($this->session->data['hips_checkout_order_id']);
            $redirect = $this->url->link('checkout/cart');
        }
        
        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();
        
        foreach ($products as $product) {
            $product_total = 0;
            
            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }
            
            if ($product['minimum'] > $product_total) {
                $redirect = $this->url->link('checkout/cart');
            }
        }
        
        // Validate cart has recurring products
        if ($this->cart->hasRecurringProducts()) {
            $redirect = $this->url->link('checkout/cart');
        }
        
        if (version_compare(VERSION, '2.2.0.0', '>=')) {
            list($totals, $taxes, $total) = $this->model_payment_hips_checkout->getTotals();
        } else {
            list($total_data, $total, $taxes) = $this->model_payment_hips_checkout->getTotals();
        }
        
        if ($this->config->get('hips_payment_type') == 'card') {
            $redirect = $this->url->link('checkout/checkout');
        }
        
        if ($this->config->get('hips_checkout_total') > 0 && $this->config->get('hips_checkout_total') > $total) {
            $redirect = $this->url->link('checkout/cart');
        }
        
        if (!$this->config->get('hips_checkout_status')) {
            $redirect = $this->url->link('checkout/cart');
        }
        
        if ($this->model_payment_hips_checkout->checkForPaymentTaxes($products)) {
            $redirect = $this->url->link('checkout/cart');
        }
        
        $text_title = $this->language->get('text_title');
        
        unset($this->session->data['success']);
        
        $this->setPayment();
        $this->setShipping();
        
        $this->session->data['payment_method'] = array(
            'code' => 'hips_checkout',
            'title' => $text_title,
            'terms' => $this->url->link('information/information', 'information_id=' . $this->config->get('hips_checkout_terms'), true),
            'sort_order' => '1'
        );
        
        // Shipping
        $unset_shipping_method = true;
        if (isset($this->session->data['shipping_method']) && isset($this->session->data['shipping_methods'])) {
            foreach ($this->session->data['shipping_methods'] as $shipping_method) {
                if ($shipping_method['quote']) {
                    foreach ($shipping_method['quote'] as $quote) {
                        if ($quote['code'] == $this->session->data['shipping_method']['code']) {
                            $unset_shipping_method = false;
                            break 2;
                        }
                    }
                }
            }
        }
        
        if ($unset_shipping_method) {
            unset($this->session->data['shipping_method']);
        }
        
        if ((!isset($this->session->data['shipping_method']) || empty($this->session->data['shipping_method'])) && (isset($this->session->data['shipping_methods']) && !empty($this->session->data['shipping_methods']))) {
            $this->session->data['shipping_method'] = $this->model_payment_hips_checkout->getDefaultShippingMethod($this->session->data['shipping_methods']);
        }
        
        if (!$redirect) {
            
            // Get currency code and currency value to use to calculate taxes
            
            // Build order_lines
            $create_order  = true;
            $hips_checkout = false;
            
            //
            if (isset($this->session->data['hips_checkout_currency']) && $this->session->data['hips_checkout_currency'] != $this->session->data['currency']) {
                $this->model_payment_hips_checkout->log('Currency changed, unsetting HC order id');
                unset($this->session->data['hips_checkout_order_id']);
                unset($this->session->data['hips_checkout_data']);
            }
            
            $this->session->data['hips_checkout_currency'] = $this->session->data['currency'];
            
            // Fetch or create order
            if (isset($this->session->data['order_token']) && isset($this->request->post['type']) && $this->request->post['type'] == 'partsection') {
                
                list($hips_order_data, $encrypted_order_data) = $this->hipsOrderData();
                
                $i = 0;
                foreach ($hips_order_data['cart']['items'] as $prodid) {
                    $hips_order_data['cart']['items'][$i]['id'] = $this->session->data['productid'][$i];
                    $i++;
                }
                
                $respnse = $this->model_payment_hips_checkout->orderUpdate($this->config->get('hips_key'), $hips_order_data, $this->session->data['order_token']);
                
                $productIdData = array();
                foreach ($respnse->cart->items as $productID) {
                    $productIdData[] = $productID->id;
                }
                
                $this->session->data['productid'] = $productIdData;
                
                if ($respnse) {
                    $hips_checkout_order_id = $respnse->id;
                    $this->model_payment_hips_checkout->updateOrder($this->session->data['order_id'], $hips_checkout['order_id'], $encrypted_order_data, json_encode($respnse));
                }
                
                $html_snippet = html_entity_decode($respnse->html_snippet);
                
            } else {
                
                $this->createOrder();
                list($hips_order_data, $encrypted_order_data) = $this->hipsOrderData();
                $this->model_payment_hips_checkout->log('Order Created');
                $this->model_payment_hips_checkout->log($hips_order_data);
                
                unset($this->session->data['hips_checkout_data']);
                
                
                $respnse = $this->model_payment_hips_checkout->orderCreate($this->config->get('hips_key'), $hips_order_data);
                
            ?>
               <?php
                if (isset($respnse->id) && $respnse->id != '') {
                ?>
                   <input type ="hidden" name="tokenValueUpdated" id="tokenValueUpdated" value="<?php
                    echo $respnse->id;
                ?>">          
                <?php
                }
                ?>      
                
            <?php
                
                if (isset($respnse->id) && $respnse->id != '') {
                    $hips_checkout_order_id = $respnse->id;
                    
                    $productIdData = array();
                    foreach ($respnse->cart->items as $productID) {
                        $productIdData[] = $productID->id;
                    }
                    
                    $this->session->data['productid'] = $productIdData;
                    
                    $this->session->data['order_token'] = $hips_checkout_order_id;
                    
                    $this->model_payment_hips_checkout->addOrder($this->session->data['order_id'], $hips_checkout_order_id, $encrypted_order_data, json_encode($respnse));
                } else {
                    if (isset($respnse->error->message)) {
                        $html_snippet = $respnse->error->message;
                    }
                }
                
                if (isset($hips_checkout_order_id) && $hips_checkout_order_id != '') {
                    
                    $html_snippet = html_entity_decode($respnse->html_snippet);
                }
                
            }
        }
        
        $data['redirect']     = $redirect;
        $data['html_snippet'] = $html_snippet;
        
        if (version_compare(VERSION, '2.2.0.0', '>=')) {
            return $this->load->view('payment/hips_checkout_main.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/hips_checkout_main.tpl', $data);
        }
        
    }
    
    public function callback()
    {
        
        $out2 = file_get_contents('php://input');
        $data = json_decode($out2);
        
        if (trim($data->event) == 'payment.successful') 
        {
			//verify token
			$ver = $this->verify_token($data->jwt);
			if($ver !== 1) {
				return false;
			}
			
            $res = $data->resource->order_id;
            
            $this->load->model('payment/hips_checkout');
            $this->load->model('checkout/order');
            
            if (!$this->config->get('hips_checkout_status')) {
                return false;
            }
            
            if (isset($res)) {
                
                $hips_checkout_order = $this->model_payment_hips_checkout->getOrder($res);
                
                if ($hips_checkout_order) {
                    $order_info = $this->model_checkout_order->getOrder($hips_checkout_order['order_id']);
                    
                    
                    if ($order_info) {
                        
                        $order = $this->model_payment_hips_checkout->orderRetrieve($this->config->get('hips_key'), $res);
                        
                        $this->model_payment_hips_checkout->log('Order details from push:');
                        $this->model_payment_hips_checkout->log($order);
                        
                        if ($this->config->get('hips_mode_bar') == 'yes') {
                            
                            $this->model_payment_hips_checkout->addShipping($hips_checkout_order['order_id'], $order);
                        }
                        
                        if ($order) {
                            
                            
                            // Update OpenCart order with payment and shipping details
                            $payment_country_info  = $this->model_payment_hips_checkout->getCountryByIsoCode2($order->billing_address->country);
                            $shipping_country_info = $this->model_payment_hips_checkout->getCountryByIsoCode2($order->shipping_address->country);
                            
                            //If region is passed, try to update OpenCart order with correct region/zone
                            $payment_zone_info = array();
                            if ($payment_country_info && isset($order->billing_address->region)) {
                                $payment_zone_info = $this->model_payment_hips_checkout->getZoneByCode($order->billing_address->region, $payment_country_info['country_id']);
                            }
                            
                            $order_data = array(
                                'firstname' => utf8_substr($order->billing_address->given_name, 0, 32),
                                'lastname' => utf8_substr($order->billing_address->family_name, 0, 32),
                                'payment_firstname' => utf8_substr($order->billing_address->given_name, 0, 32),
                                'payment_lastname' => utf8_substr($order->billing_address->family_name, 0, 32),
                                'payment_address_1' => utf8_substr($order->billing_address->street_address, 0, 128),
                                'email' => utf8_substr($order->billing_address->email, 0, 96),
                                'telephone' => utf8_substr($order->billing_address->phone_mobile, 0, 128),
                                'payment_address_2' => (isset($order->billing_address->street_address2) ? utf8_substr($order->billing_address->street_address2, 0, 128) : ''),
                                'payment_city' => utf8_substr($order->billing_address->city, 0, 128),
                                'payment_postcode' => utf8_substr($order->billing_address->postal_code, 0, 10),
                                'payment_country' => ($payment_country_info ? $payment_country_info['name'] : ''),
                                
                                
                                
                                'payment_country_id' => ($payment_country_info ? $payment_country_info['country_id'] : ''),
                                'payment_address_format' => ($payment_country_info ? $payment_country_info['address_format'] : ''),
                                'shipping_firstname' => utf8_substr($order->shipping_address->given_name, 0, 32),
                                'shipping_lastname' => utf8_substr($order->shipping_address->family_name, 0, 32),
                                'shipping_address_1' => utf8_substr($order->shipping_address->street_address, 0, 128),
                                'shipping_address_2' => (isset($order->shipping_address->street_address2) ? utf8_substr($order->shipping_address->street_address2, 0, 128) : ''),
                                'shipping_city' => utf8_substr($order->shipping_address->city, 0, 128),
                                'shipping_postcode' => utf8_substr($order->shipping_address->postal_code, 0, 10),
                                
                                
                                
                                'shipping_country' => ($shipping_country_info ? $shipping_country_info['name'] : ''),
                                'shipping_country_id' => ($shipping_country_info ? $shipping_country_info['country_id'] : ''),
                                'shipping_address_format' => ($shipping_country_info ? $shipping_country_info['address_format'] : '')
                            );
                            
                            $this->model_payment_hips_checkout->updateOcOrder($hips_checkout_order['order_id'], $order_data);
                            
                            $order_status_id = false;
                            
                            switch ($order->status) {
                                
                                case 'successful':
                                    $order_status_id = $this->config->get('config_order_status_id');
                                    break;
                            }
                            
                            if ($order_status_id) {
                                $this->model_checkout_order->addOrderHistory($hips_checkout_order['order_id'], $order_status_id);
                            }
                            
                            /*Add Payment details in comment admin section*/
                            
                            $this->load->model('payment/hips_checkout');
                            $this->load->model('checkout/order');
                            
                            $resValue = $data->resource->id;
                            
                            $jresult = $this->model_payment_hips_checkout->getPaymentInfo($resValue, $this->config->get('hips_key'));
                            
                            $message = '';
                            
                            if (isset($jresult['status']) && $jresult['status'] != "") {
                                if (isset($jresult['id'])) {
                                    $message .= 'id: ' . $jresult['id'] . "\n";
                                }
                                
                                if (isset($jresult['data']['authCode'])) {
                                    $message .= 'object: ' . $jresult['object'] . "\n";
                                }
                                
                                if (isset($jresult['data']['referenceNumber'])) {
                                    $message .= 'source: ' . $jresult['source'] . "\n";
                                }
                                
                                if (isset($jresult['data']['originalFullAmount'])) {
                                    $message .= 'status: ' . $jresult['status'] . "\n";
                                }
                                if (isset($jresult['preflight']['requires_redirect'])) {
                                    $message .= 'Requires Redirect: ' . $jresult['preflight']['requires_redirect'] . "\n";
                                }
                                if (isset($jresult['preflight']['redirect_user_to_url'])) {
                                    $message .= 'Redirect User to Url: ' . $jresult['preflight']['redirect_user_to_url'] . "\n";
                                }
                                if (isset($jresult['preflight']['status'])) {
                                    $message .= 'Preflight Status: ' . $jresult['preflight']['status'] . "\n";
                                }
                                if (isset($jresult['order']['id'])) {
                                    $message .= 'Order ID: ' . $jresult['order']['id'] . "\n";
                                }
                                if (isset($jresult['amount'])) {
                                    $message .= 'Amount: ' . number_format($jresult['amount'] / 100, 2, '.', '') . "\n";
                                    
                                }
                                if (isset($jresult['amount_currency'])) {
                                    $message .= 'Amount Currency: ' . $jresult['amount_currency'] . "\n";
                                    
                                }
                                if (isset($jresult['settlement_amount'])) {
                                    $message .= 'Settlement Amount: ' . number_format($jresult['settlement_amount'] / 100, 2, '.', '') . "\n";
                                    
                                }
                                if (isset($jresult['settlement_amount_currency'])) {
                                    $message .= 'Settlement Amount Currency: ' . $jresult['settlement_amount_currency'] . "\n";
                                    
                                }
                                if (isset($jresult['settlement_exchange_ratet'])) {
                                    $message .= 'Settlement Exchange Rate: ' . $jresult['settlement_exchange_rate'] . "\n";
                                    
                                }
                                if (isset($jresult['authorization_code'])) {
                                    $message .= 'Authorization Code: ' . $jresult['authorization_code'] . "\n";
                                    
                                }
                                if (isset($jresult['decline_reason'])) {
                                    $message .= 'Decline Reason: ' . $jresult['decline_reason'] . "\n";
                                }
                                
                                $this->model_checkout_order->addOrderHistory($hips_checkout_order['order_id'], $this->config->get('hips_order_status_id'), $message, false);
                            }
                            /*Add Payment details in comment admin section*/
                            
                        } else {
                            $this->model_payment_hips_checkout->log('Cannot retrieve HC order using order_id: ' . $this->request->get['hips_order_id']);
                        }
                    }
                }
                
            } else {
                $this->model_payment_hips_checkout->log('Cannot find HC order using order_id: ' . $this->request->get['hips_order_id']);
            }
        }
        
    }
    
    public function fail()
    {
        
        $this->load->language('payment/hips_checkout');
        
        if (isset($this->request->post['fail_reason']) && !empty($this->request->post['fail_reason'])) {
            $this->session->data['error'] = $this->request->post['fail_reason'];
        } else {
            $this->session->data['error'] = $this->language->get('error_failed');
        }
        
        $this->response->redirect($this->url->link('checkout/checkout', '', true));
        
        
    }
    
    public function left()
    {
        
        $this->load->language('payment/hips_checkout');
        
        $this->load->model('payment/hips_checkout');
        $this->load->model('localisation/country');
        
        $this->load->language('common/cart');
        
        $data['heading_title'] = $this->language->get('text_review_title');
        $data['button_remove'] = $this->language->get('button_remove');
        $data['text_empty']    = $this->language->get('text_empty');
        
        if ($this->config->get('config_cart_weight')) {
            $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
        } else {
            $data['weight'] = '';
        }
        
        $this->load->model('tool/image');
        $this->load->model('tool/upload');
        
        $data['products'] = array();
        
        $products = $this->cart->getProducts();
        
        foreach ($products as $product) {
            $product_total = 0;
            
            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }
            
            if ($product['minimum'] > $product_total) {
                $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
            }
            
            if (version_compare(VERSION, '2.2.0.0', '>=')) {
                
                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], $this->config->get($this->config->get('config_theme') . '_image_cart_width'), $this->config->get($this->config->get('config_theme') . '_image_cart_height'));
                } else {
                    $image = '';
                }
            } else {
                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_cart_width'), $this->config->get('config_image_cart_height'));
                } else {
                    $image = '';
                }
            }
            
            $option_data = array();
            
            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
                    
                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }
                
                $option_data[] = array(
                    'name' => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }
            
            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                
                $price = $this->currency->format($unit_price, $this->session->data['currency']);
                $total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
            } else {
                $price = false;
                $total = false;
            }
            
            $recurring = '';
            
            if ($product['recurring']) {
                $frequencies = array(
                    'day' => $this->language->get('text_day'),
                    'week' => $this->language->get('text_week'),
                    'semi_month' => $this->language->get('text_semi_month'),
                    'month' => $this->language->get('text_month'),
                    'year' => $this->language->get('text_year')
                );
                
                if ($product['recurring']['trial']) {
                    $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                }
                
                if ($product['recurring']['duration']) {
                    $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                } else {
                    $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                }
            }
            if (version_compare(VERSION, '2.1.0.0', '>=')) {
                $data['products'][] = array(
                    'cart_id' => $product['cart_id'],
                    'thumb' => $image,
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'option' => $option_data,
                    'recurring' => $recurring,
                    'quantity' => $product['quantity'],
                    'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                    'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                    'price' => $price,
                    'total' => $total,
                    'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                );
            } else {
                
                $data['products'][] = array(
                    'cart_id' => $product['key'],
                    'thumb' => $image,
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'option' => $option_data,
                    'recurring' => $recurring,
                    'quantity' => $product['quantity'],
                    'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                    'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                    'price' => $price,
                    'total' => $total,
                    'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                );
            }
        }
        
        // Gift Voucher
        $data['vouchers'] = array();
        
        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $key => $voucher) {
                $data['vouchers'][] = array(
                    'key' => $key,
                    'description' => $voucher['description'],
                    'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency']),
                    'remove' => $this->url->link('checkout/cart', 'remove=' . $key)
                );
            }
        }
        
        // Totals
        $this->load->model('extension/extension');
        
        if (version_compare(VERSION, '2.2.0.0', '>=')) {
            
            $totals = array();
            $taxes  = $this->cart->getTaxes();
            $total  = 0;
            
            
            
            // Because __call can not keep var references so we put them into an array.            
            $total_data = array(
                'totals' => &$totals,
                'taxes' => &$taxes,
                'total' => &$total
            );
            
            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $sort_order = array();
                
                $results = $this->model_extension_extension->getExtensions('total');
                
                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }
                
                array_multisort($sort_order, SORT_ASC, $results);
                
                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('total/' . $result['code']);
                        
                        // We have to put the totals in an array so that they pass by reference.
                        $this->{'model_total_' . $result['code']}->getTotal($total_data);
                    }
                }
                
                $sort_order = array();
                
                foreach ($totals as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }
                
                array_multisort($sort_order, SORT_ASC, $totals);
            }
            
            $data['totals'] = array();
            
            foreach ($totals as $total) {
                
                $data['totals'][] = array(
                    'title' => $total['title'],
                    'text' => $this->currency->format($total['value'], $this->session->data['currency'])
                );
            }
            
            
            return $this->load->view('payment/hips_checkout_left.tpl', $data);
        }
        
        else {
            $total_data =& $total_data;
            $taxes =& $taxes;
            $total =& $total;
            
            $total_data = array();
            $total      = 0;
            $taxes      = $this->cart->getTaxes();
            
            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                
                $sort_order = array();
                
                $results = $this->model_extension_extension->getExtensions('total');
                
                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }
                
                array_multisort($sort_order, SORT_ASC, $results);
                
                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('total/' . $result['code']);
                        
                        $this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
                        
                    }
                }
                
                $sort_order = array();
                
                foreach ($total_data as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }
                
                array_multisort($sort_order, SORT_ASC, $total_data);
            }
            
            $data['total_data'] = array();
            
            foreach ($total_data as $total) {
                $data['total_data'][] = array(
                    'title' => $total['title'],
                    'text' => $this->currency->format($total['value'], $this->session->data['currency'])
                );
            }
            
            return $this->load->view('default/template/payment/hips_checkout_left.tpl', $data);
        }
        
    }
    
    private function setPayment()
    {
        $this->load->model('account/address');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        
        if (isset($this->session->data['payment_address']) && !empty($this->session->data['payment_address'])) {
            $this->session->data['payment_address'] = $this->session->data['payment_address'];
        } elseif ($this->customer->isLogged() && $this->customer->getAddressId()) {
            $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
        } else {
            $country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));
            
            $zone_info = $this->model_localisation_zone->getZone($this->config->get('config_zone_id'));
            
            $this->session->data['payment_address'] = array(
                'address_id' => null,
                'firstname' => null,
                'lastname' => null,
                'company' => null,
                'address_1' => null,
                'address_2' => null,
                'postcode' => null,
                'city' => null,
                'zone_id' => $zone_info['zone_id'],
                'zone' => $zone_info['name'],
                'zone_code' => $zone_info['code'],
                'country_id' => $country_info['country_id'],
                'country' => $country_info['name'],
                'iso_code_2' => $country_info['iso_code_2'],
                'iso_code_3' => $country_info['iso_code_3'],
                'address_format' => '',
                'custom_field' => array()
            );
        }
        
        $this->tax->setPaymentAddress($this->session->data['payment_address']['country_id'], $this->session->data['payment_address']['zone_id']);
        $this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
    }
    
    private function setShipping()
    {
        $this->load->model('account/address');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        
        if (isset($this->session->data['shipping_address']) && !empty($this->session->data['shipping_address'])) {
            $this->session->data['shipping_address'] = $this->session->data['shipping_address'];
        } elseif ($this->customer->isLogged() && $this->customer->getAddressId()) {
            $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
        } else {
            $country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));
            
            $zone_info = $this->model_localisation_zone->getZone($this->config->get('config_zone_id'));
            
            $this->session->data['shipping_address'] = array(
                'address_id' => null,
                'firstname' => null,
                'lastname' => null,
                'company' => null,
                'address_1' => null,
                'address_2' => null,
                'postcode' => null,
                'city' => null,
                'zone_id' => $zone_info['zone_id'],
                'zone' => $zone_info['name'],
                'zone_code' => $zone_info['code'],
                'country_id' => $country_info['country_id'],
                'country' => $country_info['name'],
                'iso_code_2' => $country_info['iso_code_2'],
                'iso_code_3' => $country_info['iso_code_3'],
                'address_format' => '',
                'custom_field' => array()
            );
        }
       
        $this->tax->setShippingAddress($this->session->data['shipping_address']['country_id'], $this->session->data['shipping_address']['zone_id']);
        $this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
        
        if (isset($this->session->data['shipping_address'])) {
            // Shipping Methods
            $method_data = array();
            
            $this->load->model('extension/extension');
            
            $results = $this->model_extension_extension->getExtensions('shipping');
            
            foreach ($results as $result) {
                if ($this->config->get($result['code'] . '_status')) {
                    $this->load->model('shipping/' . $result['code']);
                    
                    $quote = $this->{'model_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);
                    
                    if ($quote) {
                        $method_data[$result['code']] = array(
                            'title' => $quote['title'],
                            'quote' => $quote['quote'],
                            'sort_order' => $quote['sort_order'],
                            'error' => $quote['error']
                        );
                    }
                }
            }
            
            $sort_order = array();
            
            foreach ($method_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }
            
            array_multisort($sort_order, SORT_ASC, $method_data);
            
            $this->session->data['shipping_methods'] = $method_data;
        }
    }
    
    private function createOrder()
    {
        //Hips defaults:
        $this->session->data['comment'] = '';
        
        if (!$this->customer->isLogged()) {
            $this->session->data['guest'] = array(
                'customer_group_id' => $this->config->get('config_customer_group_id'),
                'firstname' => '',
                'lastname' => '',
                'email' => '',
                'telephone' => '',
                'fax' => '',
                'custom_field' => array()
            );
        }
        
        //OpenCart:
        $order_data = array();
        
        list($totals, $taxes, $total) = $this->model_payment_hips_checkout->getTotals();
        $order_data['totals'] = $totals;
        
        $this->load->language('checkout/checkout');
        
        $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
        $order_data['store_id']       = $this->config->get('config_store_id');
        $order_data['store_name']     = $this->config->get('config_name');
        
        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            $order_data['store_url'] = HTTP_SERVER;
        }
        
        if ($this->customer->isLogged()) {
            $this->load->model('account/customer');
            
            $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
            
            $order_data['customer_id']       = $this->customer->getId();
            $order_data['customer_group_id'] = $customer_info['customer_group_id'];
            $order_data['firstname']         = $customer_info['firstname'];
            $order_data['lastname']          = $customer_info['lastname'];
            $order_data['email']             = $customer_info['email'];
            $order_data['telephone']         = $customer_info['telephone'];
            $order_data['fax']               = $customer_info['fax'];
            $order_data['custom_field']      = json_decode($customer_info['custom_field'], true);
        } elseif (isset($this->session->data['guest'])) {
            $order_data['customer_id']       = 0;
            $order_data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
            $order_data['firstname']         = $this->session->data['guest']['firstname'];
            $order_data['lastname']          = $this->session->data['guest']['lastname'];
            $order_data['email']             = $this->session->data['guest']['email'];
            $order_data['telephone']         = $this->session->data['guest']['telephone'];
            $order_data['fax']               = $this->session->data['guest']['fax'];
            $order_data['custom_field']      = $this->session->data['guest']['custom_field'];
        }
        
        $order_data['payment_firstname']      = $this->session->data['payment_address']['firstname'];
        $order_data['payment_lastname']       = $this->session->data['payment_address']['lastname'];
        $order_data['payment_company']        = $this->session->data['payment_address']['company'];
        $order_data['payment_address_1']      = $this->session->data['payment_address']['address_1'];
        $order_data['payment_address_2']      = $this->session->data['payment_address']['address_2'];
        $order_data['payment_city']           = $this->session->data['payment_address']['city'];
        $order_data['payment_postcode']       = $this->session->data['payment_address']['postcode'];
        $order_data['payment_zone']           = $this->session->data['payment_address']['zone'];
        $order_data['payment_zone_id']        = $this->session->data['payment_address']['zone_id'];
        $order_data['payment_country']        = $this->session->data['payment_address']['country'];
        $order_data['payment_country_id']     = $this->session->data['payment_address']['country_id'];
        $order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
        $order_data['payment_custom_field']   = (isset($this->session->data['payment_address']['custom_field']) ? $this->session->data['payment_address']['custom_field'] : array());
        
        if (isset($this->session->data['payment_method']['title'])) {
            $order_data['payment_method'] = $this->session->data['payment_method']['title'];
        } else {
            $order_data['payment_method'] = '';
        }
        
        if (isset($this->session->data['payment_method']['code'])) {
            $order_data['payment_code'] = $this->session->data['payment_method']['code'];
        } else {
            $order_data['payment_code'] = '';
        }
        
        if ($this->cart->hasShipping()) {
            $order_data['shipping_firstname']      = $this->session->data['shipping_address']['firstname'];
            $order_data['shipping_lastname']       = $this->session->data['shipping_address']['lastname'];
            $order_data['shipping_company']        = $this->session->data['shipping_address']['company'];
            $order_data['shipping_address_1']      = $this->session->data['shipping_address']['address_1'];
            $order_data['shipping_address_2']      = $this->session->data['shipping_address']['address_2'];
            $order_data['shipping_city']           = $this->session->data['shipping_address']['city'];
            $order_data['shipping_postcode']       = $this->session->data['shipping_address']['postcode'];
            $order_data['shipping_zone']           = $this->session->data['shipping_address']['zone'];
            $order_data['shipping_zone_id']        = $this->session->data['shipping_address']['zone_id'];
            $order_data['shipping_country']        = $this->session->data['shipping_address']['country'];
            $order_data['shipping_country_id']     = $this->session->data['shipping_address']['country_id'];
            $order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
            $order_data['shipping_custom_field']   = (isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : array());
            
            if (isset($this->session->data['shipping_method']['title'])) {
                $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
            } else {
                $order_data['shipping_method'] = '';
            }
            
            if (isset($this->session->data['shipping_method']['code'])) {
                $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
            } else {
                $order_data['shipping_code'] = '';
            }
        } else {
            $order_data['shipping_firstname']      = '';
            $order_data['shipping_lastname']       = '';
            $order_data['shipping_company']        = '';
            $order_data['shipping_address_1']      = '';
            $order_data['shipping_address_2']      = '';
            $order_data['shipping_city']           = '';
            $order_data['shipping_postcode']       = '';
            $order_data['shipping_zone']           = '';
            $order_data['shipping_zone_id']        = '';
            $order_data['shipping_country']        = '';
            $order_data['shipping_country_id']     = '';
            $order_data['shipping_address_format'] = '';
            $order_data['shipping_custom_field']   = array();
            $order_data['shipping_method']         = '';
            $order_data['shipping_code']           = '';
        }
        
        $order_data['products'] = array();
        
        foreach ($this->cart->getProducts() as $product) {
            $option_data = array();
            
            foreach ($product['option'] as $option) {
                $option_data[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id' => $option['option_id'],
                    'option_value_id' => $option['option_value_id'],
                    'name' => $option['name'],
                    'value' => $option['value'],
                    'type' => $option['type']
                );
            }
            
            $order_data['products'][] = array(
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $option_data,
                'download' => $product['download'],
                'quantity' => $product['quantity'],
                'subtract' => $product['subtract'],
                'price' => $product['price'],
                'total' => $product['total'],
                'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                'reward' => $product['reward']
            );
        }
        
        // Gift Voucher
        $order_data['vouchers'] = array();
        
        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $hips_order_data['cart']['items'][] = array(
                    'description' => $voucher['description'],
                    'code' => token(10),
                    'to_name' => $voucher['to_name'],
                    'to_email' => $voucher['to_email'],
                    'from_name' => $voucher['from_name'],
                    'from_email' => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message' => $voucher['message'],
                    'amount' => $voucher['amount']
                );
            }
        }
        
        $order_data['comment'] = $this->session->data['comment'];
        $order_data['total']   = $total;
        
        if (isset($this->request->cookie['tracking'])) {
            $order_data['tracking'] = $this->request->cookie['tracking'];
            
            $subtotal = $this->cart->getSubTotal();
            
            // Affiliate
            $this->load->model('affiliate/affiliate');
            
            $affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);
            
            if ($affiliate_info) {
                $order_data['affiliate_id'] = $affiliate_info['affiliate_id'];
                $order_data['commission']   = ($subtotal / 100) * $affiliate_info['commission'];
            } else {
                $order_data['affiliate_id'] = 0;
                $order_data['commission']   = 0;
            }
            
            // Marketing
            $this->load->model('checkout/marketing');
            
            $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);
            
            if ($marketing_info) {
                $order_data['marketing_id'] = $marketing_info['marketing_id'];
            } else {
                $order_data['marketing_id'] = 0;
            }
        } else {
            $order_data['affiliate_id'] = 0;
            $order_data['commission']   = 0;
            $order_data['marketing_id'] = 0;
            $order_data['tracking']     = '';
        }
        
        $order_data['language_id']    = $this->config->get('config_language_id');
        $order_data['currency_id']    = $this->currency->getId($this->session->data['currency']);
        $order_data['currency_code']  = $this->session->data['currency'];
        $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
        $order_data['ip']             = $this->request->server['REMOTE_ADDR'];
        
        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
        } else {
            $order_data['forwarded_ip'] = '';
        }
        
        if (isset($this->request->server['HTTP_USER_AGENT'])) {
            $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
        } else {
            $order_data['user_agent'] = '';
        }
        
        if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
            $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $order_data['accept_language'] = '';
        }
        
        $this->load->model('checkout/order');
        
        $this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);
    }
    
    public function leftHTML()
    {
        
        $this->load->language('payment/hips_checkout');
        
        $this->load->model('payment/hips_checkout');
        $this->load->model('localisation/country');
        
        $this->load->language('common/cart');
        
        
        $data['heading_title'] = $this->language->get('text_review_title');
        
        if ($this->config->get('config_cart_weight')) {
            $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
        } else {
            $data['weight'] = '';
        }
        
        $this->load->model('tool/image');
        $this->load->model('tool/upload');
        
        $data['products'] = array();
        
        $products = $this->cart->getProducts();
        
        foreach ($products as $product) {
            $product_total = 0;
            
            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }
            
            if ($product['minimum'] > $product_total) {
                $data['error_warning'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
            }
            
            if (version_compare(VERSION, '2.2.0.0', '>=')) {
                
                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], $this->config->get($this->config->get('config_theme') . '_image_cart_width'), $this->config->get($this->config->get('config_theme') . '_image_cart_height'));
                } else {
                    $image = '';
                }
            } else {
                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_cart_width'), $this->config->get('config_image_cart_height'));
                } else {
                    $image = '';
                }
            }
            
            $option_data = array();
            
            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
                    
                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }
                
                $option_data[] = array(
                    'name' => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }
            
            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
                
                $price = $this->currency->format($unit_price, $this->session->data['currency']);
                $total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
            } else {
                $price = false;
                $total = false;
            }
            
            $recurring = '';
            
            if ($product['recurring']) {
                $frequencies = array(
                    'day' => $this->language->get('text_day'),
                    'week' => $this->language->get('text_week'),
                    'semi_month' => $this->language->get('text_semi_month'),
                    'month' => $this->language->get('text_month'),
                    'year' => $this->language->get('text_year')
                );
                
                if ($product['recurring']['trial']) {
                    $recurring = sprintf($this->language->get('text_trial_description'), $this->currency->format($this->tax->calculate($product['recurring']['trial_price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['trial_cycle'], $frequencies[$product['recurring']['trial_frequency']], $product['recurring']['trial_duration']) . ' ';
                }
                
                if ($product['recurring']['duration']) {
                    $recurring .= sprintf($this->language->get('text_payment_description'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                } else {
                    $recurring .= sprintf($this->language->get('text_payment_cancel'), $this->currency->format($this->tax->calculate($product['recurring']['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']), $product['recurring']['cycle'], $frequencies[$product['recurring']['frequency']], $product['recurring']['duration']);
                }
            }
            if (version_compare(VERSION, '2.1.0.0', '>=')) {
                $data['products'][] = array(
                    'cart_id' => $product['cart_id'],
                    'thumb' => $image,
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'option' => $option_data,
                    'recurring' => $recurring,
                    'quantity' => $product['quantity'],
                    'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                    'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                    'price' => $price,
                    'total' => $total,
                    'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                );
                
            } else {
                $data['products'][] = array(
                    'cart_id' => $product['key'],
                    'thumb' => $image,
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'option' => $option_data,
                    'recurring' => $recurring,
                    'quantity' => $product['quantity'],
                    'stock' => $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')),
                    'reward' => ($product['reward'] ? sprintf($this->language->get('text_points'), $product['reward']) : ''),
                    'price' => $price,
                    'total' => $total,
                    'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
                );
            }
        }
        
        // Gift Voucher
        $data['vouchers'] = array();
        
        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $key => $voucher) {
                $data['vouchers'][] = array(
                    'key' => $key,
                    'description' => $voucher['description'],
                    'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency']),
                    'remove' => $this->url->link('checkout/cart', 'remove=' . $key)
                );
            }
        }
        
        // Totals
        
        $this->load->model('extension/extension');
        
        if (version_compare(VERSION, '2.2.0.0', '>=')) {
            
            $totals = array();
            $taxes  = $this->cart->getTaxes();
            $total  = 0;
            
            
            
            // Because __call can not keep var references so we put them into an array.            
            $total_data = array(
                'totals' => &$totals,
                'taxes' => &$taxes,
                'total' => &$total
            );
            
            // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $sort_order = array();
                
                $results = $this->model_extension_extension->getExtensions('total');
                
                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }
                
                array_multisort($sort_order, SORT_ASC, $results);
                
                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('total/' . $result['code']);
                        
                        // We have to put the totals in an array so that they pass by reference.
                        $this->{'model_total_' . $result['code']}->getTotal($total_data);
                    }
                }
                
                $sort_order = array();
                
                foreach ($totals as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }
                
                array_multisort($sort_order, SORT_ASC, $totals);
            }
            
            $data['totals'] = array();
            
            
            
            foreach ($totals as $total) {
                
                
                
                $data['totals'][] = array(
                    'title' => $total['title'],
                    'text' => $this->currency->format($total['value'], $this->session->data['currency'])
                );
            }
            
            
            $this->response->setOutput($this->load->view('payment/hips_checkout_left.tpl', $data));
        }
        
        else {
            $total_data =& $total_data;
            $taxes =& $taxes;
            $total =& $total;
            
            
            $total_data = array();
            $total      = 0;
            $taxes      = $this->cart->getTaxes();
            
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $sort_order = array();
                
                $results = $this->model_extension_extension->getExtensions('total');
                
                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }
                
                array_multisort($sort_order, SORT_ASC, $results);
                
                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('total/' . $result['code']);
                        
                        $this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
                    }
                }
                
                
                $sort_order = array();
                
                foreach ($total_data as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }
                
                array_multisort($sort_order, SORT_ASC, $total_data);
            }
            
            $data['total_data'] = array();
            
            foreach ($total_data as $total) {
                $data['total_data'][] = array(
                    'title' => $total['title'],
                    'text' => $this->currency->format($total['value'], $this->session->data['currency'])
                );
            }
            
            
            return $this->load->view('default/template/payment/hips_checkout_left.tpl', $data);
        }
    }
    
    private function hipsOrderData()
    {
        $this->load->language('payment/hips_checkout');
        
        $this->load->model('payment/hips_checkout');
        $this->load->model('localisation/country');
        
        $currency_code  = $this->session->data['currency'];
        $currency_value = $this->currency->getValue($this->session->data['currency']);
        
        
        $hips_order_data['order_id'] = $this->session->data['order_id'];
        
        $hips_order_data['purchase_currency'] = $currency_code;
        $hips_order_data['user_session_id']   = session_id();
        
        // Shipping
        if ($this->config->get('hips_mode_bar') == 'no') {
            $hips_order_data['checkout_settings']['extended_cart'] = false;
            $unset_shipping_method                                 = true;
            if (isset($this->session->data['shipping_method']) && isset($this->session->data['shipping_methods'])) {
                foreach ($this->session->data['shipping_methods'] as $shipping_method) {
                    if ($shipping_method['quote']) {
                        foreach ($shipping_method['quote'] as $quote) {
                            if ($quote == $this->session->data['shipping_method']) {
                                $unset_shipping_method = false;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            if ($unset_shipping_method) {
                unset($this->session->data['shipping_method']);
            }
            
            if ((!isset($this->session->data['shipping_method']) || empty($this->session->data['shipping_method'])) && (isset($this->session->data['shipping_methods']) && !empty($this->session->data['shipping_methods']))) {
                $this->session->data['shipping_method'] = $this->model_payment_hips_checkout->getDefaultShippingMethod($this->session->data['shipping_methods']);
            }
            
            
            if ($this->cart->hasShipping() && isset($this->session->data['shipping_method']) && !empty($this->session->data['shipping_method'])) {
                $total_amount = $this->currency->format($this->tax->calculate($this->session->data['shipping_method']['cost'], $this->session->data['shipping_method']['tax_class_id'], $include_taxes), $currency_code, $currency_value, false) * 100;
                
                if ($include_taxes) {
                    $total_tax_amount = $this->currency->format($this->tax->getTax($this->session->data['shipping_method']['cost'], $this->session->data['shipping_method']['tax_class_id']), $currency_code, $currency_value, false) * 100;
                } else {
                    $total_tax_amount = 0;
                }
                
                $hips_order_data['cart']['items'][] = array(
                    'type' => 'shipping_fee',
                    'sku' => '1',
                    'name' => $this->session->data['shipping_method']['title'],
                    'quantity' => '1',
                    'unit_price' => round($this->currency->format($this->tax->calculate($this->session->data['shipping_method']['cost'], $this->session->data['shipping_method']['tax_class_id'], $include_taxes), $currency_code, $currency_value, false) * 100),
                    'discount_rate' => 0,
                    'vat_amount' => $total_tax_amount
                );
            }
        }
        // Order Total
        
        if (version_compare(VERSION, '2.2.0.0', '>=')) {
            list($totals, $taxes, $total) = $this->model_payment_hips_checkout->getTotals();
        } else {
            list($total_data, $total, $taxes) = $this->model_payment_hips_checkout->getTotals();
        }
        
        $merchant_urls = array(
            'user_return_url_on_success' => html_entity_decode($this->url->link('checkout/success')),
            'user_return_url_on_fail' => html_entity_decode($this->url->link('payment/hips_checkout/fail', 'hips_order_id={checkout.order.id}', true)),
            'webhook_url' => html_entity_decode($this->url->link('payment/hips_checkout/callback', 'hips_order_id={checkout.order.id}', true))
        );
        
        if ($this->config->get('hips_checkout_terms')) {
            $merchant_urls['terms'] = html_entity_decode($this->url->link('information/information', 'information_id=' . $this->config->get('hips_checkout_terms'), true));
        }
        
        
        // Callback data to be used to spoof/simulate customer to accurately calculate shipping
        
        $encrypted_order_data = $this->encryption->encrypt(json_encode(array(
            'session_id' => session_id(),
            'session_key' => $this->session->getId(),
            'customer_id' => $this->customer->getId(),
            'order_id' => $this->session->data['order_id'],
            'private_key' => $this->config->get('hips_key')
        )));
        
        
        $average_product_tax_rate = array();
        $include_taxes            = true;
        
        $discount    = 0;
        $git_voucher = 0;
        
        $count_product = count($this->cart->getProducts());
        
        // Products (Add these last because we send encrypted session order_id)
        foreach ($this->cart->getProducts() as $product) {
         
            $unit_price = $product['price'];
            
            //Coupon
            if (isset($this->session->data['coupon'])) {
                $coupon_info = $this->model_total_coupon->getCoupon($this->session->data['coupon']);
                
                if ($coupon_info['type'] == 'F') {
                    $discount   = $coupon_info['discount'] / $count_product;
                    $unit_price = ($unit_price * $product['quantity'] - $discount) / $product['quantity'];
                } elseif ($coupon_info['type'] == 'P') {
                    $discount   = $unit_price / 100 * $coupon_info['discount'];
                    $unit_price = $unit_price - $discount;
                }
            }
            
            //Gift Voucher
            if (isset($this->session->data['voucher'])) {
                $voucher_info = $this->model_total_voucher->getVoucher($this->session->data['voucher']);
                $git_voucher  = $voucher_info['amount'] / $count_product;
            }
            
            //Calculate product tax
            $product_tax = $this->tax->getTax($unit_price, $product['tax_class_id']);
            
            //get unit price
            $product_final_price = (($unit_price + $product_tax) * $product['quantity'] - $git_voucher) / $product['quantity'];
            
            //create order        
            
            $hips_order_data['cart']['items'][] = array(
                'type' => ($product['shipping'] ? 'physical' : 'digital'),
                'sku' => $product['model'],
                'name' => $product['name'],
                'quantity' => $product['quantity'],
                'unit_price' => round($this->currency->format($product_final_price, $currency_code, $currency_value, false) * 100),
                'discount_rate' => 0,
                "vat_amount" => round($this->currency->format($product_tax, $currency_code, $currency_value, false) * 100)
                
            );
        }
        
        if ($this->config->get('hips_mode_bar') == 'yes') {
            $hips_order_data['checkout_settings']['extended_cart'] = true;
        }
        
        $hips_order_data['require_shipping'] = true;
        $hips_order_data['express_shipping'] = true;
        $hips_order_data['fulfill']          = true;
        
        $hips_order_data['ecommerce_platform'] = 'Opencart' . VERSION;
        $hips_order_data['ecommerce_module']   = "Hips Opencart Module 0.1.0";
        
        $hips_order_data['hooks'] = $merchant_urls;
        
        return array(
            $hips_order_data,
            $encrypted_order_data
        );
    }
    
    public function send()
    {
        
        $this->load->model('payment/hips_checkout');
        $this->load->model('checkout/order');
        
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            
            /*Payment Process*/
            
            $cardDetail['source']            = 'card_token';
            $cardDetail['card_token']        = $this->request->post['tokenValue'];
            $cardDetail['purchase_currency'] = $order_info['currency_code'];
            $cardDetail['amount']            = number_format($order_info['total'], 2, '.', '') * 100;
            
            $userData['email']          = $order_info['email'];
            $userData['name']           = $order_info['firstname'] . ' ' . $order_info['lastname'];
            $userData['street_address'] = $order_info['payment_address_1'];
            $userData['postal_code']    = $order_info['payment_postcode'];
            $userData['country']        = $order_info['payment_iso_code_2'];
            $userData['ip_address']     = $_SERVER['REMOTE_ADDR'];
            $cardDetail['customer']     = (object) $userData;
            
            /*Get payment Response*/
            $jresult = $this->model_payment_hips_checkout->paymentInfo($cardDetail, $this->config->get('hips_key'));
            
            $message = '';
            
            /*Set Values of response in order history*/
            if (isset($jresult['status']) && $jresult['status'] != "") {
                
                if (isset($jresult['id'])) {
                    $message .= 'id: ' . $jresult['id'] . "\n";
                }
                
                if (isset($jresult['data']['authCode'])) {
                    $message .= 'object: ' . $jresult['object'] . "\n";
                }
                
                if (isset($jresult['data']['referenceNumber'])) {
                    $message .= 'source: ' . $jresult['source'] . "\n";
                }
                
                if (isset($jresult['data']['originalFullAmount'])) {
                    $message .= 'status: ' . $jresult['status'] . "\n";
                }
                
                if (isset($jresult['preflight']['requires_redirect'])) {
                    $message .= 'Requires Redirect: ' . $jresult['preflight']['requires_redirect'] . "\n";
                }
                
                if (isset($jresult['preflight']['redirect_user_to_url'])) {
                    $message .= 'Redirect User To Url: ' . $jresult['preflight']['redirect_user_to_url'] . "\n";
                }
                
                if (isset($jresult['preflight']['status'])) {
                    $message .= 'Preflight Status: ' . $jresult['preflight']['status'] . "\n";
                }
                
                if (isset($jresult['order']['id'])) {
                    $message .= 'Order ID: ' . $jresult['order']['id'] . "\n";
                }
                
                if (isset($jresult['amount'])) {
                    $message .= 'Amount: ' . number_format(($jresult['amount'] / 100), 2, '.', '') . "\n";
                    
                }
                
                if (isset($jresult['amount_currency'])) {
                    $message .= 'Amount Currency: ' . $jresult['amount_currency'] . "\n";
                }
                
                if (isset($jresult['settlement_amount'])) {
                    $message .= 'Settlement Amount: ' . number_format(($jresult['settlement_amount'] / 100), 2, '.', '') . "\n";
                }
                
                if (isset($jresult['settlement_amount_currency'])) {
                    $message .= 'Settlement Amount Currency: ' . $jresult['settlement_amount_currency'] . "\n";
                }
                
                if (isset($jresult['settlement_exchange_rate'])) {
                    $message .= 'Settlement Exchange Rate: ' . $jresult['settlement_exchange_rate'] . "\n";
                }
                
                if (isset($jresult['authorization_code'])) {
                    $message .= 'Authorization Code: ' . $jresult['authorization_code'] . "\n";
                }
                
                if (isset($jresult['decline_reason'])) {
                    $message .= 'Decline Reason: ' . $jresult['decline_reason'] . "\n";
                }
                
                /*add details of response in order history table and redirect to success page*/
                
                
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('hips_order_status_id'), $message, false);
                
                $json['success'] = $this->url->link('checkout/success');
            }
            
            else {
                $json['error'] = $jresult['error']['message'];
            }
            
            /*add response to Ajax function*/
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }
    
	public function verify_token($token)
	{

		$verify = 0;
		$separator = '.';

		if (2 !== substr_count($token, $separator)) {
			//throw new Exception("Incorrect access token format");
			$verify = 0;
		}

		list($header, $payload, $signature) = explode($separator, $token);

		$decoded_signature = base64_decode(str_replace(array('-', '_'), array('+', '/'), $signature));

		// The header and payload are signed together
		$payload_to_verify = utf8_decode($header . $separator . $payload);

		// however you want to load your public key
		$public_key = file_get_contents('https://static.hips.com/hips_key.pub');

		// default is SHA256
		$verified = openssl_verify($payload_to_verify, $decoded_signature, $public_key, OPENSSL_ALGO_SHA256);

		if ($verified == 1) {
			//throw new Exception("Cannot verify signature");
			$verify = 1;
		}
		
		return $verify;
		
	}
   
} 
