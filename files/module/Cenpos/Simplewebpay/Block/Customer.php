<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Cenpos
 * @package     Cenpos_Simplewebpay
 * @copyright   Copyright (c) 2011 Cenpos Medien GmbH & Co. KG (http://www.phoenix-medien.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Cenpos_Simplewebpay_Block_Customer extends Mage_Customer_Block_Account_Dashboard
{

    /**
     * Available locales for content URL generation
     *
     * @var array
     */
    protected $_supportedInfoLocales = array('de');

    /**
     * Default locale for content URL generation
     *
     * @var string
     */
    protected $_defaultInfoLocale = 'en';

    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
    }


    public function _getUrlViewProcess()
    {
        return Mage::getStoreConfig("payment/simplewebpay_acc/urlviewprocess"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    public function _getCvvOption()
    {
        return ((Mage::getStoreConfig("payment/simplewebpay_acc/iscvv") == "1") ? "true" : "false"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    public function _getUrlSession()
    {
        return Mage::getUrl('simplewebpay/customer/session');
    }
  
    protected function _getHelper()
    {
        return Mage::helper('simplewebpay');
    }

    public function _getSessionSwp()
    {
        try{
            $checkout = Mage::getSingleton('checkout/session')->getQuote();
            $helper = $this->_getHelper();
         
            $Request = new stdClass();
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $Request->CustomerCode = $customer->getId();
                $customerData = $customer->getData(); 
                $addressData = $customer->getDefaultShippingAddress()->getData();
                $Street = $addressData["street"];
                if (strpos($Street, "\n") !== FALSE) {
                    $Street = str_replace("\n", " ", $Street);
                }
                if(isset($customerData["email"]) && !empty($customerData["email"])) $Request->Email = $customerData["email"];
                if(isset($Street) && !empty($Street)) $Request->CustomerBillingAddress = urlencode($Street);
                if(isset($addressData["postcode"]) && !empty($addressData["postcode"])) $Request->CustomerZipCode = $addressData["postcode"];
            }else throw new Exception("Access Denied!!! You dont have permission to access..");
            
           
            $Response = $helper->getSecretRequest($Request, true);
            
            return json_encode($Response);
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            $Response = new stdClass();
            $Response->Message = $e->getMessage();
            $Response->Result = -1;
            return json_encode($Response);
        }
    }
}
