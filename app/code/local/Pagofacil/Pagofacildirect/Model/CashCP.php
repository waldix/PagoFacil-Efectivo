<?php
/**
 * Funcionalidad de pagos en efectivo
 * 
 * @author waldix <waldix86@gmail.com>
 */


class Pagofacil_Pagofacildirect_Model_CashCP extends Mage_Payment_Model_Method_Abstract{
    protected $_formBlockType = 'pagofacildirect/cashForm';
    protected $_code = 'compropago';    

    protected $_canUseForMultiShipping = false;    
    protected $_canUseInternal         = false;
    protected $_isInitializeNeeded     = true;


    public function assignData($data)
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if (!($data instanceof Varien_Object))
        {        
            $data = new Varien_Object($data);
        }
        
        //Verificamos si existe el customer
        if($customer->getFirstname()){
            $info = array(
                "storeCode" => $data->getStoreCode(),
                "customer" => htmlentities($customer->getFirstname()),
                "email" => htmlentities($customer->getEmail())
            );
        } else {
            $sessionCheckout = Mage::getSingleton('checkout/session');
            $quote = $sessionCheckout->getQuote();
            $billingAddress = $quote->getBillingAddress();
            $billing = $billingAddress->getData();

            $info = array(
                "storeCode" => $data->getStoreCode(),
                "customer" => htmlentities($billing['firstname']),
                "email" => htmlentities($billing['email'])
            ); 

        }                       
        
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
        $order1 = Mage::getModel('sales/order')->loadByIncrementId($orderNumber);     
        
        // obtener el nombre de cada uno de los items y concatenarlos
        foreach ($order1->getAllItems() as $item) {                                        
            $name .= $item->getName();
        }   
        
        // obtener datos del pago en info y asignar monto total
        $infoIntance = $this->getInfoInstance();
        $info = unserialize($infoIntance->getAdditionalData());
        $info['order_id'] = $orderNumber;
        $info['branch_key'] = trim($this->getConfigData('sucursalkey'));
        $info['user_key'] = trim($this->getConfigData('usuariokey'));
        $info['amount'] = $grandTotal;
        $info['product'] = $name;        

        // enviar pago        
        try
        {
            $Api = new Pagofacil_Pagofacildirect_Model_Api();
            $response = $Api->paymentCash($info);            
        }
        catch (Exception $error)
        {            
            Mage::throwException($error->getMessage());
        }                   
        
        // respuesta del servicio
        if ($response == null)
        {            
            Mage::throwException("El servicio de PagoFacil Efectivo no se encuentra disponible.");
        }
        
        if ($response['error'] == '1')
        {
            $errorMessage = $response['message'] . "\n";
            if (is_array($response['error']))
            {
                $errorMessage.= implode("\n", array_values($response['error']));
            }                        
            Mage::throwException($errorMessage);
        } else {
            //Se almacenan los datos de la respuesta en session para posteriormente mostrarlos en el success.
            $convenience_store = $response['charge']['convenience_store'];
            $store_fixed_rate = $response['charge']['store_fixed_rate'];
            $store_schedule = $response['charge']['store_schedule'];
            $store_image = $response['charge']['store_image'];
            $bank_account_number = $response['charge']['bank_account_number'];
            $bank = $response['charge']['bank'];
            $expiration_date = $response['charge']['expiration_date']; 
            $amount = $response['charge']['amount'];
            $reference = $response['charge']['reference'];        

            Mage::getSingleton('core/session')->setConvenienceStore($convenience_store);
            Mage::getSingleton('core/session')->setStoreFixedRate($store_fixed_rate);
            Mage::getSingleton('core/session')->setStoreSchedule($store_schedule);
            Mage::getSingleton('core/session')->setStoreImage($store_image);
            Mage::getSingleton('core/session')->setBankAccountNumber($bank_account_number);
            Mage::getSingleton('core/session')->setBank($bank);
            Mage::getSingleton('core/session')->setExpirationDate($expiration_date);       
            Mage::getSingleton('core/session')->setAmount($amount);
            Mage::getSingleton('core/session')->setReference($reference);
            Mage::getSingleton('core/session')->setNameItem($name);
        }
                
        return $this;
    }
    
    public function getProviders()
    {
        $url = 'http://api-staging-compropago.herokuapp.com/v1/providers/'; 
        $url.= 'true';        
        $username = 'pagofacil';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ":");

        // Blindly accept the certificate.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->_response = curl_exec($ch);
        curl_close($ch);

        // tratamiento de la respuesta del servicio.
        $response = json_decode($this->_response,true);
          
        return $response;
    }   
}

?>