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

class Freestyle_Advancedexport_Model_Exportmodels_Customer 
extends Mage_Customer_Model_Api_Resource
{

    protected $_mapAttributes = array(
        'customer_id' => 'entity_id'
    );

    /**
     * Prepare data to insert/update.
     * Creating array for stdClass Object
     *
     * @param stdClass $data
     * @return array
     */
    protected function _prepareData($data)
    {
        foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
            if (isset($data[$attributeAlias])) {
                $data[$attributeCode] = $data[$attributeAlias];
                unset($data[$attributeAlias]);
            }
        }
        return $data;
    }

    //DE-10150 - refactor code
    protected function _initCustomer($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }
        return $customer;
    }

    /**
     * Retrieve customer data
     *
     * @param int $customerId
     * @param array $attributes
     * @return array
     */
    public function info($customerId, $attributes = null)
    {
        //$customer = Mage::getModel('customer/customer')->load($customerId);
        try {
            $customer = $this->_initCustomer($customerId);
        } catch (Exception $e) {
            $result = $e->getMessage();
            return false;
        }

        $locale = Mage::app()->getLocale();

        $dftBillAddr = '';
        $dftShipAddr = '';
        if ($customer->getDefaultBillingAddress()) {
            $dftBillAddr = $customer
                    ->getDefaultBillingAddress()
                    ->getId();
        }
        if ($customer->getDefaultShippingAddress()) {
            $dftShipAddr = $customer
                    ->getDefaultShippingAddress()
                    ->getId();
        }
        
        //DE-9638
        /*
          if (!$customer->getId()) {
          $this->_fault('not_exists');
          return false;
          }
         *
         */

        if (!is_null($attributes) && !is_array($attributes)) {
            $attributes = array($attributes);
        }

        $result = array();

        foreach ($this->_mapAttributes as $attributeAlias => $attributeCode) {
            $result[$attributeAlias] = $customer->getData($attributeCode);
        }

        $allowedAttribs = $this->getAllowedAttributes($customer, $attributes);
        foreach ($allowedAttribs as $attributeCode => $attribute) {
            $result[$attributeCode] = $customer->getData($attributeCode);
        }

        $customerAddresses = $customer->getAddresses();
        if (count($customerAddresses)) {
            foreach ($customer->getAddresses() as $address) {
                $data['customer_address'] = $address->toArray();
                if (isset($data['customer_address']['entity_id'])) {
                    $data['customer_address']['customer_address_id'] 
                            = $data['customer_address']['entity_id'];
                    unset($data['customer_address']['entity_id']);
                }
                if (isset($data['customer_address']['country_id'])) {
                    $countryName = $locale->
                            getCountryTranslation(
                                $data['customer_address']['country_id']
                            );
                    $data['customer_address']['country_name'] = $countryName;
                }
                $data['customer_address']['is_default_shipping'] = '0';
                $data['customer_address']['is_default_billing'] = '0';

                if ($address->getEntityId() == $dftBillAddr) {
                    $data['customer_address']['is_default_billing'] = '1';
                }
                if ($address->getEntityId() == $dftShipAddr) {
                    $data['customer_address']['is_default_shipping'] = '1';
                }

                $result['customer_addresses'][] = $data;
            }
        } else {
            $result['customer_addresses'] = array();
        }

        unset($customerAddresses);
        unset($data);
        unset($customer);
        unset($locale);
        return $result;
    }
}

// Class Mage_Customer_Model_Customer_Api End
