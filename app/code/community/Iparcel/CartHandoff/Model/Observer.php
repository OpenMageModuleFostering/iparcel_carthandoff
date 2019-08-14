<?php
/**
 * Observer model
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Observer
{
    /**
     * Removes payment methods if Iparcel_Logistics isn't installed.
     *
     * In the case of a user selecting an i-parcel shipping method when
     * Iparcel_Logistics isn't installed, only allow the Iparcel_Carthandoff
     * payment method
     */
    public function verifyPaymentMethods($observer)
    {
        $block = $observer->getBlock();

        if ($block instanceof Mage_Payment_Block_Form_Container
            && Mage::helper('iparcel')->isLogisticsInstalled() == false
        ) {
            $paymentMethod = Mage::getModel('ipcarthandoff/payment_ipcarthandoff');
            if ($paymentMethod->canUseCheckout()) {
                /**
                 * The Logistics Only extension is not installed, and the
                 * Cart Handoff payment method is set for this session
                 */
                $methods = $block->getMethods();
                foreach ($methods as $key => $method) {
                    if ($method instanceof Iparcel_CartHandoff_Model_Payment_Ipcarthandoff == false) {
                        unset($methods[$key]);
                    }
                }

                $block->setMethods($methods);
            }
        }

        return;
    }
}