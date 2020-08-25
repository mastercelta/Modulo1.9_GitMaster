<?php

class Cenpos_Simplewebpay_Model_Event
{
    const CENPOS_NO_ERROR = 0;
	const CENPOS_IS_DEBIT_CARD = 4;
	const CENPOS_TIMEOUT = 8;
	const CENPOS_ISDEBIT_AND_COMMERCIAL = 12;
	const CENPOS_ABI_INVALID_AMOUNT = 13;
	const CENPOS_INVALID_CARD_NUMBER = 14;
	const CENPOS_INVALID_OPERATION = 39;
	const CENPOS_DUPLICATED_TRANSACTION = 2;
	const CENPOS_PIN_REQUIRED = 303;
	const CENPOS_INVALID_USER = 101;
	const CENPOS_NOT_ENOUGH_PRIVILEGES = 102;
	const CENPOS_BLOCKED_USER = 103;
	const CENPOS_INACTIVE_USER = 104;
	const CENPOS_USER_MUST_CHANGE_PASSWORD = 105;
	const CENPOS_WEAK_PASSWORD = 106;
	const CENPOS_PREVIOUSLY_USED_PASSWORD = 107;
	const CENPOS_INVALID_DATA = 201;
	const CENPOS_CANT_DELETE_SELF = 111;
	const CENPOS_CANT_DELETE_MASTER_USER = 110;
	const CENPOS_UNKNOWN_ERROR = 999;
	const CENPOS_ERROR_XML = 9999;
	const CENPOS_TRANSACTON_REJECTED = 1;
	const CENPOS_TRANSACTION_REJECTED_BY_PARAM = 6;
	const CENPOS_DEBIT_CANT_BE_PROCESSED_HAS_CREDIT = 4;
	const CENPOS_CREDIT_CANT_BE_PROCESSED_HAS_DEBIT = 5;
	const CENPOS_TRANSACTION_REJECTED_CHECK_MERCHANT_CONFIG = 3;
	const CENPOS_PASSWORD_ALREADY_IN_USE = 108;
	const CENPOS_GENERIC_ERROR_TPI = 99;
	const CENPOS_CANT_CHANGE_PASSWORD_TO_MASTER_USER = 112;
	const CENPOS_DATA_CANT_BE_NULL = 202;
	const CENPOS_DEVICE_BRANCH_INVALID = 210;
	const CENPOS_INVALID_CARDNUMBER = 211;
	const CENPOS_INVALID_COUNTRY = 212;
	const CENPOS_INVALID_DEVICE_TYPE = 213;
	const CENPOS_INVALID_STORE = 214;
	const CENPOS_INVALID_MERCHANTID = 215;
	const CENPOS_INVALID_RECEIPT_TYPE = 216;
	const CENPOS_INVALID_VENDOR = 217;
	const CENPOS_INVALID_SIGNATURE_TYPE = 218;
	const CENPOS_INVALID_STATE = 219;
	const CENPOS_INVALID_TIME_ZONE = 220;
	const CENPOS_ABI_INVALID_TRANSACTION_TYPE = 221;
	const CENPOS_INVALID_USER_PARAMETER = 222;
	const CENPOS_INVALID_USER_PRIVILEGE = 223;
	const CENPOS_INVALID_AMOUNT = 250;
	const CENPOS_INVALID_OPERATION_FOR_CARD_BIN = 251;
	const CENPOS_INVALID_CARD_EXPIRATION_DATE = 252;
	const CENPOS_ABI_INVALID_REFERENCE_NUMBER = 254;
	const CENPOS_INVALID_TAX_OUT_OF_RANGE = 255;
	const CENPOS_INVALID_ZIP_CODE = 256;
	const CENPOS_INVALID_CVV = 257;
	const CENPOS_INVALID_LAST_FOUR_DIGITS = 259;
	const CENPOS_AUTHORIZATION_NUMBER_REQUIRED = 300;
	const CENPOS_BILLING_INFORMATION_REQUIRED = 301;
	const CENPOS_CLIENT_CODE_REQUIRED = 302;
	const CENPOS_REFERENCE_NUMBER_REQUIRED = 304;
	const CENPOS_SIGNATURE_REQUIRED = 305;
	const CENPOS_TRANSACTION_TYPE_REQUIRED = 306;
	const CENPOS_ZIP_CODE_REQUIRED = 307;
	const CENPOS_TAX_REQUIRED = 308;
	const CENPOS_INVOICE_NUMBER_REQUIRED = 309;
	const CENPOS_CVV_REQUIRED = 310;
	const CENPOS_DATABASE_ERROR = 998;
	const CENPOS_APPROVED = 0;

    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**T
     * Event request data
     * @var array
     */
    protected $_eventData = array();

    /**
     * Enent request data setter
     * @param array $data
     * @return Cenpos_Simplewebpay_Model_Event
     */
    public function setEventData(array $data)
    {
        $this->_eventData = $data;
        return $this;
    }

    /**
     * Event request data getter
     * @param string $key
     * @return array|string
     */
    public function getEventData($key = null)
    {
        if (null === $key) {
            return $this->_eventData;
        }
        return isset($this->_eventData[$key]) ? $this->_eventData[$key] : null;
    }

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Process status notification from Monebookers server
     *
     * @return String
     */
    public function processStatusEvent()
    {
        die("processStatusEvent");
        try {
            $params = $this->_validateEventData();
            $msg = '';
            
            if(!empty($params->Result)) $params->Result = -1;
            
            switch($params->Result) {
                case self::CENPOS_APPROVED: //ok
                    $msg = Mage::helper('simplewebpay')->__('The amount has been authorized and paid by Simplewebpay.');
                    $this->_processSale($params->Result, $msg, $params);
                    break;
                default: //cancel
                    $msg = Mage::helper('simplewebpay')->__('Your order was canceled due to a declined payment. Would you like to retry? <a href="'.Mage::getUrl("sales/order/reorder/order_id/".$params->RealOrderID."/").'">Reorder</a>');
                    $this->_processCancel($msg." ".$params->Message, $params);
                break;
            }
            return $msg;
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return;
    }

    /**
     * Process cancelation
     */
    public function cancelEvent() {
        try {
            $this->_validateEventData(false);
            $this->_processCancel('Payment was canceled.');
            return Mage::helper('simplewebpay')->__('The order has been canceled.');
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return '';
    }

    /**
     * Validate request and return QuoteId
     * Can throw Mage_Core_Exception and Exception
     *
     * @return int
     */
    public function successEvent(){
        $this->_validateEventData(false);
        return $this->_order->getQuoteId();
    }

    /**
     * Processed order cancelation
     * @param string $msg Order history message
     */
    protected function _processCancel($msg)
    {
        $this->_order->cancel();
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $msg);
        
        $this->_order->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     * @param string $msg Order history message
     */
    protected function _processSale($status, $msg, $params)
    {
        switch ($status) {
            case self::CENPOS_APPROVED:
                die();
                 // send new order email
                 // save transaction ID
                $this->_order->getPayment()->setLastTransId($params->InvoiceNumber);
                $this->_order->sendNewOrderEmail();
                $this->_order->setEmailSent(true);
                $data = array('ReferenceNumber' => $params->ReferenceNumber,
                    'TokenID' => $params->TokenID,
                    'Authorization' => $params->AuthorizationNumber,
                    'Result' => $params->Result,
                    'Message' => $params->Message,
                    'Invoice' => $params->InvoiceNumber,
                    'CardType' => $params->CardType,
                    'Amount' => $params->Amount,
                    'CardNumber' => $params->CardNumber,
                    'Expiration' => $params->Expiration
                        );
                
                $payment = $this->_order->getPayment();
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,$data);
                $payment->setTransactionId($params->ReferenceNumber);
                if($params->TransactionType === "Sale") $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT, null, false, "");
                else $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, true, "");
                $transaction->setParentTxnId($params->ReferenceNumber);
                $transaction->setIsClosed(true);
                $transaction->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,$data);
                
                $transaction->save();
                
                $invoice = $this->_createInvoice();
                
                $this->_order->setData('state', "complete");
                $this->_order->setStatus("complete");       
                $history = $this->_order->addStatusHistoryComment('Order was set to Complete by our automation tool.', false);
                $history->setIsCustomerNotified(false);
                $this->_order->save();
                 
                
                
                //  $this->_order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true, $msg);
                
            break;
            default:
            break;
        }
        
        
    }

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
     
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->setCanVoidFlag(1);
       
        $invoice->register();
        $transactionSave = Mage::getModel('core/resource_transaction')
        ->addObject($invoice)
        ->addObject($invoice->getOrder());

        $transactionSave->save();
        
        return $invoice;
    }

    /**
     * Checking returned parameters
     * Thorws Mage_Core_Exception if error
     * @param bool $fullCheck Whether to make additional validations such as payment status, transaction signature etc.
     *
     * @return array  $params request params
     */
    protected function _validateEventData($fullCheck = true)
    {
        // get request variables
        $params = $this->_eventData;
        if (empty($params)) {
            Mage::throwException('Request does not contain any elements.');
        }
        if (empty($params->transaction_id)
            || ($fullCheck == false && $this->_getCheckout()->getSimplewebpayRealOrderId() != $params->transaction_id)
        ) {
            Mage::throwException('Missing or invalid order ID.');
        }
        // load order for further validation
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($params->transaction_id);
        if (!$this->_order->getId()) {
            Mage::throwException('Order not found.');
        }

        if (0 !== strpos($this->_order->getPayment()->getMethodInstance()->getCode(), 'simplewebpay_')) {
            Mage::throwException('Unknown payment method.');
        }

        return $params;
    }
}
