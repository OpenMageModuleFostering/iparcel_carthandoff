<?php
/**
 * Class for i-parcel external sales API payment method
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Payment_Iparcel extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'iparcel';
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping = false;
    protected $_canUseInternal = false;
    protected $_canCapture = true;

    protected $_taxCode = 'iparcel_tax';
    protected $_dutyCode = 'iparcel_duty';

    public function getTaxCode() 
    {
    	return $this->_taxCode;
    }

    public function getDutyCode()
    {
    	return $this->_dutyCode;
    }
}
