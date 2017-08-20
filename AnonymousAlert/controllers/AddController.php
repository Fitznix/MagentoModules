<?php
/**
 * AnonymousAlert controller
 *
 * @category   Mage
 * @package    YourSite_AnontmousAlert
 * @author     Harl
 */
class YourSite_AnonymousAlert_AddController extends Mage_Core_Controller_Front_Action
{
	 public function preDispatch()
    {
        parent::preDispatch();
    }
    public function testObserverAction()
    {
        $object = new Varien_Object();
        $observer = Mage::getSingleton('anonymousalert/observer');
        $observer->process($object);
    }

    public function stockAction()
    {
       $session = Mage::getSingleton('catalog/session');
        /* @var $session Mage_Catalog_Model_Session */
        $backUrl    = $this->getRequest()->getParam(Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED);
        $productId  = (int) $this->getRequest()->getParam('product_id');
        if (!$backUrl || !$productId) {
            $this->_redirect('/');
            return ;
        }

        if (!$product = Mage::getModel('catalog/product')->load($productId)) {
            /* @var $product Mage_Catalog_Model_Product */
           $session->addError($this->__('Not enough parameters.'));
            $this->_redirectUrl($backUrl);
            return ;
        }

        try {
            $model = Mage::getModel('anonymousalert/stock')
                ->setAlertEmail($this->getRequest()->getParam('alert_email', false))
                ->setProductId($product->getId())
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
            $model->save();
            $session->addSuccess($this->__('Alert request has been saved.'));
        }
        catch (Exception $e) {
            $session->addException($e, $this->__('Unable to update the alert request.'));
        }
        $this->_redirectReferer();
    }
}
?>