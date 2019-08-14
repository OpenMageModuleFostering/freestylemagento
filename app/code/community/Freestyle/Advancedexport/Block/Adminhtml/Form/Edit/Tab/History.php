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

class Freestyle_Advancedexport_Block_Adminhtml_Form_Edit_Tab_History 
extends Mage_Adminhtml_Block_Widget_Grid
{

    public $storeId;

    public function __construct()
    {
        parent::__construct();
        $this->setId('historyGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('history_filter');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advancedexport/history')->getCollection();

        $this->setCollection($collection);

        parent::_prepareCollection();

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
            'export_date', array(
            'header' => Mage::helper('advancedexport')->__('Export Date'),
            'index' => 'export_date',
            'type' => 'datetime',
            )
        );

        $this->addColumn(
            'export_date_time_start', array(
            'header' => Mage::helper('advancedexport')->__('Export Start Time'),
            'index' => 'export_date_time_start',
            'type' => 'datetime',
            )
        );

        $this->addColumn(
            'export_date_time_end', array(
            'header' => Mage::helper('advancedexport')->__('Export End Time'),
            'index' => 'export_date_time_end',
            'type' => 'datetime',
            )
        );

        $this->addColumn(
            'created_files', array(
            'header' => Mage::helper('advancedexport')->__('Created Files'),
            'index' => 'created_files',
            'renderer' => 'Freestyle_Advancedexport_Block_Adminhtml_'
                . 'Form_Edit_Tab_FilesRender',
            'filter' => false,
            'sortable' => false,
            )
        );

        $this->addColumn(
            'init_from', array(
            'header' => Mage::helper('advancedexport')->__('Init From'),
            'index' => 'init_from',
            'filter' => false,
            'sortable' => false,
            )
        );

        $this->addColumn(
            'export_entity', array(
            'header' => Mage::helper('advancedexport')->__('Exported Entity'),
            'index' => 'export_entity',
            'filter' => false,
            'sortable' => false,
            )
        );

        $this->addColumn(
            'errors', array(
            'header' => Mage::helper('advancedexport')->__('Errors'),
            'index' => 'errors',
            'renderer' => 'Freestyle_Advancedexport_Block_Adminhtml_'
                . 'Form_Edit_Tab_ErrorsRender',
            'filter' => false,
            'sortable' => false,
            )
        );


        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
