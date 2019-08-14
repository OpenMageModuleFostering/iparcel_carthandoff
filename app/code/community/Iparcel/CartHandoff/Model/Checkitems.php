<?php
/**
 * Provides an interface to the CheckItems cache
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Checkitems extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('ipcarthandoff/checkitems');
    }

    /**
     * Caches the response from the CheckItems web service
     *
     * @param array $items
     * @param Mage_Catalog_Model_Product_Collection $collection
     * @param string $country
     * @param integer $storeId
     * @return $this
     */
    public function cacheResponse($items, $collection, $country, $storeId)
    {
        // Find just the list of SKUs
        foreach ($items as $key => $item) {
            $items[$item['SKU']] = $item['ValueCompanyCurrency'];
            unset($items[$key]);
        }

        // Save eligibility of each SKU
        foreach ($collection as $product) {
            if (in_array($product->getSku(), array_keys($items))) {
                $eligibile = true;
                $price = $items[$product->getSku()];
            } else {
                $eligibile = false;
                $price = null;
            }

            $checkItems = Mage::getModel('ipcarthandoff/checkitems')
                ->getCollection()
                ->addFieldToFilter('sku', $product->getSku())
                ->addFieldToFilter('country', $country)
                ->addFieldToFilter('store_id', $storeId)
                ->getFirstItem();

            $checkItems->setSku($product->getSku());
            $checkItems->setCountry($country);
            $checkItems->setUpdatedAt(0);
            $checkItems->setStoreId($storeId);
            $checkItems->setPrice($price);
            $checkItems->setEligible($eligibile);
            $checkItems->save();

            unset($checkItems);
        }

        return $this;
    }

    /**
     * Retrieves cache from database
     *
     * @param Mage_Catalog_Product_Collection $collection
     * @param string $country
     * @return array
     */
    public function getCache($collection, $country, $storeId)
    {
        $results = array();

        // Pull skus from the collection
        $skus = array();
        foreach ($collection as $product) {
            $skus[] = $product->getSku();
        }

        $pastDate = $this->_getCacheExpirationFromDate();
        $now = Mage::getModel('core/date')
            ->date('Y-m-d H:i:s', strtotime('now'));

        // Query the cached results from the database
        $cache = $this->getCollection()
            ->addFieldToFilter('sku', array('in' => $skus))
            ->addFieldToFilter('country', $country)
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('updated_at', array(
                'from' => $pastDate,
                'to' => $now,
                'date' => true
            ));

        foreach ($cache as $item) {
            // Only return eligible items
            if ($item->getEligible()) {
                $results[$item->getSku()] = $item->getPrice();
            }
        }

        return $results;
    }

    public function getCachedPrice($product)
    {
        $storeId = Mage::app()->getStore()->getId();

        // Get iparcel cookie
        $cookie = Mage::getModel('core/cookie')->get('ipar_iparcelSession');
        $cookie = json_decode($cookie);

        if (!isset($cookie->locale) || $cookie->locale == 'US') {
            return null;
        }

        // Make checkItems call for the product
        $apiHelper = Mage::helper('ipcarthandoff/api');
        $product = $apiHelper->checkItems($product);

        $checkItem = $this->getCollection()
            ->addFieldToFilter('sku', $product->getSku())
            ->addFieldToFilter('country', $cookie->locale)
            ->addFieldToFilter('store_id', $storeId)
            ->getFirstItem();

        return $checkItem->getPrice();
    }

    /**
     * Finds the "From" date that matches the cache expiration setting
     *
     * @return object
     */
    private function _getCacheExpirationFromDate()
    {
        $value = Mage::getStoreConfig('iparcel/international_customer/checkitems_cache_lifetime');
        $sourceModel = Mage::getModel('ipcarthandoff/system_config_source_checkitems_cache');
        $sourceArray = $sourceModel->toArray();
        $value = $sourceArray[$value];

        return Mage::getModel('core/date')
            ->date('Y-m-d H:i:s ', strtotime('-1 ' . $value));
    }
}