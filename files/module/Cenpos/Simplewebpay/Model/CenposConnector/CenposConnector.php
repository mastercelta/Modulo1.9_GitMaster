<?php
require_once 'Model/ModelConnector.php';
class CenposConnector extends ModelConnector {

    public static function Init() {
        $Path = __FILE__;
        $Path = str_replace("\\", "/", $Path);
        $Name = basename($Path);
        $FolderPath = str_replace($Name, "", $Path);

        if (is_dir($FolderPath)) {
            $Import = array(self::VOFolder, self::ModelFolder);
            $ImportFirst = array("CommonRequest.php", "CommonResponse.php", "ModelConnector.php", "TransactionRequest.php");
            $No = array(".", "..", "index.php");
            if (is_dir($FolderPath)) {
                $fp = opendir($FolderPath);
                while (false !== ($Folder = readdir($fp))) {
                    if ((in_array($Folder, $Import))) {
                        if (is_dir($FolderPath . $Folder)) {
                            $finp = scandir ($FolderPath . $Folder);
							foreach($finp as $File) {
                                if (in_array($File, $ImportFirst) && !in_array($File, $No)) {
                                    include_once $FolderPath . $Folder . "/" . $File;
                                }
                            }
                            foreach($finp as $File) {
                                if (!in_array($File, $ImportFirst) && !in_array($File, $No)) {
                                    include_once $FolderPath . $Folder . "/" . $File;
                                }
                            }
                        }
                    }
                }
                closedir($fp);
            }
        }
    }

    public static function GetAllMethod() {
        $getClassMethod = get_class_methods(new CenposConnector());

        $inarray = array("geturlpath","getPathView","xml2js", "renderPhpToString","recoveryPass","Login","__construct", "__destruct", "Init", "GetAllMethod", "ConnectSoapClient", "CallFunctionClient", "GetTranx",
            "ProcessCard", "GetCardTrxDetailXML", "ShowView", "ShowData", "GetUrlBridge", "UpdateToken");

        $response = array();
        $response["frontend"] = array();
        $response["frontendview"] = array();
        $response["backend"] = array();
        $response["backendview"] = array();

        foreach ($getClassMethod as $methodName) {
            if (!in_array($methodName, $inarray)) {
                if (strpos($methodName, 'FView') !== false) {
                    array_push($response["frontendview"], $methodName);
                } else if (strpos($methodName, 'BView') !== false) {
                    array_push($response["backendview"], $methodName);
                } else if (strpos($methodName, 'BackOffice') !== false) {
                    array_push($response["backend"], $methodName);
                } else {
                    array_push($response["frontend"], $methodName);
                }
            }
        }

        return $response;
    }

    public function __construct() {
        // Init();
    }

    public function __destruct() {
        
    }

    /** :::::::::::::::::::::::VIEW FRONT-END:::::::::::::::::::::::::::* */
    public function TokenFView($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://www3.cenpos.net/simplewebpay/cards/";
        $Response = array();
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        $Response["MerchantId"] = urlencode($Request->Merchant);
        $Response["Url"] = $Url;
        $Response["OnlyForm"] = $Request->OnlyForm;
        $Response["IsCVV"] = $Request->IsCVV;
        $Response["Email"] = (empty($Request->Email)) ? "&isemail=false" : "&isemail=true&email=" . $Request->Email;
        $Response["CallbackJS"] = $Request->CallbackJS;
        $Response["CustomerBillingAddress"] = "&address=" . $Request->CustomerBillingAddress;
        $Response["CustomerZipCode"] = "&zipcode=" . $Request->CustomerZipCode;
        $Response["SubmitAction"] = $Request->SubmitAction;
        $Response["Width"] = $Request->Width;
        $Response["Height"] = $Request->Height;
        $Response["CustomerCode"] = "&customercode=" . $Request->CustomerCode;
        $Response["SessionToken"] = "&sessiontoken=" . $Request->SessionToken;
        
        if(!empty($Request->SecretKey)){
            $merchant = $Response["MerchantId"];
            $privatekey = $Request->SecretKey;
            $ip = $_SERVER["REMOTE_ADDR"];
            $email = $Request->Email;
            $customercode = $Request->CustomerCode;
                    
            $ch = curl_init($Url."?app=genericcontroller&action=siteVerify");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, "secretkey=$privatekey&merchant=$merchant&email=$email&ip=$ip&customercode=$customercode");
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
            $resconn = curl_exec($ch);
            $error = curl_error($ch);
            curl_close ($ch);
            if(!empty($error)) { die($error); };
            $ResponseEncrypted = json_decode($resconn);
            if($ResponseEncrypted->Result != 0) { die($ResponseEncrypted->Message); }
            $Response["SecretKey"] = "&verifyingpost=".urlencode($ResponseEncrypted->Data);
        }
        
        $ViewProcess = "Token";

        $this->ShowView($ViewProcess, $Response);
    }



    /** :::::::::::::::::::::::PROCESS FRONT-END:::::::::::::::::::::::::::* */

    /**
     * Login
     *
     * 
     *
     * @param integer $Request the original entity
     * @param integet $Url the referring entity
     */
    public function Login($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/admin.asmx?wsdl";

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "LoginNew", $Request);
    }

    public function recoveryPass($Request, $Url = ""){
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/admin.asmx?wsdl";

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "PasswordReset", $Request);
    }
    
    /**
     * AddCryptoToken
     *
     * 
     *
     * @param integer $Request the original entity
     * @param integet $Url the referring entity
     */
    public function AddCryptoToken($Request, $Url = "") {

        if (empty($Url))
            $Url = "https://ww3.cenpos.net/2/tokens.asmx?wsdl";
        
        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "GenerateCryptoToken", $Request);
    }

    /**
     * ValidateToken
     *
     * 
     *
     * @param ValidateTokenRequest $Request the original entity
     * @param String $Url the referring entity
     */
    public function ValidateToken($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/transact.asmx?wsdl";

        //$Request->

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "ProcessCard", $Request);
    }

    /**
     * UseCrytpoToken
     *
     * 
     *
     * @param ValidateTokenRequest $Request the original entity
     * @param String $Url the referring entity
     */
    public function UseCryptoToken($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/transact.asmx?wsdl";

       //die($Url);
        $client = $this->ConnectSoapClient($Url);
        
        $response = $this->CallFunctionClient($client, ((!isset($Request->SecureCode)) ? "ProcessCard" : "ProcessCreditCard" ), $Request);
        
        if((isset($Request->SecureCode))){
             $response->ProcessCardResult = $response2->ProcessCreditCardResult;
        }
        
        if($response->ProcessCardResult == null) { return $response; }
        if ($response->ProcessCardResult->Message == "Commercial card" && $response->ProcessCardResult->Result == 3)
        {
            $Request->ReferenceNumber = $response->ProcessCardResult->ReferenceNumber;
            $response2 = $this->CallFunctionClient($client, "ProcessCreditCard", $Request);
            $response->ProcessCardResult = $response2->ProcessCreditCardResult;
        }
        if ($response->ProcessCardResult->Result == 21)
        {
            $BeginSecure = strpos($response->ProcessCardResult->Message, "<SecureCode>");
            $EndSecure = strlen($response->ProcessCardResult->Message) - $BeginSecure;
            
            $TextSecureCode = substr($response->ProcessCardResult->Message,$BeginSecure, $EndSecure);
          
            $ResponseCardinal = simplexml_load_string($TextSecureCode);
            
            $ResponseCardinal = json_decode($this->xml2js($ResponseCardinal));
            
           // print_r($ResponseCardinal->SecureCode[0]->_);
            
            if($ResponseCardinal == null) { return $response; }
            if($ResponseCardinal->SecureCode == null) { return $response; }
            if($ResponseCardinal->SecureCode[0] == null) { return $response; }
            
            $vars = array();
            $vars["acsurl"] = $response->ProcessCardResult->ACSUrl = $ResponseCardinal->SecureCode[0]->ACSUrl[0]->_;
            $vars["pareq"] = $response->ProcessCardResult->Payload = $ResponseCardinal->SecureCode[0]->Payload[0]->_;
            $vars["md"] = $response->ProcessCardResult->TransactionId = $ResponseCardinal->SecureCode[0]->TransactionId[0]->_;
            $vars["urlreturn"] = $Request->SecureReturn;
            $vars["isiframe"] = $Request->AutoProcess3D;
            if($Request->AutoProcess3D){
                $file_path= $this->geturlpath($this->getPathView("Process3D"));
                $vars["urlreturn"] = $file_path. "?callback=".urlencode($Request->SecureReturn);
                $vars["height"] = "400";
            }
            $view = $this->renderPhpToString("Show3D",$vars);
            $response->ProcessCardResult->View = $view;
            
            $response->ProcessCardResult->Message = "3D Rejected";
            
            if (session_status() == PHP_SESSION_NONE)
                session_start();
            
            $_SESSION["CardinalTransactionID"] = $vars["md"];
            $_SESSION["CardinalTransactionData"] = json_encode($Request);
            
        }
             
        return $response;
    }

    /**
     * ConvertToPermanentToken
     *
     * 
     *
     * @param ValidateTokenRequest $Request the original entity
     * @param String $Url the referring entity
     */
    public function ConvertToPermanentToken($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/2/tokens.asmx";

        $client = $this->ConnectSoapClient($Url);

        $Request->Token = array("CardNumber" => $Request->Token, "CustomerCode" => $Request->CustomerCode);
        $Request->CustomerCode = "Jump";

        return $this->CallFunctionClient($client, "AddCardToken", $Request);
    }

    /**
     * GetListToken
     *
     * 
     *
     * @param ValidateTokenRequest $Request the original entity
     * @param String $Url the referring entity
     */
    public function GetListToken($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/2/tokens.asmx";

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "GetToken", $Request);
    }

    /**
     * UseToken
     *
     * 
     *
     * @param ValidateTokenRequest $Request the original entity
     * @param String $Url the referring entity
     */
    public function UseToken($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/2/tokens.asmx";

        $client = $this->ConnectSoapClient($Url);

        $Response = $this->CallFunctionClient($client, "UseToken", $Request);
        
        if($Response->UseTokenResult == null) { return $Response; }
        if ($Response->UseTokenResult->Result == 21)
        {
            $BeginSecure = strpos($Response->UseTokenResult->Message, "<SecureCode>");
            $EndSecure = strlen($Response->UseTokenResult->Message) - $BeginSecure;

            $TextSecureCode = substr($Response->UseTokenResult->Message,$BeginSecure, $EndSecure);

            $ResponseCardinal = simplexml_load_string($TextSecureCode);
            $ResponseCardinal = json_decode($this->xml2js($ResponseCardinal));
            if($ResponseCardinal == null) { return $Response; }
            if($ResponseCardinal->SecureCode == null) { return $Response; }
            if($ResponseCardinal->SecureCode[0] == null) { return $Response; }

            $vars = array();
            $vars["acsurl"] = $Response->UseTokenResult->ACSUrl = $ResponseCardinal->SecureCode[0]->ACSUrl[0]->_;
            $vars["pareq"] = $Response->UseTokenResult->Payload = $ResponseCardinal->SecureCode[0]->Payload[0]->_;
            $vars["md"] = $Response->UseTokenResult->TransactionId = $ResponseCardinal->SecureCode[0]->TransactionId[0]->_;
            $vars["urlreturn"] = $Request->SecureReturn;
            $vars["isiframe"] = $Request->AutoProcess3D;
            if($Request->AutoProcess3D){
                $file_path= $this->geturlpath($this->getPathView("Process3D"));
                $vars["urlreturn"] = $file_path. "?callback=".urlencode($Request->SecureReturn);
                $vars["height"] = "400";
            }
            $view = $this->renderPhpToString("Show3D",$vars);
            $Response->UseTokenResult->View = $view;

            $Response->UseTokenResult->Message = "3D Rejected";

            if (session_status() == PHP_SESSION_NONE)
                session_start();

            $_SESSION["CardinalTransactionID"] = $vars["md"];
            $_SESSION["CardinalTransactionData"] = json_encode($Request);

        }
         return $Response;
    }

    /**
     * UseToken
     *
     * 
     *
     * @param ValidateTokenRequest $Request the original entity
     * @param String $Url the referring entity
     */
    public function RevalidateToken($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/transact.asmx?wsdl";

        //$Request->

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "ProcessCard", $Request);
    }

    /**
     * UseToken
     *
     * 
     *
     * @param ValidateTokenRequest $Request the original entity
     * @param String $Url the referring entity
     */
    public function UpdateToken($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/2/tokens.asmx";

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "ModifyCardToken", $Request);
    }

    /** :::::::::::::::::::::::VIEW BACK-END:::::::::::::::::::::::::::* */
    public function VoidBView($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://www3.cenpos.net/webpaytest/viewprocess/";
            $Response = array();
            if (session_status() == PHP_SESSION_NONE)
                session_start();
            $Response["MerchantId"] = $Request->MerchantId;
            $Response["Password"] = base64_encode($Request->Password);
            $Response["Userid"] = $Request->UserId;
            $Response["Url"] = $Url;
            $Response["CallbackJS"] = $Request->CallbackJS;
            $Response["Width"] = $Request->Width;
            $Response["Height"] = $Request->Height;
            $Response["Type"] = "Void";
            
            $ViewProcess = "ViewMain";

        $this->ShowView($ViewProcess, $Response);
    }
    
    public function AuthBView($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://www3.cenpos.net/webpaytest/viewprocess/";
            $Response = array();
            if (session_status() == PHP_SESSION_NONE)
                session_start();
            $Response["MerchantId"] = $Request->MerchantId;
            $Response["Password"] = base64_encode($Request->Password);
            $Response["Userid"] = $Request->UserId;
            $Response["Url"] = $Url;
            $Response["CallbackJS"] = $Request->CallbackJS;
            $Response["Width"] = $Request->Width;
            $Response["Height"] = $Request->Height;
            $Response["Type"] = "Auth";
            
            $ViewProcess = "ViewMain";

        $this->ShowView($ViewProcess, $Response);
    }
   
    public function ReversalBView($Request, $Url = "") {
         if (empty($Url))
            $Url = "https://www3.cenpos.net/webpaytest/viewprocess/";
            $Response = array();
            if (session_status() == PHP_SESSION_NONE)
                session_start();
            $Response["MerchantId"] = $Request->MerchantId;
            $Response["Password"] = base64_encode($Request->Password);
            $Response["Userid"] = $Request->UserId;
            $Response["Url"] = $Url;
            $Response["CallbackJS"] = $Request->CallbackJS;
            $Response["Width"] = $Request->Width;
            $Response["Height"] = $Request->Height;
            $Response["Type"] = "Reversal";
            
            $ViewProcess = "ViewMain";

        $this->ShowView($ViewProcess, $Response);
    }
   
    public function ReturnBView($Request, $Url = "") {
         if (empty($Url))
            $Url = "https://www3.cenpos.net/webpaytest/viewprocess/";
            $Response = array();
            if (session_status() == PHP_SESSION_NONE)
                session_start();
            $Response["MerchantId"] = $Request->MerchantId;
            $Response["Password"] = base64_encode($Request->Password);
            $Response["Userid"] = $Request->UserId;
            $Response["Url"] = $Url;
            $Response["CallbackJS"] = $Request->CallbackJS;
            $Response["Width"] = $Request->Width;
            $Response["Height"] = $Request->Height;
            $Response["Type"] = "Return";
            
            $ViewProcess = "ViewMain";

        $this->ShowView($ViewProcess, $Response);
    }
   
    public function ForceBView($Request, $Url = "") {
         if (empty($Url))
            $Url = "https://www3.cenpos.net/webpaytest/viewprocess/";
            $Response = array();
            if (session_status() == PHP_SESSION_NONE)
                session_start();
            $Response["MerchantId"] = $Request->MerchantId;
            $Response["Password"] = base64_encode($Request->Password);
            $Response["Userid"] = $Request->UserId;
            $Response["Url"] = $Url;
            $Response["CallbackJS"] = $Request->CallbackJS;
            $Response["Width"] = $Request->Width;
            $Response["Height"] = $Request->Height;
            $Response["Type"] = "Force";
            
            $ViewProcess = "ViewMain";

        $this->ShowView($ViewProcess, $Response);
    }

    /** :::::::::::::::::::::::PROCESS FRONT-END:::::::::::::::::::::::::::* */
//    public function RefundBackOffice($Request, $Url = "") {
//
//        $Request->TransactionType = "Refund";
//
//        return  $this->ProcessCard($Request, $Url);
//        
//    }

    public function reAuthTrxBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";

        $_original = $this->getTrxByRefNumBackOffice($Request);
        $original = (array) $_original->TransactionDetails;

        $Request1 = new reAuthTrxBackOfficeRequest();

        $Request1->MerchantId = $Request->MerchantId;
        $Request1->Password = $Request->Password;
        $Request1->UserId = $Request->UserId;
        $Request1->Amount = $original['Original_Amount'];
        $Request1->CardExpirationDate = $original['Exp_CH'];
        $Request1->CardLastFourDigits = $original['Acct_Num_CH'];
        $Request1->CustomerBillingAddress = $original['Street_CH'];
        $Request1->CustomerZipCode = $original['Zip_CH'];
        $Request1->InvoiceNumber = $original['Invoice_ID'];
        $Request1->NameOnCard = $original['Name_on_Card_VC'];
        $Request1->ReferenceNumber = $Request->ReferenceNumber;
        $Request1->TransactionType = 'ReAuth';

        $client = $this->ConnectSoapClient($Url);
        return $this->CallFunctionClient($client, "ProcessCard", $Request1);
    }

    public function getTrxByRefNumBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";

        $Request->BringLinkedHierarchy = false;
        $Request->ExcludeVoid = false;
        $Request->IncludeHeader = false;
        $Request->IncludeImage = false;
        $Request->Result = '0';
        $Request->Settle = '0';
        $Request->Processed = null;
        $Request->TransformType = 'XML';

        $client = $this->ConnectSoapClient($Url);
        
        $Response = $this->CallFunctionClient($client, "GetTrx", $Request);
        
        return $this->GetCardTrxDetailXML($Response, $type = 'GetTrx');
        
    }
    
    public function getAllCompletedTrxBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";

        $start_date = strtotime($Request->BeginDate);
        $end_date = strtotime($Request->EndDate);

        $Request->BeginDate = date('Y-m-d', $start_date);
        $Request->EndDate = date('Y-m-d', $end_date);
        $Request->BringLinkedHierarchy = false;
        $Request->ExcludeVoid = true;
        $Request->IncludeHeader = false;
        $Request->IncludeImage = false;
        $Request->Processed = null;
        $Request->TransactionType = "'SpecialForce', 'Force'";
        $Request->Processed = null;
        $Request->TransformType = 'XML';

        $client = $this->ConnectSoapClient($Url);

        $Response = $this->CallFunctionClient($client, "GetTrx", $Request);
        return $this->GetCardTrxDetailXML($Response, $type = 'GetTrx');
    }

    public function getExpiringAuthBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";

        $currentDate = date_create();
        date_sub($currentDate, date_interval_create_from_date_string('6 days'));
        $sixDaysAgo = date_format($currentDate, 'Y-m-d');
        $end_date = date('Y-m-d');

        $Request->BeginDate = $sixDaysAgo;
        $Request->EndDate = $end_date;

        return($this->getPendingAuthsBackOffice($Request, $Url));
    }

    public function getPendingAuthsBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";


        $start_date = strtotime($Request->BeginDate);
        $end_date = strtotime($Request->EndDate);

        $Request->BringLinkedHierarchy = false;
        $Request->BeginDate = date('Y-m-d', $start_date);
        $Request->EndDate = date('Y-m-d', $end_date);
        $Request->ExcludeTransactionType = 'ForcedAuthorizations';
        $Request->ExcludeVoid = true;
        $Request->IncludeHeader = false;
        $Request->IncludeImage = false;
        $Request->Processed = null;
        $Request->TransactionType = 'Auth';
        $Request->TransformType = 'XML';

        $client = $this->ConnectSoapClient($Url);

        $Response = $this->CallFunctionClient($client, "GetTrx", $Request);
        return $this->GetCardTrxDetailXML($Response, $type = 'GetTrx');
    }

    public function getVoidableTrxBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";

        $currentDate = date_create();
        date_sub($currentDate, date_interval_create_from_date_string('1 day'));
        $oneDayAgo = date_format($currentDate, 'Y-m-d');
        $end_date = date('Y-m-d');

        $Request->BeginDate = $oneDayAgo;
        $Request->EndDate = $end_date;
        $Request->BringLinkedHierarchy = false;
        $Request->ExcludeTransactionType = "'Void','Authorization', 'PartialReversal', 'SpecialPartialReversal', 'RecurringAuth'";
        $Request->ExcludeVoid = true;
        $Request->IncludeHeader = false;
        $Request->IncludeImage = false;
        $Request->Processed = null;
        $Request->TransformType = 'XML';

        $client = $this->ConnectSoapClient($Url);
        $Response = $this->CallFunctionClient($client, "GetTrx", $Request);
        return $this->GetCardTrxDetailXML($Response, 'GetTrxResult');
    }

    public function getTrxByDateBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";

        $start_date = strtotime($Request->BeginDate);
        $end_date = strtotime($Request->EndDate);

        $Request->BringLinkedHierarchy = false;
        $Request->BeginDate = date('Y-m-d', $start_date);
        $Request->EndDate = date('Y-m-d', $end_date);
        $Request->ExcludeVoid = true;
        $Request->IncludeHeader = false;
        $Request->IncludeImage = false;
        $Request->Processed = null;
        $Request->TransformType = 'XML';

        $client = $this->ConnectSoapClient($Url);

        $Response = $this->CallFunctionClient($client, "GetTrx", $Request);
        return $this->GetCardTrxDetailXML($Response, $type = 'GetTrx');
    }

    public function ForceTrxByrefNumBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";

        $req = new getTrxByRefNumBackOfficeRequest();
        $req->ReferenceNumber = $Request->ReferenceNumber;
        $req->MerchantId = $Request->MerchantId;
        $req->Password = $Request->Password;
        $req->UserId = $Request->UserId;

        $_Response = $this->getTrxByRefNumBackOffice($req);
        $originalAuth = (array) $_Response->TransactionDetails;
        $Response = "";
        if ($originalAuth) {
            $Request->CardExpirationDate = $originalAuth['Exp_CH'];
            $Request->CardLastFourDigits = $originalAuth['Acct_Num_CH'];
            $Request->CustomerBillingAddress = $originalAuth['Street_CH'];
            $Request->CustomerZipCode = $originalAuth['Zip_CH'];
            $Request->NameOnCard = $originalAuth['Name_on_Card_VC'];
            $Request->TransactionType = 'SpecialForce';

            if ($Request->Amount == null || $Request->Amount == '') {
                $Request->Amount = $originalAuth['Original_Amount'];
            }

            $client = $this->ConnectSoapClient($Url);
            $Response =  $this->CallFunctionClient($client, "ProcessCard", $Request);
            
            if($Response->ProcessCardResult == null) { return $Response; }
            if ($Response->ProcessCardResult->Message == "Commercial card" && $Response->ProcessCardResult->Result == 3)
            {
                $Request->ReferenceNumber = $Response->ProcessCardResult->ReferenceNumber;
                $response2 = $this->CallFunctionClient($client, "ProcessCreditCard", $Request);
                $Response->ProcessCardResult = $response2->ProcessCreditCardResult;
            }
            if ($Response->ProcessCardResult->Result == 21)
            {
                $BeginSecure = strpos($Response->ProcessCardResult->Message, "<SecureCode>");
                $EndSecure = strlen($Response->ProcessCardResult->Message) - $BeginSecure;

                $TextSecureCode = substr($Response->ProcessCardResult->Message,$BeginSecure, $EndSecure);

                $ResponseCardinal = simplexml_load_string($TextSecureCode);
                $ResponseCardinal = json_decode($this->xml2js($ResponseCardinal));
                if($ResponseCardinal == null) { return $Response; }
                if($ResponseCardinal->SecureCode == null) { return $Response; }
                if($ResponseCardinal->SecureCode[0] == null) { return $Response; }

                $vars = array();
                $vars["acsurl"] = $Response->ProcessCardResult->ACSUrl = $ResponseCardinal->SecureCode[0]->ACSUrl[0]->_;
                $vars["pareq"] = $Response->ProcessCardResult->Payload = $ResponseCardinal->SecureCode[0]->Payload[0]->_;
                $vars["md"] = $Response->ProcessCardResult->TransactionId = $ResponseCardinal->SecureCode[0]->TransactionId[0]->_;
                $vars["urlreturn"] = $Request->SecureReturn;
                $vars["isiframe"] = $Request->AutoProcess3D;
                if($Request->AutoProcess3D){
                    $file_path= $this->geturlpath($this->getPathView("Process3D"));
                    $vars["urlreturn"] = $file_path. "?callback=".urlencode($Request->SecureReturn);
                    $vars["height"] = "400";
                }
                $view = $this->renderPhpToString("Show3D",$vars);
                $Response->ProcessCardResult->View = $view;

                $Response->ProcessCardResult->Message = "3D Rejected";

                if (session_status() == PHP_SESSION_NONE)
                    session_start();

                $_SESSION["CardinalTransactionID"] = $vars["md"];
                $_SESSION["CardinalTransactionData"] = json_encode($Request);

            }
        } else {
            $Response = new TransactionResponse();
            $Response->ProcessCardResult = new TransactionResponse();
            $Response->ProcessCardResult->Message = $_Response->Message;
            $Response->ProcessCardResult->Result = $_Response->Result;
           
        }
        return $Response;
    }

    public function TransReportBView($Request) {
        //Gonna need to change and POST the parameters in order to secure the view and the application 
        $Response = array();

        $_url = "https://www3.cenpos.net/posintegration/posintegration-html5-reports/?type=transactions";
        $_url .= "&merchantid=" . $Request->MerchantId;
        $_url .= "&password=" . urlencode(base64_encode($Request->Password));
        $_url .= "&userid=" . $Request->UserId;
        $Response['url'] = $_url;
        $ViewProcess = "Report";
        $this->ShowView($ViewProcess, $Response);
    }

    public function ReprintReportBView($Request) {
        //Gonna need to change and POST the parameters in order to secure the view and the application
        $Response = array();

        $_url = "https://www3.cenpos.net/posintegration/posintegration-html5-reports/?type=reprint";
        $_url .= "&merchantid=" . $Request->MerchantId;
        $_url .= "&password=" . urlencode(base64_encode($Request->Password));
        $_url .= "&userid=" . $Request->UserId;
        $Response['url'] = $_url;
        $ViewProcess = "Report";
        $this->ShowView($ViewProcess, $Response);
    }

    public function VoidTrxByRefNumBackOffice($Request, $Url = "") {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/1/transact.asmx?wsdl";

        $req = new getTrxByRefNumBackOfficeRequest();
        $req->ReferenceNumber = $Request->ReferenceNumber;
        $req->MerchantId = $Request->MerchantId;
        $req->Password = $Request->Password;
        $req->UserId = $Request->UserId;
        $req->ExcludeTransactionType = "'Void','Authorization', 'PartialReversal', 'SpecialPartialReversal', 'RecurringAuth'";
        $req->BringLinkedHierarchy = false;
        $req->ExcludeVoid = true;
        $req->IncludeHeader = false;
        $req->IncludeImage = false;
        $req->Result = '0';
        $req->Settle = '0';
        $req->Processed = null;
        $req->TransformType = 'XML';
        $req->IncludeSignature = false;
        $client = $this->ConnectSoapClient($Url);
        
        $ResponseAuth = $this->CallFunctionClient($client, "GetTrx", $req);
      // print_r($ResponseAuth);
      //  die();
        $_Response =  $this->GetCardTrxDetailXML($ResponseAuth, $type = 'GetTrx');
      //  print_r($req);
     //   print_r($_Response);
      //  die();
        $originalAuth = (array) $_Response->TransactionDetails;
        
        if ($originalAuth) {
            $Request->CardExpirationDate = $originalAuth['Exp_CH'];
            $Request->CardLastFourDigits = $originalAuth['Acct_Num_CH'];
            $Request->CustomerBillingAddress = $originalAuth['Street_CH'];
            $Request->CustomerZipCode = $originalAuth['Zip_CH'];
            $Request->NameOnCard = $originalAuth['Name_on_Card_VC'];
            $Request->TransactionType = 'Refund';

            if ($Request->Amount == null) {
                $Request->Amount = $originalAuth['Original_Amount'];
            }

            $client = $this->ConnectSoapClient($Url);
            return $this->CallFunctionClient($client, "ProcessCard", $Request);
        } else {
            $Response = new TransactionResponse();
            $Response->ProcessCardResult = new TransactionResponse();
            $Response->ProcessCardResult->Message = $_Response->Message;
            $Response->ProcessCardResult->Result = $_Response->Result;
            return $Response;
        }
    }

}
?>