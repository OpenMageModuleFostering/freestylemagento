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
class Freestyle_Advancedexport_Helper_Queue extends Mage_Core_Helper_Abstract
{
    public function getEnableQueue()
    {
        return
            Mage::getStoreConfig('freestyle_advancedexport/queue/enable_queue');
    }

    public function getIgnoreApi()
    {
        return
            Mage::getStoreConfig('freestyle_advancedexport/queue/ignore_api');
    }

    public function getQueueBatchSize()
    {
        $classDesc = 'freestyle_advancedexport/queue/queuebatchsize';

        return Mage::getStoreConfig($classDesc);
    }

    public function getQueueServiceUrl()
    {
        $classDesc = 'freestyle_advancedexport/queue/queue_service_url';

        return Mage::getStoreConfig($classDesc);
    }

    public function getSendAsync()
    {
        return
            Mage::getStoreConfig('freestyle_advancedexport/queue/send_async');
    }

    public function getSendOrderDependencies()
    {
        $classDesc = 'freestyle_advancedexport/queue/send_order_dependencies';

        return Mage::getStoreConfig($classDesc);
    }
}
