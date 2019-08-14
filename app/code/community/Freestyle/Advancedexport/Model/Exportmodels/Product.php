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

class Freestyle_Advancedexport_Model_Exportmodels_Product 
extends Mage_Catalog_Model_Product_Api
{

    /**
     * Retrieve product info
     *
     * @param int|string $productId
     * @param string|int $store
     * @param array $attributes
     * @return array
     */
    public $productId;

    public function info(
            $productId, 
            $store = null, 
            $attributes = null, 
            $identifierType = null
        )
    {
        //TODO: Cycle thru the stores?
        try {
            $product = $this->_getProduct($productId, $store, $identifierType);
        } catch (Exception $e) {
            $result = $e->getMessage();
            return false;
        }
        $helper = Mage::helper('catalog/image');

        /*
          $catalogApi = Mage::getModel('catalog/product_attribute_media_api');
          $catalogApi->info();
         *
         *
         */
        $imDt = $this->getAssignedImages($product, array(0));
        $imagesInfo = array();
        $imageOne = array();
        $_images = $product->getMediaGallery();
        $sendImages = Mage::Helper('advancedexport')
                ->getProductSendImages() == 1;
        if ($sendImages && $_images) {
            foreach ($_images as $_imageAr) {
                foreach ($_imageAr as $_image) {
                    $imageOne['label'] = $_image['label'];
                    $imageOne['url'] = (string) $helper
                            ->init($product, 'thumbnail', $_image['file']);
                    $imageOne['exclude'] = $_image['disabled'];
                    $imageOne['position'] = $_image['position'];
                    $imageOne['file'] = $_image['file'];

                    $imageTypes = array();
                    foreach ($imDt as $oneAttrImage) {
                        if ($_image['file'] == $oneAttrImage['filepath']) {
                            $imageTypes[]['string'] = 
                                    $oneAttrImage['attribute_code'];
                        }
                    }
                    $imageOne['types'] = $imageTypes;

                    $imagesInfo[]['product_image'] = $imageOne;
                }
            }
        }

        $productsCategories = $product->getCategoryIds();
        $productsWebsites = $product->getWebsiteIds();

        $catData = array();
        foreach ($productsCategories as $categoryId) {
            $catData[]['string'] = $categoryId;
        }

        $websitesData = array();
        foreach ($productsWebsites as $websiteId) {
            $websitesData[]['string'] = $websiteId;
        }

        /* JPC@DYDA:  cataloginventory/stock_item has 
         * access to qty and is_in_stock */
        $stock = Mage::getModel('cataloginventory/stock_item')
                ->loadByProduct($product);
        $result = array(// Basic product data
            'product_id' => $product->getId(),
            'sku' => $product->getSku(),
            'set' => $product->getAttributeSetId(),
            'type' => $product->getTypeId(),
            'categories' => implode(",", $productsCategories),
            'category_ids' => $catData,
            'websites' => implode(",", $productsWebsites),
            'website_ids' => $websitesData,
            'product_images' => $imagesInfo,
            'quantity' => $stock->getQty(),
            'is_in_stock' => $stock->getIsInStock(),
        );


        // lol per Vinai.. check this array.  Implode if not string?
        $grpAttribs = $product
                ->getTypeInstance(true)
                ->getEditableAttributes($product);
        foreach ($grpAttribs as $attribute) {
            if ($this->_isAllowedAttribute($attribute, $attributes)) {
                $attributeCode = $attribute->getAttributeCode();
                if ($attributeCode != 'category_ids') {
                    $attributeData = $product->getData($attributeCode);
                    $result[$attributeCode] = $this
                            ->serializeArray($attributeCode, $attributeData);
                }
            }
        }
		//20150128 HS: Output custom options of the the product:
        if($product->hasOptions) {
            $optionArray = array();
            foreach ($product->getOptions() as $opt) {
                //Do we support File type???
                $optionArray[] = $this->jsonizeOption($opt);
            }
            
            $optArray = '[';
            foreach($optionArray as $optionObj){
                $optArray = $optArray . $optionObj . ', ';
            }
            
            $optArray = rtrim($optArray, ', ') . ']';
            
            //$result['productOptions'] 
            //= $this->serializeArray('productOptions', $optionArray);
            $result['productOptions'] = $optArray;
        }
        
        unset($catData);
        unset($websitesData);
        unset($product);
        unset($_images);
        unset($stock);  //release variable

        return $result;
    }

    /**
     * For all version compatibility
     * Return assigned images for specific stores
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int|array $storeIds
     * @return array
     *
     */
    public function getAssignedImages($product, $storeIds)
    {
        if (!is_array($storeIds)) {
            $storeIds = array($storeIds);
        }

        $mainTable = $product->getResource()->getAttribute('image')
                ->getBackend()
                ->getTable();

        $resourse = Mage::getSingleton('core/resource');

        $read = $resourse->getConnection('core_read');
        $select = $read->select()
                ->from(
                    array('images' => $mainTable), 
                    array('value as filepath', 'store_id')
                )
                ->joinLeft(
                    array(
                        'attr' => $resourse->getTableName('eav/attribute')
                    ), 
                    'images.attribute_id = attr.attribute_id', 
                    array('attribute_code')
                )
                ->where('entity_id = ?', $product->getId())
                ->where('store_id IN (?)', $storeIds)
                ->where(
                    'attribute_code IN (?)', 
                    array('small_image', 'thumbnail', 'image')
                );

        $images = $read->fetchAll($select);
        return $images;
    }

    protected function serializeArray($attributeCode, $attributeData)
    {
        if ($attributeCode == 'group_price' || $attributeCode == 'tier_price') {
            return $attributeData;
        }
        if (is_array($attributeData) && !$this->isAssoc($attributeData)) {
            //return json_encode($attributeData,JSON_FORCE_OBJECT);  
            $returnJson = '';
            try {
                //serialize the data
                $returnJson = json_encode($attributeData, JSON_FORCE_OBJECT);  
            } catch (Exception $ex) {
                Mage::log(
                    '[EXCEPTION] - Error Serializing: ' 
                    . serialize($attributeCode) . "; " 
                    . serialize($attributeData) . "; Product Id: " 
                    . (string) $this->productId, 
                    1, 
                    'freestyle.log'
                );
                if (version_compare(phpversion(), '5.3.0', '>=')) {
                    Mage::log(
                        'JSON ERROR = ' . json_last_error(), 
                        1, 
                        'freestyle.log'
                    );
                }
                Mage::log(
                    'Message: ' . $ex->getMessage() . ' In: ' . $ex->getFile() 
                    . '::' . $ex->getLine(),
                    1,
                    'freestyle.log'
                );
                $returnJson = serialize($attributeData);
            }
            return $returnJson;
        } else {
            return $attributeData;
        }
    }

    protected function isAssoc($array)
    {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }

    private function jsonizeOption($option) 
    {
        $optionType = $option->getType();
        $opt = '{' . '"Title": ' . '"' . $option->getTitle() . '", ' ;
        $opt = $opt . '"InputType": ' . '"' . $optionType . '", ';
        $opt = $opt . '"IsRequired": ' . $option->getIsRequire() . ', ';
        $opt = $opt . '"SortOrder": ' . $option->getSortOrder() . ', ';

        if ($optionType !='drop_down' && $optionType != 'radio' && $optionType != 'checkbox' && $optionType != 'multiple') {
            $opt = $opt . '"Price": ' . $option->getPrice() . ', ';
            $opt = $opt . '"PriceType": ' . '"' . $option->getPriceType() 
                    . '", ';
            $opt = $opt . '"SKU": ' . '"' . $option->getSku() . '"';

            if($optionType === 'field' || $optionType === 'area'){
                $opt = $opt . ', ' . '"MaxLength": ' 
                    . $option->getMaxCharacters();
            }
        } else {
            $opt = $opt . '"SubOptions": ' . $this->jsonizeSubOptions($option);
        }

        $opt = $opt . '}';

        return $opt;
    }

    private function jsonizeSubOptions($option) 
    {
        $values = $option->getValues();
        $suboptions = '[';
        foreach($values as $v) {
            $suboption = '{' . '"Title": ' . '"' . $v->getTitle() . '", ';
            $suboption = $suboption . '"Price": ' . $v->getPrice() . ', ';
            $suboption = $suboption . '"PriceType": ' . '"' 
                . $v->getPriceType() . '", ';
            $suboption = $suboption . '"SKU": ' . '"' . $v->getSku() . '", ';
            $suboption = $suboption . '"SortOrder": ' . $v->getSortOrder() 
                . '}, ';
            $suboptions = $suboptions . $suboption;
        }

        $suboptions = rtrim($suboptions, ", ") . ']';

        return $suboptions;
    }    
}
