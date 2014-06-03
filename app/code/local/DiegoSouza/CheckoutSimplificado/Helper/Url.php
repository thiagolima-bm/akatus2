<?php
class DiegoSouza_CheckoutSimplificado_Helper_Url extends Mage_Checkout_Helper_Url
{
    public function getCheckoutUrl()
    {
        return $this->_getUrl('checkoutsimplificado', array('_secure'=>true));
    }
}
