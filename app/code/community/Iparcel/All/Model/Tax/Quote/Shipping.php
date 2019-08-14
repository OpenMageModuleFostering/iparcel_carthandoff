<?php
/**
 * Class to override Magento's tax rate
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Tax_Quote_Shipping extends Mage_Tax_Model_Sales_Total_Quote_Shipping
{
    /**
     * Returns i-parcel's quote address abstract class
     *
     * @return Iparcel_All_Model_Quote_Address_Total_Abstract
     */
    protected function _getAbstract()
    {
        $model = new Iparcel_All_Model_Quote_Address_Total_Abstract;
        return $model;
    }
    /**
     * Prevent's Magento's tax calculation if i-parcel shipping method is used
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return object
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if ($this->_getAbstract()->isIparcelShipping($address) &&
            Mage::getStoreConfig('iparcel/tax/mode') != Iparcel_All_Model_System_Config_Source_Tax_Mode::DISABLED) {
            return;
        } else {
            return parent::fetch($address);
        }
    }
    /**
     * Prevent's Magento's tax display if i-parcel shipping method is used
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return object
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if ($this->_getAbstract()->isIparcelShipping($address) &&
            Mage::getStoreConfig('iparcel/tax/mode') != Iparcel_All_Model_System_Config_Source_Tax_Mode::DISABLED) {
            return;
        } else {
            return parent::collect($address);
        }
    }
}
