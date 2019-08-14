<?php
/**
 * Observer class
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Observer
{
    /**
    * @param $shipment
    * @param $order
    */
    protected function _submitParcel($shipment, $order)
    {
        $api = Mage::helper('iparcel/api');
        $response = $api->submitParcel($shipment);

        // Find the name of the Service Level as defined in the Admin
        $serviceLevels = Mage::helper('iparcel')->getServiceLevels();
        $responseServiceLevelId = $response->ServiceLevels[0][0]->ServiceLevelID;
        $serviceLevelTitle = 'I-Parcel';
        if (array_key_exists($responseServiceLevelId, $serviceLevels)) {
            $serviceLevelTitle = $serviceLevels[$responseServiceLevelId];
        }

        // Add tracking number from submitParcel response
        Mage::getModel('sales/order_shipment_api')->addTrack(
            $shipment->getIncrementId(),
            $order->getShippingCarrier()->getCarrierCode(),
            $serviceLevelTitle,
            $response->CarrierTrackingNumber
        );
    }

    /**
     * Handles triggering the submitParcel call for the shipment.
     *
     * @param $observer
     */
    public function shipment_save_after($observer)
    {
        // if autotrack is enabled then order can be tracked when shipped
        if (Mage::getStoreConfigFlag('carriers/iparcel/autotrack')) {
            // If we are splitting shipments, skip automatic submission.
            if (Mage::registry('iparcel_skip_auto_submit')) {
                return true;
            }

            // Check the shipment to make sure we don't already have a tracking
            // number attached
            $shipment = $observer->getShipment();
            $shipmentTracks = Mage::getModel('sales/order_shipment_api')->info(
                $shipment->getIncrementId()
            );

            if (count($shipmentTracks['tracks'])) {
                return;
            }

            $iparcelCarrierCode = Mage::getModel('iparcel/payment_iparcel')->getCode();

            $order = $observer->getShipment()->getOrder();
            if ($order->getShippingCarrier() && $order->getShippingCarrier()->getCarrierCode() == $iparcelCarrierCode) {
                $this->_submitParcel($shipment, $order);
            }
        }
    }

    /**
    * @param $order
    */
    protected function _createShipment($order)
    {
        $converter = Mage::getModel('sales/convert_order');
        /* var $converter Mage_Sales_Model_Convert_Order */
        $shipment = $converter->toShipment($order);
        /* var $shipment Mage_Sales_Model_Order_Shipment */
        foreach ($order->getAllItems() as $orderItem) {
            /* var $orderItem Mage_Sales_Model_Order_Item */
            // continue if it is virtual or there is no quantity to ship
            if (!$orderItem->getQtyToShip()) {
                continue;
            }
            if ($order->getIsVirtual()) {
                continue;
            }
            $item = $converter->itemToShipmentItem($orderItem);
            /* var $item Mage_Sales_Model_Order_Shipment_Item */
            $item->setQty($orderItem->getQtyToShip());
            $shipment->addItem($item);
        }
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($order);
        /* var $transactionSave Mage_Core_Model_Resource_Transaction */
        $transactionSave->save();
        $shipment->save();
        $shipment->sendEmail();
    }

    /**
     * Handles shipment creation if `autoship` is enabled
     *
     * @param $observer
     */
    public function order_place_after($observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        if (Mage::registry('iparcel_skip_auto_create_shipment')) {
            return true;
        }

        if (!$order->getQuote()) {
            return;
        }
        
        $iparcelCarrierCode = Mage::getModel('iparcel/payment_iparcel')->getCode();

        // if it's i-parcel shipping method
        if ($order->getShippingCarrier() && $order->getShippingCarrier()->getCarrierCode() != $iparcelCarrierCode) {
            return;
        }

        // if autoship is enabled and order can be shipped
        if (Mage::getStoreConfigFlag('carriers/iparcel/autoship')) {
            if ($order->canShip()) {
                $this->_createShipment($order);
            }
        }
    }

    /**
     * Cancels shipments for i-parcel orders if they are canceled.
     *
     * @param $observer
     */
    public function order_cancel_after($observer)
    {
        $order = $observer->getOrder();

        Mage::helper('iparcel/api')->cancelShipmentsByOrder($order);
    }

    /**
     * Add observer to add "Cancel Shipment" button to shipment view
     *
     * @param $observer
     * @return bool
     */
    public function core_block_abstract_to_html_before($observer)
    {
        $block = $observer->getEvent()->getData('block');
        $helper = Mage::helper('iparcel');

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View) {
            $order = $block->getShipment()->getOrder();
            $cancelUrl = $helper->getShipmentCancelUrl($block->getShipment()->getId());
            $splitUrl = $helper->getShipmentSplitUrl($block->getShipment()->getId());

            if ($helper->isIparcelOrder($order)) {
                $confirmationMessage = $helper->__('Are you sure you want to cancel this shipment?');

                $block->addButton(
                    'shipment_cancel',
                    array(
                        'label' => $helper->__('Cancel'),
                        'onclick' => 'deleteConfirm(\'' . $confirmationMessage . '\', \'' . $cancelUrl . '\')'
                    )
                );

                $block->addButton(
                    'shipment_split',
                    array(
                        'label' => $helper->__('Split'),
                        'onclick'   => 'setLocation(\'' . $splitUrl . '\')',
                        'class' => 'go'
                    )
                );
            }
        }

        return true;
    }

    /**
     * Adds the "tax" and "duty" line item to PayPal API requests
     *
     * @param Varien_Event_Observer $observer
     */
    public function paypal_prepare_line_items(Varien_Event_Observer $observer)
    {
        try {
            $cart = $observer->getEvent()->getPaypalCart();
            $shippingAddress = $cart->getSalesEntity()->getShippingAddress();
            $totalAbstract = Mage::getModel('iparcel/quote_address_total_abstract');
            if (!is_object($shippingAddress)) {
                return;
            }

            $iparcelTax = 0;
            $iparcelDuty = 0;

            if ($totalAbstract->isIparcelShipping($shippingAddress)) {
                $iparcelTax = $shippingAddress->getIparcelTaxAmount();
                $iparcelDuty = $shippingAddress->getIparcelDutyAmount();
            } else {
                $carrier = $cart->getSalesEntity()->getShippingCarrier();
                $iparcelCarrierCode = Mage::getModel('iparcel/payment_iparcel')->getCode();

                if (is_object($carrier) && $carrier->getCarrierCode() == $iparcelCarrierCode) {
                    $iparcelTax = $cart->getSalesEntity()->getIparcelTaxAmount();
                    $iparcelDuty = $cart->getSalesEntity()->getIparcelDutyAmount();
                }
            }

            if ($iparcelTax > 0) {
                $cart->addItem('Tax', 1, $iparcelTax, 'tax');
            }

            if ($iparcelDuty > 0) {
                $cart->addItem('Duty', 1, $iparcelDuty, 'duty');
            }
        } catch (Exception $e) {
            Mage::log('Unable to add i-parcel Tax/Duty to PayPal Order.');
            Mage::log($e->getMessage());
        }

        return true;
    }
}
