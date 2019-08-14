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

class Freestyle_Advancedexport_Model_Order_Api 
    extends Mage_Sales_Model_Order_Api
{
    // Mage_Sales_Model_Api_Resource {//Mage_Api_Model_Resource_Abstract {
    public function canInvoice($orderId)
    {
        try {
            $orderModel = $this->getOrder($orderId);
            $result = $orderModel->canInvoice();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        } catch (Exception $ex) {
            $this->_fault('data_invalid', $ex->getMessage());
        }
        return intval($result);
    }
    
    public function canShip($orderId)
    {
        try {
            $orderModel = $this->getOrder($orderId);
            $result = $orderModel->canShip();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        } catch (Exception $ex) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return intval($result);
    }
    
    public function canCreditMemo($orderId)
    {
        try {
            $orderModel = $this->getOrder($orderId);
            $result = $orderModel->canCreditMemo();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        } catch (Exception $ex) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return intval($result);
    }
    
    public function checkactions($orderId)
    {
        $result = array();
        try {
            //$orderModel = $this->getOrder($orderId);
            $orderModel = $this->getOrder();
            $orderModel->load($orderId);
            $result['canInvoice']       = $orderModel->canInvoice();
            $result['canShip']          = $orderModel->canShip();
            $result['canCreditMemo']    = $orderModel->canCreditMemo();
            $result['canEdit']          = $orderModel->canEdit();
            $result['canCancel']        = $orderModel->canCancel();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        } catch (Exception $ex) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        if (!$orderModel->getId()) {
            $this->_fault('not_exists', "Order Id not found.");
        }
        $result = array($result, $orderModel->canInvoiceReason());
        return $result;
    }


    //protected function getOrder($orderId)
    protected function getOrder()
    {
        //return Mage::getModel('sales/order')->load($orderId);
        return Mage::getModel('freestyle_utilities/order');//->load($orderId);
    }
}
