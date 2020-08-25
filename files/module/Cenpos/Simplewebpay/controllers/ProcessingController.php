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
class Cenpos_Simplewebpay_ProcessingController extends Mage_Core_Controller_Front_Action {

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function paymentAction() {
        $session = $this->_getCheckout();
        try {
            $order = Mage::getModel('sales/order');

            $order->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
            }
            
            $Type = Mage::getStoreConfig("payment/simplewebpay_acc/payment_action");

            $data = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addAttributeToFilter('order_id', array('eq' => $order->getEntityId()))
                    ->addAttributeToFilter('txn_type', array('eq' => (($Type === "authorize") ? Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH : Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE)));
                  
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
                
                if ($is3dSecure === false) {
                    $this->_redirect('checkout/onepage/success');
                } else if ($transaction->additional_information->raw_details_info->Result === 0)
                    $this->_redirect('checkout/onepage/success');
                else{
                    $invoiceModel = Mage::getModel('sales/order_invoice');

                    $invoiceIncrementId = 0;
                    $invoiceCollection = $order->getInvoiceCollection();
                    foreach ($invoiceCollection as $invoice):
                        $invoiceIncrementId = $invoice->getIncrementId();
                    endforeach;

                    if($invoiceIncrementId > 0 ){
                        $invoice = $invoiceModel->loadByIncrementId($invoiceIncrementId);

                        $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN)->save();
                    }
                    $this->loadLayout();
                    $this->renderLayout();
                }
            } 
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function response3dsecureAction() {
        $PaRES = $_REQUEST["PaRes"];
        $MD = $_REQUEST["MD"];
        
        if($PaRES === false) Mage::throwException('The transaction PaRes not found');
        if($MD === false) Mage::throwException('The TransactionID not found');
        
        
        $session = $this->_getCheckout();
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

                if ($is3dSecure === false) {
                    $this->_redirect('checkout/onepage/success');
                } else if ($transaction->Result === 0)
                    $this->_redirect('checkout/onepage/success');


                $invoiceModel = Mage::getModel('sales/order_invoice');

                $invoiceIncrementId = 0;
                $invoiceCollection = $order->getInvoiceCollection();
                foreach ($invoiceCollection as $invoice):
                    $invoiceIncrementId = $invoice->getIncrementId();
                endforeach;

                $invoice = $invoiceModel->loadByIncrementId($invoiceIncrementId);

                $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_PAID)->save();
                
                $order->getPayment()->setTransactionId($transaction->ReferenceNumber);
                $order->getPayment()->setIsTransactionClosed(true);
                $order->getPayment()->save();
                $order->save();
                
                echo '<html><body>
                        <script type="text/javascript">parent.location.href="'.Mage::getUrl('checkout/onepage/success').'";</script>
                      </body></html>';
                
            } else
                Mage::throwException('No transaction found 3');
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            parent::_redirect('checkout/cart');
        }
    }
    protected function _getHelper()
    {
        return Mage::helper('simplewebpay');
    }
    public function saveAction() {

        $order = Mage::getSingleton('checkout/session')->getQuote();

        $isRegister = false;

        if ($order->getId()) {
            $method = $order->getCheckoutMethod();
            if ($method === "register")
                $isRegister = true;
        }

        if ($isRegister) {
            $genericObject = new stdClass();
            $genericObject->Message = "The card will be save after processing";
            $genericObject->Result = 102;
            echo json_encode($genericObject);
        } else {
            $email = "";

            $billing = $order->getBillingAddress();
            if ($billing->getEmail()) {
                $email = $billing->getEmail();
            } else {
                $email = $order->getCustomerEmail();
            }

            $payment = $order->getPayment();

            $helper = $this->_getHelper();
            
            $request = new stdClass();

            $request->TokenId = $payment->getWebpayrecurringsaletokenid();
            $request->Email =  $email;
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $request->CustomerCode = $customerData->getId();
            };

            $Response = $helper->getSecretRequest($request);
            
            if( $Response->Result != 0 )  echo json_encode($Response);
            else{
                $responseToken = $helper->sendActionApi("ConvertCrypto", $Response->Data, $request);

                if ($responseToken->Result === 0) {
                    $payment->setWebpayrecurringsaletokenid($responseToken->AddCardTokenResult->TokenId);
                    $payment->setWebpayistoken("yestoken");
                    $payment->save();
                    $order->save();
                }
    
                echo json_encode($responseToken);
            }
        }
    }

    /**
     * Action to which the customer will be returned when the payment is made.
     */
    public function successAction() {
        $event = Mage::getModel('simplewebpay/event')
                ->setEventData($this->getRequest()->getParams());
        try {
            $quoteId = $event->successEvent();
            $this->_getCheckout()->setLastSuccessQuoteId($quoteId);

            $message = $event->processStatusEvent();
            $this->_redirect('checkout/onepage/success');
            $this->loadLayout();
            $this->renderLayout();

            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * Action to which the customer will be returned if the payment process is
     * cancelled.
     * Cancel order and redirect user to the shopping cart.
     */
    public function cancelAction() {
        $event = Mage::getModel('simplewebpay/event')
                ->setEventData($this->getRequest()->getParams());
        $message = $event->cancelEvent();

// set quote to active
        $session = $this->_getCheckout();
        if ($quoteId = $session->getSimplewebpayQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }

        $session->addError($message);
        $this->_redirect('checkout/cart');
    }

    /**
     * Action to which the transaction details will be posted after the payment
     * process is complete.
     */
    public function statusAction() {
        $event = Mage::getModel('simplewebpay/event')
                ->setEventData($this->getRequest()->getParams());
        $message = $event->processStatusEvent();
        $this->getResponse()->setBody($message);
    }

    /**
     * Set redirect into responce. This has to be encapsulated in an JavaScript
     * call to jump out of the iframe.
     *
     * @param string $path
     * @param array $arguments
     */
    protected function _redirect($path, $arguments = array()) {
        $this->getResponse()->setBody(
                $this->getLayout()
                        ->createBlock('simplewebpay/redirect')
                        ->setRedirectUrl(Mage::getUrl($path, $arguments))
                        ->toHtml()
        );
        return $this;
    }

    function getRealIP() {
        if (!empty($_SERVER["HTTP_CLIENT_IP"]))
            return $_SERVER["HTTP_CLIENT_IP"];
        if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        return $_SERVER["REMOTE_ADDR"];
    }

}
