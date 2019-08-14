<?php
/**
 * Source model for catalog_mapping/config/cron_weekday config field
 *
 * @category    Iparcel
 * @package     Iparcel_Shipping
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Source_Date_Weekday
{
    /**
     * Options list
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array = array();
        for ($i=1; $i<8; $i++) {
            $time = mktime(0, 0, 0, 1, $i, 1970);
            $dayofweek = date('N', $time);
            $array[$dayofweek] = array(
                'value' => $dayofweek,
                'label' => Mage::helper('iparcel')->__(date('l', $time))
            );
        }
        ksort($array, SORT_NUMERIC);
        return $array;
    }
}
