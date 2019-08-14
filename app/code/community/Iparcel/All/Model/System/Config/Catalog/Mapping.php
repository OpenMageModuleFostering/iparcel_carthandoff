<?php
/**
 * Backend model of auto catalog updates config field
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Catalog_Mapping extends Mage_Core_Model_Config_Data
{
    const CRON_STRING_PATH = 'crontab/jobs/iparcel_catalog_mapping/schedule/cron_expr';

    /**
     * Getting i-parcel cron string path
     *
     * @return string
     */
    protected function _getCron()
    {
        return Mage::getModel('core/config_data')->load(self::CRON_STRING_PATH, 'path');
    }

    /**
     * Method called after config save
     * Checking if value is cron and enabling/disabling cron
     */
    protected function _afterSave()
    {
        if ($this->getValue() == Iparcel_All_Model_System_Config_Source_Catalog_Mapping_Mode::CRON) {
            $params = Mage::app()->getRequest()->getParams();
            $params = $params["groups"]["config"]["fields"];
            /* var $params array */

            $freq = $params['cron_frequency']['value'];
            $cron_expr = array();
            $cron_expr[2] = '*';
            $cron_expr[3] = '*';
            $cron_expr[4] = '*';
            switch ($freq) {
                case Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY:
                    $cron_expr[2] = $params['cron_monthday']['value'];
                    break;
                case Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY:
                    $cron_expr[4] = $params['cron_weekday']['value'];
                    break;
            }
            $cron_expr[0] = $params['cron_minute']['value'];
            $cron_expr[1] = $params['cron_hour']['value'];

            ksort($cron_expr, SORT_NUMERIC);
            $cron_expr = trim(implode(' ', $cron_expr));

            try {
                $this->_getCron()
                    ->setValue($cron_expr)
                    ->setPath(self::CRON_STRING_PATH)
                    ->save();
            } catch (Exception $e) {
                throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression'));
            }
        } else {
            try {
                $this->_getCron()
                    ->delete();
            } catch (Exception $e) {
                throw new Exception(Mage::helper('cron')->__('Unable to remove the cron expression'));
            }
        }
    }
}
