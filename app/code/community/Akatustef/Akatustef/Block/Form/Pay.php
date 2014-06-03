<?php

class Akatustef_Akatustef_Block_Form_Pay extends Akatusbase_Akatusbase_Block_Form_Pay
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('akatustef/form/pay.phtml');
	}
}
