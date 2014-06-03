<?php
 
class Akatusbase_Akatusbase_Model_Pagar extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'akatusbase';

    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc               = false;
    protected $_isInitializeNeeded      = true;

    const MEIOS_PAGAMENTO           = "https://www.akatus.com/api/v1/meios-de-pagamento.json";
    const MEIOS_PAGAMENTO_SANDBOX   = "https://sandbox.akatus.com/api/v1/meios-de-pagamento.json";
    
    const PARCELAMENTO              = "https://www.akatus.com/api/v1/parcelamento/simulacao.json?email={EMAIL}&amount={AMOUNT}&payment_method=cartao_master&api_key={API_KEY}";
    const PARCELAMENTO_SANDBOX      = "https://sandbox.akatus.com/api/v1/parcelamento/simulacao.json?email={EMAIL}&amount={AMOUNT}&payment_method=cartao_master&api_key={API_KEY}";
    
    const ESTORNO           = "https://www.akatus.com/api/v1/estornar-transacao.xml";
    const ESTORNO_SANDBOX   = "https://sandbox.akatus.com/api/v1/estornar-transacao.xml";
    
    const CARRINHO          = "https://www.akatus.com/api/v1/carrinho.xml";
    const CARRINHO_SANDBOX  = "https://sandbox.akatus.com/api/v1/carrinho.xml";
    
    const BOLETO            = "https://www.akatus.com/boleto/";
    const BOLETO_SANDBOX    = "https://sandbox.akatus.com/boleto/";
    
    const TEF               = "https://www.akatus.com/tef/";
    const TEF_SANDBOX       = "https://sandbox.akatus.com/tef/";
    
    
    public function __construct()
    {
        return parent::__construct();
    }


    public function getMeiosPagamentoUrl()
    {
        return $this->_isSandboxMode() ? self::MEIOS_PAGAMENTO_SANDBOX : self::MEIOS_PAGAMENTO;
    }

    public function getParcelamentoUrl()
    {
        return $this->_isSandboxMode() ? self::PARCELAMENTO_SANDBOX : self::PARCELAMENTO;
    }

    public function getEstornoUrl()
    {
        return $this->_isSandboxMode() ? self::ESTORNO_SANDBOX : self::ESTORNO;
    }
    
    public function getCarrinhoUrl()
    {
        return $this->_isSandboxMode() ? self::CARRINHO_SANDBOX : self::CARRINHO;        
    }    

    public function getBoletoUrl()
    {
        return $this->_isSandboxMode() ? self::BOLETO_SANDBOX : self::BOLETO;
    }
    
    public function getTefUrl()
    {
        return $this->_isSandboxMode() ? self::TEF_SANDBOX : self::TEF;
    }        
    
    private function _isSandboxMode()
    {
        $storeId = Mage::app()->getStore()->getId();

        if (Mage::getModel('akatusbase/pagar')->getConfigData('modo', $storeId) === 'SANDBOX') {
            return true;
        }
        
        return false;
    }

    public function isTelephoneValid($tel)
    {
        $valid = true;

        $telSoNumeros = preg_replace('([^0-9])','',$tel);
        $size = strlen($telSoNumeros);
        
        if($size < 10 || $size > 11){
            $valid = false;
        }
        
        if(!$valid) {
            $errorMsg = $this->_getHelper()->__('Telefone inválido. Deve ser informado o código de área com 2 dígitos seguido do número do telefone com 8 ou 9 dígitos, e somente números (Ex.: 1199999999).');
            Mage::throwException($errorMsg);
        }

        return $valid;
    }
    
    public function stringToUf($estado)
    {
        $uf = "";

        switch ($estado) {
            case 'Acre':
            case 'AC':
                $uf = 'AC';
                break;
            case 'Alagoas':
            case 'AL':
                $uf = 'AL';
                break;
            case 'Amazonas':
            case 'AM':
                $uf = 'AM';
                break;
            case 'Amapá':
            case 'Amapa':
            case 'AP':
                $uf = 'AP';
                break;
            case 'Bahia':
            case 'BA':
                $uf = 'BA';
                break;
            case 'Ceará':
            case 'Ceara':
            case 'CE':
                $uf = 'CE';
                break;
            case 'Distrito Federal':
            case 'DF':
                $uf = 'DF';
                break;
            case 'Espírito Santo':
            case 'Espirito Santo':
            case 'ES':
                $uf = 'ES';
                break;
            case 'Goiás':
            case 'Goias':
            case 'GO':
                $uf = 'GO';
                break;
            case 'Maranhão':
            case 'Maranhao':
            case 'MA':
                $uf = 'MA';
                break;
            case 'Minas Gerais':
            case 'MG':
                $uf = 'MG';
                break;
            case 'Mato Grosso do Sul':
            case 'MS':
                $uf = 'MS';
                break;
            case 'Mato Grosso':
            case 'MT':
                $uf = 'MT';
                break;
            case 'Pará':
            case 'Para':
            case 'PA':
                $uf = 'PA';
                break;
            case 'Paraíba':
            case 'Paraiba':
            case 'PB':
                $uf = 'PB';
                break;
            case 'Pernambuco':
                $uf = 'PE';
                break;
            case 'Piauí':
            case 'Piaui':
            case 'PI':
                $uf = 'PI';
                break;
            case 'Paraná':
            case 'Parana':
            case 'PR':
                $uf = 'PR';
                break;
            case 'Rio de Janeiro':
            case 'RJ':
                $uf = 'RJ';
                break;
            case 'Rio Grande do Norte':
            case 'RN':
                $uf = 'RN';
                break;
            case 'Rio Grande do Sul':
            case 'RS':
                $uf = 'RS';
                break;
            case 'Roraima':
            case 'RR':
                $uf = 'RR';
                break;
            case 'Rondônia':
            case 'Rondonia':
            case 'RO':
                $uf = 'RO';
                break;
            case 'Santa Catarina':
            case 'SC':
                $uf = 'SC';
                break;
            case 'Sergipe':
            case 'SE':
                $uf = 'SE';
                break;
            case 'São Paulo':
            case 'SP':
                $uf = 'SP';
                break;
            case 'Tocantins':
            case 'TO':
                $uf = 'TO';
                break;
            default:
                $uf = "";
                break;
        }

        return $uf;
    }

    public function limpaTelefone($tel)
    {
        return preg_replace('([^0-9])','',$tel);
    }
    
    public function isCpfValid($cpf)
    {
        //Etapa 1: Cria um array com apenas os digitos numéricos, isso permite receber o cpf em diferentes formatos como "000.000.000-00", "00000000000", "000 000 000 00" etc...
        $j=0;
        for($i=0; $i < (strlen($cpf)); $i++) {
            if(is_numeric($cpf[$i])) {
                $num[$j]=$cpf[$i];
                $j++;
            }
        }
        
        //Etapa 2: Conta os dígitos, um cpf válido possui 11 dígitos numéricos.
        if(count($num) != 11) {
            $isCpfValid = false;
        } else { //Etapa 3: Combinações como 00000000000 e 22222222222 embora não sejam cpfs reais resultariam em cpfs válidos após o calculo dos dígitos verificares e por isso precisam ser filtradas nesta parte.
            for($i=0; $i<10; $i++) {
                if ($num[0]==$i && $num[1]==$i && $num[2]==$i && $num[3]==$i && $num[4]==$i && $num[5]==$i && $num[6]==$i && $num[7]==$i && $num[8]==$i) {
                    $isCpfValid = false;
                    break;
                }
            }
        }

        //Etapa 4: Calcula e compara o primeiro dígito verificador.
        if(! isset($isCpfValid)) {
            $j=10;
            for($i=0; $i<9; $i++) {
                $multiplica[$i] = $num[$i] * $j;
                $j--;
            }

            $soma = array_sum($multiplica);	
            $resto = $soma % 11;			

            if($resto < 2) {
                $dg = 0;
            } else {
                $dg = 11 - $resto;
            }
            
            if($dg != $num[9]) {
                $isCpfValid = false;
            }
        }

        //Etapa 5: Calcula e compara o segundo dígito verificador.
        if( ! isset($isCpfValid)) {
            $j=11;
            for($i=0; $i<10; $i++) {
                $multiplica[$i]=$num[$i]*$j;
                $j--;
            }
            
            $soma = array_sum($multiplica);
            $resto = $soma % 11;
                
            if($resto < 2) {
                $dg = 0;
            } else {
                $dg = 11 - $resto;
            }
            
            if($dg != $num[10]) {
                $isCpfValid = false;
            } else {
                $isCpfValid = true;
            }
        }

        return $isCpfValid;					
    }

	public function xml2array($contents, $get_attributes=1) {
        if (!$contents)
                return array();

        if (!function_exists('xml_parser_create')) {
                return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $contents, $xml_values);
        xml_parser_free($parser);

        if (!$xml_values)
                return; //Hmm...
                
		//Initializations

        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array;

        //Go through the tags.

        foreach ($xml_values as $data) {
                unset($attributes, $value); //Remove existing values, or there will be trouble
                extract($data); //We could use the array by itself, but this cooler.

                $result = '';

                if ($get_attributes) {//The second argument of the function decides this.
                        $result = array();
                        if (isset($value))
                                $result['value'] = $value;

                        //Set the attributes too.
                        if (isset($attributes)) {
                                foreach ($attributes as $attr => $val) {
                                        if ($get_attributes == 1)
                                                $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                                }
                        }
                } elseif (isset($value)) {
                        $result = $value;
                }

                //See tag status and do the needed.

                if ($type == "open") {//The starting of the tag '<tag>'
                        $parent[$level - 1] = &$current;

                        if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                                $current[$tag] = $result;
                                $current = &$current[$tag];
                        } else { //There was another element with the same tag name
                                if (isset($current[$tag][0])) {
                                        array_push($current[$tag], $result);
                                } else {
                                        $current[$tag] = array($current[$tag], $result);
                                }
                                $last = count($current[$tag]) - 1;
                                $current = &$current[$tag][$last];
                        }
                } elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
                        //See if the key is already taken.
                        if (!isset($current[$tag])) { //New Key
                                $result = str_replace('|', '&', $result);
                                $current[$tag] = $result;
                        } else { //If taken, put all things inside a list(array)
                                if ((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array...
                                        or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
                                        array_push($current[$tag], $result); // ...push the new element into that array.
                                } else { //If it is not an array...
                                        $current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
                                }
                        }
                } elseif ($type == 'close') { //End of tag '</tag>'
                        $current = &$parent[$level - 1];
                }
        }

        if (!empty($xml_array['root']['node']['id'])) {
                $return['root']['node'][0] = $xml_array['root']['node'];
        } else {
                $return = $xml_array;
        }
        return($return);
	}    
}
