<?php

/**
 * Product alert for back in stock resource model
 *
 * @category    YourSite
 * @package     YourSite_AnonymousAlert
 * @author      Harl
 */
class YourSite_AnonymousAlert_Model_Resource_Stock extends YourSite_AnonymousAlert_Model_Resource_Abstract
{
    /**
     * Initialize connection
     *
     */
    protected function _construct()
    {
        $this->_init('anonymousalert/stock', 'alert_stock_id');
    }

    /**
     * Before save action
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (is_null($object->getId()) && $object->getAlertEmail() && $object->getProductId() && $object->getWebsiteId()) {
            if ($row = $this->_getAlertRow($object)) {
                $object->addData($row);
                $object->setStatus(0);
            }
        }
        if (is_null($object->getAddDate())) {
            $object->setAddDate(Mage::getModel('core/date')->gmtDate());
            $object->setStatus(0);
        }
        return parent::_beforeSave($object);
    }
}

?>