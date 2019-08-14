<?php
/**
 * Iparcel_CartHandoff default data Helper
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_cartUrl = 'https://pay.i-parcel.com/v1/Cart';

    /**
     * Find the default address for a customer
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param bool $shipping Finds default shipping if true, billing if false
     * @return mixed Address object if a default is set, null if no default
     */
    public function getDefaultAddress(Mage_Customer_Model_Customer $customer, $shipping = false)
    {
        if (!is_object($customer)) {
            return null;
        }

        if ($customer->getId() == false) {
            return null;
        }

        $addressId = null;
        if ($shipping) {
            $addressId = $customer->getDefaultShipping();
        } else {
            $addressId = $customer->getDefaultBilling();
        }

        if ($addressId == null) {
            return null;
        }

        return Mage::getModel('customer/address')->load($addressId);
    }

    /**
     * Constructs the checkout URL from a given transaction ID
     *
     * @param string $transactionId
     * @return string URL for checkout page
     */
    public function getCheckoutUrl($transactionId)
    {
        $key = Mage::getStoreConfig('iparcel/config/publickey');
        if (is_null($key) || $key == '') {
            $key = Mage::getStoreConfig('iparcel/config/userid');
        }
        return $this->_cartUrl . '?key=' . $key . '&tx=' . $transactionId;
    }

    /**
     * Returns the 'return' URL, used to receive customers after completing
     * checkout on UPS i-parcel
     *
     * @return string URL of return page
     */
    public function getReturnUrl()
    {
        return Mage::getUrl('ipcarthandoff/handoff/return');
    }

    /**
     * Finds and returns Magento Order from given tracking number
     *
     * @param string $trackingNumber
     * @return Mage_Sales_Model_Order If no order is found, `false` is returned
     */
    public function loadOrderByTrackingNumber($trackingNumber)
    {
        $track = Mage::getModel('sales/order_shipment_track')->load(
            $trackingNumber,
            'track_number'
        );

        $order = Mage::getModel('sales/order')->load($track->getOrderId());

        if (is_null($order->getId())) {
            return false;
        }

        return $order;
    }

    /**
     * Returns the store's configured order status for paid Cart Handoff orders
     *
     * @return string
     */
    public function getOrderStatus()
    {
        $status = Mage::getStoreConfig('payment/ipcarthandoff/order_status');
        if ($status == '' || is_null($status)) {
            return Mage_Sales_Model_Order::STATE_COMPLETE;
        }

        return $status;
    }
}