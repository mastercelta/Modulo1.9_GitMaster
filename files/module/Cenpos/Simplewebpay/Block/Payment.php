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
class Cenpos_Simplewebpay_Block_Payment extends Mage_Core_Block_Template {

    /**
     * Return Payment logo src
     *
     * @return string
     */
    public function __construct() {
        
    }

    public function getSimplewebpayLogoSrc() {
        $locale = Mage::getModel('simplewebpay/acc')->getLocale();
        $logoFilename = Mage::getDesign()
                ->getFilename('images' . DS . 'simplewebpay' . DS . 'banner_120_' . $locale . '.gif', array('_type' => 'skin'));

        if (file_exists($logoFilename)) {
            return $this->getSkinUrl('images/simplewebpay/banner_120_' . $locale . '.gif');
        }

        return $this->getSkinUrl('images/simplewebpay/banner_120_int.gif');
    }

   

    public function getData3D() {
        $session = Mage::getSingleton('checkout/session');
        try {
            $order = Mage::getModel('sales/order');

            $order->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
            }

            $data = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addAttributeToFilter('order_id', array('eq' => $order->getEntityId()))
                    ->addAttributeToFilter('txn_type', array('eq' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE));

            $data = json_decode(json_encode($data->toArray()), FALSE);

            if ($data->totalRecords > 0) {
                $is3dSecure = false;
                $transaction = null;
                foreach ($data->items as $key => $item) {
                    if ($item->is_closed == 0) {
                        $is3dSecure = true;
                        $transaction = $item;
                    }
                }

                if($transaction->additional_information === false) Mage::throwException('Transaction Details not found');
                if($transaction->additional_information->raw_details_info === false) Mage::throwException('Transaction Details not found Raw ');
                
                $ResponsRaw = $transaction->additional_information->raw_details_info;
                $ResponsRaw->UrlReturnCardinal = Mage::getUrl('simplewebpay/processing/response3dsecure');
                echo json_encode($ResponsRaw);
            } else
                Mage::throwException('No transaction found 2');
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            parent::_redirect('checkout/cart');
        }
    }

    public function getFormAction() {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getUrl();
    }

}
