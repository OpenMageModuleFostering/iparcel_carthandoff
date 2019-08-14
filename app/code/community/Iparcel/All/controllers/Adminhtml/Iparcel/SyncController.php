<?php
/**
 * Adminhtml i-parcel sync controller
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Adminhtml_Iparcel_SyncController extends Mage_Adminhtml_Controller_Action
{
    public function salesruleAction()
    {
        $_salesRuleCollection = Mage::getResourceModel('salesrule/rule_collection');
        /* var $_salesRuleCollection Mage_SalesRule_Model_Resource_Rule_Collection */
        Mage::helper('iparcel/api')->salesRule($_salesRuleCollection);
        $this->_redirectReferer();
    }
}
