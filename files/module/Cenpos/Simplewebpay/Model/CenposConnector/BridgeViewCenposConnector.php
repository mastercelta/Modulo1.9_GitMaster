<?php

$origin = __FILE__;
$Name = basename($origin);
$FolderPath = str_replace($Name, "", $origin);

include_once $FolderPath . "Model\\ModelConnector.php";
include_once $FolderPath . "CenposConnector.php";

CenposConnector::Init();

$value = $_REQUEST["ClassName"] . "Request";
$Method = $_REQUEST["ClassName"];

$getClassMethod = get_class_vars($value);
$Request = new $value();

foreach ($getClassMethod as $RequestVO => $ValueVO) {
    if (gettype($Request->$RequestVO) != NULL) {
        if(isset($_REQUEST[$RequestVO])) $Request->$RequestVO = $_REQUEST[$RequestVO];
    }
}
$CenposConnector = new CenposConnector();
$CenposConnector->$Method($Request);
?>
