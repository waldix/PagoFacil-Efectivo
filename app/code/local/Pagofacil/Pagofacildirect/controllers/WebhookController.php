<?php
/** 
*  Webhook para notificaciones de pagos.
*
*  @author waldix (waldix86@gmail.com)
*/

class Pagofacil_Pagofacildirect_WebhookController extends Mage_Core_Controller_Front_Action{

    public function indexAction(){
		$params = $this->getRequest()->getParams();

		$body = @file_get_contents('php://input');
	    $event_json = json_decode($body);
	    $_id = $event_json->{'customer_order'};
	    $_type = $event_json->{'status'};	    

        $config = Mage::getModel('pagofacildirect/CashCP');        
		$_order = Mage::getModel('sales/order')->loadByIncrementId($_id);

		switch ($_type) {    
			case 1:
			    $status = $config->getConfigData('order_status_in_process');
			    $message = 'The user has not completed the payment process yet.';
			    $_order->addStatusToHistory($status, $message);
			    break;
		    case 2:
			    $status = $config->getConfigData('order_status_in_process');
			    $message = 'The user has not completed the payment process yet.';
			    $_order->addStatusToHistory($status, $message);
			    break;
			case 3:         
				$status = $config->getConfigData('order_status_cancelled');
			    $message = 'The user has not completed the payment and the order was cancelled.';
				$_order->cancel();
				break;
			case 4:
			    $createinvoice = Mage::getModel('pagofacildirect/CashCP')->getConfigData('auto_create_inovice');
			    if ($createinvoice == 1){     
					if(!$_order->hasInvoices()){
					    $invoice = $_order->prepareInvoice();   
					    $invoice->register()->pay();
					    Mage::getModel('core/resource_transaction')
					    ->addObject($invoice)
					    ->addObject($invoice->getOrder())
					    ->save();					    
					    
					    $message = 'Payment '.$invoice->getIncrementId().' was created. ComproPago automatically confirmed payment for this order.';
					    $status = $config->getConfigData('order_status_approved');
					    $_order->addStatusToHistory($status,$message,true);
					    $invoice->sendEmail(true, $message);
					}
			    } else {				
					$message = 'ComproPago automatically confirmed payment for this order.';
					$status = $config->getConfigData('order_status_approved');
					$_order->addStatusToHistory($status,$message,true);				
			    }
			    break;
			default:
			    $status = $config->getConfigData('order_status_in_process');
			    $message = "";    
			    $_order->addStatusToHistory($status, $message);
		}
			
			
		$_order->save();		    
    }        
}