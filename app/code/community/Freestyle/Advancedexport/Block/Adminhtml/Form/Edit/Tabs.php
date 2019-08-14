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

class Freestyle_Advancedexport_Block_Adminhtml_Form_Edit_Tabs 
extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('form_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('core')->__('Params'));
    }

    protected function _prepareLayout()
    {
        $this->addTab(
            'general', array(
            'label' => Mage::helper('core')->__('Manual Export'),
            'content' => $this->getLayout()->createBlock(
                'advancedexport/adminhtml_form_edit_tab_general'
            )->toHtml(),
            )
        );

        //Add Serializer for Product Grid and Creating Product Tab

        $productsGrid = $this->getLayout()->createBlock(
            'advancedexport/adminhtml_form_edit_tab_history', 
            'export_edit_tab_history'
        );
        $gridSerializer = $this->getLayout()->createBlock(
            'adminhtml/widget_grid_serializer'
        );
        $gridSerializer->initSerializerBlock(
            'export_edit_tab_history', 
            'getRelatedhistory', 
            'export_assigned_history', 
            'export_assigned_history'
        );


        $this->addTab(
            'form_history', array(
            'label' => Mage::helper('advancedexport')->__('Export History'),
            'content' => $productsGrid->toHtml() . $gridSerializer->toHtml(),
            )
        );
        
        //queue Grid
        $queueGrid = $this->getLayout()->createBlock(
            'advancedexport/adminhtml_form_edit_tab_queue', 
            'export_edit_tab_queue'
        );
        $queueSerializer = $this->getLayout()->createBlock(
            'adminhtml/widget_grid_serializer'
        );
        $queueSerializer->initSerializerBlock(
            'export_edit_tab_queue', 
            'getRelatedhistory', 
            'export_assigned_queue', 
            'export_assigned_queue'
        );
        
        $this->addTab(
            'queue_history', array(
            'label' => Mage::helper('advancedexport')->__('Queue History'),
            'content' => $queueGrid->toHtml() . $queueSerializer->toHtml(),
            )
        );
        
        if (Mage::Helper('advancedexport')->getEnablePassiveGui()=='1') {
            $passiveGrid = $this->getLayout()->createBlock(
                'advancedexport/adminhtml_form_edit_tab_passive', 
                'export_edit_tab_passive'
            );
            $passGridSerializer = $this->getLayout()->createBlock(
                'adminhtml/widget_grid_serializer'
            );
            $passGridSerializer->initSerializerBlock(
                'export_edit_tab_passive', 
                'getRelatedhistory', 
                'export_assigned_passive', 
                'export_assigned_passive'
            );

            $this->addTab(
                'passive_history', array(
                'label' => Mage::helper('advancedexport')
                    ->__('Passive Mode History'),
                'content' => $passiveGrid->toHtml() 
                . $passGridSerializer->toHtml(),
                )
            );
        }

        return parent::_prepareLayout();
    }
    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')
                ->getUrl('adminhtml/advancedexport/processdata');
    }
    
    /*
    public function getLogFile()
    {
        return Mage::helper('advancedexport')->readLogFile();
    }
     *
     */
}
