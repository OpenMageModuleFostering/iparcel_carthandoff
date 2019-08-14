<?php
/**
 * Admin Shipment split controller
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Adminhtml_Iparcel_Shipment_SplitController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Save a split shipment
     *
     * @return bool
     */
    public function saveAction()
    {
        $params = $this->getRequest()->getParams();
        $shipment = Mage::getModel('sales/order_shipment')->load($params['shipment']['info']['shipment_id']);
        $shipmentItems = $params['shipment']['items'];

        $itemsCollection = array();
        foreach($shipmentItems as $itemId => $itemQty) {
            if ($itemQty < 1) {
                continue;
            }

            $orderItem = $shipment->getOrder()->getItemById($itemId);
            $itemsCollection[] = array(
                'sku' => $orderItem->getSku(),
                'qty' => $itemQty
            );
        }

        try {
            if (Mage::helper('iparcel/api')->splitShipment($shipment, $itemsCollection)) {
                Mage::getSingleton('adminhtml/session')->addSuccess('Shipment successfully split');
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/sales_order_shipment/view', array(
                'shipment_id' => $shipment->getId()
            ));
            return true;
        }

        $this->_redirect('adminhtml/sales_order/view', array(
            'order_id' => $shipment->getOrder()->getId()
        ));
        return true;
    }
}
