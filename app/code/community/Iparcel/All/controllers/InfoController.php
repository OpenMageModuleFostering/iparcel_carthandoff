<?php
/**
 * Controller to display extension information
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_InfoController extends Mage_Core_Controller_Front_Action
{
    /**
     * Format and display extension information
     */
    public function indexAction()
    {
        $versions = Mage::helper('iparcel')->gatherExtensionVersions();

        foreach ($versions as $key => $version) {
            print "<b>$key</b>: $version<br />";
        }
    }
}
