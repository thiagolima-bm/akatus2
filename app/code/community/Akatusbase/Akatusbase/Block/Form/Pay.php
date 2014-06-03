<?php

class Akatusbase_Akatusbase_Block_Form_Pay extends Mage_Payment_Block_Form
{
    private $meioDePagamento;

    public function avisoConfiguracoes()
    {
        if ( ! in_array('curl', get_loaded_extensions())) {
            echo "<p><strong>Verifique o suporte a cURL, obrigatório para o módulo Akatus.</strong></p>";
        }

        $storeId = Mage::app()->getStore()->getId();
        $email = Mage::getModel('akatusbase/pagar')->getConfigData('email_gateway', $storeId);
        $apiKey = Mage::getModel('akatusbase/pagar')->getConfigData('api_key', $storeId);

        if ($email === '' || $apiKey === '') {
            echo "<p><strong>Verifique os dados relacionados à Akatus nas configurações do módulo.</strong></p>";
        }

        if ($apiKey !== null && preg_match('/ /', $apiKey)) {
            echo "<p><strong>Verifique a API Key nas configurações do módulo Akatus.<br/>Remova espaços presentes no começo ou final do campo.</strong></p>";
        }
    }

    public function cartaoDisponivel()
    {
        return $this->meioDePagamentoDisponivel('Cartão de Crédito');
    }

    public function boletoDisponivel()
    {
        return $this->meioDePagamentoDisponivel('Boleto Bancário');
    }

    public function tefDisponivel()
    {
        return $this->meioDePagamentoDisponivel('TEF');
    }

    public function getMeioDePagamento()
    {
        return $this->meioDePagamento;
    }

    public function getBandeirasMeioDePagamento()
    {
        return $this->meioDePagamento->bandeiras;
    }

    public function getParcelas($valor)
    {
        $storeId = Mage::app()->getStore()->getId();

        $tokens = array(
            '{EMAIL}',
            '{API_KEY}',
            '{AMOUNT}'
        );

        $valores = array(
            Mage::getModel('akatusbase/pagar')->getConfigData('email_gateway', $storeId),
            Mage::getModel('akatusbase/pagar')->getConfigData('api_key', $storeId),
            $valor
        );

        $url = str_replace($tokens, $valores, Mage::getModel('akatusbase/pagar')->getParcelamentoUrl());

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);

        $apiResponse = json_decode($response);

        return $this->filtrarParcelas($apiResponse);
    }

    private function meioDePagamentoDisponivel($descricao)
    {
		$responseObject = $this->requestMeiosPagamento();

        if (isset($responseObject->resposta)) {

            if (isset($responseObject->resposta->status) && 
                strval($responseObject->resposta->status) === 'erro' && 
                strval($responseObject->resposta->descricao) === 'conta não encontrada') {
                echo "<p><strong>Conta não encontrada.<br/>Verifique no módulo Akatus se está utilizando o ambiente correto para a conta informada.</strong></p>";
                return false;

            } else if(isset($responseObject->resposta->meios_de_pagamento)) {
                $meiosDePagamento = $responseObject->resposta->meios_de_pagamento;

                foreach($meiosDePagamento as $meioDePagamento){

                    if ($meioDePagamento->descricao === $descricao) {
                        $this->meioDePagamento = $meioDePagamento;

                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function requestMeiosPagamento()
    {
        $storeId = Mage::app()->getStore()->getId();

        $email = Mage::getModel('akatusbase/pagar')->getConfigData('email_gateway', $storeId);
        $apiKey = Mage::getModel('akatusbase/pagar')->getConfigData('api_key', $storeId);

		$credenciais = array(
            "meios_de_pagamento" => array(
                "correntista" => array(
                    "api_key" => $apiKey,
					"email" => $email
                )
            )
        );

		$data = json_encode($credenciais);

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, Mage::getModel('akatusbase/pagar')->getMeiosPagamentoUrl());
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($curl);
		curl_close($curl);
        
        return json_decode($response);
    }

    private function filtrarParcelas($apiResponse)
    {
        $cloneObject = unserialize(serialize($apiResponse));
        $cloneObject->resposta->parcelas = array();

        $parcelasAssumidas = $apiResponse->resposta->parcelas_assumidas;

        foreach($apiResponse->resposta->parcelas as $parcela){

            $novaParcela = new stdClass();
            $novaParcela->quantidade = $parcela->quantidade;
            
            $numeroParcelaAtual = $parcela->quantidade;
            $valorFormatado = number_format($parcela->valor, 2, ",", ".");
            $descricaoJurosFormatada = str_replace('.', ',', $apiResponse->resposta->descricao);

            if (($numeroParcelaAtual > 1) && ($numeroParcelaAtual > $parcelasAssumidas)) {
                $novaParcela->label = "{$numeroParcelaAtual}x de R$ {$valorFormatado} (juros de {$descricaoJurosFormatada})";
            } else {
                $novaParcela->label = "{$numeroParcelaAtual}x de R$ {$valorFormatado} sem juros";
            }

            $cloneObject->resposta->parcelas[] = $novaParcela;

            if ($parcela->quantidade > 1 && $parcela->valor < 5) {
                array_pop($cloneObject->resposta->parcelas);
                break;
            }
        }

        $numeroMaximoParcelas = Mage::getModel('akatuscartao/pagar')->getConfigData('numero_maximo_parcelas', Mage::app()->getStore()->getId());
        $cloneObject->resposta->parcelas = array_slice($cloneObject->resposta->parcelas, 0, $numeroMaximoParcelas);

        return $cloneObject->resposta->parcelas;
    }
}
