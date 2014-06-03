<?php

$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

$prefix = Mage::getConfig()->getTablePrefix();

if (! $connection->tableColumnExists($prefix . 'sales_flat_order_payment', 'check_codtransacao')) {
    $query = "ALTER TABLE `".$prefix."sales_flat_order_payment` ADD `check_codtransacao` VARCHAR(255) NOT NULL";
    $installer->run($query);
}

$installer->endSetup();