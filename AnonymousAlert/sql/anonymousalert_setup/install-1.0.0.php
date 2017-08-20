<?php
/**
 * AnonymousAlert install
 *
 * @category    YourSite
 * @package     YourSite_AnonymousAlert
 * @author      Harl
 */
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();
/**
 * Create table 'anonymousalert/stock'
 */
 $table = $installer->getConnection()
    ->newTable($installer->getTable('anonymousalert/stock'))
    ->addColumn('alert_stock_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Product alert stock id')
	->addColumn('alert_email', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		'nullable'  => false,
        ), 'Alert email')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Product id')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Website id')
    ->addColumn('add_date', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable'  => false,
        ), 'Product alert add date')
    ->addColumn('send_date', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        ), 'Product alert send date')
    ->addColumn('send_count', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Send Count')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Product alert status')
    ->addIndex($installer->getIdxName('anonymousalert/stock', array('product_id')),
        array('product_id'))
    ->addIndex($installer->getIdxName('anonymousalert/stock', array('website_id')),
        array('website_id'))
    ->addForeignKey($installer->getFkName('anonymousalert/stock', 'website_id', 'core/website', 'website_id'),
        'website_id', $installer->getTable('core/website'), 'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey($installer->getFkName('anonymousalert/stock', 'product_id', 'catalog/product', 'entity_id'),
        'product_id', $installer->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Anonymous Alert Stock');
$installer->getConnection()->createTable($table);

$installer->endSetup();
?>