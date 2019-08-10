<?php
/**
* @package Pages
* @copyright Copyright 2008-2009 RubikIntegration.com
* @copyright Copyright 2003-2019 Zen Cart Development Team
* @copyright Portions Copyright 2003 osCommerce
* @license http://www.zen-cart-pro.at/license/3_0.txt GNU Public License V3.0
* @version $Id: loader_ot_paymentmodulefee.php 6 2019-08-10 21:42:10Z webchills $
*/                                             
if (MODULE_ORDER_TOTAL_PAYMENTMODULEFEE_STATUS == 'true') {                                                            
  $loaders[] = array('conditions' => array('pages' => array('checkout', 'quick_checkout')),
										  'jscript_files' => array(
										  'jquery/jquery_ot_paymentmodulefee.js' => 1										
                      )
                    );  
}