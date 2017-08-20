<?php

/**
 * Product alert for back in stock collection
 *
 * @category    YourSite
 * @package     YourSite_AnonymousAlert
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class YourSite_AnonymousAlert_Model_Resource_Stock_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Define stock collection
     *
     */
    protected function _construct()
    {
        $this->_init('anonymousalert/stock');
    }

    /**
     * Add website filter
     *
     * @param mixed $website
     * @return YourSite_AnonymousAlert_Model_Resource_Stock_Collection
     */
    public function addWebsiteFilter($website)
    {
        $adapter = $this->getConnection();
        if (is_null($website) || $website == 0) {
            return $this;
        }
        if (is_array($website)) {
            $condition = $adapter->quoteInto('website_id IN(?)', $website);
        } elseif ($website instanceof Mage_Core_Model_Website) {
            $condition = $adapter->quoteInto('website_id=?', $website->getId());
        } else {
            $condition = $adapter->quoteInto('website_id=?', $website);
        }
        $this->addFilter('website_id', $condition, 'string');
        return $this;
    }

    /**
     * Add status filter
     *
     * @param int $status
     * @return YourSite_AnonymousAlert_Model_Resource_Stock_Collection
     */
    public function addStatusFilter($status)
    {
        $condition = $this->getConnection()->quoteInto('status=?', $status);
        $this->addFilter('status', $condition, 'string');
        return $this;
    }
}
