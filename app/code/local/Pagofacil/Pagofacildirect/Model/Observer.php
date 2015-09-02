<?php
/**
 * @author ivelazquex <isai.velazquez@gmail.com>
 */
class Pagofacil_Pagofacildirect_Model_Observer
{    
    public function orderPlaceAfter($event)
    {    
        $order = $event->getOrder();
        
        if (!$order->getId())
        {
            //order is not saved in the database
            return $this;
        }
        
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        $paymentMethod = $methodInstance->getCode();        
        
        if ($paymentMethod == 'pagofacildirect')
        {
            $model = new Pagofacil_Pagofacildirect_Model_Standard();
            $status = $model->getConfigData('status');            
            
            if ($status == '')
            {
                $status = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            }            
            $this->processOrderStatus($order, $status);
        }
                
        return $this;
    }
    
    private function processOrderStatus($order, $status)
    {        
        if ($status == 'processing' || $status == 'complete')
        {
            $this->invoicedOrder($order); // factura(invoiced)
            if ($status == 'complete')
            {                
                $this->shippedOrder($order); // envia(shipped)
            }
            $status = Mage_Sales_Model_Order::STATE_PROCESSING;
        }
        $order->setState($status, true);
        $order->save();
        
        return $this;
    }    
        
    private function invoicedOrder($order)
    {        
        if ($order->canInvoice())
        {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            $order->setCustomerNoteNotify(false);
            $order->setIsInProcess(true);
            $order->addStatusHistoryComment('Automatically INVOICED by PagoFacil.', false);

            $transactionSave = Mage::getModel('core/resource_transaction');
            $transactionSave->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();
        }
    }
    
    /**
     * envia a embarque y notifica al cliente
     * @param type $order
     */
    private function shippedOrder($order)
    {
        $shipment = $order->prepareShipment();
        $shipment->register();
        
        $order->setCustomerNoteNotify(true);
        $order->setIsInProcess(true);
        $order->addStatusHistoryComment('Automatically SHIPPED by PagoFacil.', false);
        
        $transactionSave = Mage::getModel('core/resource_transaction');
        $transactionSave->addObject($shipment)->addObject($shipment->getOrder());
        $transactionSave->save();
    }
}