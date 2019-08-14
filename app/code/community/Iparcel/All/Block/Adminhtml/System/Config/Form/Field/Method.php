<?php
/**
 * Form Field for Methods Names
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Adminhtml_System_Config_Form_Field_Method extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('service_id', array(
            'label'     => $this->__('Service Level ID'),
            'style'     => 'width:85px'
        ));
        $this->addColumn('title', array(
            'label'     => $this->__('Method Title'),
            'style'     => 'width:120px'
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = $this->__('Add Description');

        parent::__construct();
    }
}
