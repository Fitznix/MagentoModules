<?php
class YourSite_Update_Model_Sync extends Mage_Catalog_Model_Abstract
{
	private function getDbclass(){
		return Mage::registry('Dbclass');
	}
	
	public function getYourSiteCategories(){
		if( is_null(Mage::registry('YourSite_categories')) ) {
			$YourSite_categories = $this->getDbclass()->GetCategories();
			Mage::register('YourSite_categories', $YourSite_categories);
		}
		return Mage::registry('YourSite_categories');
	}
	
	public function getYourSiteProducts(){
		if( is_null(Mage::registry('YourSite_products')) ) {
			$YourSite_products = $this->getDbclass()->GetProducts();
			Mage::register('YourSite_products', $YourSite_products);
		}
		return Mage::registry('YourSite_products');	
	}
	
	public function getYourSiteCombos(){
		if( is_null(Mage::registry('YourSite_combos')) ) {
			$YourSite_combos = $this->getDbclass()->GetCombos();
			Mage::register('YourSite_combos', $YourSite_combos);
		}
		return Mage::registry('YourSite_combos');	
	}
	//get products category ids by product id
	public function getCategoriesByDbclassProductId($id){
		$category = Mage::getModel('catalog/category');
		$catTree = $category->getTreeModel()->load();
		$catIds = $catTree->getCollection()->getAllIds();
		$cats = $this->getYourSiteCategories()->GetByExternalID($catIds);
		$categoryProductIDs = array();
		$categoriesByProductID = array();
		foreach($catIds as $_id ){
			if(isset($cats[$_id])) $categoryProductIDs[$_id] = $cats[$_id]->GetProductIDs(StockLevels::AVAILABLE, Visibility::VISIBLE);		
		}
		foreach($categoryProductIDs as $_key => $IDS){
			if(in_array($id, $IDS))$categoriesByProductID[]=$_key;
		}		
		return $categoriesByProductID;
	}
	// get array of all active product ids by parameter route=$key
	public function getDbclassProductIds($key = ""){
		if($key == "accessories")$products = $this->getYourSiteProducts()->GetByType(array(3));
		if($key == "products" || $key == "")$products = $this->getYourSiteProducts()->GetByType(array(1,2));
		if($key == "sync")$products = $this->getYourSiteProducts()->GetByType(array(1,2,3));
		$product_ids = array();
		
		foreach( $products as $product ) {
			if( isset($product) ) {
				foreach($product as $_product){
					if(($key === "accessories" && $_product->getInformation('type') === "Faucet") || $_product->getStock()['master_stock_id'] > 7)continue;
					array_push($product_ids, $_product->getId());
				}
			}
		}
		return $product_ids;
	}
	// get Dbclasssnfder_product_id array from MAGE
	public function getMageDbclassProductIds($key = ""){
		$products = Mage::getResourceModel('catalog/product_collection')
    	->addAttributeToSelect(array('Dbclass_product_id'));
		if($key == "accessories")$products->addAttributeToFilter('attribute_set_id',array(11,12,13,14,15,16,17,18,19,20,21,22)); //Accessories only
    	if($key == "products" || $key == "")$products->addAttributeToFilter('attribute_set_id',array(9,10)); //Products only
    	if($key == "bundles" || $key == "")$products->addAttributeToFilter('attribute_set_id',array(23)); //Bundles only
    	$products->load();
		$product_ids = array();
		 foreach ($products as $product) {
				$id = str_replace("-b", "", $product->getDbclassProductId());
		 		array_push($product_ids, $id);
			}
		return $product_ids;
	}
	
	public function getCompareProductIdsDiff($key = ""){
		return array_diff($this->getDbclassProductIds($key), $this->getMageDbclassProductIds($key));
	}
	
	public function getProductsDiff($key = ""){
		return $this->getYourSiteProducts()->Get($this->getCompareProductIdsDiff($key), Products::ALL);
	}
	
	// set product data from id array
	public function setProductSync($product_ids){
		ignore_user_abort(true);
		set_time_limit(0);
		ini_set('memory_limit','512M');
		$prods = $this->getYourSiteProducts()->Get($product_ids, Products::ALL);
		$changeProduct  = $this->getUpdateResults();
		$results = "";
		$productAttributesArray = array();
		$count = 0;
		foreach($prods as $__key => $_prod){
			//if($count == 10)break;
			$count += 1;
			$sku 			= $_prod->GetInformation()['sku'];
			$description 	= $sku;//$_prod->GetInformation()['description'];
			$gauge			= $_prod->GetAttribute('Thickness')['entries']['name'];
			$name 			= $_prod->GetInformation()['fullname'];
			$name			= $sku." ".$name;
			$id 			= $_prod->GetID();
			$price			= round($_prod->GetInformation()['price']);
			$weight   		= $_prod->GetDimensions()['shipping_weight'];
			$finish_name 	= $_prod->GetAttribute('Finish')['entries']['name'];
			$finish 		= $_prod->GetAttribute('Finish')['entries']['value'];
			$type			= $_prod->GetInformation()['type'];
			if($type === "Accessory")$type = $_prod->GetAttribute('Accessory Type')['entries']['name'];
			$cycles 		= 1;
			$multi_gauges 	= "false";
			$bundleAttribute = array();
			$typeID = "simple";
			$weightType = 1;
			$product_gauges_count = 0;
			$Dbclass_stock_id = $_prod->getStock()['stock_id'];
			$is_in_stock = 1;
			if($Dbclass_stock_id === 4)$is_in_stock = 0;
			$products_gauges = $this->getYourSiteProducts()->GetByRelationship($id , Relationships::GAUGE, Products::BASE);
			foreach($products_gauges as $_products_gauges){$product_gauges_count = count($_products_gauges);}
			if ($type === "Faucet" || $type === "Sink")$cycles = 2;	
			if ($type === "Sink" && $gauge === "16_gauge" && $product_gauges_count > 0)$cycles = 1;		
			$attribute_set_id = $this->getAccessorieAttributeSetId(strtolower($type));
			
			for ($i=0; $i < $cycles; $i++) {
				$newproduct = "false";
				$b = "";$bundle = ""; $visible = 1; $display_weight =number_format((float)$weight, 4, '.', '');
				if($i > 0){
					$b = "-b";$bundle = "-bundle" ;$visible = 4;$attribute_set_id = 23;$display_weight = 0;$typeID = "bundle";$weightType = 0;
				}
				
				$attributes = array(
							'Description' 		=> $description,
							'Name' 				=> $name,
							'ShortDescription' 	=> $description,
							'Sku' 				=> $sku.$bundle,
							'Finish' 			=> $finish,
							'FinishName' 		=> $finish_name,							
							'Price' 			=> number_format((float)$price, 4, '.', ''),
							'Weight' 			=> $display_weight
					);
					$attributesNew = array();
					
				if(!Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id.$b)){
					$newproduct = "true";
					if($b === '-b')$bundleAttribute=array('CanSaveBundleSelections' => true);
					$attributesNew = array(
						'ProductType' 		=> $type,
						'AttributeSetId' 		=> $attribute_set_id,
						'CanSaveCustomOptions' 	=> true,
						'CreatedAt' 			=> time(),
						'PriceType' 			=> 1,
						'PriceView' 			=> 1,
						'ShipmentType' 			=> 0,
						'SkuType' 				=> 1,
						'Status' 				=> 1,
						'StoreId' 				=> Mage_Core_Model_App::ADMIN_STORE_ID,
						'TaxClassId' 			=> 2,
						'TypeId' 				=> $typeID,
						'Visibility' 			=> $visible,
						'WebsiteIds' 			=> array(1),
						'StockData' 			=> array( 
          												'is_in_stock' 				=> $is_in_stock,
														'manage_stock' 				=> 1,
														'qty' 						=> 128,
														'use_config_manage_stock' 	=> 0,
														'stock_id' 					=> 1
   														),
						'WeightType' 			=> $weightType,
						'DbclassProductId'		=> $id.$b,
					);
					
				}
				
				if($this->getMageProductDataCompare($id.$b, $attributes, $newproduct)){
					continue;
				} 
				$productAttributes = array_merge($attributesNew,$bundleAttribute,$attributes);
				$productAttributesArray[] = $productAttributes;
				$productAttributesArray[] = array('categories'=>$this->getCategoriesByDbclassProductId($id));
				
				if($newproduct == "true"){
					$_product = Mage::getModel('catalog/product');
					$changeProduct .= "<li>Insert: ".$sku.$bundle." ";
				}else{
					$_product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id.$b);
					$changeProduct .= "<li>Update: ".$sku.$bundle." ";
				}
				
				if($cycles === 2 && $b !== "")$_product->setCategoryIds($this->getCategoriesByDbclassProductId($id));
				$_product->setUsedInProductListing(true)->setIsMassupdate(true)->setExcludeUrlRewrite(true);
				$results = $this->setProduct($_product, $productAttributes);
				$changeProduct .= " ::".$results."</li>";
				
			}			
		}	
		if($changeProduct === "")$changeProduct .= "<li>Data In Sync</li>";
		Mage::log("Product Sync", null ,"update.log");	
		$changeProduct = $this->getUpdateResults().$changeProduct;
		Mage::getSingleton('core/session')->setUpdateResults($changeProduct);
		Mage::getSingleton('core/session')->setUpdateAttributeResults($productAttributesArray);
	}
	//This updates or inserts all accessory combos like right and left cutting board as YourSite product for bundles
	public function setCombonationProducts(){
		ignore_user_abort(true);
		set_time_limit(0);
		ini_set('memory_limit','512M');
		$cats = $this->getYourSiteCategories()->GetByURLKey(array("kitchen-sinks","bathroom-sinks","faucets"));
		$product_ids = array();
		$attributeArray = array();
		$changeProduct  = $this->getUpdateResults();
		foreach( $cats as $_key => $_cat ) {
			if( isset($_cat) ) {
				foreach($_cat->GetProductIDs(StockLevels::AVAILABLE, Visibility::VISIBLE) as $id){
					array_push($product_ids, $id);
				}
			}
		}
		$id = 0;
		//Insert or Update Combo Product
		$count = 0;
		$total_count = 0;
		$Dbclass_ids = array();
		$changeProduct = "";
		foreach($product_ids as $id){
			//if($count > 10)break;
			$count+=1;
			$related_ids = array();
			$combos = $this->getYourSiteCombos()->GetByProductID($id);
			$newproduct = false;
			foreach($combos as $_key => $_value){
				$category_ids 		= array(2,6);
				$Dbclass_id = '';
				$newproduct = false;
				$Dbclass_id = "";
				$sku = '';
				$price = 0;
				$discount = 0;
				$combo_type_name 	=  $_value['combo_type_name'];
				$combo_type		 	=  $_value['combo_type'];
				foreach($_value['groups'] as $__key => $__value){
					$short_description 	=  $__value['description'];
					$title			 	=  $__value['title'];
					foreach($__value['items'] as $___key => $___value){
						$finish 			=  $___value['finish'];
						$finish_name 		=  $___value['finish_name'];
						$discount	 		=  $___value['discount'];
						$title2			 	=  $___value['title'];
						$qty				=  $___value['quantity'];
						/*if($qty < 2){
							if(count($___value['products']) < 2) continue;
						}*/
						foreach($___value['products'] as $____key => $____value){
							$Dbclass_id				= $____value['id'];
							if($qty == 2){
							$sku 				=  $____value['sku']."~".$____value['sku'];
							$Dbclass_id 		= $Dbclass_id	."~".$Dbclass_id	;
							$price 				= $____value['price']*2;	
							}elseif($qty == 3){
							$sku 				=  $____value['sku']."~".$____value['sku']."~".$____value['sku'];
							$Dbclass_id 		= $Dbclass_id	."~".$Dbclass_id	."~".$Dbclass_id	;
							$price 				= $____value['price']*3;	
							}else{
							$sku 				=  $sku."~".$____value['sku'];
							$Dbclass_id 		= $Dbclass_id."~".$Dbclass_id;
							$price 				+=  $____value['price'];
							}															
							if($Dbclass_id == '')continue;			
							$prods = $this->getYourSiteProducts()->Get(array($Dbclass_id), Products::ALL);		
							foreach($prods as $_prod_key => $_prod){
								$name 			= $title2;
								$description 	= $_prod->GetInformation()['details'];
								$weight   		= $_prod->GetDimensions()['weight'];
								$type 			= $_prod->GetAttributes()[12]['entries']['name'];
								}
							$Dbclass_id 	= trim($Dbclass_id, "~");
							$sku 			= trim($sku, "~");
						}
						if($type == 'faucet_vessel')continue;
								$attribute_set_id = $this->getAccessorieAttributeSetId(strtolower($type));
								$price = $price - $discount;
								if($Dbclass_id === "~~~~" || $Dbclass_id === "~~" || $Dbclass_id === "~")continue;
							if (!in_array($Dbclass_id, $Dbclass_ids)) {
								array_push($Dbclass_ids, $Dbclass_id);
								//$changeProduct .= $Dbclass_id." ".$name."</br>";
								$attributes = array(
								'Description' 		=> $description,
								'Name' 				=> $name,
								'ShortDescription' 	=> $short_description,
								'Sku' 				=> $sku,
								'Finish' 			=> $finish,
								'FinishName' 		=> $finish_name,								
								'Price' 			=> number_format((float)$price, 4, '.', ''),
								'Weight' 			=> number_format((float)$weight, 4, '.', '')
								);
								$attributesNew = array();
							if(!Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $Dbclass_id)){
								$newproduct = "true";
								$attributesNew = array(
									'ProductType' 			=> $combo_type_name,
									'AttributeSetId' 		=> $attribute_set_id,
									'CanSaveCustomOptions' 	=> true,
									'CreatedAt' 			=> time(),
									'PriceType' 			=> 1,
									'PriceView' 			=> 1,
									'ShipmentType' 			=> 0,
									'SkuType' 				=> 1,
									'Status' 				=> 1,
									'StoreId' 				=> Mage_Core_Model_App::ADMIN_STORE_ID,
									'TaxClassId' 			=> 2,
									'TypeId' 				=> 'simple',
									'Visibility' 			=> Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
									'WebsiteIds' 			=> array(1),
									'StockData' 			=> array( 
          															'is_in_stock' 				=> 1,
																	'manage_stock' 				=> 1,
																	'qty' 						=> 128,
																	'use_config_manage_stock' 	=> 0,
																	'stock_id' 					=> 1
   																),
									'WeightType' 			=> 1,
									'DbclassProductId'		=> $Dbclass_id,
								);
								
							}
							$productAttributes = array_merge($attributesNew,$attributes);
							$productAttributesArray[] = $productAttributes;
							if($newproduct == "true"){
								$_product = Mage::getModel('catalog/product');
								$changeProduct .= "<li>Insert: ".$sku." ".$type;
							}else{
								$_product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $Dbclass_id);
								$changeProduct .= "<li>Update: ".$sku." ".$type;
							}
								$results = "";			
								$total_count +=1;	
								$attributeArray[$total_count] = $productAttributes;
								$_product->setCategoryIds($category_ids)->setUsedInProductListing(true)->setIsMassupdate(true)->setExcludeUrlRewrite(true);						
								if($Dbclass_id != '') $results = $this->setProduct($_product, $productAttributes);
								$changeProduct .= " ::".$results."</li>";	
								$Dbclass_id = "";
								$sku = "";
								$price = 0;
								$discount = 0;
							}
							$Dbclass_id = "";
							$sku = "";	
							$price = 0;
							$discount = 0;		
						}
					}			
				}
			}
		Mage::log("Multi Product Sync", null ,"update.log");	
		Mage::getSingleton('core/session')->setUpdateResults($changeProduct);
		Mage::getSingleton('core/session')->setUpdateAttributeResults($attributeArray);
	}

	// setBundles
	public function setBundles($product_ids){
		$id = 0;
		$count = 0;
		$changeProduct = "";
		$attributeArray = array();		
		//Insert or Update Combo Product
		foreach($product_ids as $id){
			//if($count > 20)break;
			$count += 1;
			$price = 0;
			$sale_price = 0;
			$compareSelections = "";
			set_time_limit(200);
			$combos = $this->getYourSiteCombos()->GetByProductID($id);
			//return $combos;
			$bundle_options_data 	= array();
			$bundle_selections_data = array();
			$bundle_selection_array = array();
			$product 				= Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id);
			$_product 				= Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id.'-b');
			if(!is_object($_product))continue;
			if(!is_object($product))continue;
			$sink_id 				= $product->getId();
			$product_type			= $_product->getProductType();
			$prods 					= $this->getYourSiteProducts()->Get(array($id), Products::BASE);
			$related_id 			= "";
			$related_product_id		= "";
			$type					="radio";		
			foreach($prods as $prod_key => $prod){
				$sku 			= $prod->GetInformation()['sku'];
				$name 			= $prod->GetInformation()['fullname'];
				$name			= $sku." ".$name;
				$price			= round($prod->GetInformation()['price']);
				$sale_price		= round($prod->GetInformation()['sale_price']);
				if($sale_price != "")$price = $sale_price;
			}
			$_product->setPrice($price);			
		$related =	$this->getReneProducts()->GetByRelationship(array($id) , Relationships::GAUGE, Products::BASE);
		foreach( $related as $_key => $_f_product ) {
				foreach( $_f_product as $_rel => $_s_products ) {
					if( count($_s_products) > 0 ) {
						foreach( $_s_products as $_s_product ) {
							$related_sku 		= $_s_product->GetInformation()['sku'];
							$name 				= $_s_product->GetInformation()['fullname'];
							$name				= $sku." ".$name;
							$related_product_id = $_s_product->GetID();
							$weight   			= $_s_product->GetDimensions()['weight'];
							$related_price		= round($_s_product->GetInformation()['price']);
							$sale_price			= round($_s_product->GetInformation()['sale_price']);
							if($sale_price != "")$related_price = $sale_price;
						}
					}
				}
			}
		if($related_product_id != ''){
			$related_product 		= Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $related_product_id);
			$related_id 			= $related_product->getId();
			$related_price_total 	= $related_price - $price;
			$type 					= "select";
		}
		
			$bundle_options_data[0] =  
				array(
					'delete' => '',
					'option_id' => '',
					'position' => 0,
					'required' => 1,
					'title' => $product_type,
					'type' => $type,
					);
			$compareSelections .= $sink_id." "."0"." ";
			if($related_id == ''){
				$bundle_selections_data[0] =
				array(
					array(
							'delete' => '',
							'is_default' => 1,
							'position' => 0,
							'product_id' => $sink_id,
							'selection_can_change_qty' => 0,
							'selection_price_type' => 0,
							'selection_price_value' => 0,
							'selection_qty' => 1,
						)
						);
			}else{
				$bundle_selections_data[0] =
			array(
				array(
						'delete' => '',
						'is_default' => 1,
						'position' => 0,
						'product_id' => $sink_id,
						'selection_can_change_qty' => 0,
						'selection_price_type' => 0,
						'selection_price_value' => 0,
						'selection_qty' => 1,
					),
				array(
						'delete' => '',
						'is_default' => 0,
						'position' => 1,
						'product_id' => $related_id,
						'selection_can_change_qty' => 0,
						'selection_price_type' => 0,
						'selection_price_value' => $related_price_total,
						'selection_qty' => 1,
					),
					);
					$compareSelections .= $related_id." ".$related_price_total." ";
			}
			foreach($combos as $_key => $_value){
				$category_ids 		= array(2,6);
				$Dbclass_id = '';
				$combo_name = $_value['combo_name'];
				$_combo_name = strtolower(trim(explode("-",$_value['combo_name'])[0]));				
				foreach($_value['groups'] as $__key => $__value){				
					foreach($__value['items'] as $___key => $___value){
						$discount = 0;
						$title 			= $___value['title'];
						$finish_name 	= $___value['finish_name'];
						$discount		=  round($___value['discount']);
						$qty			= $___value['quantity'];
						$price_total 	= 0;
						$Dbclass_id = "";
						foreach($___value['products'] as $____key => $____value){
							$Dbclass_id		= $____value['id'];
							$price 				= round($____value['price']);
							$sale_price			= round($____value['sale_price']);
							if($sale_price != "")$price = $sale_price;
							$sku2				= $____value['sku'];
							$price_total += $price;
							$Dbclass_id = 	$Dbclass_id."~".$Dbclass_id;																												
						}
						if($qty == '')$qty = 1;	
						if($qty == 2){
							$price_total = $price*2;
							$Dbclass_id = 	$Dbclass_id."~".$Dbclass_id;
						}
						if($qty == 3){
							$price_total = $price*3;
							$Dbclass_id = 	$Dbclass_id."~".$Dbclass_id."~".$Dbclass_id;
						}
						$Dbclass_id = trim($Dbclass_id, "~");
						if($Dbclass_id === "")$Dbclass_id = $Dbclass_id;
						if($price_total === 0)$price_total = $price;
						if(Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $Dbclass_id)){
							$__product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $Dbclass_id);
							$product_id = $__product->getId();
							if(strpos(strtolower($title),'drain') !== false)$combo_name = 'Drain';
													
							$bundle_selection_array[$combo_name][$title][$____key] = array(	'finish_name'	=>$finish_name,
																							'sku'			=>$sku2,
																							'discount'		=>$discount,
																							'qty'			=>1, 
																							'Dbclass_id'	=>$Dbclass_id,
																							'product_id'	=>$product_id,
																							'price'			=>$price_total
																							);	
																						
							}
					}									
				}
			}
			
			$option_count = 1;
			foreach($bundle_selection_array as $bundle_key => $bundle_value){						
				$bundle_count = 0;
				foreach($bundle_value as $_bundle_key => $_bundle_value){					
					$price = 0;
					$bundle_options_data[$option_count] =  
								array(
									'delete' 		=> '',
									'option_id' 	=> '',
									'position' 		=> $option_count,
									'required' 		=> 0,
									'title' 		=> $bundle_key,
									'type' 			=> 'select',
									);
					foreach($_bundle_value as $__bundle_key => $__bundle_value){
						$qty 			= $__bundle_value['qty'];	
						$_product_id 	= $__bundle_value['product_id'];
						$discount 		= $__bundle_value['discount'];					
						$price 			= $__bundle_value['price'];						
					}					
					$price = $price - $discount;
					$bundle_selections_data[$option_count][$bundle_count]=
											array(
													'delete' 					=> '',
													'is_default' 				=> 0,
													'position' 					=> $bundle_count,
													'product_id' 				=> $_product_id,
													'selection_can_change_qty' 	=> 0,
													'selection_price_type' 		=> 0,
													'selection_price_value' 	=> $price,
													'selection_qty' 			=> $qty,
												);
					$bundle_count += 1;
					$compareSelections .= $_product_id." ".$price." ";
				}
				$option_count += 1;			
			}
			$productAttributes = array(
			'AffectBundleProductSelections' => true,
			'BundleOptionsData' 		=> $bundle_options_data,
			'BundleSelectionsData' 		=> $bundle_selections_data,
			'CanSaveBundleSelections' 	=> true,
			'CanSaveCustomOptions' 		=> true,
			'WebsiteIds' 				=> array(1),
			'StoreId' 					=> Mage_Core_Model_App::ADMIN_STORE_ID
			);
			
			if($this->getCompareBundleArray($compareSelections,$id)){continue;}else{
				$changeProduct .= "<li>".$sku."</li>";
				$attributeArray[] = $productAttributes;
				//Remove current bundle options by Dbclass_product_id for bundles
				$this->removeBundle($id);
				$_product->setUsedInProductListing(true)->setIsMassupdate(true)->setExcludeUrlRewrite(true);			
				$this->setProduct($_product, $productAttributes);
			}						
		}
		if($changeProduct == "")$changeProduct = "<li>Bundles Up to Date</li>";
		Mage::log("Bundles Updated", null ,"update.log");	
		$changeProduct = $this->getUpdateResults().$changeProduct;
		Mage::getSingleton('core/session')->setUpdateResults($changeProduct);
		Mage::getSingleton('core/session')->setUpdateAttributeResults($attributeArray);
		return;
	}
	
	public function setBundlePrices($DbclassId){
		$Dbclass_id = $DbclassId."-b";	
		$rene_products 	= $this->getReneProducts();
		$rene_combos	= $this->getReneCombos();
		$multiplier 	= 1;
		$changeProduct  = $this->getUpdateResults();
		Mage::unregister('product');					
		if(!Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $Dbclass_id))return;
		$_product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $Dbclass_id);	
		Mage::register('product', $_product);				 
		$_id 		= str_replace("-b", "", $Dbclass_id);					 
		$prods 		= $rene_products->Get(array($_id), Products::BASE);
		$price		= 0;
		$sale_price = 0;
		$magePrice = round($_product->getPrice());			
		foreach($prods as $prod_key => $prod){
			$price			= round($prod->GetInformation()['price'])*$multiplier;
			$sale_price		= round($prod->GetInformation()['sale_price'])*$multiplier;
			}
		$_product->setRegularPrice($price);
		$_product->setSalePrice($sale_price);
		if($sale_price != "")$price = $sale_price;
		$base_sku = $_product->getSku();
		if($magePrice != $price){
			$_product->setPrice($price);
			$_product->save();
			$changeProduct .= "<li>".$_product->getSku()."--".$price."</li>";
		}
		$optionCollection = $_product->getTypeInstance()->getOptionsCollection();
		$selectionCollection = $_product->getTypeInstance()->getSelectionsCollection($_product->getTypeInstance()->getOptionsIds());
		$options = $optionCollection->appendSelections($selectionCollection);
		$selection_price_array = array();
		$combos = $rene_combos->GetByProductID($_id);
		foreach($combos as $_key => $_value){
				$Dbclass_id = '';
				$combo_name = $_value['combo_name'];
				$_combo_name = strtolower(trim(explode("-",$_value['combo_name'])[0]));				
				foreach($_value['groups'] as $__key => $__value){				
					foreach($__value['items'] as $___key => $___value){
						$discount = 0;
						$discount		=  round($___value['discount']);
						$qty			= $___value['quantity'];
						$price_total 	= 0;
						$Dbclass_id = "";
						foreach($___value['products'] as $____key => $____value){
							$Dbclass_id		= $____value['id'];
							$price 				= round($____value['price']);
							$sale_price			= round($____value['sale_price']);
							if($sale_price != "")$price = $sale_price;
							$price_total += $price;
							$Dbclass_id = 	$Dbclass_id."~".$Dbclass_id;																												
						}
						if($qty == '')$qty = 1;	
						if($qty == 2){
							$price_total = $price*2;
							$Dbclass_id = 	$Dbclass_id."~".$Dbclass_id;
						}
						if($qty == 3){
							$price_total = $price*3;
							$Dbclass_id = 	$Dbclass_id."~".$Dbclass_id."~".$Dbclass_id;
						}
						$Dbclass_id = trim($Dbclass_id, "~");
						if($Dbclass_id === "")$Dbclass_id = $Dbclass_id;
						if($price_total === 0)$price_total = $price;											
						$bundle_selection_array[$Dbclass_id] = $price_total-$discount; 																						
					}									
				}
			}	
		foreach( $options as $option )
		{
			$_selections = $option->getSelections();
			$default_title = $option->getDefaultTitle();
			$less_amount = 0;
			foreach( $_selections as $selection)
			   {
			    $price		= 0;
				$sale_price = 0;
				$product_id = $selection->getData()['product_id'];
				$selectionId = $selection->getSelectionId();
				$selectionSku = $selection->getSku();
				$selection_product = Mage::getModel('catalog/product')->load($product_id);
				$__id = $selection_product->getDbclassProductId();			
 				$selection_price = array("selection_id" =>$selectionId, "price" => $bundle_selection_array[$__id], "sku"=>$selectionSku, "order" => 2);
				if($default_title === "Sink" || $default_title === "Faucet"){
					$_prods = $rene_products->Get(array($__id), Products::BASE);
					foreach($_prods as $prod_key => $_prod){
						if(!isset($_prod))continue;
						$price			= round($_prod->GetInformation()['price'])*$multiplier;
						$sale_price		= round($_prod->GetInformation()['sale_price'])*$multiplier;
					}
					if($sale_price != "")$price = $sale_price;
					if(count($_selections) > 1){
						if($less_amount == 0){
							$less_amount = $price;
							$price = 0;
						}else{
							$price = $price - $less_amount;
						}
					}else{
						$price = 0;
					}
					$selection_price = array("selection_id" =>$selectionId, "price" => $price, "sku"=>$selectionSku, "order" => 3);
				}
				$selection_price_array[] = $selection_price;			
			 }
		}
		foreach($selection_price_array as $array){
				if(!Mage::getModel('bundle/selection')->load($array['selection_id']))continue;	
				$setPrice = Mage::getModel('bundle/selection')->load($array['selection_id']);
				$selectionCurrentPrice = $setPrice->getSelectionPriceValue();
				if($selectionCurrentPrice != $array['price']){
					$setPrice->setSelectionPriceValue($array['price']);
					$setPrice->save();
					$changeProduct .= "<li>".$_product->getSku()."::".$array['sku']."--".$array['price']."</li>";
				}			
		}
		Mage::unregister('product');	
		if(!$changeProduct)$changeProduct = "<li>Bundle pricing is up to date</li>";
		Mage::getSingleton('core/session')->setUpdateResults($changeProduct);
	}
	
	public function getUpdateResults(){
		return Mage::getSingleton('core/session')->getUpdateResults();
	}
	
	public function setBundlesWithNoOptions($product_ids){
		$id_array = array();
		foreach($product_ids as $id){
			$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id."-b");
			if(!is_object($product))continue;
			$options = Mage::getModel('bundle/option')->getResourceCollection()
			->setProductIdFilter($product->getId())
			->setPositionOrder()->getItems();
			if(count($options)<1)$id_array[] = str_replace("-b", "", $product->getDbclassProductId());
		}
		$this->setBundles($id_array);
	}
	
	public function getStockLevelVisibility(){
		$products = $this->getReneProducts()->GetByType(array(1,2));
		$visibiityArray = array();		
		foreach( $products as $product ) {
			if( isset($product) ) {
				foreach($product as $_product){
					$visibility = 4;
					if($_product->getStock()['master_stock_id'] > 7)$visibility = 1;
					$visibiityArray[$_product->getId()] = $visibility;
				}
			}
		}
		return $visibiityArray;
	}
	
	public function getIsInStock(){
		$products = $this->getReneProducts()->GetByType(array(1,2,3));
		$inStockArray = array();		
		foreach( $products as $product ) {
			if( isset($product) ) {
				foreach($product as $_product){
					$isInStock = 1;
					if($_product->getStock()['stock_id'] > 2)$isInStock = 0;
					$inStockArray[$_product->getId()] = $isInStock;
				}
			}
		}
		return $inStockArray;
	}
	
	public function setIsInStock(){
		$products = $this->getReneProducts()->GetByType(array(1,2,3));
		$product_ids = array();		
		foreach( $products as $product ) {
			if( isset($product) ) {
				foreach($product as $_product){
					array_push($product_ids, $_product->getId());
				}
			}
		}
		$inStockArray = $this->getIsInStock();
		$return = "";
		$returnArray2 = array();
		foreach($product_ids as $id){
			$is_in_stock = $inStockArray[$id];
			$mageIsInStock = 1;
			$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id);
			if(!is_object($product))continue;
			$sku = $product->getSku();
			$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
			if(!$stockItem->getIsInStock()) $mageIsInStock = 0;
			if($mageIsInStock === $is_in_stock)continue;
			$stockItem->setData('is_in_stock', $is_in_stock);
			try{
				$stockItem->save(); 
				$stock = "In Stock";
				if($is_in_stock === 0)$stock = "Not In Stock";
				$return .= "<li>".$sku." ".$stock."</li>";
			}
			catch( Exception $e ){
				$returnArray2[] = $e;
			}		 
		}
		if($return == "")$return = "<li>Stock Availability Up To Date</li>";
		$changeProduct = $this->getUpdateResults().$return;
		Mage::log("Update Stock: ".trim(str_replace("</li>", " -- ",str_replace("<li>", "", $return)), " --"), null ,"update.log");
		Mage::getSingleton('core/session')->setUpdateResults($changeProduct);
		Mage::getSingleton('core/session')->setUpdateAttributeResults($returnArray2);
	}
	
	public function setComboProductStock(){
		$Dbclass_ids = $this->getMageDbclassProductIds("accessories");
		$product_ids = array();
		 foreach ($Dbclass_ids as $_id) {
				if (strpos($_id, '~') === false)continue;
		 		array_push($product_ids, $_id);
			}		 
		 $inStockArray = $this->getIsInStock();
		 foreach($product_ids as $ids){
		 	$id_array = explode("~", $ids);
			$is_in_stock = 0;
			$array_count = count($id_array);
			 foreach($id_array as $id){
			 	$is_in_stock = $inStockArray[$id];
				$in_stock_count += $is_in_stock;
			 }
			if($in_stock_count === $array_count) $is_in_stock = 1;
			$mageIsInStock = 1;
			$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $ids);
			if(!is_object($product))continue;
			$sku = $product->getSku();
			$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
			if(!$stockItem->getIsInStock()) $mageIsInStock = 0;
			if($mageIsInStock === $is_in_stock)continue;
			$stockItem->setData('is_in_stock', $is_in_stock);
			try{
				$stockItem->save(); 
				$stock = "In Stock";
				if($is_in_stock === 0)$stock = "Not In Stock";
				$return .= "<li>".$sku." ".$stock."</li>";
			}
			catch( Exception $e ){
				$returnArray2[] = $e;
			}
		 }
		if($return == "")$return = "<li>Stock Availability Up To Date</li>";
		$changeProduct = $this->getUpdateResults().$return;
		Mage::log("Update Multi Item Stock: ".trim(str_replace("</li>", " -- ",str_replace("<li>", "", $return)), " --"), null ,"update.log");
		Mage::getSingleton('core/session')->setUpdateResults($changeProduct);
		Mage::getSingleton('core/session')->setUpdateAttributeResults($returnArray2);	
		return $product_ids;	
	}
	
	// set visibility based on Dbclass stock availability skips unchanged items 
	public function setVisibility($product_ids){
		$visibilityArray = $this->getStockLevelVisibility();
		$return = "";
		$returnArray2 = array();
		foreach($product_ids as $id){
			$visibility = $visibilityArray[$id];
			$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id."-b");
			if(!is_object($product))continue;
			$sku = $product->getSku();
			if($product->getVisibility() == $visibility) continue;
			$product
			->setUsedInProductListing(true)
			->setIsVisibleOnFront(true)
			->setWebsiteIds(array(1))
			->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
			->setIsMassupdate(true)
			->setExcludeUrlRewrite(true)
			->setVisibility($visibility);
			Mage::unregister('product');
			Mage::register('product', $product);
			try{
				$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
				$stockItem->setStockData(
	  			 array( 
	          			'is_in_stock' 				=> 1,
						'manage_stock' 				=> 1,
						'qty' 						=> 128,
						'use_config_manage_stock' 	=> 0,
						'stock_id' 					=> 1
	   				)
	 			); 
				$stockItem->save(); 
				$product->save();
				$is_visible = "Visible";
				if($visibility === 1)$is_visible = "Not Visible";
				$return .= "<li>".$sku." ".$is_visible."</li>";
			}
			catch( Exception $e ){
				$returnArray2[] = $e;
			}
			Mage::unregister('product');
		}
		if($return == "")$return = "<li>Visibility Up To Date</li>";
		$return = $this->getUpdateResults().$return;
		Mage::getSingleton('core/session')->setUpdateResults($return);
		Mage::getSingleton('core/session')->setUpdateAttributeResults($returnArray2);
	}

// visibility all resets all product ids passed this was needed for new product bulk import stocking issue.
	public function setVisibilityAll($product_ids){
		$visibilityArray = $this->getStockLevelVisibility();
		$return = "";
		$returnArray2 = array();
		foreach($product_ids as $id){
			$visibility = $visibilityArray[$id];
			$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id."-b");
			if(!is_object($product))continue;
			$sku = $product->getSku();
			$product
			->setUsedInProductListing(true)
			->setIsVisibleOnFront(true)
			->setWebsiteIds(array(1))
			->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
			->setIsMassupdate(true)
			->setExcludeUrlRewrite(true)
			->setVisibility($visibility);
			Mage::unregister('product');
			Mage::register('product', $product);
			try{
				$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
				$stockItem->setStockData(
	  			 array( 
	          			'is_in_stock' 				=> 1,
						'manage_stock' 				=> 1,
						'qty' 						=> 128,
						'use_config_manage_stock' 	=> 0,
						'stock_id' 					=> 1
	   				)
	 			); 
				$stockItem->save(); 
				$product->save();
				$is_visible = "Visible";
				if($visibility === 1)$is_visible = "Not Visible";
				$return .= "<li>".$sku." ".$is_visible."</li>";
			}
			catch( Exception $e ){
				$returnArray2[] = $e;
			}
			Mage::unregister('product');
		}
		if($return == "")$return = "<li>Visibility Up To Date</li>";
		Mage::log("Visibility Updated", null, "update.log");	
		$return = $this->getUpdateResults().$return;
		Mage::getSingleton('core/session')->setUpdateResults($return);
	}
	

	public function setCategoriesByDbclassIds($product_ids){
		$skus = "";		
		foreach($product_ids as $id){
			$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id."-b");
			if(!is_object($product))continue;
			$comp1 = "";
			$comp2 = "";			
			foreach($product->getCategoryIds() as $value)$comp1 .= $value;
			foreach($this->getCategoriesByDbclassProductId($id) as $value)$comp2 .= $value;	
			if($comp1 == $comp2)continue;
			$skus .= "<li>".$product->getSku()."::Category Update</li>";	
			$product->setWebsiteIds(array(1))->setCategoryIds($this->getCategoriesByDbclassProductId($id));
			Mage::unregister('product');
			Mage::register('product', $product);
			$product->save();
			Mage::unregister('product');
		}
		if($skus == "")$skus = "Categories in Sync";
		Mage::getSingleton('core/session')->setUpdateResults($skus);
	}

	// generic set product parses thru attribute array and sets product in magento database
	private function setProduct($product, $productAttributes = array())
	{		
		try {
				foreach($productAttributes as $methodName => $methodArgument) {
							$realMethodName = 'set'.$methodName;
							$product->$realMethodName($methodArgument);
						}
					Mage::unregister('product');
					Mage::register('product', $product);
					$product->save();
					Mage::unregister('product');
					$results = "Success";
				} 
		catch (Exception $e) {
					$results = "Fail ".$e->getMessage();
				}
		return $results;			
	}
	
	// compares current attribute values in MAGE and Dbclass
	public function getMageProductDataCompare($DbclassID, $attributesDbclass){
		if(Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $DbclassID)){
		$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $DbclassID);
		$attributesMAGE = array(
								'Description' 		=> $product->getDescription(),
								'Name' 				=> $product->getName(),
								'ShortDescription' 	=> $product->getShortDescription(),
								'Sku' 				=> $product->getSku(),
								'Finish' 			=> $product->getFinish(),
								'FinishName' 		=> $product->getFinishName(),
								'Price' 			=> $product->getPrice(),
								'Weight' 			=> $product->getWeight()
								);
		if(md5 (serialize($attributesDbclass)) ===	md5(serialize($attributesMAGE))) return true;
		}
		return false;	
	}
	
	//compares current Bundle values in MAGE and Dbclass
	public function getCompareBundleArray($compareSelections,$id){
		$compareMAGESelections = '';
		$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id."-b");
		$options = Mage::getModel('bundle/option')->getResourceCollection()
		->setProductIdFilter($product->getId())
		->setPositionOrder();
		$selections = $product->getTypeInstance(true)
		->getSelectionsCollection($product->getTypeInstance(true)
		->getOptionsIds($product), $product);
		foreach ($options->getItems() as $option) {
		$option_id = $option->getId();
			foreach($selections as $selection){
				if($option_id == $selection->getOptionId()){
				$compareMAGESelections .= $selection->getId()." ".round($selection->getSelectionPriceValue())." ";
				}
			}
		}
		if($compareSelections == $compareMAGESelections){return true;}else{return false;}
	}
	
	//remove bundle by id
	public function removeBundle($id){
		$product = Mage::getModel('catalog/product')->loadByAttribute('Dbclass_product_id', $id."-b");
		$options = Mage::getModel('bundle/option')->getResourceCollection()
		->setProductIdFilter($product->getId())
		->setPositionOrder();
		foreach ($options->getItems() as $option) {
					$optionModel = Mage::getModel('bundle/option');
		          	$optionModel->setId($option->getId());
		            $optionModel->delete();	
		}
	}
	
	private function getAccessorieAttributeSetId($key){
		if (strpos($key,'drain') !== false) $key = 'drain';
		switch($key){
			case "sink":
				return 9;
				break;
			case "faucet":
				return 10;
				break;
			case "drain":
				return 11;
				break;
			case "sink_ring":
				return 12;
				break;
			case "faucet_waterfall":
				return 13;
				break;
			case "grid":
				return 14;
				break;
			case "cutting_board":
				return 15;
				break;
			case "strainer":
				return 16;
				break;
			case "sink_basket":
				return 17;
				break;
			case "strainer_ninety":
				return 18;
				break;
			case "soap_dispenser":
				return 19;
				break;
			case "base_plate":
				return 20;
				break;
			case "side_spray":
				return 21;
				break;
			default:
				return 22;
		}
	}
	
	public function getOutOfStock(){
		$outOfStockItems = Mage::getModel('cataloginventory/stock_item')
        ->getCollection()
        ->addFieldToFilter('is_in_stock', 0);
		return $outOfStockItems;
	}
	
}
?>