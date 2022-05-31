<?php

namespace XCHANGE\XMP31\Block;


class Success extends \Magento\Checkout\Block\Onepage\Success {




    public function getAdditionalInfoHtml()
    {

		$order_id = '';
		try{
		} catch(Exception $e) {
		}

		$i = 0;
		$testData = '';
		$getid = '';
		$sessionCustomerName = '';
		$lastFoundCustomerOrder = '';

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		$items = $cart->getItems();
		// Get custom options value of cart items
		foreach ($items as $item) {
			  $i = $i + 1;
		}

		$customerSession = $objectManager->get('Magento\Customer\Model\Session');
		if($customerSession->isLoggedIn()) {
		   // Customer login action
		   $testData .= '<br>/Customer is logged in.';
		   $testData .= '<br>/CustomerID='.$customerSession->getCustomerId();
		   $sessionCustomerName = $customerSession->getCustomer()->getName();
		}

		$orderDatamodel = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
		foreach($orderDatamodel as $orderDatamodel1)
		{
			$i = $i + 1;

			$getid =  $orderDatamodel1->getData("increment_id");

			if($orderDatamodel1->getCustomerName()==$sessionCustomerName)
			{
			   $lastFoundCustomerOrder = $getid;
			}

		}

		$testData .= "<br>/lastOrd=".$lastFoundCustomerOrder;

		$sbov = $objectManager->get('Magento\Sales\Block\Order\View');
		if( isset($sbov) )
		{
			$ooo = $sbov->getOrder();
			if( isset($ooo ) )
			{
				$testData .= '<br>/TTL='.$ooo->getTotalDue();
				$testData .= '<br>/getRealOrderId='.$ooo->getRealOrderId();
				$testData .= '<br>/getStatus='.$ooo->getStatus();

				$ordID = $ooo->getRealOrderId();
				$order = $ooo;
				//--------------------------------------
				if($ordID=='000000355')
				{

					if (!$order->canShip()) {
						throw new \Magento\Framework\Exception\LocalizedException(
							__('You can\'t create an shipment.')
						);
					}

					$packageParams = array();
					$packageParams['width'] = 100;
					$packageParams['length'] = 100;
					$packageParams['height'] = 100;
					$packageParams['weight'] = 1;

					$convertOrder = $objectManager->create('\Magento\Sales\Model\Convert\Order');
					$shipment = $convertOrder->toShipment($order);

					$shipment->register();
					$shipment->getOrder()->setIsInProcess(true);
					try {
						$shipment->save();
						$shipment->getOrder()->save();
						$notify = $objectManager->create('Magento\Shipping\Model\ShipmentNotifier');
						$notify->notify($shipment);
						$shipment->save();
					} catch (\Exception $e) {
						throw new \Magento\Framework\Exception\LocalizedException(
							__($e->getMessage())
						);
					}
				}//....if($ordID=='000000355')
				//--------------------------------------
			}
		}


		$bos = $objectManager->get('Magento\Checkout\Block\Onepage\Success');
		if( isset($bos) )
		{
		   $testData .= '<br>/CheckoutOrderId='.$bos->getOrderId();
		   $testData .= '<br>/CheckoutURL='.$bos->getViewOrderUrl();
		   $testData .= '<br>/Checkout_getStatus='.$bos->getStatus();
		}


		// Checkout_Block_Success
		$bos = $objectManager->get('Magento\Checkout\Block\Success');
		if( isset($bos) )
		{
		   $testData .= '<br>/getRealOrderId='.$bos->getRealOrderId();
		}


		$ver = '';

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
		$ver = $productMetadata->getVersion();

		$testData .= '<br>/mageVersion='.$ver;


$connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magent\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
$tableName = $objectManager->get('Magento\Framework\App\ResourceConnection')->getTableName('aaa_XCHANGE_order'); // Use res without conn to get prefix.
$testData .= '<br>/tableName='.$tableName;
if($connection->isTableExists($tableName) == true)
{
    $result1 = $connection->fetchAll("SELECT title FROM ".$tableName." where id=22");
    foreach ($result1 as $item){
	   $jjj= json_encode($item);
	   $testData .= '<br>/data='.$jjj;
	   $testData .= '<br>/data='.$item['title'];
    }
}


    $scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
    $sectionId = 'example_section';
    $groupId = 'general';

    $fieldId = 'text_example';
    $configPath = $sectionId.'/'.$groupId.'/'.$fieldId;
    $value =  $scopeConfig->getValue(
        $configPath,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );
    $testData .= '<br>/ResellerID='.$value ;


    $fieldId = 'dropdown_example';
    $configPath = $sectionId.'/'.$groupId.'/'.$fieldId;
    $value =  $scopeConfig->getValue(
        $configPath,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );
    $testData .= '<br>/APImode='.$value ;


    $fieldId = 'CCType';
    $configPath = $sectionId.'/'.$groupId.'/'.$fieldId;
    $value =  $scopeConfig->getValue(
        $configPath,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );
    $testData .= '<br>/CCType='.$value ;


    $fieldId = 'CCName';
    $configPath = $sectionId.'/'.$groupId.'/'.$fieldId;
    $value =  $scopeConfig->getValue(
        $configPath,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );
    $testData .= '<br>/CCName='.$value ;


    $fieldId = 'CCNumber';
    $configPath = $sectionId.'/'.$groupId.'/'.$fieldId;
    $value =  $scopeConfig->getValue(
        $configPath,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );
    $testData .= '<br>/CCNumber='.$value ;


    $fieldId = 'CCExpiryMonth';
    $configPath = $sectionId.'/'.$groupId.'/'.$fieldId;
    $value =  $scopeConfig->getValue(
        $configPath,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );
    $testData .= '<br>/CCExpiryMonth='.$value ;


    $fieldId = 'CCExpiryYear';
    $configPath = $sectionId.'/'.$groupId.'/'.$fieldId;
    $value =  $scopeConfig->getValue(
        $configPath,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );
    $testData .= '<br>/CCExpiryYear='.$value ;



    $fieldId = 'ProductionURL';
    $groupId = 'Interface';
    $configPath = $sectionId.'/'.$groupId.'/'.$fieldId;
    $ProductionURL =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);



    $testData .= '<br>/ProductionURL='.$ProductionURL;


		return '<div style="background-color:red;color:white;">@ WHOOHOOOPEE '.$i.'/'.$testData.' @</div>';

	}//...getAdditionalInfoHtml()


	public function getOrdID()
	{
		return '....getOrdID....';

		return 'getLastOrderId=['.$this->getLastOrderId().']';

		return 'chkout=['.$this->getCheckout().']';

	}

  public function getRealOrdID(){

          $lastorderId = "";

          $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
          $checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');

          $lastorderId = $checkoutSession->getLastOrderId();
          return $lastorderId;

  }


}
