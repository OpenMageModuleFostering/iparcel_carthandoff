<?php
/**
 * i-parcel shipping method model
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
abstract class Iparcel_All_Model_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract
{
    protected $_trackingUrl = 'https://tracking.i-parcel.com/secure/track.aspx?track=';

    /**
     * Check if carrier has shipping label option available
     *
     * @return bool
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Get info for track order page
     *
     * @param string $number Tracking Number
     * @return Varien_Object
     */
    public function getTrackingInfo($number)
    {
        return new Varien_Object(array(
            'tracking' => $number,
            'carrier_title' => $this->getConfigData('title'),
            'url' => $this->_trackingUrl.$number
        ));
    }
}
