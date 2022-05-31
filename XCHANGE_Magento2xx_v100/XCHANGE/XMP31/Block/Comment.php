<?php

namespace XCHANGE\XMP31\Block;



class Comment extends \Magento\Framework\View\Element\Template
{


    /**
     * @var Order
     */
    protected $_order;
    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;
/*** ***********/
protected $orderRepository;


   public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
         array $data = []
     ) {
    $this->orderRepository = $orderRepository;
     parent::__construct($context, $data);
   }

   //----Your Block Method----
   public function sayHello()
   {
      return __('XCHANGE: Hello vendors and resellers.<br/><br/>');
   }

    public function getSource()
    {
        return $this->_source;
    }

    /**
     * @return Order
     */
    public function getOrder($id)
    {
        return $this->orderRepository->get($id);
    }

    public function getOrdID()
    {

        if ($this->_order === null)
            if ($this->hasData('order'))
                $this->_order = $this->_getData('order');

        return $this->_order->getIncrementId();
    }




}
