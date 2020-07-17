<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AddCryptoTokenRequest
 *
 * @author JuanCamilo
 */
class AddCryptoTokenRequest extends CommonRequest {

    public $CardNumber = "";
    public $NameOnCard = "";
    public $CardExpirationDate = "";
    public $CardVerificationNumber = "";

}