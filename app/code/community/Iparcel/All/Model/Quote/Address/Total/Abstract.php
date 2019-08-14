<?php
/**
 * Abstract model for i-parcel Quote Address totals
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Quote_Address_Total_Abstract extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    /**
     * Determines if the address is using an i-parcel shipping method
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return boolean
     */
    public function isIparcelShipping($address)
    {
        $shippingMethod = $address->getShippingMethod();
        $shippingMethod = explode('_', $shippingMethod);
        if ($shippingMethod[0] == 'iparcel') {
            return true;
        }
        return false;
    }
}
