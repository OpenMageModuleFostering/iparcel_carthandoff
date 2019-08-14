<?php
/**
 * Frontend Model Class for checkitems_clear_cache button
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Block_Adminhtml_Checkitems_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Get Button Html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $url = Mage::helper('adminhtml')->getUrl("adminhtml/ipcarthandoff_checkitems_cache/clear");
        $js = "new Ajax.Request('" . $url . "', { onSuccess: function() { alert('Cache Cleared'); }, onFailure: function() { alert('Unable to clear cache'); }});";

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Clear Cache Now')
            ->setOnClick($js)
            ->toHtml();

        return $html;
    }
}
