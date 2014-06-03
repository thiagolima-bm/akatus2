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
    $query = "alter table `".$prefix."sales_flat_order_payment` add `check_formapagamento` varchar(40) not null";
    $installer->run($query);
}

if (! $connection->tablecolumnexists($prefix . 'sales_flat_order_payment', 'check_cartaobandeira')) {
    $query = "alter table `".$prefix."sales_flat_order_payment`
                     add `check_cartaobandeira` varchar(20) not null,
                     add `check_nome` varchar(200) not null,
                     add `check_cpf` varchar(30) not null,
                     add `check_numerocartao` varchar(20) not null,
                     add `check_parcelamento` varchar(10) not null";

    $installer->run($query);
}

$installer->endSetup();
