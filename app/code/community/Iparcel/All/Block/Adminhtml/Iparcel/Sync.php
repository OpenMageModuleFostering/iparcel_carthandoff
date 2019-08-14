<?php
/**
 * Adminhtml i-parcel Sync Block
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Adminhtml_Iparcel_Sync extends Mage_Adminhtml_Block_Template
{
    /**
     * Initialize factory instance
     */
    public function __construct()
    {
        $this->_blockGroup = 'iparcel';
        $this->_controller = 'adminhtml_iparcel_sync_ajax';
        $this->_headerText = $this->__('i-parcel Catalog Sync');
        parent::__construct();
    }

    /**
     * Add "Start" Button block
     *
     * @return Iparcel_All_Block_Adminhtml_Iparcel_Sync
     */
    protected function _prepareLayout()
    {
        $this->setChild('start_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(array(
                            'label' => 'Start',
                            'class' => 'go',
                            'onclick' => 'window.catalogSync.run()'
                        )));

        return parent::_prepareLayout();
    }

    /**
     * Return the HTML for the start button
     *
     * @return string
     */
    public function getStartButton()
    {
        return $this->getChildHtml('start_button');
    }
}
