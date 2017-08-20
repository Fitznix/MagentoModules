<?php
/**
 * AnonymousAlert for back in stock model
 *
 * @method YourSite_AnonymousAlert_Model_Resource_Stock _getResource()
 * @method YourSite_AnonymousAlert_Model_Resource_Stock getResource()
 * @method int getProductId()
 * @method YourSite_AnonymousAlert_Model_Stock setProductId(int $value)
 * @method int getWebsiteId()
 * @method YourSite_AnonymousAlert_Model_Stock setWebsiteId(int $value)
 * @method string getAddDate()
 * @method YourSite_AnonymousAlert_Model_Stock setAddDate(string $value)
 * @method string getSendDate()
 * @method YourSite_AnonymousAlert_Model_Stock setSendDate(string $value)
 * @method int getSendCount()
 * @method YourSite_AnonymousAlert_Model_Stock setSendCount(int $value)
 * @method int getStatus()
 * @method YourSite_AnonymousAlert_Model_Stock setStatus(int $value)
 *
 * @category    YourSite
 * @package     YourSite_AnonymousAlert
 * @author      Harl
 */
class YourSite_AnonymousAlert_Model_Stock extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('anonymousalert/stock');
    }
	
    public function loadByParam()
    {
       if (!is_null($this->getProductId()) && !is_null($this->getWebsiteId())) {
            $this->getResource()->loadByParam($this);
        }
        return $this;
    }
}

?>