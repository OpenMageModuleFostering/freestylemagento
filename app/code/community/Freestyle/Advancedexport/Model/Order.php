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

class Freestyle_Advancedexport_Model_Order extends Mage_Sales_Model_Order
{
    public function canInvoiceReason()
    {
        $reason = array(
            canUnhold => 0,
            isPaymentReview => 0,
            isCanceled => 0,
            isCompleteState => 0,
            isClosedState => 0,
            actionFlagIsInvoice => 0,
            someItemsAreInvoiced => 1,
            unknownError => 0
        );
        $reason['canUnhold'] = intval($this->canUnhold());
        $reason['isPaymentReview'] = intval($this->isPaymentReview());
        
        $state = $this->getState();
        $reason['isCanceled'] = intval($this->isCanceled());
        $reason['isCompleteState'] = intval($state === self::STATE_COMPLETE);
        
        $reason['actionFlagIsInvoice'] = 
            intval($this->getActionFlag(self::ACTION_FLAG_INVOICE) === false);
        
        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToInvoice()>0 && !$item->getLockedDoInvoice()) {
                $reason['someItemsAreInvoiced'] = 0;
                break;
            }
        }
        
        if ($this->canUnhold() || $this->isPaymentReview()) {
            return $reason;
        }
        
        $reasonCheck = $this->isCanceled() 
                || $state === self::STATE_COMPLETE 
                || $state === self::STATE_CLOSED;
        if ($reasonCheck) {
            return $reason;
        }

        if ($this->getActionFlag(self::ACTION_FLAG_INVOICE) === false) {
            return $reason;
        }
        
        if ($reason['someItemsAreInvoice'] == 1) {
            return $reason;
        }
        
        $reason['unknownError'] = 1;
        return $reason;
    }
}
