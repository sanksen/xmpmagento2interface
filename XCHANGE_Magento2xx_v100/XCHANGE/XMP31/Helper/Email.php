<?php
namespace XCHANGE\XMP31\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $inlineTranslation;
    protected $escaper;
    protected $transportBuilder;
    protected $logger;
    protected $orderRepository;

    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $context->getLogger();
        $this->orderRepository = $orderRepository;
    }

    public function sendEmail($order_id = "")
    {

        #!-- $order_id = $order->getID();
        $order = $this->orderRepository->get($order_id);
        $orderIncrementId = $order->getIncrementId();
        $xchli = $order->getData('XCHANGE_license_info');
        $custname = $order->getCustomerName();
        $custemail = $order->getCustomerEmail();
        $custmsg = "";
        $custmsg = "Please find below your XCHANGE License Info for your order " . $orderIncrementId;

        try {
            $this->inlineTranslation->suspend();
            $sender = [
                'name' => $this->escaper->escapeHtml('XCHANGE Sales'),
                'email' => $this->escaper->escapeHtml('s.senathira@xchangemarket.com'),
            ];
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('xch_license_template')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars([
                    'orderid' => $orderIncrementId,
                    'xchli' => $xchli,
                    'custname' => $custname,
                    'custmsg' => $custmsg,
                ])
                ->setFrom($sender)
                ->addTo($custemail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
