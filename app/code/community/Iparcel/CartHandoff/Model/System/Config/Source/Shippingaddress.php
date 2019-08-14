<?php
/**
 * Source model for shipping_address option
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_System_Config_Source_Shippingaddress
{
    const CUSTOMER = 0;
    const NJ = 1;
    const IN = 2;
    const CA = 3;
    const UK = 4;

    const ADDRESS = array(
        self::NJ => array(
            'shipping_first_name' => 'UPS',
            'shipping_last_name' => 'i-parcel',
            'shipping_address1' => '45 Fernwood Ave',
            'shipping_address2' => '',
            'shipping_city' => 'Edison',
            'shipping_state' => 'New Jersey',
            'shipping_zip' => '08837',
            'shipping_country' => 'US'
        ),
        self::IN => array(
            'shipping_first_name' => 'UPS',
            'shipping_last_name' => 'i-parcel',
            'shipping_address1' => '7735 Winton Drive',
            'shipping_address2' => '',
            'shipping_city' => 'Indianapolis',
            'shipping_state' => 'Indiana',
            'shipping_zip' => '46268',
            'shipping_country' => 'US'
        ),
        self::CA => array(
            'shipping_first_name' => 'UPS',
            'shipping_last_name' => 'i-parcel',
            'shipping_address1' => '9115 Dice Road',
            'shipping_address2' => 'Unit 2',
            'shipping_city' => 'Santa Fe Springs',
            'shipping_state' => 'California',
            'shipping_zip' => '90670',
            'shipping_country' => 'US'
        ),
        self::UK => array(
            'shipping_first_name' => 'UPS',
            'shipping_last_name' => 'i-parcel',
            'shipping_address1' => 'Unit 1',
            'shipping_address2' => 'Blackthorne Road',
            'shipping_city' => 'Poyle',
            'shipping_state' => 'Berkshire',
            'shipping_zip' => 'SL3 0DA',
            'shipping_country' => 'GB'
        )
    );

    /**
     * Options list
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::CUSTOMER, 'label' => Mage::helper('ipcarthandoff')->__('Customer\'s Shipping Address')),
            array('value' => self::NJ, 'label' => Mage::helper('ipcarthandoff')->__('45 Fernwood Ave. Edison, NJ 08837')),
            array('value' => self::IN, 'label' => Mage::helper('ipcarthandoff')->__('7735 Winton Drive Indianapolis, IN 46268')),
            array('value' => self::CA, 'label' => Mage::helper('ipcarthandoff')->__('9115 Dice Road Unit 2 Santa Fe Springs, CA 90670')),
            array('value' => self::UK, 'label' => Mage::helper('ipcarthandoff')->__('Unit 1, Blackthorne Road, Poyle, Berks, SL3 0DA UK')),
        );
    }

    /**
     * Returns array of option values
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::CUSTOMER => 'customer',
            self::NJ => 'nj',
            self::IN => 'in',
            self::CA => 'ca',
            self::UK => 'uk'
        );
    }

}