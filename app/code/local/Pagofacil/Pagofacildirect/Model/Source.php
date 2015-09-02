<?php
class Pagofacil_Pagofacildirect_Model_Source
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'pending', 'label' => 'Pendiente')
            ,array('value' => 'processing', 'label' => 'En Procesamiento')
            ,array('value' => 'complete', 'label' => 'Completada')
        );
    }
}