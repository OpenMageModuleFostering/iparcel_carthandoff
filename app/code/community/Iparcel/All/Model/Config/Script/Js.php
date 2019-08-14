<?php
/**
 * Backend model for JavaScript file config field
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Config_Script_Js extends Mage_Adminhtml_Model_System_Config_Backend_File
{
    const UPLOAD_ROOT = 'js';
    const UPLOAD_DIR = 'iparcel_custom';

    /**
     * Method called before config save
     * Unlinking old or deleted file
     */
    protected function _beforeSave()
    {
        $uploadDir = $this->_getUploadDir();

        $file = $uploadDir.'/'.$this->getValue();

        // if it's set new action and old file exists
        $file = $uploadDir.'/'. $_FILES['groups']['name'][$this->getGroupId()]['fields'][$this->getField()]['value'];
        if (file_exists($file)) {
            unlink($file);
        }
        parent::_beforeSave();
    }

    /**
     * Getting directory where scripts are uploaded
     *
     * @param string $field
     * @return string
     */
    public function getUploadDir($field)
    {
        $uploadDir = self::UPLOAD_DIR;

        $uploadDir = $uploadDir.'/'.$field. '/';
        return $uploadDir;
    }

    /**
     * Getting directory wher to upload
     *
     * @return string
     */
    protected function _getUploadDir()
    {
        $uploadRoot = $this->_getUploadRoot(self::UPLOAD_ROOT);
        $uploadDir = $this->_appendScopeInfo(self::UPLOAD_DIR.'/'.$this->getField());

        $uploadDir = $uploadRoot .'/'. $uploadDir;
        return $uploadDir;
    }

    /**
     * Returning if scope info should be added
     *
     * @return bool
     */
    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    /**
     * Returning array of proper extensions
     *
     * @return array
     */
    protected function _getAllowedExtensions()
    {
        return array('js');
    }

    /**
     * Getting upload root directory
     *
     * @return string
     */
    protected function _getUploadRoot($token)
    {
        return Mage::getBaseDir()."/$token";
    }
}
