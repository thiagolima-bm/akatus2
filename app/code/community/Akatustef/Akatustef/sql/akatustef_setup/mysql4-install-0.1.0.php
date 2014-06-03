<?php

$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

$prefix = Mage::getConfig()->getTablePrefix();


$installer->run("CREATE TABLE IF NOT EXISTS `akatus_transacoes` ( `id` INT NULL AUTO_INCREMENT ,
                                                                  `idpedido` INT NOT NULL ,
                                                                  `codtransacao` VARCHAR( 255 ) NOT NULL ,
                                                                  PRIMARY KEY ( `id` ) 
                                                                  ) ENGINE = InnoDB");

if (! $connection->tablecolumnexists($prefix . 'sales_flat_order_payment', 'check_formapagamento')) {
    $query = "ALTER TABLE `".$prefix."sales_flat_order_payment` ADD `check_formapagamento` VARCHAR(40) NOT NULL";
    $installer->run($query);
}

if (! $connection->tablecolumnexists($prefix . 'sales_flat_order_payment', 'check_tefbandeira')) {
    $query = "ALTER TABLE `".$prefix."sales_flat_order_payment` ADD `check_tefbandeira` VARCHAR(40) NOT NULL";
    $installer->run($query);
}

$installer->endSetup();
