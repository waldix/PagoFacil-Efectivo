<?php
/**
 * Description of Standard
 *
 * @author ivelazquex <isai.velazquez@gmail.com>
 */
class Pagofacil_Pagofacildirect_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'pagofacildirect';
    protected $_formBlockType = 'pagofacildirect/form';
    
    protected $_canUseForMultiShipping = false;    
    protected $_canUseInternal         = false;
    protected $_isInitializeNeeded     = true;
    
    
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object))
        {        
            $data = new Varien_Object($data);
        }
        
        $info = array(
            "nombre" => $data->getNombre()
            ,"apellidos" => $data->getApellidos()
            ,"numeroTarjeta" => $data->getNumeroTarjeta()
            ,"cvt" => $data->getCvt()
            ,"cp" => $data->getCp()
            ,"mesExpiracion" => $data->getMesExpiracion()
            ,"anyoExpiracion" => $data->getAnyoExpiracion()
            ,"email" => $data->getEmail()
            ,"telefono" => $data->getTelefono()
            ,"celular" => $data->getCelular()
            ,"calleyNumero" => $data->getCalleyNumero()
            ,"colonia" =>( trim($data->getColonia()) == '' ? substr(trim($data->getCalleyNumero()), 0, 30) : $data->getColonia() )
            ,"municipio" => $data->getMunicipio()
            ,"estado" => $data->getEstado()
            ,"pais" => $data->getPais()
            ,"mensualidades" => $data->getMsi()
        );        
        
        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalData(serialize($info));
        
        return $this;
    }
    
    public function validate()
    {
        parent::validate();
        
        // extraer datos de configuracion
        $prod = $this->getConfigData('prod');        
        
        // entorno de produccion
        if ($prod == '1')
        {
            if (trim($this->getConfigData('sucursalkey')) == ''
                || trim($this->getConfigData('usuariokey')) == ''
            ) {
                Mage::throwException("Datos incompletos del servicio, contacte al administrador del sitio");
            }
        }
        
        return $this;
    }
    
    public function initialize($paymentAction, $stateObject)
    {
        parent::initialize($paymentAction, $stateObject);
        
        if($paymentAction != 'sale')
        {
            return $this;
        }
                 
        // Set the default state of the new order.
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT; // state now = 'pending_payment'
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
        
        //Retrieve cart/quote information.        
        $sessionCheckout = Mage::getSingleton('checkout/session');
        $quoteId = $sessionCheckout->getQuoteId();

        // obtiene el quote para informacion de la orden         
        $quote = Mage::getModel("sales/quote")->load($quoteId);
        $grandTotal = $quote->getData('grand_total');
        $subTotal = $quote->getSubtotal();
        $shippingHandling = ($grandTotal-$subTotal);        
        
        $convertQuote = Mage::getSingleton('sales/convert_quote');
        $order = $convertQuote->toOrder($quote);
        $orderNumber = $order->getIncrementId();        
        
        // obtener datos del pago en info y asignar monto total
        $infoIntance = $this->getInfoInstance();
        $info = unserialize($infoIntance->getAdditionalData());
        $info['idPedido'] = $orderNumber;
        $info['prod'] = trim($this->getConfigData('prod'));
        $info['idSucursal'] = trim($this->getConfigData('sucursalkey'));
        $info['idUsuario'] = trim($this->getConfigData('usuariokey'));
        $info['monto'] = $grandTotal;
        $info['ipBuyer'] = $_SERVER['REMOTE_ADDR'];
        $info['noMail'] = ((int)trim($this->getConfigData('notify')) == 1 ? 0 : 1 );
        $info['plan'] = ( (int)trim($this->getConfigData('msi')) == 1 ? ($info['mensualidades'] == '00' ? 'NOR' : 'MSI' ) : 'NOR' );
        
        // enviar pago        
        try
        {
            $Api = new Pagofacil_Pagofacildirect_Model_Api();
            $response = $Api->payment($info);            
        }
        catch (Exception $error)
        {            
            Mage::throwException($error->getMessage());
        }
                
        // respuesta del servicio
        if ($response == null)
        {            
            Mage::throwException("El servicio de PagoFacil no se encuentra");
        }
        
        if ($response['autorizado'] == '0')
        {
            $errorMessage = $response['texto'] . "\n";
            if (is_array($response['error']))
            {
                $errorMessage.= implode("\n", array_values($response['error']));
            }                        
            Mage::throwException($errorMessage);
        }
                
        return $this;
    }
    
    public function getBillingInfo()
    {
        $sessionCheckout = Mage::getSingleton('checkout/session');
        $quote = $sessionCheckout->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $data = $billingAddress->getData();
        
        $country = Mage::getModel('directory/country')->load($data['country_id']);
        $data['country'] = $country->getName();
        
        return $data;
    }
    
    public function showLogoPagoFacil()
    {
        return ( (int)trim($this->getConfigData("logo")) == 1 ? true : false );
    }
    
    public function enabledMSI()
    {
        return ( (int)trim($this->getConfigData("msi")) == 1 ? true : false );
    }    
}