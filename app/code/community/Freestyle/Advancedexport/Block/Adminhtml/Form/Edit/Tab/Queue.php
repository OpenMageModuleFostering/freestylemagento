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

class Freestyle_Advancedexport_Block_Adminhtml_Form_Edit_Tab_Queue 
extends Mage_Adminhtml_Block_Widget_Grid
{

    public $storeId;

    public function __construct()
    {
        parent::__construct();
        $this->setId('queueGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('queue_filter');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advancedexport/queue')->getCollection();

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('queue_id');
        $this->getMassactionBlock()->setFormFieldName('queue');
        $this->getMassactionBlock()->addItem(
            'send', array(
            'label'=> Mage::helper('advancedexport')->__('Send Now'),
            'url'  => $this->getUrl('*/*/batchSend', array('' => '')),        
            'confirm' => Mage::helper('advancedexport')->__('Are you sure?')
            )
        );
        //DE-11662
        $this->getMassactionBlock()->addItem(
            'update', array(
            'label'=> Mage::helper('advancedexport')->__('Mark as Sent'),
            'url'  => $this->getUrl('*/*/batchUpdate', array('' => '')),        
            'confirm' => Mage::helper('advancedexport')->__('Are you sure?')
            )
        );
        return $this;
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id', array(
            'header' => Mage::helper('advancedexport')->__('ID'),
            'width' => '50px',
            'type' => 'number',
            'index' => 'id',
            )
        );

        $this->addColumn(
            'entity_id', array(
            'header' => Mage::helper('advancedexport')->__('Entity ID'),
            'index' => 'entity_id',
            'type' => 'number',
            )
        );

        $this->addColumn(
            'entity_type', array(
            'header' => Mage::helper('advancedexport')->__('Entity Type'),
            'index' => 'entity_type',
            )
        );
        
        $this->addColumn(
            'entity_value', array(
            'header' => Mage::helper('advancedexport')->__('Entity Value'),
            'index' => 'entity_value',
            )
        );
        
        /*
         *
        // doesn't need to be shown why it was added to the queue
        $this->addColumn('action', array(
            'header' => Mage::helper('advancedexport')->__('Event Type'),
            'index' => 'action',
        ));
         *
         */
        
        $this->addColumn(
            'create_time', array(
            'header' => Mage::helper('advancedexport')->__('Creation Time'),
            'index' => 'create_time',
            'type' => 'datetime',
            )
        );

        $this->addColumn(
            'update_time', array(
            'header' => Mage::helper('advancedexport')->__('Update Time'),
            'index' => 'update_time',
            'type' => 'datetime',
            'renderer' => 'Freestyle_Advancedexport_Block_Adminhtml'
            . '_Form_Edit_Tab_UpdatetimeRender',
            )
        );

        $this->addColumn(
            'status', array(
            'header' => Mage::helper('advancedexport')->__('Status'),
            'index' => 'status',
            )
        );
        

        $this->addColumn(
            'error_msg', array(
            'header' => Mage::helper('advancedexport')->__('Error Message'),
            'sortable' => false,
            'filter'   => false,
            'index' => 'error_msg',
            )
        );

        $this->addColumn(
            'scope_value', array(
            'header' => Mage::helper('advancedexport')->__('Website Id'),
            'index' => 'scope_value',
            )
        );
        
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/gridqueue', array('_current' => true));
    }
}
