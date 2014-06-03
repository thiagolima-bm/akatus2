<?php

class Akatuscartao_Akatuscartao_Block_Form_Pay extends Akatusbase_Akatusbase_Block_Form_Pay
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('akatuscartao/form/pay.phtml');
	}
}
