<?php
 
class Akatuscartao_Akatuscartao_Model_Pagar extends Akatusbase_Akatusbase_Model_Pagar
{
    public function __construct()
    {
        parent::__construct();
        $this->_code = 'akatuscartao';
        $this->_formBlockType = 'akatuscartao/form_pay';
        $this->_infoBlockType = 'akatuscartao/info_pay';
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
    
    	$info->setCheckCartaobandeira($data->getCheckCartaobandeira())
    	->setCheckNome($data->getCheckNome())
    	->setCheckCpf($data->getCheckCpf())
    	->setCheckNumerocartao($data->getCheckNumerocartao())
    	->setCheckExpiracaomes($data->getCheckExpiracaomes())
    	->setCheckExpiracaoano($data->getCheckExpiracaoano())
    	->setCheckCodseguranca($data->getCheckCodseguranca())
    	->setCheckParcelamento($data->getCheckParcelamento())
    	->setCheckFormapagamento("cartaodecredito");
    
    	return $this;
    }
 	
    public function validaNumeroDoCartao($numeroCartao, $codseg, $cartaobandeira) {
        
        $isValid = true;
        switch($cartaobandeira){
            
            case "cartao_amex":
                $prefix = substr($numeroCartao, 0,1);                
                if($prefix != "3"){
                    $isValid = false;                             
                } else if(strlen($numeroCartao) != 15){
                    $isValid = false;                            
                } else if(strlen($codseg) != 4){                            
                    $isValid = false;
                }

                break;
                
            case "cartao_diners":
                $prefix = substr($numeroCartao, 0,1);
                if($prefix != "3"){
                    $isValid = false;                            
                }else if(strlen($numeroCartao) != 14){
                    $isValid = false;
                }else if(strlen($codseg) != 3){                           
                    $isValid = false;
                }

                break;
                
            case "cartao_master":               
                $prefix = substr($numeroCartao, 0,1);       
                if($prefix != "5"){
                    $isValid = false;                             
                }else  if(strlen($numeroCartao) != 16){
                    $isValid = false;                           
                }else if(strlen($codseg) != 3){
                    $isValid = false;
                }

                break;
                
            case "cartao_visa":
                $prefix = substr($numeroCartao, 0,1);                   
                if($prefix != "4"){
                    $isValid = false;                            
                }else if(strlen($numeroCartao) != 13 && strlen($numeroCartao) != 16){
                    $isValid = false;                               
                }else  if(strlen($codseg) != 3){                           
                    $isValid = false;  
                }

            break;
            
            case "cartao_elo":
                break;
            
            default:	
                
        }	

        return $isValid;
    }
    
    public function validaDataCartaoDeCredito($mes, $ano) {
        $anoAtual = date("y");        
        $mesAtual = date("m");
        
        $dataAtual = (int)($anoAtual . "" . $mesAtual);
        $dataInformada = (int)($ano . "" . $mes);
        $isValid = true;
        
        if($dataInformada < $dataAtual){
            $isValid = false;
        } 
        
        return $isValid;
    }
    
    public function validate() {
    	parent::validate();
    
    	$info = $this->getInfoInstance();
    
    	$cartaobandeira     = str_replace("cc_", "", $info->getCheckCartaobandeira());
        $nome               = $info->getCheckNome();
    	$cpf                = $info->getCheckCpf();
    	$numerocartao       = $info->getCheckNumerocartao();
    	$expiracaomes       = $info->getCheckExpiracaomes();
    	$expiracaoano       = $info->getCheckExpiracaoano();
    	$codseguranca       = $info->getCheckCodseguranca();
    	$parcelamento       = $info->getCheckParcelamento();
    	$formapagamento     = "cartaodecredito";

    	#verifica se a forma de pagamento foi selecionada
    	if(empty($formapagamento)) {
    		$errorCode = 'invalid_data';
    		$errorMsg = $this->_getHelper()->__('Selecione uma forma de pagamento');
    
    		#gera uma exception caso nenhuma forma de pagamento seja selecionada
    		Mage::throwException($errorMsg);
    	}
    
    	if($formapagamento=="cartaodecredito") {

    		if(empty($cartaobandeira) || empty($nome) || empty($cpf) || empty($numerocartao) || empty($codseguranca)) {
                $errorCode = 'invalid_data';
                $errorMsg = $this->_getHelper()->__('Campos de preenchimento obrigatório');
    
                if(! $this->isCpfValid($cpf)) {
                    $errorCode = 'invalid_data';
                    $errorMsg = $this->_getHelper()->__('CPF inválido.');

                    #gera uma exception caso nenhuma forma de pagamento seja selecionada
                    Mage::throwException($errorMsg);
                }

                $validCartao = $this->validaNumeroDoCartao($numerocartao, $codseguranca, $cartaobandeira);

                if(! $validCartao) {
                    $errorCode = 'invalid_data';
                    $errorMsg = $this->_getHelper()->__('Cartão inválido. Revise os dados informados e tente novamente.');

                    #gera uma exception caso nenhuma forma de pagamento seja selecionada
                    Mage::throwException($errorMsg);
                }

                $validadataCartao = $this->validaDataCartaoDeCredito($expiracaomes, $expiracaoano);
                
                if(! $validadataCartao) {
                    $errorCode = 'invalid_data';
                    $errorMsg = $this->_getHelper()->__('Cartão vencido. Revise os dados de expiracao e envie novamente.');

                    #gera uma exception caso nenhuma forma de pagamento seja selecionada
                    Mage::throwException($errorMsg);
                }

                #gera uma exception caso os campos do cartão nao forem preenchidos
                Mage::throwException($errorMsg);
            }
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

            $isValidConsumer_tel_2 = false;
            
            $consumer_tel_2 = $address->getData("fax");
            $consumer_tel_2 = preg_replace('([^0-9])', '', $consumer_tel_2);
            if(!empty($consumer_tel_2))
                $isValidConsumer_tel_2 = $this->isTelephoneValid($consumer_tel_2);;
            
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
			$cartaobandeira = $info->getCheckCartaobandeira();
			$nome=$info->getCheckNome();
			$cpf=$info->getCheckCpf();
			$numerocartao=$info->getCheckNumerocartao();
			$expiracaomes=$info->getCheckExpiracaomes();
			$expiracaoano=$info->getCheckExpiracaoano();
			$codseguranca=$info->getCheckCodseguranca();
			$parcelamento=$info->getCheckParcelamento();
			$tefbandeira=$info->getCheckTefbandeira();
           
			$xml_forma_pagamento='
				<meio_de_pagamento>'.trim(str_replace("cc_", " ", $cartaobandeira)).'</meio_de_pagamento>
				<numero>'.$numerocartao.'</numero>
				<expiracao>'.$expiracaomes.'/'.$expiracaoano.'</expiracao>
				<codigo_de_seguranca>'.$codseguranca.'</codigo_de_seguranca>
				<parcelas>'.$parcelamento.'</parcelas>
				<portador>
					<nome>'.$nome.'</nome>
					<cpf>'.$cpf.'</cpf>
					<telefone>'.$this->limpaTelefone($address->getData("telephone")).'</telefone>
				</portador>';
			
			
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
		
		if($this->getConfigData('module_debug')=='1'){
            Mage::throwException("XML RECEBIDO:\n\n".$ret."\n\n\nXML Enviado:\n".$xml);
		}
                
        Mage::Log("..:: ENVIADO ::..\n\n".$this->filter($xml)."\n\n ..:: RECEBIDO ::..\n\n".$ret);

		$info = $this->getInfoInstance();
        $resposta = $data["resposta"]["status"]["value"];
		 
		if($resposta == "erro") {
            $this->protectCardNumber($info);
            $transacaoId = $data["resposta"]["transacao"]["value"];
            $this->SalvaIdTransacao($orderId,$transacaoId);

            $stateAndStatus = Mage_Sales_Model_Order::STATE_CANCELED;
            $order->setState($stateAndStatus, $stateAndStatus);
            $order->setStatus($stateAndStatus);
            $order->save();

            Mage::Log('Um erro ocorreu ao efetuar transação: '.$data["resposta"]["descricao"]["value"]);
            Mage::getSingleton('checkout/session')->addError("Não foi possível realizar a transação, por favor verifique os dados do seu cartão de crédito.");
            Mage::getSingleton('customer/session')->setErrorCC(true);
			//Mage::throwException("Não foi possível realizar a transação.");
            
		} else if($resposta == "Em Análise"){
            $info->setCheckCodtransacao($data["resposta"]["transacao"]["value"]);
            $info->save();

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

        } else {
            $this->protectCardNumber($info);
            $transacaoId = $data["resposta"]["transacao"]["value"];
            $this->SalvaIdTransacao($orderId,$transacaoId);
            
            $stateAndStatus = Mage_Sales_Model_Order::STATE_CANCELED;
            $order->setState($stateAndStatus, $stateAndStatus, 'Pagamento não autorizado pela operadora de cartão de crédito');
            $order->setStatus($stateAndStatus);
            Mage::getModel('core/resource_transaction')
                ->addObject($info)
                ->addObject($order)
                ->save();

			Mage::Log('Pagamento não autorizado. ID do pedido: ' . $order->getId());
            Mage::getSingleton('checkout/session')->addError('Pagamento não autorizado pela operadora de cartão de crédito');
            Mage::getSingleton('customer/session')->setErrorCC(true);

            //Mage::throwException("Pagamento não autorizado.\nConsulte a operadora do seu cartão de crédito para maiores informações.");
		}
	}
	
	public function SalvaIdTransacao($orderId, $transacaoId) {
		//Salva as informaces do pedido para Validacao com o NIP
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');	
		$db->query("DELETE FROM akatus_transacoes WHERE idpedido='".$orderId."'");
		$db->query("INSERT into akatus_transacoes (idpedido,codtransacao) VALUES('".$orderId."','".$transacaoId."')");
    }

    private function protectCardNumber($info)
    {
        $numeroCartao = $info->getCheckNumerocartao();
        $cardDigits = '';

        $first6 = substr($numeroCartao, 0, 6);
        $last4 = substr($numeroCartao,(strlen($numeroCartao)-4),strlen($numeroCartao));
                
        $cardDigits = $first6 . "******" . $last4;	
            
        $info->setCheckNumerocartao($cardDigits);
    }

    private function filter($string)
    {                                                                                                                                                                                     
        $patterns = array(
            '/<numero>.*<\/numero>/',
            '/<codigo_de_seguranca>.*<\/codigo_de_seguranca>/',
            '/<expiracao>.*<\/expiracao>/'
        );

        $replacements = array(
            '<numero>INFORMACAO_FILTRADA_POR_SEGURANCA</numero>',
            '<codigo_de_seguranca>INFORMACAO_FILTRADA_POR_SEGURANCA</codigo_de_seguranca>',
            '<expiracao>INFORMACAO_FILTRADA_POR_SEGURANCA</expiracao>'
        );

        return preg_replace($patterns, $replacements, $string);

    }   

}
