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
class Freestyle_Advancedexport_Block_Adminhtml_Form_Edit_Tab_Passive 
extends Mage_Adminhtml_Block_Widget_Grid
{
    
    public function __construct()
    {
        parent::__construct();
        $this->setId('passiveGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('passive_filter');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advancedexport/passivemode')
                ->getCollection();
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            array(
                'header'=> Mage::helper('advancedexport')->__('Mode ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'id',
            )
        );
        
        $this->addColumn(
            'passivemod_enabled',
            array(
                'header'=> Mage::helper('advancedexport')->__('Is Enabled'),
                'index' => 'passivemod_enabled',
                'type'  => 'options',
                'options' => array('0' => 'No', '1' => 'Yes')
            )
        );
        
        $this->addColumn(
            'passivemod_start',
            array(
                'header'=> Mage::helper('advancedexport')->__('Start Date'),
                'index' => 'passivemod_start',
                'type'  => 'datetime',
            )
        );
        
        $this->addColumn(
            'passivemod_end',
            array(
                'header'=> Mage::helper('advancedexport')->__('End Date'),
                'index' => 'passivemod_end',
                'type'  => 'datetime',
            )
        );
        
        $this->addColumn(
            'created_files',
            array(
                'header'=> Mage::helper('advancedexport')->__(
                    'Created Files List'
                ),
                'index' => 'created_files',
                'type'  => 'text',
                'filter'  =>false,
                'sortable'   =>false,
                'renderer' => 'Freestyle_Advancedexport_Block_'
                . 'Adminhtml_Form_Edit_Tab_FilesPassiveRender',
            )
        );
        
        $this->addColumn(
            'is_notification_sent',
            array(
                'header'=> Mage::helper('advancedexport')->__(
                    'Is Notification Sent'
                ),
                'index' => 'is_notification_sent',
                'type'  => 'options',
                'options' => array('0' => 'No', '1' => 'Yes')
            )
        );

        return parent::_prepareColumns();
    }

    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/gridpassive', array('_current'=>true));
    }
}
