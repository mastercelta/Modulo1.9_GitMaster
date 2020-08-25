<?php
class Cenpos_SimpleWebpay_CustomerController extends   Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $this->getLayout()->getBlock('content')->append(
            $this->getLayout()->createBlock('customer_token')
        );
        $this->getLayout()->getBlock('head')->setTitle($this->__('Stored Credit Cards'));
        $this->renderLayout();
    }

    protected function _getHelper()
    {
        return Mage::helper('simplewebpay');
    }
}    