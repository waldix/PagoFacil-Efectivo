<?php
/**
 * API para el consumo de los servicios
 * de PagoFacil
 * @author ivelazquex <isai.velazquez@gmail.com>
 */
class Pagofacil_Pagofacildirect_Model_Api
{
    // --- ATTRIBUTES ---
    /**
     * URL del servicio de PagoFacil para pruebas
     * @var string
     */
    protected $_urlDemo = 'https://www.pagofacil.net/st/public/Wsrtransaccion/index/format/json';
    
    /**
     * URL del servicio de PagoFacil en ambiente de produccion
     * @var string 
     */
    protected $_urlProd = 'https://www.pagofacil.net/ws/public/Wsrtransaccion/index/format/json';

    /**
     * Clave de sucursal para el entorno de pruebasa(STAGE)
     * @var string
     */
    protected $_sucursalKeyDemo = "60f961360ca187d533d5adba7d969d6334771370";
    
    /**
     * Clave de usuario para el entorno de pruebas(STAGE)
     * @var string
     */
    protected $_usuarioKeyDemo = "62ad6f592ecf2faa87ef2437ed85a4d175e73c58";
    
    /**
     * respuesta sin parsear del servicio
     * @var string
     */
    protected $_response = NULL;
    
    
    // --- OPERATIONS ---
    
    public function __construct()
    {
        
    }
    
    /**
     * consume el servicio de pago de PagoFacil
     * @param string[] vector con la informacion de la peticion
     * @return mixed respuesta del consumo del servicio
     * @throws Exception
     */
    public function payment($info)
    {
        $response = null;
        // NOTA la url a la cual s edebe conectar en produccion no viene 
        // la direccion de produccion en el documento 
        
        if (!is_array($info))
        {
            throw new Exception('parameter is not an array');
        }

        $info['url'] = $this->_urlProd;
        // determinar si el entorno es para pruebas
        if ($info['prod'] == '0')
        {
            $info['url'] = $this->_urlDemo;
            $info['idSucursal'] = $this->_sucursalKeyDemo;
            $info['idUsuario'] = $this->_usuarioKeyDemo;
        }

        // datos para la peticion del servicio
        $data = array(
            'idServicio'        => '3',
            'idSucursal'        => $info['idSucursal'],
            'idUsuario'         => $info['idUsuario'],
            'nombre'            => $info['nombre'],
            'apellidos'         => $info['apellidos'],
            'numeroTarjeta'     => $info['numeroTarjeta'],
            'cvt'               => $info['cvt'],
            'cp'                => $info['cp'],
            'mesExpiracion'     => $info['mesExpiracion'],
            'anyoExpiracion'    => $info['anyoExpiracion'],
            'monto'             => $info['monto'],
            'email'             => $info['email'],
            'telefono'          => $info['telefono'],
            'celular'           => $info['celular'],
            'calleyNumero'      => $info['calleyNumero'],
            'colonia'           => $info['colonia'],
            'municipio'         => $info['municipio'],
            'estado'            => $info['estado'],
            'pais'              => $info['pais'],
            'idPedido'          => $info['idPedido'],
            'ip'                => $info['ipBuyer'],
            'noMail'            => $info['noMail'],
            'plan'              => $info['plan'],
            'mensualidades'     => $info['mensualidades'],
        );

        // construccion de la peticion
        $query = '';
        foreach ($data as $key=>$value){
            $query .= sprintf("&data[%s]=%s", $key, urlencode($value));
        }        
        $url = $info['url'].'/?method=transaccion'.$query;

        // consumo del servicio
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Blindly accept the certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->_response = curl_exec($ch);
        curl_close($ch);

        // tratamiento de la respuesta del servicio
        $response = json_decode($this->_response,true);
        $response = $response['WebServices_Transacciones']['transaccion'];
                
        return $response;
    }
    
    /**
     * obtiene la respuesta del servicio
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * consume el servicio de pago en efectivo de PagoFacil
     * @param string[] vector con la informacion de la peticion
     * @return mixed respuesta del consumo del servicio
     * @throws Exception
     */
     public function paymentCash($info)
    {
        $response = null;        
        
        if (!is_array($info))
        {
            throw new Exception('parameter is not an array');
        }

        $info['url'] = 'https://www.pagofacil.net/st/public/cash/charge';
        // determinar si el entorno es para pruebas
        if ($info['prod'] == '0')
        {
            $info['url'] = 'https://www.pagofacil.net/ws/public/cash/charge';
            $info['branch_key'] = $this->_sucursalKeyDemo;
            $info['user_key'] = $this->_usuarioKeyDemo;
        }

        // datos para la peticion del servicio
        $data = array(
            'branch_key'       => $info['branch_key'],
            'user_key'         => $info['user_key'],
            'order_id'         => $info['order_id'],
            'product'          => $info['product'],
            'amount'           => $info['amount'],
            'store_code'       => $info['storeCode'],
            'customer'         => $info['customer'],
            'email'            => $info['email']
        );

        // consumo del servicio
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $info['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Blindly accept the certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->_response = curl_exec($ch);
        curl_close($ch);

        // tratamiento de la respuesta del servicio
        $response = json_decode($this->_response,true);               

        return $response;
    }

}