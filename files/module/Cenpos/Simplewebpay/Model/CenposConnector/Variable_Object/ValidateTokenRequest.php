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
class ValidateTokenRequest extends CommonRequest{
    public $CardNumber = "";
    public $Amount = "0";
    public $InvoiceDetail = "TransactionType:Sale";
    public $InvoiceNumber = "Invoice";
    public $TaxAmount = "0";
    public $TransactionType = "Sale";
    
    public function __construct() {
        $this->InvoiceNumber = rand() . "Invoice";
    }
}

