<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Cenpos
 * @package     Cenpos_Simplewebpay
 * @copyright   Copyright (c) 2011 Cenpos Medien GmbH & Co. KG (http://www.phoenix-medien.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;

$installer->startSetup();
$conn = $installer->getConnection();

if($this->getConnection()->tableColumnExists($this->getTable('sales/quote_payment'),'webpaycardtype')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` DROP COLUMN `webpaycardtype`;");
}       
if($this->getConnection()->tableColumnExists($this->getTable('sales/quote_payment'),'webpayrecurringsaletokenid')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` DROP COLUMN `webpayrecurringsaletokenid`;");
}  
if($this->getConnection()->tableColumnExists($this->getTable('sales/quote_payment'),'webpayistoken')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` DROP COLUMN `webpayistoken`;");
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
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'),'webpayistoken')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` DROP COLUMN `webpayistoken`;");
}      
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'),'webpayprotectedcardnumber')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` DROP COLUMN `webpayprotectedcardnumber`;");
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'),'webpaycardexpirationdate')){
        $installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` DROP COLUMN `webpaycardexpirationdate`;");        
}

$installer->run("
 
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpaycardtype` VARCHAR( 255 ) NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpayrecurringsaletokenid` TEXT  NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpayistoken` VARCHAR( 255 )  NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpayprotectedcardnumber` VARCHAR( 255 )  NULL ;
ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `webpaycardexpirationdate` VARCHAR( 255 )  NULL ;
 
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpaycardtype` VARCHAR( 255 )  NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpayrecurringsaletokenid` TEXT  NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpayistoken` VARCHAR( 255 )  NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpayprotectedcardnumber` VARCHAR( 255 )  NULL ;
ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `webpaycardexpirationdate` VARCHAR( 255 )  NULL ;
");


// Add column to grid table
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_grid'),'reference_number')){
    $this->getConnection()->dropColumn(
            $this->getTable('sales/order_grid'),
            'reference_number'
    );
    $this->getConnection()->dropKey(
        $this->getTable('sales/order_grid'),
        'reference_number',
        'reference_number'
    );
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order'),'reference_number')){
    $this->getConnection()->dropColumn(
        $this->getTable('sales/order'),
        'reference_number'
    );
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_grid'),'tokenid')){
    $this->getConnection()->dropColumn(
        $this->getTable('sales/order_grid'),
        'tokenid'
    );
    $this->getConnection()->dropKey(
        $this->getTable('sales/order_grid'),
        'tokenid',
        'tokenid'
    );
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order'),'tokenid')){
    $this->getConnection()->dropColumn(
        $this->getTable('sales/order'),
        'tokenid'
    );
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order_grid'),'customer_cenposdetails')){
    $this->getConnection()->dropColumn(
        $this->getTable('sales/order_grid'),
        'customer_cenposdetails'
    );
    $this->getConnection()->dropKey(
        $this->getTable('sales/order_grid'),
        'customer_cenposdetails',
        'customer_cenposdetails'
    );
}
if($this->getConnection()->tableColumnExists($this->getTable('sales/order'),'customer_cenposdetails')){
    $this->getConnection()->dropColumn(
        $this->getTable('sales/order'),
        'customer_cenposdetails'
    );
}

// Add key to table for this field,
// it will improve the speed of searching & sorting by the field


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

