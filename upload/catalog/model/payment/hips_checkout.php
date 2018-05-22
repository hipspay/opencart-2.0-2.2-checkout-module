<?php

class ModelPaymentHipsCheckout extends Model
{
    public function orderCreate($api, $order)
    {
        
        $json = json_encode($order);
        
        $ch = curl_init('https://api.hips.com/v1/orders');
        curl_setopt($ch, CURLOPT_USERPWD, $api . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ));
        $response = curl_exec($ch);
        
        
        if (!$response) {
            $this->log('HIPS_CHECKOUT :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
        }
        
        return json_decode($response);
        
        // Close handle
        curl_close($ch);
        
    }
    
    public function orderRetrieve($api, $order_id)
    {
        
        $ch = curl_init('https://api.hips.com/v1/orders/' . $order_id);
        curl_setopt($ch, CURLOPT_USERPWD, $api . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        $response = curl_exec($ch);
        
        if (!$response) {
            $this->log('HIPS_CHECKOUT :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
        }
        
        return json_decode($response);
        
        // Close handle
        curl_close($ch);
    }
    
    
    public function orderUpdate($api, $order, $order_id)
    {
        
        $json = json_encode($order);
        
        $ch = curl_init('https://api.hips.com/v1/orders/' . $order_id);
        curl_setopt($ch, CURLOPT_USERPWD, $api . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ));
        $response = curl_exec($ch);
        
        if (!$response) {
            $this->log('HIPS_CHECKOUT :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
        }
        
        return json_decode($response);
        
        // Close handle
        curl_close($ch);
        
    }
    
    public function paymentInfo($jsonData, $api)
    {
        $json = json_encode($jsonData);
        $ch   = curl_init('https://api.hips.com/v1/payments');
        curl_setopt($ch, CURLOPT_USERPWD, $api . ":");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ));
        $response = curl_exec($ch);
        
        if (!$response) {
            $this->log('HIPS_CHECKOUT :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
        }
        
        return json_decode($response, TRUE);
        
        // Close handle
        curl_close($ch);
    }
    
    public function getPaymentInfo($token, $api)
    {
        $ch = curl_init('https://api.hips.com/v1/payments/' . $token);
        curl_setopt($ch, CURLOPT_USERPWD, $api . ":");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        
        $response = curl_exec($ch);
        
        if (!$response) {
            $this->log('HIPS_CHECKOUT :: CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
        }
        
        return json_decode($response, TRUE);
        
        // Close handle
        curl_close($ch);
    }
    
    public function getOrder($order_ref)
    {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "hips_checkout_order` WHERE `order_ref` = '" . $this->db->escape($order_ref) . "' LIMIT 1")->row;
    }
    
    public function getOrderByOrderId($order_id)
    {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "hips_checkout_order` WHERE `order_id` = '" . (int) $order_id . "' LIMIT 1")->row;
    }
    
    public function addOrder($order_id, $order_ref, $data, $response)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "hips_checkout_order` SET `order_id` = '" . (int) $order_id . "', `order_ref` = '" . $this->db->escape($order_ref) . "', `data` = '" . $this->db->escape($data) . "', `response` = '" . $this->db->escape($response) . "'");
    }
    
    public function updateOrder($order_id, $order_ref, $data, $response)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "hips_checkout_order` SET `data` = '" . $this->db->escape($data) . "', `response` = '" . $this->db->escape($response) . "' WHERE  `order_id` = '" . (int) $order_id . "'");
    }
    
    public function updateOcOrder($order_id, $data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `firstname` = '" . $this->db->escape($data['firstname']) . "', `lastname` = '" . $this->db->escape($data['lastname']) . "',  `email` = '" . $this->db->escape($data['email']) . "', `telephone` = '" . $this->db->escape($data['telephone']) . "', `payment_firstname` = '" . $this->db->escape($data['payment_firstname']) . "', `payment_lastname` = '" . $this->db->escape($data['payment_lastname']) . "', `payment_address_1` = '" . $this->db->escape($data['payment_address_1']) . "', `payment_address_2` = '" . $this->db->escape($data['payment_address_2']) . "', `payment_city` = '" . $this->db->escape($data['payment_city']) . "', `payment_postcode` = '" . $this->db->escape($data['payment_postcode']) . "', `payment_country` = '" . $this->db->escape($data['payment_country']) . "', `payment_country_id` = '" . (int) $data['payment_country_id'] . "', `payment_address_format` = '" . $this->db->escape($data['payment_address_format']) . "', `shipping_firstname` = '" . $this->db->escape($data['shipping_firstname']) . "', `shipping_lastname` = '" . $this->db->escape($data['shipping_lastname']) . "', `shipping_address_1` = '" . $this->db->escape($data['shipping_address_1']) . "', `shipping_address_2` = '" . $this->db->escape($data['shipping_address_2']) . "', `shipping_city` = '" . $this->db->escape($data['shipping_city']) . "', `shipping_postcode` = '" . $this->db->escape($data['shipping_postcode']) . "',  `shipping_country` = '" . $this->db->escape($data['shipping_country']) . "', `shipping_country_id` = '" . (int) $data['shipping_country_id'] . "', `shipping_address_format` = '" . $this->db->escape($data['shipping_address_format']) . "' WHERE `order_id` = '" . (int) $order_id . "'");
    }
    
    public function checkForPaymentTaxes($products = array())
    {
        foreach ($products as $product) {
            $query = $this->db->query("SELECT COUNT(*) AS `total` FROM " . DB_PREFIX . "tax_rule WHERE `based` = 'payment' AND `tax_class_id` = '" . (int) $product['tax_class_id'] . "'");
            
            if ($query->row['total']) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getDefaultShippingMethod($shipping_methods)
    {
        $first_shipping_method = reset($shipping_methods);
        
        if ($first_shipping_method && isset($first_shipping_method['quote']) && !empty($first_shipping_method['quote'])) {
            $first_shipping_method_quote = reset($first_shipping_method['quote']);
            
            if ($first_shipping_method_quote) {
                $shipping = explode('.', $first_shipping_method_quote['code']);
                
                return $shipping_methods[$shipping[0]]['quote'][$shipping[1]];
            }
        }
        
        return array();
    }
    
    public function log($message)
    {
        if ($this->config->get('hips_checkout.log_debug')) {
            $log = new Log('hips_checkout.log');
            $log->write($message);
        }
    }
    
    
    
    public function getTotals()
    {
        
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
           
            $this->load->model('extension/extension');
            
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
            
            return array(
                $totals,
                $taxes,
                $total
            );
        } else {
            $total_data =& $total_data;
            $taxes =& $taxes;
            $total =& $total;
            
            
            $total_data = array();
            $total      = 0;
            $taxes      = $this->cart->getTaxes();
            
            
            $this->load->model('extension/extension');
            
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
            
            return array(
                $total_data,
                $total,
                $taxes
            );
        }
    }
    
    public function getMethod($address, $total)
    {
        $this->load->language('payment/hips_checkout');
        
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('hips_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");
        
        if ($this->config->get('hips_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('hips_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }
        
        $currencies = array(
            'AUD',
            'CAD',
            'EUR',
            'GBP',
            'JPY',
            'USD',
            'NZD',
            'CHF',
            'HKD',
            'SGD',
            'SEK',
            'DKK',
            'PLN',
            'NOK',
            'HUF',
            'CZK',
            'ILS',
            'MXN',
            'MYR',
            'BRL',
            'PHP',
            'TWD',
            'THB',
            'TRY',
            'RUB'
        );
        
        if (!in_array(strtoupper($this->session->data['currency']), $currencies)) {
            $status = false;
        }
        
        $method_data = array();
        
        if ($this->config->get('hips_payment_type') == 'full') {
            $status = false;
        }
        
        if ($status) {
            $method_data = array(
                'code' => 'hips_checkout',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('hips_checkout_sort_order')
            );
        }
        
        return $method_data;
    }
    
    public function getCountryByIsoCode2($iso_code_2)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE `iso_code_2` = '" . $this->db->escape($iso_code_2) . "' AND `status` = '1'");
        
        return $query->row;
    }
    
    
    public function getZoneByCode($code, $country_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE (`code` = '" . $this->db->escape($code) . "' OR `name` = '" . $this->db->escape($code) . "') AND `country_id` = '" . (int) $country_id . "' AND `status` = '1'");
        
        return $query->row;
    }
    
    public function addShipping($hips_checkout_order, $order)
    {
        
        $shipping_name = $order->shipping->name;
        $shipping_fee  = ($order->shipping->fee) / 100;
        
        $query1 = $this->db->query("SELECT order_id, code, title, value from " . DB_PREFIX . "order_total WHERE order_id ='" . $hips_checkout_order . "'");
        
        $existingshipping = 0;
        
        foreach ($query1->rows as $row) {
            
            
            if ($row['code'] == 'shipping') {
                $existingshipping = $row['value'];
                
            }
            
            if ($row['code'] == 'total') {
                $total = $row['value'] + $shipping_fee - $existingshipping;
                
                $this->db->query("UPDATE " . DB_PREFIX . "order_total SET value = '" . $total . "' WHERE order_id ='" . $hips_checkout_order . "' and code='total'");
                
                $this->db->query("UPDATE " . DB_PREFIX . "order SET total = '" . $total . "' WHERE order_id ='" . $hips_checkout_order . "'");
                
            }
        }
        
        $query2 = $this->db->query("SELECT * from " . DB_PREFIX . "order_total WHERE order_id ='" . $hips_checkout_order . "' and code='shipping'");
        
        
        if ($query2->num_rows > 0) {
            
            $query = $this->db->query("UPDATE " . DB_PREFIX . "order_total SET title = '" . $shipping_name . "', value = '" . $shipping_fee . "', sort_order = '3' 
                    WHERE order_id ='" . $hips_checkout_order . "' and code='shipping'");
            
        }
        
        else {
            $query = $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . $hips_checkout_order . "', code = 'shipping', title = '" . $shipping_name . "', value = '" . $shipping_fee . "', sort_order = '3'");
        }
    }
} 