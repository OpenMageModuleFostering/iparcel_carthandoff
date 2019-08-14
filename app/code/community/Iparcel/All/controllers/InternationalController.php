<?php
/**
 * i-parcel International Customer controller
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_InternationalController extends Mage_Core_Controller_Front_Action
{
    /**
     * Preparing headers for external ajax
     */
    protected function _prepareHeaders()
    {
        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Access-Control-Allow-Origin', '*');
    }

    /**
     * Changing international flag to true and printing if there was flag change
     */
    public function enableAction()
    {
        $this->_prepareHeaders();
        $current = Mage::helper('iparcel/international')->getInternational();
        if ($current) {
            $this->getResponse()->setBody('false');
        } else {
            Mage::helper('iparcel/international')->setInternational(true);
            $this->getResponse()->setBody('true');
        }
    }

    /**
     * Changing international flag to false and printing if there was flag change
     */
    public function disableAction()
    {
        $this->_prepareHeaders();
        $current = Mage::helper('iparcel/international')->getInternational();
        if ($current) {
            Mage::helper('iparcel/international')->setInternational(false);
            $this->getResponse()->setBody('true');
        } else {
            $this->getResponse()->setBody('false');
        }
    }
}
