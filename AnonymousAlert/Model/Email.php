<?php

/**
 * AnonymousAlert Email processor
 *
 * @category   Mage
 * @package    YourSite_AnonymousAlert
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class YourSite_AnonymousAlert_Model_Email extends Mage_Core_Model_Abstract
{
    const XML_PATH_EMAIL_STOCK_TEMPLATE = 'catalog/anonymousalert/email_stock_template';
    const XML_PATH_EMAIL_IDENTITY       = 'catalog/anonymousalert/email_identity';

    /**
     * Type
     *
     * @var string
     */
    protected $_type = 'stock';

    /**
     * Website Model
     *
     * @var Mage_Core_Model_Website
     */
    protected $_website;

    /**
     * Product collection which of back in stock
     *
     * @var array
     */
    protected $_stockProducts = array();

    /**
     * Stock block
     *
     * @var YourSite_AnonymousAlert_Block_Email_Stock
     */
    protected $_stockBlock;
	
	/**
     * Alert Email
     *
     * @var YourSite_AnonymousAlert_Block_Email_Stock
     */
    protected $_alertEmail = null;
	
	/**
     * Set model type
     *
     * @param string $type
     */
    public function setAlertEmail($alertEmail)
    {
        $this->_alertEmail = $alertEmail;
    }

	/**
     * Retrieve alert email
     *
     * @return string
     */
    public function getAlertEmail()
    {
        return $this->_alertEmail;
    }

    /**
     * Set model type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->_type = $type;
    }

    /**
     * Retrieve model type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Set website model
     *
     * @param Mage_Core_Model_Website $website
     * @return YourSite_AnonymousAlert_Model_Email
     */
    public function setWebsite(Mage_Core_Model_Website $website)
    {
        $this->_website = $website;
        return $this;
    }

    /**
     * Set website id
     *
     * @param int $websiteId
     * @return YourSite_AnonymousAlert_Model_Email
     */
    public function setWebsiteId($websiteId)
    {
        $this->_website = Mage::app()->getWebsite($websiteId);
        return $this;
    }

    /**
     * Clean data
     *
     * @return YourSite_AnonymousAlert_Model_Email
     */
    public function clean()
    {        	
		$this->_alertEmail	= null;
        $this->_stockProducts = array();
        return $this;
    }

    /**
     * Add product (back in stock) to collection
     *
     * @param Mage_Catalog_Model_Product $product
     * @return YourSite_AnonymousAlert_Model_Email
     */
    public function addStockProduct(Mage_Catalog_Model_Product $product)
    {
        $this->_stockProducts[$product->getId()] = $product;
        return $this;
    }

    /**
     * Retrieve stock block
     *
     * @return YourSite_AnonymousAlert_Block_Email_Stock
     */
    protected function _getStockBlock()
    {
       if (is_null($this->_stockBlock)) {
            $this->_stockBlock = Mage::helper('anonymousalert')
                ->createBlock('anonymousalert/email_stock');
		}
        return $this->_stockBlock;
    }
	
    /**
     * Send customer email
     *
     * @return bool
     */
    public function send()
    {

        if (is_null($this->_website) || is_null($this->getAlertEmail())) {
            return false;
        }

        if ($this->_type == 'stock' && count($this->_stockProducts) == 0) {
            return false;
        }

        if (!$this->_website->getDefaultGroup() || !$this->_website->getDefaultGroup()->getDefaultStore()) {
            return false;
        }

        $store      = $this->_website->getDefaultStore();
        $storeId    = $store->getId();
        if ($this->_type == 'stock' && !Mage::getStoreConfig(self::XML_PATH_EMAIL_STOCK_TEMPLATE, $storeId)) {
           return false;
        }

        if ($this->_type != 'stock') {
           return false;
        }

        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
            $this->_getStockBlock()
                ->setStore($store)
                ->reset();
            foreach ($this->_stockProducts as $product) {           	              
                $this->_getStockBlock()->addProduct($product);
            }
            $block = $this->_getStockBlock()->toHtml();		
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_STOCK_TEMPLATE, $storeId);
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        Mage::getModel('core/email_template')
            ->setDesignConfig(array(
                'area'  => 'frontend',
                'store' => $storeId
            ))->sendTransactional(
                $templateId,
                Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId),
                $this->getAlertEmail(),
                $this->getAlertEmail(),
                array(
                    'customerName'  => $this->getAlertEmail(),
                    'alertGrid'     => $block
                )
            ); 
        return true;
    }
}
