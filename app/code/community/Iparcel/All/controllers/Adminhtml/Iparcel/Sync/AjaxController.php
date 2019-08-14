<?php
/**
 * Ajax Sync Controller
 *
 * @category    Iparcel
 * @package     Iparcel_Shipping
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Adminhtml_Iparcel_Sync_AjaxController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Preparing handles for controller
     *
     * @params array $handles
     * @return Iparcel_All_Adminhtml_Iparcel_Sync_AjaxController
     */
    protected function _setupHandles(array $handles)
    {
        $update = $this->getLayout()->getUpdate();
        /* @var $update Mage_Core_Model_Layout_Update */
        $update->resetHandles();
        $update->addHandle('adminhtml_iparcel_sync_ajax');
        foreach ($handles as $handle) {
            if (is_string($handle)) {
                $update->addHandle($handle);
            }
        }
        $this->addActionLayoutHandles();
        $this->loadLayoutUpdates();
        $this->generateLayoutXml();
        $this->generateLayoutBlocks();
        $this->_isLayoutLoaded = true;
        return $this;
    }

    /**
     * Response for init querry
     *
     * @return array
     */
    protected function _initResponse()
    {
        $step = Mage::getStoreConfig('catalog_mapping/upload/step');
        return array('count' => Mage::getModel('catalog/product')->getCollection()->getSize()-floor(Mage::getStoreConfig('catalog_mapping/upload/offset')/$step)*$step);
    }

    /**
     * Response for initCatalog querry
     *
     * @alias _initResponse
     * @return array
     */
    protected function _initCatalogResponse()
    {
        return $this->_initResponse();
    }

    /**
     * Response for initCheckitems querry
     *
     * @alias _initResponse
     * @return array
     */
    protected function _initCheckitemsResponse()
    {
        return $this->_initResponse();
    }

    /**
     * Response for uploadCatalog querry
     *
     * @param array $params
     * @return array
     */
    protected function _uploadCatalogResponse($params)
    {
        $page = $params['page'];
        $step = $params['step'];

        $offset = Mage::getStoreConfig('catalog_mapping/upload/offset');
        $page += floor($offset/$step);

        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setPageSize($step)
            ->setCurPage($page);
        /* var $productCollection Mage_Catalog_Model_Resource_Product_Collection */

        $n = Mage::helper('iparcel/api')->submitCatalog($productCollection);

        if ($n != -1) {
            return array(
                'page'=>$page,
                'step'=>$step,
                'uploaded'=>$n
            );
        } else {
            return array(
                'error'=>'1'
            );
        }
    }

    /**
     * Response for _uploadCheckitems querry
     *
     * @param array $params
     * @return array
     */
    protected function _uploadCheckitemsResponse($params)
    {
        $page = $params['page'];
        $step = $params['step'];

        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setPageSize($step)
            ->setCurPage($page);
        /* var $productCollection Mage_Catalog_Model_Resource_Product_Collection */

        $n = Mage::helper('iparcel/api')->checkItems($productCollection);

        if ($n != -1) {
            return array(
                'page' => $page,
                'step' => $step,
                'uploaded' => $n
            );
        } else {
            return array(
                'error' => '1'
            );
        }
    }

    /**
     * Submit Catalog request action
     */
    public function catalogAction()
    {
        // if is xmlHttp Request
        if ($this->getRequest()->isXmlHttpRequest()) {
            // proceed it
            $params = $this->getRequest()->getParams();
            $params['type'] = '_'.$params['type'].'CatalogResponse';
            if (method_exists($this, $params['type'])) {
                $_response = $this->$params['type']($params);
                $this->getResponse()
                    ->setHeader('Content-Type', 'application/json')
                    ->setBody(json_encode($_response));
            }
        } else {
            // show layout if not
            $this->_setupHandles(array('adminhtml_iparcel_sync_ajax_catalog'))
                ->renderLayout();
        }
    }

    /**
     * Check Items request action
     */
    public function checkitemsAction()
    {
        // if is xmlHttp Request
        if ($this->getRequest()->isXmlHttpRequest()) {
            // proceed it
            $params = $this->getRequest()->getParams();
            $params['type'] = '_'.$params['type'].'CheckItemsResponse';
            if (method_exists($this, $params['type'])) {
                $_response = $this->$params['type']($params);
                $this->getResponse()
                    ->setHeader('Content-Type', 'application/json')
                    ->setBody(json_encode($_response));
            }
        } else {
            // show layout if not
            $this->_setupHandles(array('adminhtml_iparcel_sync_ajax_checkitems'))
                ->renderLayout();
        }
    }
}
