<?php
class YourSite_Update_Block_Page extends Mage_Core_Block_Template{

	private function getSync(){
		$sync = Mage::getModel('update/sync');
		return $sync;
	}
		
	public function getTest(){
		$id = array(136);
		//return $this->getSync()->setBundles($id);
		//return $this->getSync()->setVisibility($ids);
		//return $this->getSync()->getYourSiteProducts()->Get($id, Products::ALL);
		//return $this->getSync()->getProductsDiff('products');
		//return $this->getSync()->getYourSiteCombos()->GetByProductID($id);
		//return $this->getSync()->getDbclassProductIds("sync");
	}
	
	private function getSkuList($products){
		$html = "";
		foreach( $products as $product ) {
			if( isset($product) ) {
				$html .= "<li>".$product->getInformation('sku')."</li>";
			}				
		}
		return $html;
	}
	
	public function getMissingProducts($key = ""){
		$products = $this->getSync()->getProductsDiff($key);
		return $this->getSkuList($products);
	}
}

?>