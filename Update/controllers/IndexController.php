<?php
class YourSite_Update_IndexController extends Mage_Adminhtml_Controller_Action
{
	private function getSync(){
		$sync = Mage::getModel('update/sync');
		return $sync;
	}
	  
    public function indexAction()
    {
    	
    	$this->loadLayout();				
		if($this->getRequest()->getParam('route', false))$this->setSync($this->getRequest());
		$text = $this->getLayout()->createBlock('cms/block')->setBlockId('update_page')->toHtml();
		$block = $this->getLayout()->createBlock('core/text', 'update-block')->setText($text);
        $this->_addContent($block);
        $this->_setActiveMenu('update_menu')->renderLayout(); 

    } 
	
	private function clear(){
		Mage::getSingleton('core/session')->unsUpdateResults();
		Mage::getSingleton('core/session')->unsUpdateAttributeResults();
	}
	
	private function setSync($params){
		$route = $params->getParam('route', false);
		if($route === "products" || $route === "accessories"){
			$this->clear();
			$this->getSync()->setProductSync($this->getSync()->getCompareProductIdsDiff("accessories"));
			$this->getSync()->setCombonationProducts();
			if($route != "accessories"){
				$productIds = $this->getSync()->getCompareProductIdsDiff($route);
				$this->getSync()->setProductSync($productIds);				
				$this->getSync()->setVisibilityAll($productIds);
				$this->getSync()->setBundles($productIds);
			}
		}
		
		if($route === "sync"){
			$this->clear();
			$this->getSync()->setCombonationProducts();
			$productIds = $this->getSync()->getDbclassProductIds("products");
			$this->getSync()->setProductSync($productIds);			
			$this->getSync()->setVisibilityAll($productIds);
			$this->getSync()->setBundles($productIds);
		}
		
		if($route === "combo_products"){
			$this->clear();
			$this->getSync()->setCombonationProducts();
		}
		
		if($route === "update_bundles"){
			$this->clear();
			$product_ids = $this->getSync()->getMageDbclassProductIds("bundles");			
			$this->getSync()->setVisibility($product_ids);
			$this->getSync()->setBundles($product_ids);
		}
		
		if($route === "update_missing_bundles"){
			$this->clear();
			$this->getSync()->setBundlesWithNoOptions($this->getSync()->getMageDbclassProductIds("bundles"));
		}
		
		if($route === "update_visibility"){
			$this->clear();
			$this->getSync()->setVisibility($this->getSync()->getMageDbclassProductIds("bundles"));
		}
		
		if($route === "update_categories"){
			$this->clear();
			$this->getSync()->setCategoriesByDbclassIds($this->getSync()->getMageDbclassProductIds("bundles"));
		}
		
		if($route === "update_prices"){
			$this->clear();
			$result = "";
			$product_ids = $this->getSync()->getMageDbclassProductIds("bundles");
			foreach($product_ids as $id){
				$this->getSync()->setBundlePrices($id);
			}
			$result = Mage::getSingleton('core/session')->getUpdateResults();
			if($result === "")Mage::getSingleton('core/session')->setUpdateResults("<li>Bundle Prices Up to Date</li>");
		}
		
		if($route === "update_everything"){
			$this->clear();
			$this->getSync()->setCombonationProducts();
			$productIds = $this->getSync()->getDbclassProductIds("products");
			$this->getSync()->setProductSync($productIds);			
			$this->getSync()->setVisibilityAll($productIds);
			$this->getSync()->setBundles($productIds);
			$this->getSync()->setIsInStock($product_ids);
		}
		
		if($route === "update_stock"){
			$this->clear();
			$this->getSync()->setIsInStock();
			$this->getSync()->setComboProductStock();
		}
		
		if($route === "clear"){
			$this->clear();
		}
	}
}
?>