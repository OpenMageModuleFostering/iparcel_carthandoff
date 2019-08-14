<?php
/**
 * i-parcel international customer helper
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Helper_International
{
    /**
     * Get international flag from customer session
     *
     * @return bool
     */
    public function getInternational()
    {
        return Mage::getSingleton('checkout/session')->getInternationalFlag();
    }

    /**
     * Set international flag to customer session, respond with new state
     *
     * @param bool $v
     * @return bool
     */
    public function setInternational($v)
    {
        Mage::getSingleton('checkout/session')->setInternationalFlag($v);
        return Mage::getSingleton('checkout/session')->getInternationalFlag();
    }

    /**
     * Checking if international customer is enabled
     *
     * @return bool
     */
    public function checkEnabled()
    {
        return Mage::getStoreConfigFlag('iparcel/international_customer/enable');
    }

    /**
     * Get international visibility attribute ID
     *
     * @return string
     */
    public function getVisibilityAttributeId()
    {
        return Mage::getStoreConfig('iparcel/international_customer/visibility');
    }

    /**
     * Get international visibility attribute name
     *
     * @return string
     */
    public function getVisibilityAttributeName()
    {
        return Mage::getModel('catalog/resource_eav_attribute')->load($this->getVisibilityAttributeId())->getName();
    }
}
