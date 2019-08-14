<?php
/**
 * Iparcel Logs Adminhtml Grid Block
 *
 * @category   Iparcel
 * @package    Iparcel_All
 * @author     Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Adminhtml_Iparcel_Logs_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Prepare grid collection object
     *
     * @return Iparcel_All_Block_Adminhtml_Logs_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('iparcel/log')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return Iparcel_All_Block_Adminhtml_Logs_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('created_at', array(
            'header' => $this->__('Timestamp'),
            'index' => 'created_at',
            'width' => '200px'
        ));
        $this->addColumn('controller', array(
            'header' => $this->__('API Controller'),
            'index' => 'controller',
            'width' => '200px'
        ));
        $this->addColumn('request', array(
            'header' => $this->__('Request'),
            'index' => 'request'
        ));
        $this->addColumn('response', array(
            'header' => $this->__('Response'),
            'index' => 'response'
        ));

        return parent::_prepareColumns();
    }
}
