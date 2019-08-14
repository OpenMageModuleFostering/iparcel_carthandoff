<?php
/**
 * Button model for Payment Method section of checkout
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Block_Form_Button extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('iparcel/carthandoff/form/button.phtml');
    }
}
