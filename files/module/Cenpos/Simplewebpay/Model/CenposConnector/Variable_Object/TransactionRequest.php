<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TransactionRequest
 *
 * @author JuanCamilo
 */
abstract class TransactionRequest extends CommonRequest{
      public $BeginDate = "";
      public $EndDate = "";
      public $BringLinkedHierarchy = false;
      public $ExcludeTransactionType = "";
      public $ExcludeVoid = false; 
      public $IncludeHeader = false;
      public $IncludeSignature = false;
      public $Settle = 0;
      public $Result = 0;
      public $TransformType = "XML";
}
