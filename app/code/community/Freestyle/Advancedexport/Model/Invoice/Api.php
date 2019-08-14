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

class Freestyle_Advancedexport_Model_Invoice_Api 
extends Mage_Api_Model_Resource_Abstract
{
    public function cancapture($invoiceId)
    {
        try {
            $invoiceModel = $this->getInvoice($invoiceId);
            $result = $invoiceModel->canCapture();
        } catch (Mage_Core_Exception $e) {
            return $this->_fault('data_invalid', $e->getMessage());
        } catch (Exception $ex) {
            return $this->_fault('data_invalid', $ex->getMessage());
        }
        return intval($result);
    }
    protected function getInvoice($invoiceId)
    {
        return Mage::getModel('sales/order_invoice')->load($invoiceId);
    }
}
