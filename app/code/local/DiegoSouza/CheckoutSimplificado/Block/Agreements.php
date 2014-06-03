<?php
class DiegoSouza_CheckoutSimplificado_Block_Agreements extends Mage_Core_Block_Template
{
    public function getAgreements()
    {
        if (!$this->hasAgreements())
        {
        	$agre = array();
            if (Mage::getStoreConfigFlag('checkoutsimplificado/agreements/enabled'))
            {
                $agre = Mage::getModel('checkout/agreement')->getCollection()
                    										->addStoreFilter(Mage::app()->getStore()->getId())
                    										->addFieldToFilter('is_active', 1);
                
            }
			$this->setAgreements($agre);            
        }
        return $this->getData('agreements');
    }
}
