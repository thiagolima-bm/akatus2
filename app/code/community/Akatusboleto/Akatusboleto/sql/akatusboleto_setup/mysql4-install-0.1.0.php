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


if (! $connection->tableColumnExists($prefix . 'sales_flat_order_payment', 'check_formapagamento')) {
    $query = "ALTER TABLE `".$prefix."sales_flat_order_payment` ADD `check_formapagamento` VARCHAR(40) NOT NULL";
    $installer->run($query);
}

if (! $connection->tableColumnExists($prefix . 'sales_flat_order_payment', 'check_boletourl')) {
    $query = "ALTER TABLE `".$prefix."sales_flat_order_payment` ADD `check_boletourl` VARCHAR(200) NOT NULL";
    $installer->run($query);
}

$installer->endSetup();
