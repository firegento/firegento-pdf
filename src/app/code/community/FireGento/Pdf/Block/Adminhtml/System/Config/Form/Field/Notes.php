<?php

class FireGento_Pdf_Block_Adminhtml_System_Config_Form_Field_Notes
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    /**
     * @var FireGento_Pdf_Block_Adminhtml_System_Config_Form_Field_Notes_Country
     */
    protected $_countryRenderer;

    /**
     * Retrieve country column renderer
     *
     * @return FireGento_Pdf_Block_Adminhtml_System_Config_Form_Field_Notes_Country
     */
    protected function _getCountryRenderer()
    {
        if (!$this->_countryRenderer) {
            $this->_countryRenderer = $this->getLayout()->createBlock(
                'firegento_pdf/adminhtml_system_config_form_field_notes_country', '',
                array('is_render_to_js_template' => true)
            );
        }

        return $this->_countryRenderer;
    }

    /**
     * Add columns, change button labels etc.
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'country', array(
                'label'    => Mage::helper('firegento_pdf')->__('Shipping Country'),
                'renderer' => $this->_getCountryRenderer()
            )
        );
        $this->addColumn(
            'note', array(
                'label' => Mage::helper('firegento_pdf')->__('Note')
            )
        );
        $this->_addButtonLabel = Mage::helper('firegento_pdf')->__('Add Note');
        $this->_addAfter       = false;
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getCountryRenderer()->calcOptionHash($row->getData('country')),
            'selected="selected"'
        );
    }

}
