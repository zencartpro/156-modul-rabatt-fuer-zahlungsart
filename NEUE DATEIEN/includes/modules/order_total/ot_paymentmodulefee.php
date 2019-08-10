<?php
/**
 * ot_total order-total module
 *
 * @package orderTotal
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @modified for Zen-Cart German and PHP 7.3  2019-08-10 22:38:17Z webchills $
 */
  class ot_paymentmodulefee {
    public $title, $output;

    public function __construct() {
      $this->code = 'ot_paymentmodulefee';
      $this->title = MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_TITLE;
      $this->description = MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_DESCRIPTION;
      $this->sort_order = defined('MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_SORT_ORDER') ? MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_SORT_ORDER : null;
      if (null === $this->sort_order) return false;
      $this->payment_modules = explode(',', MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_PAYMENT_MODULES); 
      $this->output = array();
    }

    public function process() {
      global $order, $currencies;

      if (MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_FEE_ALLOW == 'true') {
        switch (MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_DESTINATION) {
          case 'national':
            if ($order->delivery['country_id'] == STORE_COUNTRY) {
                $pass = true;
            }
              break;
          case 'international':
            if ($order->delivery['country_id'] != STORE_COUNTRY) $pass = true; break;
          case 'both':
            $pass = true; break;
          default:
            $pass = false; break;
        }

        if (($pass == true) && in_array($_SESSION['payment'], $this->payment_modules)) {
          $charge_it = 'true';
          if ($charge_it == 'true') {
            $tax_address = zen_get_tax_locations();
            $tax = zen_get_tax_rate(MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_TAX_CLASS, $tax_address['country_id'], $tax_address['zone_id']);
            $tax_description = zen_get_tax_description(MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_TAX_CLASS, $tax_address['country_id'], $tax_address['zone_id']);
            $key = array_search($_SESSION['payment'], $this->payment_modules);
            $this->payment_fees = explode(',', MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_FEE);
            $this->payment_fee = $this->payment_fees[$key]; 
// calculate from flat fee or percentage
            if (substr($this->payment_fee, -1) == '%') {
              $payment_module_fee = ($order->info['subtotal'] * ($this->payment_fee/100));
            } else {
              $payment_module_fee = $this->payment_fee;
            }


            $order->info['tax'] += zen_calculate_tax($payment_module_fee, $tax);
            $order->info['tax_groups']["$tax_description"] += zen_calculate_tax($payment_module_fee, $tax);
            $order->info['total'] += $payment_module_fee + zen_calculate_tax($payment_module_fee, $tax);
            if (DISPLAY_PRICE_WITH_TAX == 'true') {
              $payment_module_fee += zen_calculate_tax($payment_module_fee, $tax);
            }

            $this->output[] = array('title' => $this->title . ':',
                                    'text' => $currencies->format($payment_module_fee, true, $order->info['currency'], $order->info['currency_value']),
                                    'value' => $payment_module_fee);
          }
        }
      }
    }

    public function check() {
	  global $db;
      if (!isset($this->_check)) {
        $check_query = 'select configuration_value
                        from ' . TABLE_CONFIGURATION . "
                        where configuration_key = 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_STATUS'";

        $check_query = $db->Execute($check_query);
        $this->_check = $check_query->RecordCount();
      }

      return $this->_check;
    }

    public function keys() {
      return array('MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_STATUS', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_SORT_ORDER', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_FEE', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_FEE_ALLOW', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_PAYMENT_MODULES', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_DESTINATION', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_TAX_CLASS');
    }

    public function install() {
      global $db;
      $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('This module is installed', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_STATUS', 'true', '', '6', '1','zen_cfg_select_option(array(\'true\'), ', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_SORT_ORDER', '500', 'Sort order of display.', '6', '2', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Allow Payment Module Fee', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_FEE_ALLOW', 'false', 'Do you want to allow payment module fees?', '6', '3', 'zen_cfg_select_option(array(\'true\', \'false\'), ', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Payment Modules', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_PAYMENT_MODULES', 'eustandardtransfer', 'Enter the payment module codes separate by commas (no spaces)', '6', '4', '', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values ('Fee', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_FEE', '-3%', 'For Percentage Calculation - include a % Example: -10%<br />For a flat amount just enter the amount - Example: -5 for $5.00', '6', '5', '', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Attach Payment Module Fee On Orders Made', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_DESTINATION', 'both', 'Attach payment module fee for orders sent to the set destination.', '6', '6', 'zen_cfg_select_option(array(\'national\', \'international\', \'both\'), ', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_TAX_CLASS', '0', 'Use the following tax class on the payment module fee.', '6', '7', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");
      // www.zen-cart-pro.at languages_id==43 START
      $db->Execute('insert into ' . TABLE_CONFIGURATION_LANGUAGE   . " (configuration_title, configuration_key, configuration_language_id, configuration_description, date_added) values ('Dieses Modul ist installiert.', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_STATUS', '43', 'true', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION_LANGUAGE   . " (configuration_title, configuration_key, configuration_language_id, configuration_description, date_added) values ('Sortierreihenfolge', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_SORT_ORDER', '43', 'Voreinstellung: 500', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION_LANGUAGE   . " (configuration_title, configuration_key, configuration_language_id, configuration_description, date_added) values ('Wollen Sie das Modul aktivieren?', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_FEE_ALLOW', '43', 'Um Rabatt für eine Zahlungsart zu aktivieren, müssen Sie hier auf true stellen.<br/><b>Bitte beachten Sie, dass seit Mitte Januar 2018 ausschließlich Rabatte für Zahlungsarten erlaubt sind und keine Zuschläge!</b>', now())");
       $db->Execute('insert into ' . TABLE_CONFIGURATION_LANGUAGE   . " (configuration_title, configuration_key, configuration_language_id, configuration_description, date_added) values ('Zahlungsarten für Rabatt', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_PAYMENT_MODULES', '43', 'Tragen Sie hier die Zahlungsmodule ein mit Komma getrennt und ohne Leerzeichen.', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION_LANGUAGE   . " (configuration_title, configuration_key, configuration_language_id, configuration_description, date_added) values ('Rabatt', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_FEE', '43', 'Für einen prozentualen Rabatt fügen Sie ein % hinzu.<br/>Einen Rabatt kennzeichnen Sie mit einem Minuszeichen davor.<br/>Für einen Fixbetrag tragen Sie einfach den Betrag ein.<br/>Beispiele:<br/>Rabatt von 3%: -3%<br/>Rabatt von 10 Euro: -10', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION_LANGUAGE   . " (configuration_title, configuration_key, configuration_language_id, configuration_description, date_added) values ('Für welche Destinationen soll der Rabatt gelten?', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_DESTINATION', '43', 'National, International oder für beide?', now())");
      $db->Execute('insert into ' . TABLE_CONFIGURATION_LANGUAGE   . " (configuration_title, configuration_key, configuration_language_id, configuration_description, date_added) values ('Steuerklasse', 'MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_TAX_CLASS', '43', 'Folgende Steuerklasse auf den Rabatt anwenden', now())");
     	// www.zen-cart-pro.at languages_id==43  END
    }

    public function remove() {
	  global $db;
      $db->Execute('delete from ' . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
      // www.zen-cart-pro.at languages_id == delete all
      $db->Execute('delete from ' . TABLE_CONFIGURATION_LANGUAGE . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
  }