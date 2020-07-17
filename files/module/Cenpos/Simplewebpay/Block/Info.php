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
class Cenpos_Simplewebpay_Block_Info extends Mage_Payment_Block_Info {

    /**
     * Constructor. Set template.
     */
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('simplewebpay/info.phtml');
    }

    /**
     * Returns code of payment method
     *
     * @return string
     */
    public function getMethodCode() {
        return $this->getInfo()->getMethodInstance()->getCode();
    }

    /**
     * Build PDF content of info block
     *
     * @return string
     */
    public function toPdf() {
        $this->setTemplate('simplewebpay/pdf/info.phtml');
        return $this->toHtml();
    }

    public function isToken() {
        $info = $this->getInfo();

        $response = $info->getWebpayistoken();

        $order = Mage::getSingleton('checkout/session')->getQuote();
        
        if ($order->getId()) {
            $method = $order->getCheckoutMethod();
            if($method === "guest") $response = "nosave";
        }

        return $response;
    }

    protected function _prepareSpecificInformation($transport = null) {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $info = $this->getInfo();
        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);
        $transport->addData(array(
//            Mage::helper('payment')->__('Token ID') => $info->getWebpayrecurringsaletokenid(),
            Mage::helper('payment')->__('Card Type') => $info->getWebpaycardtype(),
            Mage::helper('payment')->__('Card Number') => $info->getWebpayprotectedcardnumber(),
            Mage::helper('payment')->__('Expiration') => $info->getWebpaycardexpirationdate(),
        ));

        // print_r($info);

        return $transport;
    }

}
