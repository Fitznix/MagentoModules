<?php
/**
 * AnonymousAlert observer
 *
 * @category   YourSite
 * @package    YourSite_Update
 * @author     Harl
 */
class YourSite_Update_Model_Observer
{
	private function getSync(){
		$sync = Mage::getModel('update/sync');
		return $sync;
	}
	
	private function clear(){
		Mage::getSingleton('core/session')->unsUpdateResults();
		Mage::getSingleton('core/session')->unsUpdateAttributeResults();
	}
	
	public function stock(){
		$this->clear();
		$this->getSync()->setIsInStock();
		$this->getSync()->setComboProductStock();
		$this->clear();
	}
	
	public function updateBundlePrices(){
		$this->clear();
		$product_ids = $this->getSync()->getMageDbclassProductIds("bundles");
		Mage::log("Bundle Prices Updated", null, "update.log");
		foreach($product_ids as $id){
			$this->getSync()->setBundlePrices($id);
		}
	}
	
	public function sync(){
		$this->clear();
		$this->getSync()->setCombonationProducts();
		$productIds = $this->getSync()->getDbclassProductIds("sync");
		$this->getSync()->setProductSync($productIds);			
		$this->getSync()->setVisibilityAll($productIds);
		$this->getSync()->setBundles($productIds);
		$this->getSync()->setIsInStock($product_ids);
		$this->clear();
	}
	
	public function bundles(){
		$this->clear();
		$product_ids = $this->getSync()->getMageDbclassProductIds("bundles");			
		$this->getSync()->setVisibility($product_ids);
		$this->getSync()->setBundles($product_ids);
		$this->clear();
	}
	
}
?>