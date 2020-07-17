<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ValidateTokenRequest
 *
 * @author JuanCamilo
 */
class UseCryptoTokenRequest extends CommonRequest{
    public $CardNumber = "";
    public $Amount = "";
    public $InvoiceDetail = "TransactionType:Sale";
    public $InvoiceNumber = "";
    public $TaxAmount = "0";
    public $TransactionType = "Sale";
    public $SecureReturn = "#";
    public $AutoProcess3D = false;
    public $CustomerZipCode;
    public $CustomerEmail;
    public $CustomerBillingAddress;
    
    public function __construct() {
    }
}

