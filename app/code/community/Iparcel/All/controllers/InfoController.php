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
        $versions = $this->_gatherExtensionVersions();

        foreach ($versions as $key => $version) {
            print "<b>$key</b>: $version<br />";
        }
    }

    /**
     * Gathers extension versions for any installed i-parcel extensions
     *
     * @return array
     */
    private function _gatherExtensionVersions()
    {
        $extensions = array(
            'Iparcel_All' => 0,
            'Iparcel_GlobaleCommerce' => 0,
            'Iparcel_Logistics' => 0
        );

        $allExtensions = Mage::app()->getConfig()->getNode('modules')->asArray();

        foreach ($extensions as $key => &$version) {
            if (array_key_exists($key, $allExtensions)) {
                $version = $allExtensions[$key]['version'];
            } else {
                unset($extensions[$key]);
            }
        }

        return $extensions;
    }
}
