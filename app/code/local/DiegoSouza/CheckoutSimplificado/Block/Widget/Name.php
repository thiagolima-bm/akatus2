<?php
class DiegoSouza_CheckoutSimplificado_Block_Widget_Name extends Mage_Customer_Block_Widget_Name
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('checkoutsimplificado/widget/name.phtml');
    }
}
