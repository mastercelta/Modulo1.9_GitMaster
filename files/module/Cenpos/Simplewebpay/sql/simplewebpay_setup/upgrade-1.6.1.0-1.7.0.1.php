<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$conn = $installer->getConnection();
if($this->getConnection()->tableColumnExists($this->getTable('sales/quote_payment'),'webpaycardtype')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` DROP COLUMN `webpaycardtype`;");
}       
if($this->getConnection()->tableColumnExists($this->getTable('sales/quote_payment'),'webpayrecurringsaletokenid')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` DROP COLUMN `webpayrecurringsaletokenid`;");
}        
if($this->getConnection()->tableColumnExists($this->getTable('sales/quote_payment'),'webpayprotectedcardnumber')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` DROP COLUMN `webpayprotectedcardnumber`;");
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/quote_payment'),'webpaycardexpirationdate')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` DROP COLUMN `webpaycardexpirationdate`;");        
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'),'webpaycardtype')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` DROP COLUMN `webpaycardtype`;");
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'),'webpayrecurringsaletokenid')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` DROP COLUMN `webpayrecurringsaletokenid`;");
}        
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'),'webpayprotectedcardnumber')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` DROP COLUMN `webpayprotectedcardnumber`;");
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'),'webpaycardexpirationdate')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` DROP COLUMN `webpaycardexpirationdate`;");        
}

$installer->run("
 
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpaycardtype` VARCHAR( 255 ) NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpayrecurringsaletokenid` VARCHAR( 255 ) NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpayprotectedcardnumber` VARCHAR( 255 ) NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpaycardexpirationdate` VARCHAR( 255 ) NULL ;
 
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpaycardtype` VARCHAR( 255 ) NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpayrecurringsaletokenid` VARCHAR( 255 ) NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpayprotectedcardnumber` VARCHAR( 255 ) NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpaycardexpirationdate` VARCHAR( 255 ) NULL ;
");

$select = $conn
    ->select()
    ->from($this->getTable('core/config_data'), array('scope', 'scope_id', 'path', 'value'))
    ->where(new Zend_Db_Expr("path LIKE 'simplewebpay/simplewebpay%'"));
$data = $conn->fetchAll($select);

if (!empty($data)) {
    foreach ($data as $key => $value) {
        $data[$key]['path'] = preg_replace('/^simplewebpay\/simplewebpay/', 'payment/simplewebpay', $value['path']);
    }
    $conn->insertOnDuplicate($this->getTable('core/config_data'), $data, array('path'));
    $conn->delete($this->getTable('core/config_data'), new Zend_Db_Expr("path LIKE 'simplewebpay/simplewebpay%'"));
}

$installer->endSetup();
