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
     * Response for init query
     *
     * @return bool
     */
    protected function catalogJsonInitAction()
    {
        $count = Mage::getModel('catalog/product')
               ->getCollection()
               ->addAttributeToFilter(
                   'type_id', array('in' =>
                                    array(
                                        'simple',
                                        'configurable'
                                    )))
               ->getSize();

        $response = array(
            'count' => $count
        );

        $this->getResponse()
            ->setHeader('Content-type', 'application/json', true)
            ->setBody(json_encode($response));

        return true;
    }

    /**
     * Response for uploadCatalog query
     *
     * @return bool
     */
    protected function catalogJsonUploadAction()
    {
        $params = $this->getRequest()->getParams();

        $page = (int) $params['page'];
        $step = (int) $params['step'];

        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->setPageSize($step)
            ->setCurPage($page);
        /** @var $productCollection Mage_Catalog_Model_Resource_Product_Collection */

        $n = Mage::helper('iparcel/api')->submitCatalog($productCollection);
        $skuList = array(
            $productCollection->getFirstItem()->getSku(),
            $productCollection->getLastItem()->getSku()
        );

        if ($n != -1) {
            $response = array(
                'page' => $page,
                'step' => $step,
                'uploaded' => $n,
                'SKUs' => $skuList
            );
        } else {
            $response = array(
                'error' => '1'
            );
        }

        $this->getResponse()
            ->setHeader('Content-type', 'application/json', true)
            ->setBody(json_encode($response));

        return true;
    }

    /**
     * Submit Catalog request action
     */
    public function catalogAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
