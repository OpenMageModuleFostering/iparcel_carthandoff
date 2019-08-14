<?php
/**
 * Block for splitting shipments
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Adminhtml_Iplogistics_Shipment_Split extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'shipment_id';
        $this->_controller = 'iparcel_shipment';
        $this->_mode = 'create';

        parent::__construct();

        $this->_removeButton('save');
        $this->_removeButton('delete');
    }

    /**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        return Mage::registry('current_shipment');
    }

    public function getHeaderText()
    {
        $header = Mage::helper('iparcel')->__('Split Shipment #%s', $this->getShipment()->getIncrementId());
        return $header;
    }

    public function getBackUrl()
    {
        return $this->getUrl('*/sales_order_shipment/view', array('shipment_id'=>$this->getShipment()->getId()));
    }
}
