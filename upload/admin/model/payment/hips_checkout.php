<?php

class ModelPaymentHipsCheckout extends Model
{
    public function getOrder($order_id)
    {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "hips_checkout_order` WHERE `order_id` = '" . (int) $order_id . "' LIMIT 1")->row;
    }
    
    public function checkForPaymentTaxes()
    {
        $query = $this->db->query("SELECT COUNT(*) AS `total` FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "tax_rule tr ON (`tr`.`tax_class_id` = `p`.`tax_class_id`) WHERE `tr`.`based` = 'payment'");
        
        return $query->row['total'];
    }
    
    public function install()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "hips_checkout_order` (
              `hips_checkout_order_id` INT(11) NOT NULL AUTO_INCREMENT,
              `order_id` INT(11) NOT NULL,
              `order_ref` VARCHAR(255) NOT NULL,
              `data` text NOT NULL,
              `response` text NOT NULL,
              PRIMARY KEY (`hips_checkout_order_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }
    
    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "hips_checkout_order`;");
        $this->db->query("Update `" . DB_PREFIX . "setting` SET value='0' where `key` = 'hips_checkout_status'");
    }
}