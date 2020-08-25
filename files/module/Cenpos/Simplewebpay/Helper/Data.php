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
class Cenpos_Simplewebpay_Helper_Data extends Mage_Payment_Helper_Data
{
    const XML_PATH_EMAIL        = 'simplewebpay/settings/simplewebpay_email';
    const XML_PATH_MERCHANT       = 'simplewebpay/settings/simplewebpay_merchant';
    const XML_PATH_TAX        = 'simplewebpay/settings/simplewebpay_tax';
    const XML_PATH_CUSTOMER_ID  = 'simplewebpay/settings/customer_id';
    const XML_PATH_SECRET_KEY   = 'simplewebpay/settings/secret_key';
    /**
     * Internal parameters for validation
     */
    protected $_simplewebpayServer           = 'https://www.simplewebpay.com';
    protected $_checkEmailUrl                = '/app/email_check.pl';
    protected $_checkEmailCustId             = '6999315';
    protected $_checkEmailPassword           = 'a4ce5a98a8950c04a3d34a2e2cb8c89f';
    protected $_checkSecretUrl               = '/app/secret_word_check.pl';
    protected $_activationEmailTo            = 'ecommerce@simplewebpay.com';
    protected $_activationEmailSubject       = 'Magento Simplewebpay Activation';
    protected $_simplewebpayMasterCustId     = '7283403';
    protected $_simplewebpayMasterSecretHash = 'c18524b6b1082653039078a4700367f0';

    /**
     * Send activation Email to Simplewebpay
     */
    public function activateEmail()
    {
        $storeId = Mage::app()->getStore()->getId();

        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        Mage::getModel('core/email_template')
            ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
            ->sendTransactional(
                'simplewebpay_activateemail',
                Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, $storeId),
                $this->_activationEmailTo,
                null,
                array(
                    'subject'     => $this->_activationEmailSubject,
                    'email_addr'  => Mage::getStoreConfig(self::XML_PATH_EMAIL),
                    'url'         => Mage::getBaseUrl(),
                    'customer_id' => Mage::getStoreConfig(self::XML_PATH_CUSTOMER_ID),
                    'language'    => Mage::getModel('core/locale')->getDefaultLocale()
                )
            );

        $translate->setTranslateInline(true);
    }

    /**
     * Check if email is registered at Simplewebpay
     *
     * @param array $params
     * @return array
     */
    public function checkEmailRequest(Array $params) {
        $response = null;
        try {
            $response = $this->_getHttpsPage($this->_simplewebpayServer . $this->_checkEmailUrl, array(
                'email'    => $params['email'],
                'cust_id'  => $this->_checkEmailCustId,
                'password' => $this->_checkEmailPassword)
            );
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            return null;
        }
        return $response;
    }

    /**
     * Check if entered secret is valid
     * @param array $params
     * @return array
     */
    public function getSecretRequest(Object $params, $captcha = null)
    {
        $response = new stdClass();
        try {

            $params->IsCaptcha = ((Mage::getStoreConfig("payment/simplewebpay_acc/recaptcha") == "1") ? "true" : "false");

            $params->Url = Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay");
            $params->Merchant = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
            $params->SecretKey = Mage::getStoreConfig("payment/simplewebpay_acc/secretkey");
            $params->CaptchaVersion = Mage::getStoreConfig("payment/simplewebpay_acc/recaptchaversion");

            if(!isset($params->SecretKey) || empty($params->SecretKey))  throw new Exception("No Key Configured..");
            if(!isset($params->Url) || empty($params->Url))  throw new Exception("No Url Configured..");
            if(!isset($params->Merchant) || empty($params->Merchant))  throw new Exception("No Merchant Key Configured..");
            $ip = $_SERVER["REMOTE_ADDR"];

            $params->Url = str_replace("default.aspx", "", $params->Url);
            if(substr($params->Url, -1) != "/") $params->Url = $params->Url ."/";

            if(isset($captcha) && $captcha) $captcha = true;

            $postSend = "";
            $postSend = "secretkey=".urlencode($params->SecretKey);
            $postSend .= "&merchant=".urlencode($params->Merchant);
            if(isset($params->Amount) && !empty($params->Amount)) $postSend .= "&amount=".$params->Amount;
            if(isset($params->City) && !empty($params->City))$postSend .= "&city=FL".$params->Amount;
            if(isset($params->CustomerBillingAddress) && !empty($params->CustomerBillingAddress)) $postSend .= "&address=".$params->CustomerBillingAddress;
            if(isset($params->CustomerZipCode) && !empty($params->CustomerZipCode)) $postSend .= "&zipcode=".$params->CustomerZipCode;
            if(isset($params->CustomerCode) && !empty($params->CustomerCode)) $postSend .= "&customercode=".$params->CustomerCode;
            if(isset($params->Email) && !empty($params->Email))$postSend .= "&email=".$params->Email;
            if(isset($params->InvoiceNumber) && !empty($params->InvoiceNumber)) $postSend .= "&invoicenumber=".$params->InvoiceNumber;
            if(isset($params->TaxAmount) && !empty($params->TaxAmount)) $postSend .= "&taxamount=".$params->TaxAmount;
            if(isset($params->Type) && !empty($params->Type)) $postSend .= "&type=".$params->Type;
            if(isset($params->ReferenceNumber) && !empty($params->ReferenceNumber)) $postSend .= "&referencenumber=".$params->ReferenceNumber;
            if(isset($params->GeoLocationInformation) && !empty($params->GeoLocationInformation)) $postSend .= "&ip=".$params->GeoLocationInformation;
            if(isset($params->IsCaptcha) && !empty($params->IsCaptcha))  $postSend .= "&isrecaptcha=".$params->IsCaptcha; 
            if(isset($params->CaptchaVersion) && !empty($params->CaptchaVersion))  $postSend .= "&recaptchaversion=".$params->CaptchaVersion; 
            if(isset($params->TokenId) && !empty($params->TokenId))  $postSend .= "&tokenid=".$params->TokenId; 
          
            $ch = curl_init($params->Url."?app=genericcontroller&action=siteVerify");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $postSend);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

            
            $resconn = curl_exec($ch);
            $error = curl_errno($ch);
            curl_close ($ch);
            if(!empty($error)) { 
                throw new Exception($error);
            };
            $error2 = curl_error($ch);
            if(!empty($error2)) { 
                throw new Exception($error2);
            };
            
            $response = json_decode($resconn);

            if($response->Result != 0) throw new Exception($response->Message);
        } catch (Exception $e) {

          //  return $e->getMessage();
            $response->Message = $e->getMessage();
            $response->Result = -1;
            Mage::log($e->getMessage());
        }

        return $response;
    }

 /**
     * Check if entered secret is valid
     * @param array $params
     * @return array
     */
    public function sendActionApi(string $command, string  $verifying, Object $params)
    {
        $response = new stdClass();
        try {


            $params->IsCaptcha = ((Mage::getStoreConfig("payment/simplewebpay_acc/recaptcha") == "1") ? "true" : "false");
            $params->Url = Mage::getStoreConfig("payment/simplewebpay_acc/urlsimplewebpay");
            $params->Merchant = Mage::getStoreConfig("payment/simplewebpay_acc/merchant");
            $params->SecretKey = Mage::getStoreConfig("payment/simplewebpay_acc/secretkey");

            if(!isset($params->SecretKey) || empty($params->SecretKey))  throw new Exception("No Key Configured..");
            if(!isset($params->Url) || empty($params->Url))  throw new Exception("No Url Configured..");
            if(!isset($params->Merchant) || empty($params->Merchant))  throw new Exception("No Merchant Key Configured..");
            $ip = $_SERVER["REMOTE_ADDR"];

            $postSend = "";
            $postSend = "verifyingpost=".urlencode($verifying);

        
            if(isset($params->Amount) && !empty($params->Amount)) $postSend .= "&amount=".$params->Amount;
            if(isset($params->City) && !empty($params->City))$postSend .= "&city=FL".$params->Amount;
            if(isset($params->CustomerBillingAddress) && !empty($params->CustomerBillingAddress)) $postSend .= "&address=".$params->CustomerBillingAddress;
            if(isset($params->CustomerZipCode) && !empty($params->CustomerZipCode)) $postSend .= "&zipcode=".$params->CustomerZipCode;
            if(isset($params->CustomerCode) && !empty($params->CustomerCode)) $postSend .= "&customercode=".$params->CustomerCode;
            if(isset($params->Email) && !empty($params->Email))$postSend .= "&email=".$params->Email;
            if(isset($params->InvoiceNumber) && !empty($params->InvoiceNumber)) $postSend .= "&invoicenumber=".$params->InvoiceNumber;
            if(isset($params->InvoiceDetail) && !empty($params->InvoiceDetail)) $postSend .= "&invoicedetail=".urlencode(base64_encode($params->InvoiceDetail));
            if(isset($params->TaxAmount) && !empty($params->TaxAmount)) $postSend .= "&taxamount=".$params->TaxAmount;
            if(isset($params->Type) && !empty($params->Type)) $postSend .= "&type=".$params->Type;
            if(isset($params->ReferenceNumber) && !empty($params->ReferenceNumber)) $postSend .= "&referencenumber=".$params->ReferenceNumber;
            if(isset($params->GeoLocationInformation) && !empty($params->GeoLocationInformation)) $postSend .= "&ip=".urlencode($params->GeoLocationInformation.$ip);
            if(isset($params->TokenId) && !empty($params->TokenId))  $postSend .= "&tokenid=".urlencode($params->TokenId); 

            $ch = curl_init($params->Url."/api/".$command."/");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_POST, 1);


            curl_setopt ($ch, CURLOPT_POSTFIELDS, $postSend);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
            $resconn = curl_exec($ch);
            $error = curl_errno($ch);

            curl_close ($ch);
            
            if(!empty($error)) { 
                throw new Exception($error);
            };

            $ResponseEncrypted = json_decode($resconn);
            if($ResponseEncrypted->Result != 0) throw new Exception($ResponseEncrypted->Message);
            $response  = $ResponseEncrypted;
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            $response->Message = $e->getMessage();
            $response->Result = -1;
        }

        return $response;
    }

    /**
     * Reading a page via HTTPS and returning its content.
     */
    protected function _getHttpsPage($host, $parameter)
    {
        $client = new Varien_Http_Client();
        $client->setUri($host)
            ->setConfig(array('timeout' => 30))
            ->setHeaders('accept-encoding', '')
            ->setParameterGet($parameter)
            ->setMethod(Zend_Http_Client::GET);
        $request = $client->request();
        // Workaround for pseudo chunked messages which are yet too short, so
        // only an exception is is thrown instead of returning raw body
        if (!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $request->getRawBody(), $m))
            return $request->getRawBody();

        return $request->getBody();
    }
}
