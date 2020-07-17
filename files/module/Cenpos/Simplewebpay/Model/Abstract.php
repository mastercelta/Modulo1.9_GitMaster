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
abstract class Cenpos_Simplewebpay_Model_Abstract extends Mage_Payment_Model_Method_Abstract {

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
    public function __construct() {
        parent::__construct();
    }

    public function getOrder() {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    /**
     * Return url for redirection after order placed
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('simplewebpay/processing/payment');
    }

    /**
     * Capture payment through Simplewebpay api
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Cenpos_Simplewebpay_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount) {
        $Response = new stdClass();
        $data = Mage::getModel('sales/order_payment_transaction')->getCollection()
                ->addAttributeToFilter('order_id', array('eq' => $payment->getOrder()->getEntityId()))
                ->addAttributeToFilter('txn_type', array('eq' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH));

        $data = json_decode(json_encode($data->toArray()), FALSE);

        $ReferenceNumber = "";

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
                require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/Model/ModelConnector.php');
                require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/CenposConnector.php');

                CenposConnector::Init();

                $Cenpos = new CenposConnector();

                $RequestForce = new ForceTrxByrefNumBackOfficeRequest();
                $RequestForce->MerchantId = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
                $RequestForce->Password = Mage::getStoreConfig("payment/simplewebpay_acc/password");
                $RequestForce->UserId = Mage::getStoreConfig("payment/simplewebpay_acc/userID");
                $RequestForce->ReferenceNumber = $ReferenceNumber = $transaction->txn_id;
                $RequestForce->Amount = round($amount, 2);
                $RequestForce->AutoProcess3D = false;
                $RequestForce->InvoiceNumber = $payment->getOrder()->getRealOrderId();

                $ResponsForce = $Cenpos->ForceTrxByrefNumBackOffice($RequestForce);

                if ($ResponsForce->ProcessCardResult === false)
                    Mage::throwException($this->_getHelper()->__('Error Processing the request'));

                $ResponsForce->ProcessCardResult->InvoiceNumber = $RequestForce->InvoiceNumber;

                $Response = $ResponsForce->ProcessCardResult;
            } else
                Mage::throwException($this->_getHelper()->__('Error Processing the request'));
        }else {
            $Response = $this->callApi($payment, $amount, "Sale");
        }

        if ($Response->Result == 0 || $Response->Result == 21) {
            $data = array('ReferenceNumber' => $Response->ReferenceNumber,
                'Authorization' => $Response->AuthorizationNumber,
                'Result' => $Response->Result,
                'Message' => $Response->Message,
                'Invoice' => $Response->InvoiceNumber,
                'CardType' => $Response->CardType,
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

    public function authorize(Varien_Object $payment, $amount) {
        $Response = $this->callApi($payment, $amount, "Auth");
        $order = $payment->getOrder();
        if ($Response === false)
            Mage::throwException($this->_getHelper()->__('Error Processing the request'));

        if ($Response->Result === 0 || $Response->Result == 21) {
            $data = array('ReferenceNumber' => $Response->ReferenceNumber,
                'TokenID' => $Response->TokenID,
                'Authorization' => $Response->AuthorizationNumber,
                'Result' => $Response->Result,
                'Message' => $Response->Message,
                'Invoice' => $Response->InvoiceNumber,
                'CardType' => $Response->CardType,
                'Amount' => $Response->Amount,
                'CardNumber' => $Response->CardNumber,
                'Expiration' => $Response->Expiration
            );
            if ($Response->Result === 21) {

                $BeginSecure = strpos($Response->Message, "<SecureCode>");
                $EndSecure = strlen($Response->Message) - $BeginSecure;

                $TextSecureCode = substr($Response->Message, $BeginSecure, $EndSecure);

                $xmlSecure = simplexml_load_string($TextSecureCode);
                $jsonSecure = json_encode($xmlSecure);
                $SecureCode = json_decode($jsonSecure, FALSE);

                $data["ACSUrl"] = $SecureCode->ACSUrl;
                $data["Payload"] = $SecureCode->Payload;
                $data["TransactionId"] = $SecureCode->TransactionId;
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

    private function callApi(Varien_Object $payment, $amount, $type) {
        require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/Model/ModelConnector.php');
        require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/CenposConnector.php');

        CenposConnector::Init();

        $Cenpos = new CenposConnector();

        $ResultAbi = -1;

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
        if ($order->getBillingAddress()->getEmail()) {
            $email = $order->getBillingAddress()->getEmail();
        } else {
            $email = $order->getCustomerEmail();
        }
        
        $customer = $billing->getData();
        
        $Street = $customer["street"];
        if (strpos($Street, "\n") !== FALSE) {
            $Street = str_replace("\n", " ", $Street);
        }
        $Level3data = $this->createlevel3data($payment);
        
        if ($payment->getWebpayistoken() == "notoken") {
            $request = new UseCryptoTokenRequest();

            $request->MerchantId = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
            $request->Password = Mage::getStoreConfig("payment/simplewebpay_acc/password");
            $request->UserId = Mage::getStoreConfig("payment/simplewebpay_acc/userID");
            $request->Amount = round($amount, 2);
            $request->InvoiceDetail = "TransactionType:$type".$Level3data;
            $request->InvoiceNumber = $order->getIncrementId();
            $request->TaxAmount = "0";
            $request->CardNumber = $payment->getWebpayrecurringsaletokenid();
            $request->CustomerEmail = $email;
            $request->AutoProcess3D = false;
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $request->CustomerCode = $customerData->getId();
            }else{
                $request->CustomerCode = rand(9000000000, 9999999999);
            }
            
            $request->CustomerZipCode = $customer["postcode"];
            $request->CustomerBillingAddress = $Street;
            $request->TransactionType = $type;
			
            $response = $Cenpos->UseCryptoToken($request);
	  
            $response->ProcessCardResult->InvoiceNumber = $order->getLastRealOrderId();
            $response->ProcessCardResult->Amount = $order->getLastRealOrderId();
            $response->ProcessCardResult->TokenID = $payment->getWebpayrecurringsaletokenid();
            $response->ProcessCardResult->CardNumber = $payment->getWebpayprotectedcardnumber();
            $response->ProcessCardResult->Expiration = $payment->getWebpaycardexpirationdate();
            $response->ProcessCardResult->transaction_id = $order->getLastRealOrderId();
            $response->ProcessCardResult->RealOrderID = $order->getLastOrderId();
            $response->ProcessCardResult->TransactionType = $request->TransactionType;


            $ResultAbi = $response->ProcessCardResult;

            if ($isRegister && $ResultAbi->Result === 0) {
                $request = new ConvertToPermanentTokenRequest();

                $request->MerchantId = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
                $request->Password = Mage::getStoreConfig("payment/simplewebpay_acc/password");
                $request->UserId = Mage::getStoreConfig("payment/simplewebpay_acc/userID");
                $request->Token = $payment->getWebpayrecurringsaletokenid();
                if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                    $customerData = Mage::getSingleton('customer/session')->getCustomer();
                    $request->CustomerCode = $customerData->getId();
                }

                $order2 = Mage::getModel('sales/order');

                $responseToken = $Cenpos->ConvertToPermanentToken($request);
            }
        } else {
            $request = new UseTokenRequest();

            $request->MerchantId = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
            $request->Password = Mage::getStoreConfig("payment/simplewebpay_acc/password");
            $request->UserId = Mage::getStoreConfig("payment/simplewebpay_acc/userID");
            $request->AutoProcess3D = false;
            $request->GeoLocationInformation = "ReplacementIp:" . $this->getRealIP();
            $request->Amount = round($order->getGrandTotal(), 2);
            $request->InvoiceDetail = "TransactionType:$type".$Level3data;
            $request->InvoiceNumber = $order->getIncrementId();
            $request->TaxAmount = "0";
            $request->TokenId = $payment->getWebpayrecurringsaletokenid();
            $request->TransactionType = $type;
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $request->CustomerCode = $customerData->getId();
            }else{
                $request->CustomerCode = rand(9000000000, 9999999999);
            }

            $response = $Cenpos->UseToken($request);

            $response->UseTokenResult->InvoiceNumber = $order->getIncrementId();
            $response->UseTokenResult->Amount = $request->Amount;
            $response->UseTokenResult->TokenID = $payment->getWebpayrecurringsaletokenid();
            $response->UseTokenResult->CardNumber = $payment->getWebpayprotectedcardnumber();
            $response->UseTokenResult->Expiration = $payment->getWebpaycardexpirationdate();
            $response->UseTokenResult->transaction_id = $order->getIncrementId();
            $response->UseTokenResult->RealOrderID = $order->getId();
            $response->UseTokenResult->TransactionType = $request->TransactionType;


            $ResultAbi = $response->UseTokenResult;
        }
        
        if($ResultAbi->Result === 0){
            $order->sendNewOrderEmail();
            $order->setEmailSent(true);
        }
        
        return $ResultAbi;
    }

    private function createlevel3data(Varien_Object $payment    ){
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
            $headerXML .= "<CustomerCode>" .$customerData->getId(). "</CustomerCode>";
        }
        
        $shipping = $order->getShippingAddress();
        
        $headerXML .= "<ShiptofromZIPcode>" .$shipping->getdata()["postcode"]. "</ShiptofromZIPcode>";
        $headerXML .= "<Destinationcountrycode>" .$shipping->getCountry(). "</Destinationcountrycode>";
        $headerXML .= "<VATinvoicereferencenumber>" .$order->getLastRealOrderId(). "</VATinvoicereferencenumber>";
        $headerXML .= "<VATtaxamountrate>" ."0.00". "</VATtaxamountrate>";
        $headerXML .= "<Freightshippingamount>" ."0". "</Freightshippingamount>";
        $headerXML .= "<Dutyamount>" ."0". "</Dutyamount>";
        $headerXML .= "<Discountamount>" ."0". "</Discountamount>";
        $headerXML .= "<Orderdate>" .date("d").date("m").date("y"). "</Orderdate>";
        $headerXML .= "</Header>";
        
        $producXMl = "<Products>";
        
        $items = Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection();
        $count = 1;
        foreach($items as $item) {
            $producXMl .= "<product>";
           // $producXMl .= "<DiscountLineItem>0</DiscountLineItem>";
            //$producXMl .= "<ItemCommodityCode>" + product["ItemCommodityCode"].InnerText + "</ItemCommodityCode>";
            $description = Mage::getModel('catalog/product')->load($item->getId())->getDescription();
            if($description != null) $producXMl .= "<ItemDescription>" . $description . "</ItemDescription>";
            $producXMl .= "<ItemSequenceNumber>$count</ItemSequenceNumber>";
            $producXMl .= "<LineItemTotal>" . $item->getPrice() . "</LineItemTotal>";
            $producXMl .= "<ProductCode>" . $item->getProductId() ."</ProductCode>";
            $producXMl .= "<Quantity>" . $item->getQty() + "</Quantity>";
            $producXMl .= "<Selected>true</Selected>";
            $count++;
            $producXMl .= "</product>";
        }
        $producXMl .= "</Products>";
        
        $response .= $headerXML.$producXMl;
        $response .= "<Notes><Note></Note></Notes>";
        $response .= "</LevelIIIData>";
        return $response;
    }
    
    public function processBeforeRefund($invoice, $payment) {
        return parent::processBeforeRefund($invoice, $payment);
    }

    public function refund(Varien_Object $payment, $amount) {
        $payment->setIsTransactionClosed(0);

        require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/Model/ModelConnector.php');
        require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/CenposConnector.php');

        CenposConnector::Init();

        $Cenpos = new CenposConnector();

        $request = new VoidTrxByRefNumBackOfficeRequest();

        $request->MerchantId = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
        $request->Password = Mage::getStoreConfig("payment/simplewebpay_acc/password");
        $request->UserId = Mage::getStoreConfig("payment/simplewebpay_acc/userID");
        $request->ReferenceNumber = $payment->getOrigData()["last_trans_id"];
        $request->Amount = round($payment->getMethodInstance()->getOrder()->getGrandTotal(), 2);
        $request->InvoiceNumber = $payment->getMethodInstance()->getOrder()->getIncrementId();

        $response = $Cenpos->VoidTrxByRefNumBackOffice($request);

        if ($response->ProcessCardResult->Result === 0) {
            return parent::refund($payment);
        } else
            Mage::throwException(Mage::helper('paygate')->__($response->ProcessCardResult->Message));
    }

    public function processCreditmemo($creditmemo, $payment) {
        return parent::processCreditmemo($creditmemo, $payment);
    }

    /**
     * Camcel payment
     *
     * @param Varien_Object $payment
     * @return Cenpos_Simplewebpay_Model_Abstract
     */
    public function cancel(Varien_Object $payment) {
        $payment->setStatus(self::STATUS_DECLINED)
                ->setTransactionId($this->getTransactionId())
                ->setIsTransactionClosed(1);

        return $this;
    }

    public function void(Varien_Object $payment) {

        $payment->setIsTransactionClosed(0);

        require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/Model/ModelConnector.php');
        require_once('app/code/local/Cenpos/Simplewebpay/Model/CenposConnector/CenposConnector.php');

        CenposConnector::Init();

        $Cenpos = new CenposConnector();

        $request = new VoidTrxByRefNumBackOfficeRequest();

        $request->MerchantId = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
        $request->Password = Mage::getStoreConfig("payment/simplewebpay_acc/password");
        $request->UserId = Mage::getStoreConfig("payment/simplewebpay_acc/userID");
        $request->ReferenceNumber = $payment->getOrigData()["last_trans_id"];
        $request->Amount = round($payment->getMethodInstance()->getOrder()->getGrandTotal(), 2);
        $request->InvoiceNumber = $payment->getMethodInstance()->getOrder()->getIncrementId();


        $response = $Cenpos->VoidTrxByRefNumBackOffice($request);

        if ($response->ProcessCardResult->Result === 0) {
            parent::void($payment);
        } else
            Mage::throwException(Mage::helper('paygate')->__($response->ProcessCardResult->Message));
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getUrl() {
        return Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getSessionDataPOST() {
        Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay"); //https://www.cenpos.net/simplewebpay-ebpp-new/simplewebpay-new/paymentweb.aspx';
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getLocale() {
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
    public function getFormFields() {
        $order_id = $this->getOrder()->getRealOrderId();
        $billing = $this->getOrder()->getBillingAddress();
        if ($this->getOrder()->getBillingAddress()->getEmail()) {
            $email = $this->getOrder()->getBillingAddress()->getEmail();
        } else {
            $email = $this->getOrder()->getCustomerEmail();
        }

        $url = (($_SERVER['HTTPS']) ? "http://" : "http://");
        $url .= $_SERVER["SERVER_NAME"];
        if ((strrpos($_SERVER["REQUEST_URI"], "index.php")) === false)
            $url .= str_replace("simplewebpay/processing/placeform/", "cenpossimplewebpay.php", $_SERVER["REQUEST_URI"]);
        else
            $url .= str_replace("index.php/simplewebpay/processing/placeform/", "cenpossimplewebpay.php", $_SERVER["REQUEST_URI"]);


        $GUID = trim($this->getGUID(), '{}');
        $Begin = rand(0, strlen($GUID));
        $End = rand($Begin, strlen($GUID) - $Begin);

        $SessionDataPOST = Mage::getStoreConfig("payment/simplewebpay_acc/merchant") . substr($GUID, $Begin, $End);
        $SessionData = "";
        if (Mage::getStoreConfig("payment/simplewebpay_acc/keyencrypt") != "") {
            $SessionData = $this->encryptaes(Mage::getStoreConfig("payment/simplewebpay_acc/keyencrypt"), $Begin . "," . $End . "," . $GUID . "," . Mage::getStoreConfig("payment/simplewebpay_acc/merchant"));
        } else
            $SessionDataPOST = "";
        $params = array(
            'merchantID' => Mage::getStoreConfig("payment/simplewebpay_acc/merchant"),
            'isPresta' => 'true',
            'pay_to_email' => Mage::getStoreConfig(Cenpos_Simplewebpay_Helper_Data::XML_PATH_EMAIL),
            'transaction_id' => $order_id,
            'invoice' => $order_id,
            'sessionDataPost' => $SessionDataPOST,
            'sessionData' => $SessionData,
            'encryptmode' => "php",
            'autologin' => "false",
            'urlreturn' => $url,
            'cancel_url' => Mage::getUrl('simplewebpay/processing/cancel', array('transaction_id' => $order_id)),
            'status_url' => Mage::getUrl('simplewebpay/processing/status'),
            'language' => $this->getLocale(),
            'amount' => round($this->getOrder()->getGrandTotal(), 2),
            'taxamount' => Mage::getStoreConfig("payment/simplewebpay_acc/tax"),
            'currency' => $this->getOrder()->getOrderCurrencyCode(),
            'recipient_description' => $this->getOrder()->getStore()->getWebsite()->getName(),
            'firstname' => $billing->getFirstname(),
            'lastname' => $billing->getLastname(),
            'address' => $billing->getStreet(-1),
            'zip' => $billing->getPostcode(),
            'city' => $billing->getCity(),
            'country' => $billing->getCountryModel()->getIso3Code(),
            'state' => $billing->getRegion(),
            'email' => $email,
            'phone_number' => $billing->getTelephone(),
            'detail1_description' => Mage::helper('simplewebpay')->__('Order ID'),
            'detail1_text' => $order_id,
            'payment_methods' => $this->_paymentMethod,
            'hide_login' => $this->_hidelogin,
            'new_window_redirect' => '1'
        );

// add optional day of birth
        if ($billing->getDob()) {
            $params['date_of_birth'] = Mage::app()->getLocale()->date($billing->getDob(), null, null, false)->toString('dmY');
        }

        return $params;
    }

    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded() {
        return false;
    }

    /**
     * Get config action to process initialization
     *
     * @return string
     */
    public function getConfigPaymentAction() {
        $paymentAction = $this->getConfigData('payment_action');

        return empty($paymentAction) ? true : $paymentAction;
    }

    private function addpadding($string, $blocksize = 16) {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }

    private function encryptaes($key, $string = "") {
        $key = $key . "Cenpos";
        $iv = $key;

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $this->addpadding($string), MCRYPT_MODE_CBC, $iv));
    }

    private function getGUID() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = chr(123)// "{"
                    . substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12)
                    . chr(125); // "}"
            return $uuid;
        }
    }

    public function assignData($data) {
        $result = parent::assignData($data);

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
// $info = $this->getInfoInstance();
//$info->setWebpaycardtype($data->getWebpaycardtype());
// $info->setData("cc_owner", $data->getWebpaycardtype());
        return $this;
    }

    public function prepareSave() {
        $info = $this->getInfoInstance();
//$info->setCcCidEnc($info->encrypt($info->getCcCid()));
// $info->setCcNumber(null)
//       ->setCcCid(null);
        return $this;
    }

    public function validate() {
        parent::validate();

        $info = $this->getInfoInstance();


        return $this;
    }

}
