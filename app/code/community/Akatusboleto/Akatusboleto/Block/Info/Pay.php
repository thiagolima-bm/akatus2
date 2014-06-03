<?php

class Akatusboleto_Akatusboleto_Block_Info_Pay extends Mage_Payment_Block_Info
{

    protected function _prepareSpecificInformation($transport = null)
	{

        if (null !== $this->_akatusboletoSpecificInformation) {
			return $this->_akatusboletoSpecificInformation;
		}

		$info = $this->getInfo();

        $checkCodtransacao = $info->getCheckCodtransacao();
        if ( ! empty($checkCodtransacao)) {

            echo ("<table>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>ID Transação: </strong>{$info->getCheckCodtransacao()}<br>
                                </td>
                            </tr>
                        </tbody>
                    </table>");
        }

        $checkBoletourl = $info->getCheckBoletourl();
        if ( ! empty($checkBoletourl)) {

            echo ("<table>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Segunda Via: </strong><a href = '{$info->getCheckBoletourl()}' target='_blank'>Imprimir</a><br>
                                </td>
                            </tr>
                        </tbody>
                    </table>");
        }

        $isToShowRefund = $this->isToShowRefund($info->getOrder());
        if ($isToShowRefund) {
         
            $estornoURL = $this->getEstornoURL($info->getOrder()->getId());
         
            echo ("<table>
                       <tbody>                                                                                                                                                                                
                             <tr>
                                 <td><button onclick=this.disabled='disabled';window.location.href='$estornoURL'>Solicitar estorno</button></td>
                             </tr>
                         </tbody>
                     </table>");
        }

		$transport = new Varien_Object();
		return parent::_prepareSpecificInformation($transport);
	}

    private function isToShowRefund($order) 
    {
        if (isset($order)) {

            $adminSession = Mage::getSingleton('admin/session', array('name' => 'adminhtml'));
            $isAdmin = $adminSession->isLoggedIn();
            $state = $order->getState();

            if ($isAdmin && ($state === Mage_Sales_Model_Order::STATE_COMPLETE || $state === Mage_Sales_Model_Order::STATE_PROCESSING)) {
                return true;
            }
        }

        return false;
    }

    private function getEstornoURL($orderId)
    {
        return Mage::helper("adminhtml")->getUrl("akatusbase/refund/index", array("order" => $orderId));
    }
}
