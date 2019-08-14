<?php
/**
 * i-parcel model for shipping tax
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Quote_Address_Total_Tax extends Iparcel_All_Model_Quote_Address_Total_Abstract
{
    public function __construct()
    {
        $this->setCode(Mage::getModel('iparcel/payment_iparcel')->getTaxCode());
    }

    /**
     * Used each time collectTotals is invoked to find the duty rate
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Iparcel_All_Model_Total_Tax
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getAddressType() != 'shipping') {
            return;
        }

        // Make sure that we are not working on a quote with no items
        if ($address->getQuote()->hasItems() == false) {
            return;
        }

        if ($this->isIparcelShipping($address) &&
            (Mage::helper('iparcel')->getDisplayTaxAndDutyCumulatively()
                || Mage::helper('iparcel')->getDisplayTaxAndDutySeparately())) {
            parent::collect($address);

            $tax = null;

            $quote = Mage::getModel('iparcel/api_quote');
            $quote->loadByQuoteId($address->getQuote()->getId());

            /**
             * Load data from stored shipping quote.
             */
            if ($quote->getId()) {
                $dutyAndTaxes = array(
                    'parcel_id' => $quote->getParcelId(),
                    'service_levels' => $quote->getServiceLevels()
                );
                /**
                 * Catch an error caused by unserialize returning `false`
                 * if the data stored in the databse is invalid
                 */
                if ($dutyAndTaxes['service_levels'] == false) {
                    Mage::throwException('Failed to set shipping rates tax and duty.');
                }
                Mage::unregister('iparcel_duty_and_taxes');
                Mage::register('iparcel_duty_and_taxes', $dutyAndTaxes);
            }

            $tax = $dutyAndTaxes['service_levels'][$address->getShippingMethod()]['tax'];

            $this->_setBaseAmount($tax);
            $this->_setAmount(
                $address->getQuote()->getStore()->convertPrice($tax, false)
            );

            /**
             * If this is an i-parcel shipped order, we have to remove Magento's
             * calculated tax, to allow i-parcel's to be the only tax calculated
             */
            $address->setTaxAmount(0);
            $address->setBaseTaxAmount(0);

            return $this;
        }
    }

    /**
     * Display the duty rate
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Iparcel_All_Model_Total_Tax
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getAddressType() != 'shipping') {
            return;
        }

        /**
         * We only display the tax if the duty and tax are displayed seperately.
         * Otherwise, the Duty model will handle displaying the combined total.
         */
        if ($this->isIparcelShipping($address)
            && Mage::helper('iparcel')->getDisplayTaxAndDutySeparately()
            && $address->getIparcelTaxAmount() != null) {
            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => Mage::helper('iparcel')->getTaxLabel(),
                'value' => $address->getIparcelTaxAmount()
            ));
        }
    }
}
