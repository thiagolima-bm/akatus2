<?php

class Akatusbase_Akatusbase_Adminhtml_Model_Modo
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'SANDBOX', 'label' => 'Sandbox'),
      array('value' => 'PRODUCAO', 'label' => 'Produção'),
    );
  }
}
