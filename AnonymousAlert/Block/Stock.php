<?php
/**
 * Customer login block
 *
 * @category   YourSite
 * @package    YourSite_AnonymousAlert
 * @author     Harl
 */
class YourSite_AnonymousAlert_Block_Stock extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('anonymousalert/stock.phtml');
    }
	
	public function getUrl($route = '', $params = array())
    {
        return Mage::helper('anonymousalert')->getSaveUrl();
    }
	
	public function getSubmitUrl()
    {
        $submitRouteData = $this->getData('submit_route_data');
        if ($submitRouteData) {
            $route = $submitRouteData['route'];
            $params = isset($submitRouteData['params']) ? $submitRouteData['params'] : array();
            $submitUrl = $this->getUrl($route, $params);
        } else {
            $submitUrl = $this->getUrl();
        }
        return $submitUrl;
    }
}
?>