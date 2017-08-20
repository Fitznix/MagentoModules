<?php
/**
 * Product alert for back in abstract resource model
 *
 * @category    YourSite
 * @package     YourSite_AnonymousAlert
 * @author      Harl
 */
abstract class YourSite_AnonymousAlert_Model_Resource_Abstract extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Retrieve alert row by object parameters
     *
     * @param Mage_Core_Model_Abstract $object
     * @return array|bool
     */
    protected function _getAlertRow(Mage_Core_Model_Abstract $object)
    {
        $adapter = $this->_getReadAdapter();
        if ($object->getAlertEmail() && $object->getProductId() && $object->getWebsiteId()) {
            $select = $adapter->select()
                ->from($this->getMainTable())
				->where('alert_email  = :alert_email')
                ->where('product_id  = :product_id')
                ->where('website_id  = :website_id');
            $bind = array(
            	':alert_email'  => $object->getAlertEmail(),
                ':product_id'  => $object->getProductId(),
                ':website_id'  => $object->getWebsiteId()
            );
            return $adapter->fetchRow($select, $bind);
        }
        return false;
    }

    /**
     * Load object data by parameters
     *
     * @param Mage_Core_Model_Abstract $object
     * @return YourSite_AnonymousAlert_Model_Resource_Abstract
     */
    public function loadByParam(Mage_Core_Model_Abstract $object)
    {
        $row = $this->_getAlertRow($object);
        if ($row) {
            $object->setData($row);
        }
        return $this;
    }
}
?>