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

class Freestyle_Advancedexport_Model_Exportmodels_Customergroup 
extends Mage_Customer_Model_Group_Api
{
    
    protected $_mapAttributes = array(
        'customer_group_id' => 'customer_group_id',
        'customer_group_code' => 'customer_group_code'
    );

    protected function _initCustomerGroup($customerGroupId)
    {
        $customerGroup = Mage::getModel('customer/group')
                ->load($customerGroupId);
        
        return $customerGroup;
    }
    
    /**
     * Retrieve customer group data
     *
     * @param int $customerGroupId
     * @return array
     */
    public function info($customerGroupId, $attributes = null) 
    {
        try {
            $customerGroup = $this->_initCustomerGroup($customerGroupId);
        } catch(Exception $e) {
            $result = $e->getMessage();
            return false;
        }
                
        $result = array();
        
        if ($customerGroup) {
            foreach ($this->_mapAttributes as $attribAlias => $attributeCode) {
                $result[$attribAlias] = $customerGroup
                        ->getData($attributeCode);
            }
            
            $result['is_default'] = $customerGroup->usesAsDefault();
            
            unset($customerGroup);
        }
        
        return $result;
    }
    
    public function getEntityIdFieldName()
    {
        return "customer_group_id";
    }
}

