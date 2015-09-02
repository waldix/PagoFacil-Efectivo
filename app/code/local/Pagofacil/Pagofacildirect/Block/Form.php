<?php
/**
 * Description of Form
 *
 * @author ivelazquex <isai.velazquez@gmail.com>
 */
class Pagofacil_Pagofacildirect_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {        
        parent::_construct();
        $this->setTemplate('pagofacildirect/pay.phtml');      
    }
    
    public function getMethod()
    {        
        return parent::getMethod();
    }
    
}