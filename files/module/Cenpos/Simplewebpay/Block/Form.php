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
class Cenpos_Simplewebpay_Block_Form extends Mage_Payment_Block_Form
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
        $this->setTemplate('simplewebpay/form.phtml');
    }

    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return info URL for eWallet payment
     *
     * @return string
     */
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }

    public function _getUrl()
    {
        return Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    public function _getCvvOption()
    {
        return ((Mage::getStoreConfig("payment/simplewebpay_acc/iscvv") == "1") ? "true" : "false"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    public function _getTokenOption()
    {
        return ((Mage::getStoreConfig("payment/simplewebpay_acc/onlyform") == "0") ? "true" : "false"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }
    public function _getTokenOption19()
    {
        return ((Mage::getStoreConfig("payment/simplewebpay_acc/onlyform") == "token19") ? "createtoken19" : ""); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
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
            $billAddress = $checkout->getBillingAddress();
            $customer = $billAddress->getData();

            $Street = $customer["street"];
            if (strpos($Street, "\n") !== FALSE) {
                $Street = str_replace("\n", " ", $Street);
            }
            $Request = new stdClass();
            $Request->Email = $customer["email"];
            $Request->CustomerBillingAddress = urlencode($Street);
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $Request->CustomerCode = $customerData->getId();
            }
            $Request->Type =  $this->_getTokenOption19();
            $Request->CustomerZipCode = $customer["postcode"];
            
           
            $Response = $helper->getSecretRequest($Request);
            
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
