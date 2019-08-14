<?php
/**
 * i-parcel shipping method model
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Carrier_Iparcel extends Iparcel_All_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'iparcel';

    /**
     * Return container types of carrier
     *
     * @return array
     */
    public function getContainerTypes(Varien_Object $params = null)
    {
        return array('DEFAULT' => Mage::helper('iparcel')->__('Default box'));
    }

    /**
     * Collect shipping rates for i-parcel shipping
     * refactor: add result check, add intermediate storage for parcel_id
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        try {
            /** @var boolean $internationalOrder */
            $internationalOrder = Mage::helper('iparcel')->getIsInternational($request);
            if ($internationalOrder && Mage::getStoreConfig('carriers/iparcel/active')) {
                /** @var array $iparcelTaxAndDuty Tax & Duty totals */
                $iparcelTaxAndDuty = array();
                /** @var Mage_Shipping_Model_Rate_Result $result*/
                $result = Mage::getModel('shipping/rate_result');
                // Get Allowed Methods
                /** @var array $allowed_methods Shipping method allowed via admin config "names" */
                $allowed_methods = $this->getAllowedMethods();
                /** @var stdClass $quote */
                $quote = Mage::helper('iparcel/api')->quote($request);
                $iparcelTaxAndDuty['parcel_id'] = $quote->ParcelID;
                $serviceLevel = new stdClass;
                if (isset($quote->ServiceLevels)) {
                    $serviceLevel = $quote->ServiceLevels;
                }
                // Handling serviceLevels results and set up the shipping method
                foreach ($serviceLevel as $ci) {
                    // setting up values
                    $servicename = @$ci->ServiceLevelID;
                    $duty = (float)@$ci->DutyCompanyCurrency;
                    $tax = (float)@$ci->TaxCompanyCurrency;
                    $shipping = (float)@$ci->ShippingChargeCompanyCurrency;
                    $tax_flag = Mage::getStoreConfig('iparcel/tax/mode') == Iparcel_All_Model_System_Config_Source_Tax_Mode::DISABLED
                        || $request->getDestCountryId() == $request->getCountryId();
                    // true if tax intercepting is disabled
                    $total = $tax_flag ? (float)($duty + $tax + $shipping) : (float)$shipping;
                    if (!isset($allowed_methods[$servicename])) {
                        continue;
                    }
                    $shiplabel = $allowed_methods[$servicename];
                    $title = $shiplabel;
                    if ($tax_flag) {
                        $title = Mage::helper('iparcel')->__(
                            '%s (Shipping Price: %s Duty: %s Tax: %s)',
                            $shiplabel,
                            $this->_formatPrice($shipping),
                            $this->_formatPrice($duty),
                            $this->_formatPrice($tax)
                        );
                    }
                    $method = Mage::getModel('shipping/rate_result_method');
                    $method->setCarrier($this->_code);
                    $method->setCarrierTitle($this->getConfigData('title'));
                    $method->setMethod($servicename);
                    $method->setMethodTitle($title);
                    $method->setPrice($total);
                    $method->setCost($total);
                    // append method to result
                    $result->append($method);
                    $iparcelTaxAndDuty['service_levels'][$this->_code . '_' . $servicename] = array(
                        'duty' => $duty,
                        'tax' => $tax
                    );
                }
                // Store the shipping quote
                $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
                $quote = Mage::getModel('iparcel/api_quote');
                $quote->loadByQuoteId($quoteId);
                $quote->setQuoteId($quoteId);
                $quote->setParcelId($iparcelTaxAndDuty['parcel_id']);
                $quote->setServiceLevels($iparcelTaxAndDuty['service_levels']);
                $quote->save();
                return $result;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return false;
    }


    public function getMethodsNames()
    {
        $names = array();
        $raw = $this->getConfigData('name');
        $raw = unserialize($raw);
        foreach ($raw as $method) {
            $names[$method['service_id']] = $method['title'];
        }
        return $names;
    }

    /**
     * Get Allowed Shipping Methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->getMethodsNames();
    }
}
