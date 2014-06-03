<?php
 
class Akatusboleto_Akatusboleto_Model_Pagar extends Akatusbase_Akatusbase_Model_Pagar
{
    public function __construct()
    {
        parent::__construct();
        $this->_code = 'akatusboleto';
        $this->_formBlockType = 'akatusboleto/form_pay';
        $this->_infoBlockType = 'akatusboleto/info_pay';
    }

    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
        $order = $this->getInfoInstance()->getOrder();

        Mage::getModel('core/resource_transaction')
            ->addObject($order)
            ->save();

        $xml_gateway = $this->gerarXML($order);

		return $this->enviaGateway($order, $xml_gateway);
    }

    public function assignData($data) {
        if (! ($data instanceof Varien_Object)) {
    		$data = new Varien_Object($data);
    	}
    	$info = $this->getInfoInstance();
    
    	$info->setCheckFormapagamento("boleto");
    
    	return $this;
    }
 	
    public function validate() {
    	parent::validate();
    
    	$info = $this->getInfoInstance();
    	$formapagamento="boleto";
        
    	#verifica se a forma de pagamento foi selecionada
    	if(empty($formapagamento)) {
    		$errorCode = 'invalid_data';
    		$errorMsg = $this->_getHelper()->__('Selecione uma forma de pagamento');
    
    		#gera uma exception caso nenhuma forma de pagamento seja selecionada
    		Mage::throwException($errorMsg);
    	}
    
    	return $this;
    }    
	
	public function gerarXML($order) {
		$xml = "";
        $incrementId = $order->getIncrementId();
                
		$customer = Mage::getSingleton('customer/session')->getCustomer();
        $billingId = $order->getBillingAddress()->getId();
        $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
        
        if ($customerAddressId) {
           $address = Mage::getModel('customer/address')->load($customerAddressId);
        } else {           
           $address = Mage::getModel('sales/order_address')->load($billingId);              
        }
		
        if (str_replace(' ', '', $order->customer_firstname) !== "") {
            $customer_nome = $order->customer_firstname . " ".$order->customer_lastname;

        } else if (str_replace(' ', '', $customer->getName()) !== "") {
    		$customer_nome = $customer->getName();

        } else {
            $customer_nome = $_POST['billing']['firstname'] . " " . $_POST['billing']['lastname'];
        }
   	
    	$customer_email = $order->customer_email;
    	if ($customer_email=="") {
    		$customer_email = $customer->getEmail();
    	}
    	
        $storeId = Mage::app()->getStore()->getId();

		$xml = '<?xml version="1.0" encoding="UTF-8"?>
		<carrinho>			
			<recebedor>
			<api_key>'.Mage::getModel('akatusbase/pagar')->getConfigData('api_key', $storeId).'</api_key>
			<email>'.Mage::getModel('akatusbase/pagar')->getConfigData('email_gateway', $storeId).'</email>
			</recebedor>';
			
			$consumer_tel = $address->getData("telephone");
			$consumer_tel = preg_replace('([^0-9])', '', $consumer_tel);
            $isValidTelephone = $this->isTelephoneValid($consumer_tel);

            $isValidConsumer_fax = false;            
            $consumer_fax = $address->getData("fax");
            $consumer_fax = preg_replace('([^0-9])', '', $consumer_fax);
            if(!empty($consumer_fax))
                $isValidConsumer_fax = $this->isTelephoneValid($consumer_fax);;
            
            $isValidConsumer_cel = false;            
            $consumer_cel = $address->getData("celular");
            $consumer_cel = preg_replace('([^0-9])', '', $consumer_cel);
            if(!empty($consumer_cel))
                $isValidConsumer_cel = $this->isTelephoneValid($consumer_cel);;

            $xml.='
			<pagador>
				<nome>'.$customer_nome.'</nome>
				<email>'.$customer_email.'</email>';

            $logradouro = $address->getData("street");
            $numero = "0";
            $complemento = "Não Informado";
            $bairro = "Vide Logradouro";
            
            $mg_cidade = $address->getData("city");
            $mg_estado = $this->stringToUf($address->getData("region"));
            $mg_cep = $address->getData("postcode");

            $cidade = empty($mg_cidade) ? "Não Informado" : $mg_cidade;
            $estado = empty($mg_estado) ? "SP" : $mg_estado;
            $cep    = empty($mg_cep) ? "12345678" : $mg_cep;
		
			$xml .= '<enderecos>
				<endereco>
					<tipo>entrega</tipo>
						<logradouro>'.$logradouro.'</logradouro>
						<numero>'.$numero.'</numero>
						<complemento>'.$complemento.'</complemento>
						<bairro>'.$bairro.'</bairro>
						<cidade>'.$cidade.'</cidade>
						<estado>'.$estado.'</estado>
						<pais>BRA</pais>
						<cep>'.$cep.'</cep>
				   </endereco>
				</enderecos>';
				
			$xml .= '<telefones>';
                $xml .='<telefone>
                            <tipo>residencial</tipo>
                            <numero>'.$consumer_tel.'</numero>
                        </telefone>';

                    if(!empty($isValidConsumer_cel))
                        $xml.='<telefone>
                                <tipo>celular</tipo>
                                <numero>'.$consumer_cel.'</numero>
                            </telefone>';
                
                    if(!empty($isValidConsumer_fax))
                        $xml.='<telefone>
                                <tipo>fax</tipo>
                                <numero>'.$consumer_fax.'</numero>
                            </telefone>';
                
                $xml .= '</telefones>';
            $xml .= '</pagador>';
			
			$items = $order->getAllVisibleItems();
			$xml .= '
			<!-- Produtos -->
			<produtos>';
                        
            $totalItens= sizeof($items);                    

            $valorTotal = '';
            $freteTotal = '';
            $nome = '';
            $quantidadeTotal = '';
            $pesoTotal = '';
            $desc = '';
            $codigo = '';

            foreach ($items as $itemId => $item) {
                $valorTotal      += number_format($item->getPrice()*$item->getQtyToInvoice(),2,'.','');
                $freteTotal      += round( ($order->base_shipping_incl_tax/$order->total_item_count/$item->getQtyToInvoice()), 2);
                $quantidadeTotal += $item->getQtyToInvoice();
                $pesoTotal       += $item->getWeight();
                $cod              = str_replace("-","",$item->getSku());

                $preco_item = number_format($item->getPrice(), 2, '', '');
                $peso_item = number_format($item->getWeight(), 2, '', '');

                $xml .='<produto>
                            <codigo>'.$cod.'</codigo>
                            <descricao><![CDATA['.$item->getName().']]></descricao>
                            <quantidade>'.$item->getQtyToInvoice().'</quantidade>
                            <preco>'.$preco_item.'</preco>
                            <peso>'.$peso_item.'</peso>
                            <frete>0</frete>
                            <desconto>0</desconto>
                        </produto>';
            }

            $descontoTotal = abs(number_format($order->discount_amount,'2','.',''));

            $_totalData =$order->getData();
            $_grand = number_format($_totalData['grand_total'],2,'.', '');

            if(empty($_grand)) {
                 $_grand = number_format($valorTotal-$descontoTotal, 2, '.', '');
            }
                       
			$xml.='</produtos>';
			
			$info = $this->getInfoInstance();  
			$formapagamento='boleto';
			
			$xml_forma_pagamento='<meio_de_pagamento>'.$formapagamento.'</meio_de_pagamento>';
			
			$transacao_freteTotal = number_format($order->base_shipping_incl_tax, 2, '.', '');
            $transacao_descontoTotal = number_format($descontoTotal, 2, '.', '');
            $transacao_pesoTotal = number_format($pesoTotal, 2, '.', '');

            $fingerprint_akatus = isset($_POST['fingerprint_akatus']) ? $_POST['fingerprint_akatus'] : '';
            $fingerprint_partner_id = isset($_POST['fingerprint_partner_id']) ? $_POST['fingerprint_partner_id'] : '';

            $ipv4_address = filter_var($order->getRemoteIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

            $xml.='
                <!-- Transacao -->
                <transacao>
                    '.$xml_forma_pagamento.'
                    <!-- Dados do checkout -->
                    <moeda>BRL</moeda>
                    <frete>'.$transacao_freteTotal.'</frete> 
                    <desconto>'.$transacao_descontoTotal.'</desconto>
                    <peso>'.$transacao_pesoTotal.'</peso> 
                    <referencia>'.$incrementId.'</referencia>				
                    <fingerprint_akatus>'.$fingerprint_akatus.'</fingerprint_akatus>				
                    <fingerprint_partner_id>'.$fingerprint_partner_id.'</fingerprint_partner_id>				
                    <ip>'. $ipv4_address .'</ip>
                </transacao>';
                        
                        
		$xml.='</carrinho>';

		return $xml;
	}

	public function enviaGateway($order, $xml) {
		$orderId = $order->getId();
		
		$url = $this->getCarrinhoUrl();

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,$url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($curl);
		curl_close($curl);
                
		$data = $this->xml2array($ret);
		
		if(Mage::getModel('akatusbase/pagar')->getConfigData('module_debug', Mage::app()->getStore()->getId())){
            Mage::throwException("XML RECEBIDO:\n\n".$ret."\n\n\nXML Enviado:\n".$xml);
		}
                
        Mage::Log("..:: ENVIADO ::..\n\n". $xml ."\n\n ..:: RECEBIDO ::..\n\n".$ret);

		$info = $this->getInfoInstance();
        $resposta = $data["resposta"]["status"]["value"];
		 
		if($resposta == "erro") {

            $stateAndStatus = Mage_Sales_Model_Order::STATE_CANCELED;
            $order->setState($stateAndStatus, $stateAndStatus);
            $order->setStatus($stateAndStatus);
            $order->save();

            Mage::Log('Um erro ocorreu ao efetuar transação: '.$data["resposta"]["descricao"]["value"]);
			Mage::throwException("Não foi possível realizar a transação.");
            
		} else {
			$info->setCheckCodtransacao($data["resposta"]["transacao"]["value"]);
            $info->save();

            if($resposta == "Em Análise"){

                try {
                    $this->protectCardNumber($info);
                    $transacaoId = $data["resposta"]["transacao"]["value"];
                    $this->SalvaIdTransacao($orderId,$transacaoId);

                    $stateAndStatus = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
                    $order->setState($stateAndStatus, $stateAndStatus);
                    $order->setStatus($stateAndStatus);
                    $order->save();
                    
                    $msg = "Seu pedido foi realizado com sucesso. Estamos aguardando a confirmação de sua administradora e assim que o pagamento for liberado enviaremos o produto.";
                    Mage::getSingleton('checkout/session')->addSuccess(Mage::helper('checkout')->__($msg));
                    
				} catch (Exception $e){
                    Mage::Log($e->getMessage());
				}

			} else if ($resposta == "Aguardando Pagamento" || $resposta == "Processando"){

                $url_base = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
				
					$url_destino = $this->getBoletoUrl();
					$str = $data['resposta']['transacao']['value'];
					$url_destino .= base64_encode($str).'.html';
					
					$info->setCheckBoletourl($url_destino);
                    $info->save();

					$transacaoId = $data["resposta"]["transacao"]["value"];
					$this->SalvaIdTransacao($orderId, $transacaoId);
					
					$msg='Transação realizada com sucesso. Clique no botão abaixo para imprimir seu boleto.<br/>';
                    $msg.="<a href='".$url_destino."' target='_blank'><img src='" . $url_base ."skin/frontend/default/default/images/boleto.gif' /></a>";
					
					Mage::getSingleton('checkout/session')->addSuccess(Mage::helper('checkout')->__($msg));   
			}
		}
	}
	
	public function SalvaIdTransacao($orderId, $transacaoId) {
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');	
		$db->query("DELETE FROM akatus_transacoes WHERE idpedido='".$orderId."'");
		$db->query("INSERT into akatus_transacoes (idpedido,codtransacao) VALUES('".$orderId."','".$transacaoId."')");
    }
}
