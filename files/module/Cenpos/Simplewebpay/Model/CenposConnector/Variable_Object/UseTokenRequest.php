<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UseTokenRequest
 *
 * @author JuanCamilo
 */
class UseTokenRequest extends CommonRequest {
    public $TokenId;
    public $Amount;
    public $CardVerificationNumber = "";
    public $TransactionType = "Sale";
    public $InvoiceNumber = "";
    public $CurrencyCode = "";
    public $InvoiceDetail = "TransactionType:Sale";
    public $GeoLocationInformation = "ReplacementIp:";

}
