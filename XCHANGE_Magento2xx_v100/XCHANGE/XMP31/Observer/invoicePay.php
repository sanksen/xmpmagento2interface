<?php
namespace XCHANGE\XMP31\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use XCHANGE\XMP31\Helper\Email;


class invoicePay implements ObserverInterface
{

    private $helperEmail;

    protected $_messageManager;
    protected $_objectManager;

    /* Sent to Customer in Order Update email in lieu of product license info when T_Finalize call fails. Add list of XCHANGE product names with %s */
    const XCHANGE_EMAIL_ERROR_MESSAGE = "Product license information currently unavailable for %s. Please contact support@example.com";
    /* Message displayed when Order is stopped when T_Reserve fails (due to licenses out of stock, bad product config on backend, bad network, etc.)  Add list of XCHANGE product names with %s. */
    #!---> sank__ Z.T_Reserver. Can leave this out here: const XCHANGE_RESERVE_FAILED_BROWSER_ERROR_MESSAGE = "We're sorry. One or more of the following items in your cart currently cannot be reserved: %s Contact support@example.com";
    /* Emails to send copies of XCHANGE errors to. (Separate multiple email addresses with commas, no spaces) */
    #!-- const XCHANGE_EMAIL_ERRORS_TO = 's.senathira@xchangemarket.com,gatkins2004@hotmail.com';
    const XCHANGE_EMAIL_ERRORS_TO = 's.senathira@xchangemarket.com';
    /* Who the alert email appears to be sent from. Typically, your site support email */
    const XCHANGE_ADMIN_EMAIL = 's.senathira@xchangemarket.com';
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
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->helperEmail = $helperEmail;
        $this->_messageManager = $messageManager;
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

    public function execute(EventObserver $observer)
    {

        $logtext = "";

        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('GCADBUG: invoicePay() called...');

        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $order_id = $order->getID(); #---> sank__ Z. We need the increment ID, because OrderPlaceafter Observer uses increment ID for logging/etc. We need this to be consistent across.
        $logtext = "INVOICE TIME Order ID: ".$order_id."\n";
        $logerror = false;

        $this->xchangeLog($logtext, $logerror);

        #!-- sank__
        $payment_is_authorized = true;

        $this->get_xmp_config();

        $logtext .= "AFTER PAYMENT Order ID: ".$order_id."\n";

  		$Transaction_Number = $order->getData('XCHANGE_response'); /*** If we've done an XCHANGE T_Reserve on this Order, the transaction ID will be in here. ***/

  		$preexistingLicenceInfo = $order->getData('XCHANGE_license_info'); /*** Check if we've done it already so we can break out if we have... ***/

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

                              $amt_authorized = $order->getTotalPaid();
                              $grand_total = $invoice->getGrandTotal();
                              $logtext .= "grand total: ".$grand_total.", amt_authorized: ".$amt_authorized."\n";

                              if ($amt_authorized == $grand_total || $payment_is_authorized == true) /*** If payment made or (or forced true because we're called at Invoice time) retrieve product license info, otherwise throw exception to stop Order and send an alert email to the admin ---> Still gotta do this. ***********/
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
                                                                              if (is_array($resultObj->linesOUT->partNumberOUT)) /* To distinguish multiple from single results... */
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

                                                                      /*** This field is also visible on the admin site. ***/
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



      return $this;

    }


}
