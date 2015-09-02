<?php

/** 
 *  Cash Form 
 *
 *  @author waldix (waldix86@gmail.com) 
 */


class Pagofacil_Pagofacildirect_Block_CashForm extends Mage_Payment_Block_Form{
    
    protected function _construct(){

        parent::_construct();
        $this->setTemplate('pagofacildirect/cash.phtml');
        
    }
}

?>
