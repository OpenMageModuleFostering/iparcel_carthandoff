<?php
/**
 * i-parcel custom post script block
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Html_Head_Post extends Mage_Core_Block_Template
{
    /**
     * Checking if there's store config for custom post script, if yes returning script directory, if not returning default directory
     *
     * @return array
     */
    public function getScript()
    {
        $postScripts = array('iparcel/post.js');
        $file = Mage::getStoreConfig('iparcel/scripts/post');
        if ($file) {
            $postScripts[] = Mage::getModel('iparcel/config_script_js')->getUploadDir('post') . $file;
        }
        return $postScripts;
    }
}
