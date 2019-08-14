<?php
/**
 * Admin Shipment management controller
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Adminhtml_Iparcel_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Cancel's a shipment from i-parcel
     *
     * @return bool
     */
    public function cancelAction()
    {
        $params = $this->getRequest()->getParams();
        if (array_key_exists('shipment', $params)) {
            $shipment = Mage::getModel('sales/order_shipment')->load($params['shipment']);
            if (Mage::helper('iparcel/api')->cancelShipment($shipment)) {
                Mage::getSingleton('adminhtml/session')->addSuccess('Shipment canceled');
            }
        }

        $this->_redirectReferer();
        return true;
    }

    /**
     * Display the split shipment block
     *
     * @return bool
     */
    public function splitAction()
    {
        $params = $this->getRequest()->getParams();

        if (array_key_exists('shipment', $params)) {

            if (Mage::helper('iparcel')->getShipmentItemCount($params['shipment']) > 1) {
                Mage::register('current_shipment', Mage::getModel('sales/order_shipment')->load($params['shipment']));
                $this->loadLayout()->renderLayout();
            } else {
                Mage::getSingleton('adminhtml/session')->addError('Shipment must have multiple items to split.');
                $this->_redirectReferer();
                return true;
            }

        } else {
            $this->_redirectReferer();
        }

        return true;
    }
}
