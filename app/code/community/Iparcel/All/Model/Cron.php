<?php
/**
 * Model for managing Cron jobs
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Cron
{
    /**
     * Performs Catalog Mapping via cronjobs
     *
     */
    public function catalogMapping()
    {
        // Only run the cronjob if auto_upload is set to CRON
        $cronSettingValue = Iparcel_All_Model_System_Config_Source_Catalog_Mapping_Mode::CRON;
        if (Mage::getStoreConfig('catalog_mapping/config/auto_upload') == $cronSettingValue) {
            $productCollection = Mage::getModel('catalog/product')->getCollection();
            Mage::helper('iparcel/api')->submitCatalog($productCollection);
        }
    }
}
