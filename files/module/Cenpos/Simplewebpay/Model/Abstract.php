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
abstract class Cenpos_Simplewebpay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{

    /**
     * unique internal payment method identifier
     */
    protected $_code = 'simplewebpay_abstract';
    protected $_formBlockType = 'simplewebpay/form';
    protected $_infoBlockType = 'simplewebpay/info';

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canSendNewEmailFlag = false;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canVoidFlag = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_paymentMethod = 'abstract';
    protected $_defaultLocale = 'en';
    protected $_supportedLocales = array('cn', 'cz', 'da', 'en', 'es', 'fi', 'de', 'fr', 'gr', 'it', 'nl', 'ro', 'ru', 'pl', 'sv', 'tr');
    protected $_hidelogin = '1';
    protected $_order;

    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    /**
     * Return url for redirection after order placed
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('simplewebpay/processing/payment');
    }

    /**
     * Capture payment through Simplewebpay api
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Cenpos_Simplewebpay_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $data = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->addAttributeToFilter('order_id', array('eq' => $payment->getOrder()->getEntityId()))
            ->addAttributeToFilter('txn_type', array('eq' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH));

        $data = json_decode(json_encode($data->toArray()), FALSE);

        $Response = new stdClass();

        if ($data->totalRecords > 0) {
            $isForce = false;
            $transaction = null;
            foreach ($data->items as $key => $item) {
                if ($item->is_closed == 0) {
                    $isForce = true;
                    $transaction = $item;
                }
            }
            if ($isForce) {

                $RequestForce = new stdClass();
                $RequestForce->ReferenceNumber = $transaction->txn_id;
                $RequestForce->InvoiceNumber = $payment->getOrder()->getRealOrderId();
                $RequestForce->Amount = round($amount, 2);
                $RequestForce->GeoLocationInformation = "ReplacementIp:" . $this->getRealIP();
                $helper = $this->_getHelper();

                $responseSecret = $helper->getSecretRequest($RequestForce);

                if ($responseSecret->Result != 0)  Mage::throwException($this->_getHelper()->__($responseSecret->Message));
                else {
                    $Response = $helper->sendActionApi("Force", $responseSecret->Data, $RequestForce);
                    $Response->InvoiceNumber = $payment->getOrder()->getRealOrderId();
                }
            } else
                Mage::throwException($this->_getHelper()->__('Error Processing the request'));
        } else {
            $Response = $this->callApi($payment, $amount, "Sale");
        }
        if ($Response->Result == 0 || $Response->Result == 21) {
            $data = array(
                'ReferenceNumber' => $Response->ReferenceNumber,
                'Authorization' => $Response->AuthorizationNumber,
                'TokenID' => $Response->TokenID,
                'Result' => $Response->Result,
                'Message' => $Response->Message,
                'Invoice' => $Response->InvoiceNumber,
                'CardType' => $Response->CardType,
                'Expiration' => $Response->ExpirationCard,
                'Amount' => $Response->Amount
            );

            if ($Response->Result == 21) {
                $data["ACSUrl"] = $Response->ACSUrl;
                $data["Payload"] = $Response->Payload;
                $data["TransactionId"] = $Response->TransactionId;
                $data["Message"] = "Approved Required 3D";
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $data);
                $payment->setTransactionId($Response->ReferenceNumber);
                $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, true, "");
                $transaction->setParentTxnId($Response->ReferenceNumber);
                $transaction->setIsClosed(false);
                $transaction->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $data);
            } else {
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $data);
                $payment->setTransactionId($Response->ReferenceNumber);
                $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, true, "");
                $transaction->setParentTxnId($Response->ReferenceNumber);
                $transaction->setIsClosed(true);
                $transaction->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $data);

                $transaction->save();
            }
            //   $payment->setTransactionId($ReferenceNumber);
        } else
            Mage::throwException($this->_getHelper()->__($Response->Message));

        return $this;
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $Response = $this->callApi($payment, $amount, "Auth");
        $order = $payment->getOrder();
        if ($Response === false)
            Mage::throwException($this->_getHelper()->__('Error Processing the request'));

        if ($Response->Result === 0 || $Response->Result == 21) {
            $data = array(
                'ReferenceNumber' => $Response->ReferenceNumber,
                'TokenID' => $Response->TokenID,
                'Authorization' => $Response->AuthorizationNumber,
                'Result' => $Response->Result,
                'Message' => $Response->Message,
                'Invoice' => $Response->InvoiceNumber,
                'CardType' => $Response->CardType,
                'Amount' => $Response->Amount,
                'CardNumber' => $Response->CardNumber,
                'Expiration' => $Response->ExpirationCard
            );
            if ($Response->Result === 21) {
                $data["ACSUrl"] = $Response->ACSUrl;
                $data["Payload"] = $Response->Payload;
                $data["TransactionId"] = $Response->TransactionId;
                $data["Message"] = "Approved Required 3D";
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $data);
                $payment->setTransactionId($Response->ReferenceNumber);
                $payment->setIsTransactionClosed(0);
            } else {
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $data);
                $payment->setTransactionId($Response->ReferenceNumber);
                $payment->setIsTransactionClosed(0);
            }
            $order->sendNewOrderEmail();
            $order->setEmailSent(true);
        } else {
            Mage::throwException($Response->Message);
        }

        return $this;
    }

    private function callApi(Varien_Object $payment, $amount, $type)
    {
        $response = new stdClass();
        $request = new stdClass();
        try {

            $helper = $this->_getHelper();

            $order = $payment->getOrder();
            $sessionmethod = Mage::getSingleton('checkout/session')->getQuote();
            $isRegister = false;
            if ($sessionmethod->getId()) {
                $method = $sessionmethod->getCheckoutMethod();
                if ($method === "register")
                    $isRegister = true;
            }
            $billing = $order->getBillingAddress();
            if ($order->getBillingAddress()->getEmail()) {
                $request->Email = $order->getBillingAddress()->getEmail();
            } else {
                $request->Email = $order->getCustomerEmail();
            }


            $customer = $billing->getData();

            $Street = $customer["street"];
            if (strpos($Street, "\n") !== FALSE) {
                $Street = str_replace("\n", " ", $Street);
            }

            $request->Amount = round($amount, 2);

            $request->InvoiceNumber = $order->getIncrementId();
            $request->TaxAmount = "0";
            $request->TokenId = $payment->getWebpayrecurringsaletokenid();

            $request->GeoLocationInformation = "ReplacementIp:" . $this->getRealIP();
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $request->CustomerCode = $customerData->getId();
            } else {
                $request->CustomerCode = rand(9000000000, 9999999999);
            }

            $request->CustomerZipCode = $customer["postcode"];
            $request->CustomerBillingAddress = $Street;
            $request->Type = $type;

            $responseSecret = $helper->getSecretRequest($request);


            if ($responseSecret->Result != 0)  return $responseSecret;
            else {
                $Level3data = $this->createlevel3data($payment);
                $request->InvoiceDetail = "TransactionType:$type" . $Level3data;
                $response = $helper->sendActionApi((strrpos($payment->getWebpayrecurringsaletokenid(), "CRYPTO") !== false) ? "UseCrypto" : "UseToken", $responseSecret->Data, $request);

                $response->InvoiceNumber = $order->getLastRealOrderId();
                $response->transaction_id = $order->getLastRealOrderId();
                $response->TokenID = $payment->getWebpayrecurringsaletokenid();
                $response->RealOrderID = $order->getLastOrderId();
                $response->TransactionType = $request->Type;
                $response->CardNumber = $payment->getWebpayprotectedcardnumber();

                if ($isRegister && $response->Result === 0 && ($payment->getWebpayistoken() == "notoken")) {
                    $responseconvert = $helper->sendActionApi("ConvertCrypto", $responseSecret->Data, $request);

                    if ($responseconvert->Result === 0) {
                        $payment->setWebpayrecurringsaletokenid($responseconvert->TokenId);
                        $payment->setWebpayistoken("yestoken");
                    }
                }
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            $response->Message = $e->getMessage();
            $response->Result = -1;
        }

        if ($response->Result === 0) {
            $order->sendNewOrderEmail();
            $order->setEmailSent(true);
        }

        return $response;
    }

    private function createlevel3data(Varien_Object $payment)
    {
        $order = $payment->getOrder();
        $sessionmethod = Mage::getSingleton('checkout/session')->getQuote();

        $isRegister = false;

        if ($sessionmethod->getId()) {
            $method = $sessionmethod->getCheckoutMethod();

            if ($method === "register")
                $isRegister = true;
        }

        $email = "";

        $billing = $order->getBillingAddress();
        if ($billing->getEmail()) {
            $email = $billing->getEmail();
        } else {
            $email = $order->getCustomerEmail();
        }

        $customer = $billing->getData();

        $Street = $customer["street"];
        if (strpos($Street, "\n") !== FALSE) {
            $Street = str_replace("\n", " ", $Street);
        }


        $response = "<LevelIIIData>";
        $headerXML = "<Header>";
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $headerXML .= "<CustomerCode>" . $customerData->getId() . "</CustomerCode>";
        }

        $shipping = $order->getShippingAddress();

        $headerXML .= "<ShiptofromZIPcode>" . $shipping->getdata()["postcode"] . "</ShiptofromZIPcode>";
        $headerXML .= "<Destinationcountrycode>" . $shipping->getCountry() . "</Destinationcountrycode>";
        $headerXML .= "<VATinvoicereferencenumber>" . $order->getLastRealOrderId() . "</VATinvoicereferencenumber>";
        $headerXML .= "<VATtaxamountrate>" . "0.00" . "</VATtaxamountrate>";
        $headerXML .= "<Freightshippingamount>" . "0" . "</Freightshippingamount>";
        $headerXML .= "<Dutyamount>" . "0" . "</Dutyamount>";
        $headerXML .= "<Discountamount>" . "0" . "</Discountamount>";
        $headerXML .= "<Orderdate>" . date("d") . date("m") . date("y") . "</Orderdate>";
        $headerXML .= "</Header>";

        $producXMl = "<Products>";

        $items = Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection();
        $count = 1;
        foreach ($items as $item) {
            $producXMl .= "<product>";
            // $producXMl .= "<DiscountLineItem>0</DiscountLineItem>";
            //$producXMl .= "<ItemCommodityCode>" + product["ItemCommodityCode"].InnerText + "</ItemCommodityCode>";
            $description = Mage::getModel('catalog/product')->load($item->getId())->getDescription();
            if ($description != null) $producXMl .= "<ItemDescription>" . $description . "</ItemDescription>";
            $producXMl .= "<ItemSequenceNumber>$count</ItemSequenceNumber>";
            $producXMl .= "<LineItemTotal>" . $item->getPrice() . "</LineItemTotal>";
            $producXMl .= "<ProductCode>" . $item->getProductId() . "</ProductCode>";
            $producXMl .= "<Quantity>" . $item->getQty() + "</Quantity>";
            $producXMl .= "<Selected>true</Selected>";
            $count++;
            $producXMl .= "</product>";
        }
        $producXMl .= "</Products>";

        $response .= $headerXML . $producXMl;
        $response .= "<Notes><Note></Note></Notes>";
        $response .= "</LevelIIIData>";
        return $response;
    }

    public function processBeforeRefund($invoice, $payment)
    {
        return parent::processBeforeRefund($invoice, $payment);
    }

    public function refund(Varien_Object $payment, $amount)
    {

        $payment->setIsTransactionClosed(0);

        $request = new stdClass();
        $request->ReferenceNumber = $payment->getOrigData()["last_trans_id"];
        $request->Amount = round($payment->getMethodInstance()->getOrder()->getGrandTotal(), 2);
        $request->InvoiceNumber = $payment->getMethodInstance()->getOrder()->getIncrementId();
        $helper = $this->_getHelper();

        $responseSecret = $helper->getSecretRequest($request);

        if ($responseSecret->Result != 0)  Mage::throwException($this->_getHelper()->__($responseSecret->Message));
        else {
            $Response = $helper->sendActionApi("Refund", $responseSecret->Data, $request);
            if ($Response->Result === 0) {
                parent::void($payment);
            } else
                Mage::throwException(Mage::helper('paygate')->__($Response->Message));
        }      
    }
    protected function _getHelper()
    {
        return Mage::helper('simplewebpay');
    }
    public function processCreditmemo($creditmemo, $payment)
    {
        return parent::processCreditmemo($creditmemo, $payment);
    }

    /**
     * Camcel payment
     *
     * @param Varien_Object $payment
     * @return Cenpos_Simplewebpay_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $payment->setStatus(self::STATUS_DECLINED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(1);

        return $this;
    }

    public function void(Varien_Object $payment)
    {

        $payment->setIsTransactionClosed(0);

        $request = new stdClass();
        $request->ReferenceNumber = $payment->getOrigData()["last_trans_id"];
        $request->Amount = round($payment->getMethodInstance()->getOrder()->getGrandTotal(), 2);
        $request->InvoiceNumber = $payment->getMethodInstance()->getOrder()->getIncrementId();
        $helper = $this->_getHelper();

        $responseSecret = $helper->getSecretRequest($request);

        if ($responseSecret->Result != 0)  Mage::throwException($this->_getHelper()->__($responseSecret->Message));
        else {
            $Response = $helper->sendActionApi("Void", $responseSecret->Data, $request);
            if ($Response->Result === 0) {
                parent::void($payment);
            } else
                Mage::throwException(Mage::helper('paygate')->__($Response->Message));
        }
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getUrl()
    {
        return Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getSessionDataPOST()
    {
        Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (is_array($locale) && !empty($locale) && in_array($locale[0], $this->_supportedLocales)) {
            return $locale[0];
        }
        return $this->getDefaultLocale();
    }

    /**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields()
    {
        return "";
    }

    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded()
    {
        return false;
    }

    /**
     * Get config action to process initialization
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');

        return empty($paymentAction) ? true : $paymentAction;
    }

    private function addpadding($string, $blocksize = 16)
    {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }

    private function encryptaes($key, $string = "")
    {
        $key = $key . "Cenpos";
        $iv = $key;

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $this->addpadding($string), MCRYPT_MODE_CBC, $iv));
    }

    private function getGUID()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((float) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = chr(123) // "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12)
                . chr(125); // "}"
            return $uuid;
        }
    }

    public function assignData($data)
    {
        $result = parent::assignData($data);

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        // $info = $this->getInfoInstance();
        //$info->setWebpaycardtype($data->getWebpaycardtype());
        // $info->setData("cc_owner", $data->getWebpaycardtype());
        return $this;
    }

    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        //$info->setCcCidEnc($info->encrypt($info->getCcCid()));
        // $info->setCcNumber(null)
        //       ->setCcCid(null);
        return $this;
    }

    public function validate()
    {
        parent::validate();

        $info = $this->getInfoInstance();


        return $this;
    }
}
