<?php
/**
 * CartHandoff CheckItems Cache Controller
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Adminhtml_Ipcarthandoff_Checkitems_CacheController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Clears CheckItems cache
     */
    public function clearAction()
    {
        $cache = Mage::getModel('ipcarthandoff/checkitems')
               ->getCollection();

        foreach($cache as $item) {
            $item->delete();
        }
    }
}
