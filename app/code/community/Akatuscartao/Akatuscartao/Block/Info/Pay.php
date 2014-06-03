<?php

class Akatuscartao_Akatuscartao_Block_Info_Pay extends Mage_Payment_Block_Info
{
    protected function _prepareSpecificInformation($transport = null) {
      
        if (null !== $this->_akatuscartaoSpecificInformation) {
            return $this->_akatuscartaoSpecificInformation;
        }
        
        $info = $this->getInfo();

        $checkBandCC = $info->getCheckCartaobandeira();

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

        if ($checkBandCC !== null) {
        
            if ($checkBandCC == "cartao_amex") {

                $numeroCartao = $info->getCheckNumerocartao();
                $last5 = substr($numeroCartao, (strlen($numeroCartao) - 5), strlen($numeroCartao));

                $numCart = "XXXX.XXXXXX." . $last5;
            } else {

                $numeroCartao = $info->getCheckNumerocartao();
                $last4 = substr($numeroCartao, (strlen($numeroCartao) - 4), strlen($numeroCartao));

                $numCart = "XXXX.XXXX.XXXX." . $last4;
            }

            $cartaoLabel = str_replace("cc_", "", $checkBandCC);

            switch ($cartaoLabel) {
                case "cartao_amex":
                    $cartao = "American Express";
                    break;
                case "cartao_elo":
                    $cartao = "Elo";
                    break;
                case "cartao_master":
                    $cartao = "Master";
                    break;
                case "cartao_diners":
                    $cartao = "Diners";
                    break;
                case "cartao_visa":
                    $cartao = "Visa";
                    break;
            }

            echo ("<table>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Cartão: </strong>{$cartao}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Nome: </strong>{$info->getCheckNome()}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>CPF: </strong>{$info->getCheckCpf()}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Número do Cartão: </strong>{$info->getCheckNumerocartao()}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Número de Parcelas: </strong>{$info->getCheckParcelamento()}
                                </td>
                            </tr>
                        </tbody>
                    </table>");


            if ($this->isToShowRefund($info->getOrder())) {
             
                $estornoURL = $this->getEstornoURL($info->getOrder()->getId());
             
                echo ("<table>
                           <tbody>                                                                                                                                                                                
                                 <tr>
                                     <td><button onclick=this.disabled='disabled';window.location.href='$estornoURL'>Solicitar estorno</button></td>
                                 </tr>
                             </tbody>
                         </table>");
            }
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
