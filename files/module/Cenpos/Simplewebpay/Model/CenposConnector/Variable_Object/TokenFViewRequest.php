<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TokenFViewRequest
 *
 * @author JuanCamilo
 */
class TokenFViewRequest{
    public $IsCVV = false;
    public $OnlyForm = false;
    public $Email = "";
    public $Merchant;
    public $CallbackJS;
    public $CustomerBillingAddress = "";
    public $CustomerZipCode = "";
    public $SubmitAction;
    public $Width = "100%";
    public $Height = "234";
    public $CustomerCode = "";
    public $SessionToken = "true";
    public $SecretKey = "";
}
