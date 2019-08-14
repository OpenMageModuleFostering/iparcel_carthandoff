<?php
/**
 * Block for splitting shipments form
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Adminhtml_Iplogistics_Shipment_Split_Form extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    /**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        return Mage::registry('current_shipment');
    }

    public function getItemsHtml()
    {
        return $this->getChildHtml('shipment_items');
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/iparcel_shipment_split/save', array('shipment' => $this->getShipment()));
    }
}
