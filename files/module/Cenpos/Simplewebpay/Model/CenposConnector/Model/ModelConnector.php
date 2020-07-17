<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ModelConnector
 *
 * @author JuanCamilo
 */
abstract Class ModelConnector {

    const ModelFolder = "Model";
    const VOFolder = "Variable_Object";
    const ViewFolder = "View";
    const BridgeConnector = "BridgeViewCenposConnector";

    protected function ConnectSoapClient($url) {
        
        try{
            $client = new SoapClient($url);
        } catch (Exception $ex) {
           echo $ex->getMessage();
           die();
        }
        return $client;
    }

    protected function CallFunctionClient($client, $function, $request) {
        $params = array();

        $getNameMethod = get_class($request);
        $getClassMethod = get_class_vars($getNameMethod);
        $soapParams = Array();
       // print_r($request);
        foreach ($request as $RequestVO => $ValueVO) {
        //    echo gettype($request->$RequestVO)." ".$RequestVO."=".$ValueVO."\n";
            if ($ValueVO === "Jump")
                continue;
            if (gettype($request->$RequestVO) != NULL) {
                if (is_bool($request->$RequestVO)) {
                    $soapParams[$RequestVO] = ($request->$RequestVO === true) ? "true": "false";
                }if (!empty($request->$RequestVO))
                    $soapParams[$RequestVO] = $request->$RequestVO;
                else
                    $soapParams[$RequestVO] = $ValueVO;
            }else if (gettype($ValueVO) != NULL)
                $soapParams[$RequestVO] = $ValueVO;
        }
       
        $SendRequest = Array('request' => $soapParams);
        try{
           $response = $client->$function($SendRequest);
        }  catch (Exception $e){
           $response->Message = $e->getMessage();
           $respone->Result = $e->getLine();
        }
        
        return $response;
    }

    protected function GetTranx($Request, $Url) {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/transact.asmx?wsdl";

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "GetCardTrx", $Request);
    }

    protected function ProcessCard($Request, $Url) {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/transact.asmx?wsdl";

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "ProcessCard", $Request);
    }
    
     protected function ProcessCreditCard($Request, $Url) {
        if (empty($Url))
            $Url = "https://ww3.cenpos.net/6/transact.asmx?wsdl";

        $client = $this->ConnectSoapClient($Url);

        return $this->CallFunctionClient($client, "ProcessCreditCard", $Request);
    }

    protected function GetCardTrxDetailXML($response, $type = '') {
        if ($type == '') {
            $string = $response->GetCardTrxResult->TransactionDetails;
        } else {
            $string = $response->GetTrxResult->TransactionDetails;
        }
        return simplexml_load_string($string);
    }

    protected function ShowView($view, $vars) {
        $path = $this->getPathView($view);
        if(!$path) { return $path; }

        //Si hay variables para asignar, las pasamos una a una.

        if (is_array($vars)) {
            //Para poder utilizar las variables dentro de los modulos
            $this->vars_glob = $vars;

            foreach ($vars as $key => $value) {
                $$key = $value;
            }
        }
        include($path);
    }

    protected function ShowData($vars) {
        echo json_encode($vars);
    }

    protected function GetUrlBridge() {
        $currentURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
        $currentURL .= $_SERVER["SERVER_NAME"];

        if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") {
            $currentURL .= ":" . $_SERVER["SERVER_PORT"];
        }
        //$currentURL = str_replace("/", "\\", $currentURL);

        $origin = __FILE__;
        $origin = str_replace("\\", "/", $origin);
        $Name = basename($origin);
        $path = str_replace("Model/" . $Name, self::BridgeConnector . ".php", $origin);

        $currentURL = $currentURL . str_replace(str_replace("\\", "/", $_SERVER['DOCUMENT_ROOT']), '', $path);

        return $currentURL;
    }
    protected function xml2js($xmlnode) {
        $root = (func_num_args() > 1 ? false : true);
        $jsnode = array();

        if (!$root) {
            if (count($xmlnode->attributes()) > 0) {
                $jsnode["$"] = array();
                foreach ($xmlnode->attributes() as $key => $value)
                    $jsnode["$"][$key] = (string) $value;
            }

            $textcontent = trim((string) $xmlnode);
            if (count($textcontent) > 0)
                $jsnode["_"] = $textcontent;

            foreach ($xmlnode->children() as $childxmlnode) {
                $childname = $childxmlnode->getName();
                if (!array_key_exists($childname, $jsnode))
                    $jsnode[$childname] = array();
                array_push($jsnode[$childname], $this->xml2js($childxmlnode, true));
            }
            return $jsnode;
        } else {
            $nodename = $xmlnode->getName();
            $jsnode[$nodename] = array();
            array_push($jsnode[$nodename], $this->xml2js($xmlnode, true));
            return json_encode($jsnode);
        }
    }
    protected function renderPhpToString($view, $vars=null)
    {
        $path = $this->getPathView($view);
        if(!$path) { return $path; }
        
        if (is_array($vars)) {
            foreach ($vars as $key => $value) {
                $$key = $value;
            }
        }
        ob_start();
        include $path;
        return ob_get_clean();
    }
    
   protected function getPathView($view){
        $origin = __FILE__;
        $origin = str_replace("\\", "/", $origin);
        $Name = basename($origin);
        $FolderPath = str_replace("Model/" . $Name, "", $origin);

        $path = $FolderPath . "View/$view.php";

        if (file_exists($path) == false) {
            trigger_error('View `' . $path . '` does not exist.', E_USER_NOTICE);
            return false;
        }
        
        return $path;
    }
    
    protected  function geturlpath($file){
        $filePath = str_replace('\\','/',$file);
        $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true : false;
        $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = $_SERVER['SERVER_PORT'];
        $stringPort = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $fileUrl = str_replace($_SERVER['DOCUMENT_ROOT'] ,$protocol . '://' . $host , $filePath);
        
        return $fileUrl;
    }
}
