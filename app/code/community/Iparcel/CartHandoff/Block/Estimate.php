<?php
/**
 * Block to set Estimate Shipping country
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Block_Estimate extends Mage_Core_Block_Template
{
    /**
     * Returns two-letter country code of user
     *
     * @return string
     */
    public function getCountryCode()
    {
        $cookie = Mage::getModel('core/cookie')->get('ipar_iparcelSession');
        $json = json_decode($cookie);
        $magentoDefault = Mage::getStoreConfig('general/country/default');

        if ($json == false || is_null($json->locale)) {
            $countryCode = $magentoDefault;
        } else {
            $countryCode = $json->locale;
        }

        return $countryCode;
    }
}