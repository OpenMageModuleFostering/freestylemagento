<?php

/************************************************************************
  © 2013,2014, 2015 Freestyle Solutions.   All rights reserved.
  FREESTYLE SOLUTIONS, DYDACOMP, FREESTYLE COMMERCE, and all related logos 
  and designs are trademarks of Freestyle Solutions (formerly known as Dydacomp)
  or its affiliates.
  All other product and company names mentioned herein are used for
  identification purposes only, and may be trademarks of
  their respective companies.
************************************************************************/

class Freestyle_Advancedexport_Model_Exportmodels_Order 
extends Mage_Sales_Model_Order_Api
{

    protected function _initOrder($orderIncrementId)
    {
        $order = Mage::getModel('sales/order');

        /* @var $order Mage_Sales_Model_Order */

        $order->loadByIncrementId($orderIncrementId);

        if (!$order->getId()) {
            $this->_fault('not_exists');
        }
        
        return $order;
    }

    /**
     * Retrieve full order information
     *
     * @param string $orderIncrementId
     * @return array
     */
    public function info($orderIncrementId)
    {
        //DE-10150 - gracefully handle error / exception
        try {
            $order = $this->_initOrder($orderIncrementId);
        } catch (Exception $e) {
            $result = $e->getMessage();
            return false;
        }

        if ($order->getGiftMessageId() > 0) {
            $order->setGiftMessage(
                Mage::getSingleton('giftmessage/message')
                    ->load($order->getGiftMessageId())->getMessage()
            );
        }

        $result = $this->_getAttributes($order, 'order');

        $result['shipping_address'][]['salesOrderAddressEntity'] = 
                $this->_getAttributes(
                    $order->getShippingAddress(), 
                    'order_address'
                );
        $result['billing_address'][]['salesOrderAddressEntity'] = 
                $this->_getAttributes(
                    $order->getBillingAddress(), 
                    'order_address'
                );
        $result['items'] = array();

        foreach ($order->getAllItems() as $item) {
            if ($item->getGiftMessageId() > 0) {
                $item->setGiftMessage(
                    Mage::getSingleton('giftmessage/message')
                    ->load($item->getGiftMessageId())->getMessage()
                );
            }
            $result['items'][]['salesOrderItemEntity'] = 
                    $this->_getAttributes($item, 'order_item');
        }

        //Fixes for Authorize Payment
        $paymentData = $this->_getAttributes(
            $order->getPayment(), 
            'order_payment'
        );
        if (isset($paymentData['additional_information']['authorize_cards'])) {
            $authorizeCards = array();
            foreach ($paymentData['additional_information']['authorize_cards'] as $oneCart) {
                $authorizeCards[]['card'] = $oneCart;
            }
            $paymentData['additional_information']['authorize_cards'] = 
                    $authorizeCards;
        }

        $result['payment'][]['salesOrderPaymentEntity'] = $paymentData;
        //$result['payment']['salesOrderPaymentEntity'] = 
        //$this->_getAttributes($order->getPayment(), 'order_payment');

        $result['status_history'] = array();

        foreach ($order->getAllStatusHistory() as $history) {
            $result['status_history']['salesOrderStatusHistoryEntity'][] = 
                $this->_getAttributes($history, 'order_status_history');
        }

        return $result;
    }
}
