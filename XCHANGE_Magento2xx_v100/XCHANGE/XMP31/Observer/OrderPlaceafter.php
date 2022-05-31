<?php

namespace XCHANGE\XMP31\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use XCHANGE\XMP31\Helper\Email;


#!--- Updated/new to OrderPlacebefore
class OrderPlaceafter implements ObserverInterface
{

    private $helperEmail;

    protected $_messageManager;
    protected $_objectManager;
    protected $_orderRepository;

    /* Sent to Customer in Order Update email in lieu of product license info when T_Finalize call fails. Add list of XCHANGE product names with %s */
    const XCHANGE_EMAIL_ERROR_MESSAGE = "Product license information currently unavailable for %s. Please contact support@example.com";
    /* Message displayed when Order is stopped when T_Reserve fails (due to licenses out of stock, bad product config on backend, bad network, etc.)  Add list of XCHANGE product names with %s. */
    const XCHANGE_RESERVE_FAILED_BROWSER_ERROR_MESSAGE = "We're sorry. One or more of the following items in your cart currently cannot be reserved: %s Contact support@example.com";
    /* Emails to send copies of XCHANGE errors to. (Separate multiple email addresses with commas, no spaces) */
    #!-- const XCHANGE_EMAIL_ERRORS_TO = 's.senathira@xchangemarket.com,gatkins2004@hotmail.com';
    const XCHANGE_EMAIL_ERRORS_TO = 's.senathira@xchangemarket.com';
    /* Who the alert email appears to be sent from. Typically, your site support email */
    const XCHANGE_ADMIN_EMAIL = 's.senathira@xchangemarket.com'; /*** Not really used anymore. Implemented via XML_adminemail and xmp_adminemail. No harm leaving it as it is ***********/
    /* Log all XCHANGE behavior or only errors (errors are *always* emailed to the XCHANGE_EMAIL_ERRORS_TO address(es)). */
    const XCHANGE_LOG_ONLY_ERRORS = false;

    const XML_adminemail = 'example_section/general/adminemail';

    const XML_resID = 'example_section/general/text_example';
    const XML_password = 'example_section/general/reseller_pw';
    const XML_POprefix = 'example_section/general/ponum';
    const XML_api_url = '';
    const XML_SandboxURL = 'example_section/Interface/SandboxURL';
    const XML_ProductionURL = 'example_section/Interface/ProductionURL';
    const XML_UseSandbox = 'example_section/general/dropdown_example';

    const XML_enable_prepay = 'example_section/general/dropdown_usecc';
    const XML_CC_TYPE = 'example_section/general/CCType';
    const XML_CC_NAME = 'example_section/general/CCName';
    const XML_CC_NUMBER = 'example_section/general/CCNumber';
    const XML_CC_EXPIRY_YEAR = 'example_section/general/CCExpiryYear';
    const XML_CC_EXPIRY_MONTH = 'example_section/general/CCExpiryMonth';
    const XML_CC_EMAIL = 'example_section/general/CCEmail';

    public $xmp_adminemail = '';

    public $xmp_resID = '';
    public $xmp_password = '';
    public $xmp_POprefix = '';
    public $xmp_api_url = '';
    public $xmp_SandboxURL = '';
    public $xmp_ProductionURL = '';
    public $xmp_UseSandbox = '';

    public $xmp_enable_prepay = '';
    public $xmp_CC_TYPE = '';
    public $xmp_CC_NAME = '';
    public $xmp_CC_NUMBER = '';
    public $xmp_CC_EXPIRY_YEAR = '';
    public $xmp_CC_EXPIRY_MONTH = '';
    public $xmp_CC_EMAIL = '';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

  public function __construct(
    \Magento\Framework\ObjectManagerInterface $objectManager,
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    Email $helperEmail,
    \Magento\Framework\Message\ManagerInterface $messageManager,
    \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
  ) {
        $this->_objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->helperEmail = $helperEmail;
        $this->_messageManager = $messageManager;
        $this->_orderRepository = $orderRepository;
  }

    public function get_xmp_config()
    {

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $this->xmp_adminemail = $this->scopeConfig->getValue(self::XML_adminemail, $storeScope);
        $this->xmp_resID = $this->scopeConfig->getValue(self::XML_resID, $storeScope);
        $this->xmp_password = $this->scopeConfig->getValue(self::XML_password, $storeScope);
        $this->xmp_POprefix = $this->scopeConfig->getValue(self::XML_POprefix, $storeScope);
        $this->xmp_SandboxURL = $this->scopeConfig->getValue(self::XML_SandboxURL, $storeScope);
        $this->xmp_ProductionURL = $this->scopeConfig->getValue(self::XML_ProductionURL, $storeScope);
        $this->xmp_UseSandbox = $this->scopeConfig->getValue(self::XML_UseSandbox, $storeScope);
        $this->xmp_enable_prepay = $this->scopeConfig->getValue(self::XML_enable_prepay, $storeScope);
        $this->xmp_CC_TYPE = $this->scopeConfig->getValue(self::XML_CC_TYPE, $storeScope);
        $this->xmp_CC_NAME = $this->scopeConfig->getValue(self::XML_CC_NAME, $storeScope);
        $this->xmp_CC_NUMBER = $this->scopeConfig->getValue(self::XML_CC_NUMBER, $storeScope);
        $this->xmp_CC_EXPIRY_YEAR = $this->scopeConfig->getValue(self::XML_CC_EXPIRY_YEAR, $storeScope);
        $this->xmp_CC_EXPIRY_MONTH = $this->scopeConfig->getValue(self::XML_CC_EXPIRY_MONTH, $storeScope);
        $this->xmp_CC_EMAIL = $this->scopeConfig->getValue(self::XML_CC_EMAIL, $storeScope);

        if( $this->xmp_UseSandbox == 0 )
        {
             $this->xmp_api_url = $this->xmp_SandboxURL;
        }
        else
        {
             $this->xmp_api_url = $this->xmp_ProductionURL;
        }

        if( $this->xmp_enable_prepay == 0 )
        {
             $this->xmp_enable_prepay = true;
        }
        else
        {
             $this->xmp_enable_prepay = false;
        }

    } //...get_xmp_config()

    protected function xchangeLog($intext, $is_error = false)
    {

            if ($intext == '') /** Skip empty entries **/
                    return;

            $outtext = $intext."\n****************\n";

            if (self::XCHANGE_LOG_ONLY_ERRORS == false || $is_error)
            {
                    try {
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/XCHANGEMarket.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($outtext);
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($e->getMessage());
                    } catch (\Exception $e) {
                        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($e->getMessage());
                    }
                    if (self::XCHANGE_EMAIL_ERRORS_TO != '' && $is_error && $this->xmp_adminemail != '')
                    {
                            $mailBody = "XCHANGE Error.\n".$intext."\n\n";
                            $header = "Reply-To: ".$this->xmp_adminemail."\r\n"."From: ".$this->xmp_adminemail."\r\n";
                            $result = mail(self::XCHANGE_EMAIL_ERRORS_TO, "XCHANGE Error", $mailBody, $header);
                    }
            }
    }

  public function execute(\Magento\Framework\Event\Observer $observer)
  {

        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('sank__DBUG: OrderPlacebefore() called...');

        $this->get_xmp_config();

    		$logtext = "";

    		$logerror = false;

        $mageV = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();

      	$order = $observer->getEvent()->getOrder();
      	$order_id = $order->getID();

        #!---> sank__ Z. Above orderID only works for Zero Sub-total Checkout. We need this to be consistent across multiple payment methods.
        #!---> sank__ Z. sales_order_invoice_pay needs to get called for the order object to get generated and Order ID to be formed. Note that Order ID not available for payments other than Zero Sub-total Checkout in this Observer!
        $order_number = $order->getIncrementId();

        $customer_email = $order->getCustomerEmail();
        $customerId = $order->getCustomerId();

	\Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('Order_ID via order.getID():@@'.$order_id.'@@');
	\Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('Order_ID/number.increment:@@'.$order_number.'@@');
        #! sank__
        $xchange_item_count = 0;
        $productNamesPerInternalSku = array();
        $prodAll = array();
        $linesIN = array();
	foreach ($order->getAllItems() as $item)
	{

    $prodid = $item->getProductId();
    $prodsku = $item->getSku();
    $prodname = $item->getName();
    $prodqty = $item->getQtyOrdered();
    $prodAll[] = array("prodid" => $prodid, "prodsku" => $prodsku, "prodname" => $prodname, "prodqty" => $prodqty);

		$XchangeInternalSku = $item->getProduct()->getData('XCHANGE_Internal_Sku');

    $itemQuantity = intval($item->getQtyOrdered());

		\Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('Order:@@'.'Name:'.$item->getName().', ItemID:'.$item->getProductId().', XMP_InternalSku:'.$XchangeInternalSku.'@@');

                /*** If it's an XCHANGE product, add to our line items array... ***/
                if ($XchangeInternalSku)
                {

                        $logtext .=  "XchangeInternalSku found: ".$XchangeInternalSku." for Order Item: ".$prodname." Qty: ".$itemQuantity."\n";
                        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($logtext);

                        for ($i=0; $i<$itemQuantity; $i++)
                        {

                                $linesIN[$xchange_item_count] = array(
                                                                    "InternalSku" => $XchangeInternalSku,
                                                                    "HardwareNumber" => '',
                                                                    "opt1" => '',
                                                                    "opt2" => ''
                                                            );

                                $productNamesPerInternalSku[$XchangeInternalSku] = $prodname; /* Map the product name to our Internal Sku so we can display it if needed in error message, below */

                                $xchange_item_count++;
                        }

                }

	}

        $stopOrder = false;

        /* If any XCHANGE items, do T_Reserve SOAP call... */
        if (isset($linesIN))
        {
                $logtext .= "BEFORE PAYMENT:\nXCHANGE item count: ".$xchange_item_count."\n";
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($logtext);

                if ($xchange_item_count > 0) /*** There are XCHANGE items in this order to reserve... ***/
                {
                        /*** Create our SOAP client... ***/
                        try
                        {
                                $soapClient = new \Zend\Soap\Client($this->xmp_api_url);
                                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('XMP have soapclient woohooo!');
                        }
                        catch (SoapFault $fault)
                        {
                                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('XMP SoapClient blunder!');
                                $logtext .= "<P>XCHANGE SOAP Exception creating client, faultcode: ".$fault->faultcode." faultstring: ".$fault->faultstring."\n";
                                $logerror = true;
                                $stopOrder = true;
                                goto bailout2;
                        }

                        /* Construct a PO number from the order number. */
                        $prefix = $this->xmp_POprefix;
                        $po_number = sprintf("%s%d", $prefix, $order_number);

                        $reserveStatus = -999;

                        $logtext .= "Trying XCHANGE T_Reserve() for Order #: ".$order_number."\n";
                        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($logtext);

                        if ($this->xmp_enable_prepay == 1)
                        {
                                $cc_name = $this->xmp_CC_NAME;
                                $cc_expiry = $this->xmp_CC_EXPIRY_MONTH."/".$this->xmp_CC_EXPIRY_YEAR;
                                $cc_number = $this->xmp_CC_NUMBER;
                                $cc_email = $this->xmp_CC_EMAIL;
                                $cc_type = $this->xmp_CC_TYPE;
                        }
                        else
                        {
                                $cc_name = '';
                                $cc_expiry = '';
                                $cc_number = '';
                                $cc_email = '';
                                $cc_type = '';
                        }

                        try
                        {
                                $resultReserve = $soapClient->T_Reserve(
                                       array(	"po" => array(
                                                                "UserName" => $this->xmp_resID,
                                                                "Password" => $this->xmp_password,
                                                                "Status" => 'Mage'.$mageV,
                                                                "POnumber" => $po_number,
                                                                "CC_NAME" => $cc_name,
                                                                "CC_EXPIRY" => $cc_expiry,
                                                                "CC_CARDNUMBER" => $cc_number,
                                                                "CC_EMAIL" => $cc_email,
                                                                "CC_TYPE" => $cc_type,
                                                                "feature1" => "",
                                                                "feature2" => "",
                                                                "linesIN" => $linesIN
                                                ))
                                        );
                        }
                        catch (SoapFault $fault)
                        {
                                $logtext .= "<P>XCHANGE SOAP Exception calling T_Reserve, faultcode: ".$fault->faultcode." faultstring: ".$fault->faultstring."\n";
                                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($logtext);

                                $logerror = true;
                                $stopOrder = true;
                                goto bailout2;
                        }

                        $reserveStatus = $resultReserve->T_ReserveResult->Status;
                        $Transaction_Number = $resultReserve->T_ReserveResult->Transaction_Number;
                        $logtext .= "T_Reserve Status: ".$reserveStatus." | Transaction_Number: ".$Transaction_Number."\n";
                        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($logtext);

                        if ($reserveStatus < 0)
                        {
                                $logtext .= "Failed to reserve item(s) in order ".$order_number." for customer_email: ".$customer_email.". Stopping Order.\n";
                                $stopOrder = true; /*** We were unable to reserve... ***/
                                $logerror = true;
                        }
                        else
                        {
                                $logtext .= "Item(s) reserved for order ".$order_number." for customer_email: ".$customer_email." with tx:".$Transaction_Number."\n";
                                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($logtext);

                                $txText = $Transaction_Number;
                                $order->setData('XCHANGE_response', $txText)->save(); /*** Save our Transaction ID so we can Finalize after payment. ***/

                                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('sank__ Z. After XCHANGE_response saving. +++ Before Order_save');
                                $order->save();
                                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('sank__ Z. After XCHANGE_response saving. +++ Before Order_save');

                        }

                        bailout2:
                        {
                                if ($stopOrder)
                                {
                                        $logtext .= $messageTxt = sprintf(self::XCHANGE_RESERVE_FAILED_BROWSER_ERROR_MESSAGE, implode(', ', $productNamesPerInternalSku));

                                        $this->xchangeLog($logtext, $logerror);

                                        $this->_messageManager->addError(__($messageTxt));
                                        throw new \Magento\Framework\Exception\LocalizedException(
                                                __($messageTxt)
                                        );
                                        /*** Execution stops here ***/
                                        return; /*** Just for clarity, exception above should stop execution ***/
                                }
                        }

                }
                else /*** No XCHANGE items in order, just bail without logging ***/
                {
                        return;
                }

        }

        $this->xchangeLog($logtext, $logerror);
        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('sank__ Z. After everything. Before return/this...');

        #!-- sank__ Z. This will throw an error for Non-Zero Sub-total Checkout - value greater than $0.00 :: other payment methods will not work, because Order ID never gets generated. Only for Zero Sub-total Checkout Order ID and Order gets generated I believe. Therefore, resellers must do Zero Sub-total Checkout when doing testing. If not, they will get error.
        try{
            if( $this->xmp_UseSandbox == 0 )
            {

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $subTotal = $objectManager->get('\Magento\Checkout\Model\Session')->getQuote()->getSubtotal();
                if($subTotal == 0)
                $this->sandboxFinalize($order_id);

            }
        } catch (\Exception $e) {
            if ($e->getPrevious()) {}
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($e->getMessage());
            throw new CouldNotSaveException(
                    __('An error occurred on the server. Please try to place the order again.'),
                    $e
            );
        }

        return $this;





  }

    public function sandboxFinalize($order_id = "")
    {

        $logtext = "";

        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('GCADBUG: invoicePay() called...');

        $order = $this->_orderRepository->get($order_id);   #!-- sank__ Z. Order ID used below. We should really be logging Order Increment ID and needs to be consistent across. An update required.

        $logtext = "INVOICE TIME Order ID: ".$order_id."\n";

        $logerror = false;

        $this->xchangeLog($logtext, $logerror);

        /*** True makes it generate license info even though amt_authorized is less than ***/

        #!-- sank__
        $payment_is_authorized = true;

        $this->get_xmp_config();

        $logtext .= "AFTER PAYMENT Order ID: ".$order_id."\n";

    		$Transaction_Number = $order->getData('XCHANGE_response'); /*** If we've done an XCHANGE T_Reserve on this Order, the transaction ID will be in here. ***/

    		$preexistingLicenceInfo = $order->getData('XCHANGE_license_info'); /*** Check if we've done it already so we can break out if we have ***/

    		if ($preexistingLicenceInfo != "")
    		{
    			$logtext .= "License Info already present.\n";
    		}
    		elseif ($Transaction_Number != "")
    		{
                        $orderItems = $order->getAllItems();

                        $xchange_item_count = 0;

                        $productNamesPerInternalSku = array();

                        foreach ($orderItems as $sItem)
                        {

                                $itemName = $sItem->getName();

                                $itemQuantity = intval($sItem->getQtyOrdered());

                                $customer_email = $order->getData('customer_email');

                                $XchangeInternalSku = $sItem->getProduct()->getData('XCHANGE_Internal_Sku');

                                /*** If an XCHANGE product, add to our line items array... ***/
                                if ($XchangeInternalSku)
                                {
                                        $logtext .=  "XchangeInternalSku found: ".$XchangeInternalSku." for order item: ".$itemName." | Qty: ".$itemQuantity."\n";

                                        for ($i=0; $i<$itemQuantity; $i++)
                                        {
                                                $linesIN[$xchange_item_count] = array(
                                                                                    "InternalSku" => $XchangeInternalSku,
                                                                                    "HardwareNumber" => '',
                                                                                    "opt1" => '',
                                                                                    "opt2" => ''
                                                                            );

                                                $productNamesPerInternalSku[$XchangeInternalSku] = $itemName; /** Map the product name to our Internal Sku so we can display it with returned License Info, below **/

                                                $xchange_item_count++;
                                        }

                                }

                        }

                        $resultObj = NULL;

                        if ($xchange_item_count > 0)
                        {
                                $logtext .= "AFTER PAYMENT:\n";

                                if ($payment_is_authorized == true) /*** If payment made or (or forced true because we're called at Invoice time) retrieve product license info, otherwise throw exception to stop Order and send an alert email to the admin ---> Still gotta do this. ***********/
                                {
                                        try
                                        {
                                                $soapClient = new \Zend\Soap\Client($this->xmp_api_url);
                                        }
                                        catch (SoapFault $fault)
                                        {
                                                $logtext .= "XCHANGE Exception creating SOAP client, faultcode: ".$fault->faultcode." faultstring: ".$fault->faultstring;
                                                $logerror = true;
                                                #! my/bailoutY begin
                                                $messageTxt = sprintf(self::XCHANGE_EMAIL_ERROR_MESSAGE, implode(', ', $productNamesPerInternalSku));

                                                $this->xchangeLog($logtext, $logerror);

                                                $this->_messageManager->addError(__($messageTxt));
                                                throw new \Magento\Framework\Exception\LocalizedException(
                                                        __($messageTxt)
                                                );
                                                /*** Execution stops here ***/
                                                return; /*** Just for clarity, exception above should stop execution ***/
                                                #! my/bailoutY end
                                        }

                                        $status = -99999;

                                        if (is_object($soapClient))
                                        {
                                                if ($Transaction_Number != '')
                                                {
                                                        $logtext .=  "xchange_tx found in Order, tx: ".$Transaction_Number."\n";

                                                        try
                                                        {
                                                                $result = $soapClient->T_Finalize( array (
                                                                                "UserName" => $this->xmp_resID,
                                                                                "Password" => $this->xmp_password,
                                                                                "CC_TRANSNUM" => $Transaction_Number
                                                                        ) );

                                                                $resultObj = $result->T_FinalizeResult;
                                                        }
                                                        catch (SoapFault $fault)
                                                        {
                                                                $logtext .= "<P>XCHANGE SOAP Exception calling T_Finalize, faultcode: ".$fault->faultcode." faultstring: ".$fault->faultstring."\n";
                                                                $logerror = true;
                                                                #! my/bailoutX begin
                                                                $messageTxt = sprintf(self::XCHANGE_EMAIL_ERROR_MESSAGE, implode(', ', $productNamesPerInternalSku));

                                                                $this->xchangeLog($logtext, $logerror);

                                                                $this->_messageManager->addError(__($messageTxt));
                                                                throw new \Magento\Framework\Exception\LocalizedException(
                                                                        __($messageTxt)
                                                                );
                                                                /*** Execution stops here ***/
                                                                return; /*** Just for clarity, exception above should stop execution ***/
                                                                #! my/bailoutX end
                                                        }

                                                        if (is_object($resultObj) && property_exists($resultObj, "Status"))
                                                        {
                                                                $status = $resultObj->Status;
                                                                $logtext .= "T_FinalizeResult->Status: ".$status."\n";
                                                        }
                                                        else
                                                        {
                                                                $logtext .= "T_FinalizeResult->Status: not found. Cancelling. ".$resultObj->$status."\n";
                                                                $logerror = true;
                                                        }
                                                }
                                                else
                                                {
                                                        $logtext .=  "xchange_tx NOT FOUND in Order!\n";
                                                        $logerror = true;

                                                }


                                                ####################################################################
                                                /*** If we haven't bailed by now, we should have results. Format them as HTML table... ***/

                                                $licenceinfotext = "";

                                                if (is_object($resultObj))
                                                {
                                                        if (property_exists($resultObj, "Status"))
                                                        {
                                                                $status = $resultObj->Status;
                                                                $logtext .= "Finalize result: ".$status."\n";

                                                                if ($status == 0 && property_exists($resultObj, "linesOUT"))
                                                                {
                                                                        if (property_exists($resultObj->linesOUT, "partNumberOUT"))
                                                                        {
                                                                                if (is_array($resultObj->linesOUT->partNumberOUT)) /* To distinguish multiple from single results */
                                                                                {
                                                                                        $licenceinfotext .=  "<P><h2>Product Licensing Information</h2>\n<P><TABLE>\n";
                                                                                        foreach ($resultObj->linesOUT->partNumberOUT as $key => $val)
                                                                                        {
                                                                                                if (property_exists($val, "InternalSku"))
                                                                                                $licenceinfotext .= "<tr><td colspan=20><hr/></td></tr>";
                                                                                                $licenceinfotext .= "<TR><TD>Product:</TD><TD>".$productNamesPerInternalSku[$val->InternalSku]."</TD></TR>\n";

                                                                                                if (property_exists($val, "SerialNumber"))
                                                                                                {
                                                                                                        $serial_number = $val->SerialNumber;
                                                                                                        if ($serial_number != '')
                                                                                                        $licenceinfotext .= "<TR><TD>Serial Number:</TD><TD>".$serial_number."</TD></TR>\n";
                                                                                                }

                                                                                                if (property_exists($val, "Download_Path"))
                                                                                                {
                                                                                                        $download_path = $val->Download_Path;
                                                                                                        if ($download_path != '')
                                                                                                        $licenceinfotext .= "<TR><TD>Download:</TD><TD><A href=\"".$download_path."\">".$download_path."</a></TD></TR>\n";
                                                                                                }

                                                                                                if (property_exists($val, "SupportContact"))
                                                                                                {
                                                                                                        $support_contact = $val->SupportContact;
                                                                                                        if ($support_contact != '')
                                                                                                        $licenceinfotext .= "<TR><TD>Support Contact:</TD><TD>".$support_contact."</TD></TR>\n";
                                                                                                }

                                                                                                $licenceinfotext .= "<TR><TD colspan=\"2\"></TD></TR>\n";
                                                                                        }
                                                                                        $licenceinfotext .= "</TABLE></br>\n";
                                                                                }
                                                                                else /*** Case for single purchase item, no array indexing ***/
                                                                                {
                                                                                        $licenceinfotext .=  "<P><h2>Product Licensing Information</h2>\n<P><TABLE>\n";

                                                                                        if (property_exists($resultObj->linesOUT->partNumberOUT, "InternalSku"))
                                                                                        $licenceinfotext .= "<tr><td colspan=20><hr/></td></tr>";
                                                                                        $licenceinfotext .= "<TR><TD>Product:</TD><TD>".$productNamesPerInternalSku[$resultObj->linesOUT->partNumberOUT->InternalSku]."</TD></TR>\n";

                                                                                        if (property_exists($resultObj->linesOUT->partNumberOUT, "SerialNumber"))
                                                                                        {
                                                                                                $serial_number = $resultObj->linesOUT->partNumberOUT->SerialNumber;
                                                                                                if ($serial_number != '')
                                                                                                $licenceinfotext .= "<TR><TD>Serial Number:</TD><TD>".$serial_number."</TD></TR>\n";
                                                                                        }

                                                                                        if (property_exists($resultObj->linesOUT->partNumberOUT, "Download_Path"))
                                                                                        {
                                                                                                $download_path = $resultObj->linesOUT->partNumberOUT->Download_Path;
                                                                                                if ($download_path != '')
                                                                                                $licenceinfotext .= "<TR><TD>Download:</TD><TD><A href=\"".$download_path."\">".$download_path."</a></TD></TR>\n";
                                                                                        }

                                                                                        if (property_exists($resultObj->linesOUT->partNumberOUT, "SupportContact"))
                                                                                        {
                                                                                                $support_contact = $resultObj->linesOUT->partNumberOUT->SupportContact;
                                                                                                if ($support_contact != '')
                                                                                                $licenceinfotext .= "<TR><TD>Support Contact:</TD><TD>".$support_contact."</TD></TR>\n";
                                                                                        }

                                                                                        $licenceinfotext .= "<TR></TR></TABLE></br>\n";

                                                                                }

                                                                        }

                                                                    try {
                                                                        $xmp_ord = $order;

                                                                        $xmp_ord->setData('customer_note_notify',true)->save();

                                                                        $xmp_ord->setData('XCHANGE_license_info',$licenceinfotext)->save();
                                                                        $xmp_ord->setData('customer_note',$licenceinfotext)->save();	/*** optional works! ***/

                                                                        $xmp_ord->save();

                                                                        $xch_messageTxt = "Your XCHANGE Product Licensing Info for your order will be sent to your email shortly. You can also view your XCHANGE Licensing Info under your 'My Orders' tab in your account.";
                                                                        if ($xmp_ord) {

                                                                            $orderCommentSender = $this->_objectManager->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
                                                                            $orderCommentSender->send($xmp_ord, $notify='1', $xch_messageTxt);
                                                                            #!--sank__ Z.09/22: Helper Email call below.
                                                                            $this->helperEmail->sendEmail($order_id);

                                                                        }

                                                                    }
                                                                    catch (Exception $e)
                                                                    {}

                                                                    $logtext .= "XCHANGE License Info sent in Order Update email: ".$licenceinfotext." for Order #".$order_id.", customer_email: ".$customer_email."\n";

                                                                }

                                                        }
                                                }
                                                else
                                                {
                                                        $logtext .= "Finalize failed for Order #:".$order_id.", customer_email: ".$customer_email."\n";
                                                        $logerror = true;

                                                        bailout1:

                                                        $messageTxt = sprintf(self::XCHANGE_EMAIL_ERROR_MESSAGE, implode(', ', $productNamesPerInternalSku));

                                                        $this->xchangeLog($logtext, $logerror);

                                                        $this->_messageManager->addError(__($messageTxt));
                                                        throw new \Magento\Framework\Exception\LocalizedException(
                                                                __($messageTxt)
                                                        );
                                                        /*** Execution stops here ***/
                                                        return; /*** Just for clarity, exception above should stop execution ***/

                                                }
                                        }
                                        else
                                        {
                                                $logtext .= "SOAP connection to XCHANGE failed.\n";
                                        }
                                }
                        }
                        else /*** no XCHANGE items in order, just bail without logging ***/
                        {
                                return;
                        }

        }

		$order->save();

		$this->xchangeLog($logtext, $logerror);
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('sank__ Z. Sandbox Finalize end.');

    return;

    }

}
