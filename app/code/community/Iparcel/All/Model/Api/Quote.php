<?php
/**
 * Model for retrieving stored shipping quotes from the database
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Api_Quote extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('iparcel/api_quote');
    }

    /**
     * Loads quote from database by Magento Quote ID
     *
     * @param int $quote_id
     * @return Iparcel_All_Model_Api_Quote
     */
    public function loadByQuoteId($quote_id)
    {
        $this->load($quote_id, 'quote_id');
        return $this;
    }

    /**
     * Setter for serviceLevels
     *
     * @param array serviceLevels
     * @return Iparcel_All_Model_Api_Quote
     */
    public function setServiceLevels($serviceLevels)
    {
        $this->setData('service_levels', serialize($serviceLevels));
    }

    /**
     * Getter for serviceLevels
     *
     * @return array
     */
    public function getServiceLevels()
    {
        return unserialize($this->getData('service_levels'));
    }
}
