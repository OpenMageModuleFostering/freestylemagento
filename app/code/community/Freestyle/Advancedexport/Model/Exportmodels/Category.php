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
class Freestyle_Advancedexport_Model_Exportmodels_Category 
extends Mage_Catalog_Model_Category_Api
{
    

    /**
     * Initilize and return category model
     *
     * @param int $categoryId
     * @param string|int $store
     * @return Mage_Catalog_Model_Category
     */
    protected function _initCategory($categoryId, $store = null)
    {
        $category = Mage::getModel('catalog/category')
            ->setStoreId($this->_getStoreId($store))
            ->load($categoryId);

        if (!$category->getId()) {
            Mage::log(
                '[ALERT] - Advanced Export: Category Does '
                . 'Not Exist. Category Id: ' . $categoryId, 
                1, 
                'freestyle.log'
            );
            return false;
        }

        return $category;
    }

    /**
     * Retrieve category data
     *
     * @param int $categoryId
     * @param string|int $store
     * @param array $attributes
     * @return array
     */
    public function info($categoryId, $store = null, $attributes = null)
    {
        $result = array();
        
        $isFlatEnabled = Mage::helper('catalog/category_flat')->isEnabled();
        if ($isFlatEnabled) {
            $defaultStoreId = Mage::app()->getWebsite()
                    ->getDefaultGroup()
                    ->getDefaultStoreId();
            $category = $this->_initCategory($categoryId, $defaultStoreId);
            if ($category) {
                $result = $category->getData();
                $result['category_id'] = $result['entity_id'];
            }
        } else {
            $category = $this->_initCategory($categoryId, $store);
            if ($category) {
                // Basic category data
                $result = array();
                $result['category_id'] = $category->getId();

                $result['is_active']   = $category->getIsActive();
                $result['position']    = $category->getPosition();
                $result['level']       = $category->getLevel();

                foreach ($category->getAttributes() as $attribute) {
                    if ($this->_isAllowedAttribute($attribute, $attributes)) {
                        $result[$attribute->getAttributeCode()] = 
                            $category->getData($attribute->getAttributeCode());
                    }
                }
                $result['parent_id']   = $category->getParentId();
                $result['children']           = $category->getChildren();
                $result['all_children']       = $category->getAllChildren();
                
                
                if (is_array($result['available_sort_by'])) {
                    $sortedby = array();
                    foreach ($result['available_sort_by'] as $pos) {
                        $sortedby[]['value'] = $pos;
                    }
                    $result['available_sort_by'] = $sortedby;
                }
            }
        }

        return $result;
    }
}
