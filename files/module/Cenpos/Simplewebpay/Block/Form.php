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
class Cenpos_Simplewebpay_Block_Form extends Mage_Payment_Block_Form {

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
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('simplewebpay/form.phtml');
    }

    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return info URL for eWallet payment
     *
     * @return string
     */
    protected function _getOrder() {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }

    public function getUrl() {
        return Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    public function GetParams() {
        
    }

    public function CreateSimpleWebpay() {
        require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/Model/ModelConnector.php');
        require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/CenposConnector.php');

        $checkout = Mage::getSingleton('checkout/session')->getQuote();

        $billAddress = $checkout->getBillingAddress();

        $customer = $billAddress->getData();

        $Street = $customer["street"];
        if (strpos($Street, "\n") !== FALSE) {
            $Street = str_replace("\n", " ", $Street);
        }

        
        CenposConnector::Init();

        $Cenpos = new CenposConnector();
        $Request = new TokenFViewRequest();
        $Request->Merchant = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
        $Request->Email = $customer["email"];
        $Request->IsCVV = ((Mage::getStoreConfig("payment/simplewebpay_acc/iscvv") == "1") ? "true" : "false");
        $Request->CustomerBillingAddress = urlencode($Street);

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $Request->CustomerCode = $customerData->getId();
        }

        
        $Request->OnlyForm = ((Mage::getStoreConfig("payment/simplewebpay_acc/onlyform") == "1" && !empty($Request->CustomerCode)) ? "false" : "true");
        $Request->CustomerZipCode = $customer["postcode"];
        $Request->SubmitAction = "ExecuteSimpleWebpay";
        $Request->CallbackJS = "ResponseSimpleWebpay";
        $Request->Width = "100%";
        $Request->Height = "400";
        $Request->SessionToken = "true";
        $Request->SecretKey = Mage::getStoreConfig("payment/simplewebpay_acc/secretkey");
        $Cenpos->TokenFView($Request,Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay"));
    }

}
