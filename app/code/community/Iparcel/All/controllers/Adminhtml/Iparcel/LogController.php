<?php
/**
 * Adminhtml i-parcel logs controller
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Adminhtml_Iparcel_LogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init adminhtml response
     *
     * @return Iparcel_All_Adminhtml_Iparcel_LogController
     */
    protected function _init()
    {
        $this->loadLayout()
            ->_setActiveMenu('iparcel/log')
            ->_title($this->__('Logs'))->_title($this->__('i-parcel'))
            ->_addBreadcrumb($this->__('Logs'), $this->__('Logs'));
        return $this;
    }

    /**
     * Show grid action
     */
    public function indexAction()
    {
        $this->_init()
            ->renderLayout();
    }

    /**
     * Clear log action
     */
    public function clearAction()
    {
        $collection = Mage::getModel('iparcel/log')->getCollection();
        $collection->deleteAllItems();

        $this->_redirect('*/*/index');
    }

    /**
     * Downloads CSV of i-parcel log table without file system write
     */
    public function downloadAction()
    {
        $this->getResponse()->clearHeaders();
        $this->getResponse()->setHeader('Content-Type', 'text/csv', true);
        $this->getResponse()->setHeader('Content-Disposition',  'attachment;filename="iparcel-log.csv";', true);
        $this->getResponse()->setHeader('Content-Transfer-Encoding', 'binary', true);

        $fh = fopen('php://memory', 'rw');

        $collection = Mage::getModel('iparcel/log')->getCollection();
        $csv = "ID, Timestamp, \"API Controller\", Request, Response\n";

        foreach ($collection as $item) {
            $line = array(
                $item->getId(),
                $item->getCreatedAt(),
                $item->getController(),
                $item->getRequest(),
                $item->getResponse()
            );

            fputcsv($fh, $line, ',');
            rewind($fh);
            $csv .= fgets($fh);
            rewind($fh);
        }

        fclose($fh);
        $this->getResponse()->setBody($csv);

        return;
    }
}
