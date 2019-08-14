<?php
/**
 * Controller for handling payment verification after cart handoff
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_StatusController extends Mage_Core_Controller_Front_Action
{
    /**
     * Handles payment verification requests from i-parcel
     */
    public function indexAction()
    {
        $post = $this->getRequest()->getPost();

        if ($this->_verifyPost($post) == true) {
            $paymentModel = Mage::getModel('ipcarthandoff/payment_ipcarthandoff');
            $status = $paymentModel->processStatusUpdate($post);
            if ($status === true) {
                $this->getResponse()->setHeader('HTTP/1.1', '200 OK');

                // Find the Order Increment ID for the status update
                $order = Mage::helper('ipcarthandoff')->loadOrderByTrackingNumber(
                    $post['trackingnumber']
                );
                $this->getResponse()->setBody($order->getIncrementId());

                return;
            } else {
                $this->getResponse()->setBody($status);
            }
        }

        $this->getResponse()->setHeader('HTTP/1.1', '400 Bad Request');
        return;
    }

    /**
     * Performs validations on POST message.
     *
     * In case of an error, `false` is returned, and the response body is filled
     * with an error message.
     *
     * @param array $post POST request
     * @return bool
     */
    private function _verifyPost($post)
    {
        $messages = array();

        // Check POST for errors

        // Check "business" key
        if (!array_key_exists('business', $post)
            || $post['business'] != Mage::getStoreConfig('iparcel/config/userid')
        ) {
            $messages[] = "API Key is missing or invalid.";
        }

        // Check for "status" key
        if (!array_key_exists('status', $post)
            || $post['status'] == ''
        ) {
            $messages[] = "Status is missing in request.";
        }

        // Check for tracking number
        if (!array_key_exists('trackingnumber', $post)
            || $post['trackingnumber'] == ''
        ) {
            $messages[] = "Tracking Number is missing in request.";
        }

        // If errors are found, append the messages to the response and return
        if (count($messages)) {
            $responseMessage = implode("\n", $messages);
            $this->getResponse()->setBody($responseMessage);
            return false;
        }

        return true;
    }
}