<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GetListTokenRequest
 *
 * @author JuanCamilo
 */
class GetListTokenRequest extends CommonRequest{
    public $CardNumber;
    public $CustomerCode;
    public $EmailAddress;
    public $IncludeMultipleMerchants = false;
    public $IncludeSignature = false;
}
