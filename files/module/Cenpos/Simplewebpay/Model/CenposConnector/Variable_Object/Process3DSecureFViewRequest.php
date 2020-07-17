<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Process3DSecureFViewRequest
 *
 * @author JuanCamilo
 */
class Process3DSecureFViewRequest extends CommonRequest {
    public $CustomerCode;
    public $CardNumber;
    public $NameOnCard;
    public $CardExpirationDate;
    public $CustomerBillingAddress;
    public $Amount;
    public $CustomerEmailAddress;
    public $InvoiceNumber;
    public $CustomerZipCode;
    public $TransactionType = "Sale";
    public $NumberProcess;
}
