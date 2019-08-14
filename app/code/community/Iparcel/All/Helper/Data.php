<?php
/**
 * General Helper for Iparcel_All
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get GUID key
     *
     * @return string
     */
    public function getGuid()
    {
        return Mage::getStoreConfig('iparcel/config/userid');
    }

    /**
     * Get Customer ID
     *
     * @return string
     */
    public function getCustomerId()
    {
        return Mage::getStoreConfig('iparcel/config/custid');
    }

    /**
     * Getting external JS Scripts URL
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return '//script.i-parcel.com/';
    }

    /**
     * Escape quotation mark in strings for inclusion in JavaScript objects
     *
     * @param string $string String to escape
     * @return string
     */
    public function jsEscape($string = '')
    {
        return addcslashes($string, "\"");
    }

    /**
     * Finds the tracking number of a Magento shipment
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return string
     */
    public function getTrackingNumber(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $trackingNumber = '';
        $tracks = $shipment->getAllTracks();

        if (array_key_exists(0, $tracks)) {
            $trackingNumber = $tracks[0]->getTrackNumber();
        }

        return $trackingNumber;
    }

    /**
     * Determines if the current shipping rate request is International.
     *
     * "International" is determined by the store's Shipping Settings "Origin"
     * country. This can be overridden in the i-parcel Shipping Method settings.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return boolean
     */
    public function getIsInternational(Mage_Shipping_Model_Rate_Request $request)
    {
        $isInternational = false;
        $originCountryId = $this->_getOriginCountry();
        $destinationCountryId = $request->getDestCountryId();
        if ($originCountryId !== $destinationCountryId) {
            $isInternational = true;
        }
        return $isInternational;
    }

    /**
     * Determines if an order is an i-parcel order
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function isIparcelOrder(Mage_Sales_Model_Order $order)
    {
        $iparcelCarrier = Mage::getModel('iparcel/carrier_iparcel');

        $carrier = $order->getShippingCarrier();

        if (is_null($carrier) || $carrier == false) {
            $method = $order->getShippingMethod();
            if (preg_match('/^' . $iparcelCarrier->getCarrierCode() . '.*/', $method)) {
                return true;
            }
            return false;
        }

        if ($carrier->getCarrierCode() == $iparcelCarrier->getCarrierCode()) {
            return true;
        }

        return false;
    }

    /**
     * Returns the admin URL to cancel a shipment
     *
     * @param string $shipment ID of shipment to cancel.
     * @return string
     */
    public function getShipmentCancelUrl($shipment)
    {
        return Mage::helper('adminhtml')->getUrl(
            'adminhtml/iparcel_shipment/cancel',
            array('shipment' => $shipment)
        );
    }
    /**
     * Returns the admin URL to split a shipment
     *
     * @param string $shipment ID of shipment to split.
     * @return string
     */
    public function getShipmentSplitUrl($shipment)
    {
        return Mage::helper('adminhtml')->getUrl(
            'adminhtml/iparcel_shipment/split',
            array('shipment' => $shipment)
        );
    }
    /**
     * Returns the total count of all items on a shipment
     *
     * @param $shipment Shipment ID or Mage_Sales_Model_Order_Shipment object to count
     * @return string
     */
    public function getShipmentItemCount($shipment)
    {
        if (!is_object($shipment)) {
            $shipment = Mage::getModel('sales/order_shipment')->load($shipment);
        }
        $qty = 0;
        $items = $shipment->getAllItems();
        foreach ($items as $item) {
            $qty += $item->getQty();
        }
        return $qty;
    }


    /**
     * Retrieve Service Levels from the database
     *
     * @return array Array of [ServiceLevel] => Title
     */
    public function getServiceLevels()
    {
        $serviceLevels = unserialize(Mage::getStoreConfig('carriers/iparcel/name'));
        $formatted = array();
        foreach ($serviceLevels as $level) {
            $formatted[$level['service_id']] = $level['title'];
        }
        return $formatted;
    }

    /**
     * Finds the Origin country as configured in the Magento admin.
     *
     * @return string Two-letter Country Code
     */
    private function _getOriginCountry()
    {
        // If the admin has chose to select a different "origin" country
        if (Mage::getStoreConfig('carriers/iparcel/choose_domestic')) {
            return Mage::getStoreConfig('carriers/iparcel/origin_country_id');
        }
        return Mage::getStoreConfig('shipping/origin/country_id');
    }

    /**
     * Helper method to find the value of the tax intercepting mode
     *
     * @return boolean
     */
    public function getDisplayTaxAndDutyCumulatively()
    {
        return Mage::getStoreConfig('iparcel/tax/mode') == Iparcel_All_Model_System_Config_Source_Tax_Mode::CUMULATIVELY;
    }
    /**
     * Helper method to find the value of the tax intercepting mode
     *
     * @return boolean
     */
    public function getDisplayTaxAndDutySeparately()
    {
        return Mage::getStoreConfig('iparcel/tax/mode') == Iparcel_All_Model_System_Config_Source_Tax_Mode::SEPARATELY;
    }
    /**
     * Return the Admin user created "Tax" Label
     *
     * @return string
     */
    public function getTaxLabel()
    {
        $taxLabel = Mage::getStoreConfig('iparcel/tax/tax_label');
        if ($taxLabel == '') {
            return Mage::helper('iparcel')->__('Tax');
        } else {
            return $taxLabel;
        }
    }
    /**
     * Return the Admin user created "Duty" Label
     *
     * @return string
     */
    public function getDutyLabel()
    {
        $dutyLabel = Mage::getStoreConfig('iparcel/tax/duty_label');
        if ($dutyLabel == '') {
            return Mage::helper('iparcel')->__('Duty');
        } else {
            return $dutyLabel;
        }
    }
    /**
     * Return the Admin user created "Tax And Duty" Label
     *
     * @return string
     */
    public function getTaxAndDutyLabel()
    {
        $taxAndDutyLabel = Mage::getStoreConfig('iparcel/tax/tax_duty_label');
        if ($taxAndDutyLabel == '') {
            return Mage::helper('iparcel')->__('Tax & Duty');
        } else {
            return $taxAndDutyLabel;
        }
    }

    /**
     * Returns the installed status of Iparcel_CartHandoff
     *
     * @return bool
     */
    public function isCartHandoffInstalled()
    {
        return $this->_isExtensionInstalled('Iparcel_CartHandoff');
    }

    /**
     * Returns the installed status of Iparcel_GlobaleCommerce
     *
     * @return bool
     */
    public function isGlobaleCommerceInstalled()
    {
        return $this->_isExtensionInstalled('Iparcel_GlobaleCommerce');
    }

    /**
     * Returns the installed status of Iparcel_Logistics
     *
     * @return bool
     */
    public function isLogisticsInstalled()
    {
        return $this->_isExtensionInstalled('Iparcel_Logistics');
    }

    /**
     * Finds if an extension is installed
     *
     * @param string $name
     * @return bool
     */
    private function _isExtensionInstalled($name)
    {
        if ($name == '') {
            return false;
        }

        $allExtensions = Mage::app()->getConfig()->getNode('modules')->asArray();
        return array_key_exists($name, $allExtensions);
    }

    /**
     * Gathers extension versions for any installed i-parcel extensions
     *
     * @return array
     */
    public function gatherExtensionVersions()
    {
        $extensions = array(
            'Iparcel_All' => 0,
            'Iparcel_CartHandoff' => 0,
            'Iparcel_GlobaleCommerce' => 0,
            'Iparcel_Logistics' => 0
        );

        $allExtensions = Mage::app()->getConfig()->getNode('modules')->asArray();

        foreach ($extensions as $key => &$version) {
            if (array_key_exists($key, $allExtensions)) {
                $version = $allExtensions[$key]['version'];
            } else {
                unset($extensions[$key]);
            }
        }

        return $extensions;
    }
}
