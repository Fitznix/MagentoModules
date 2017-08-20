<?php
/**
 * AnonymousAlert data helper
 *
 * @category   Mage
 * @package   YourSite_AnonymousAlert
 * @author    Harl
 */
class YourSite_AnonymousAlert_Helper_Data extends Mage_Core_Helper_Url
{
    /**
     * Current product instance (override registry one)
     *
     * @var null|Mage_Catalog_Model_Product
     */
    protected $_product = null;

    /**
     * Get current product instance
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!is_null($this->_product)) {
            return $this->_product;
        }
        return Mage::registry('product');
    }

    /**
     * Set current product instance
     *
     * @param Mage_Catalog_Model_Product $product
     * @return YourSite_AnonymousAlert_Helper_Data
     */
    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }

    public function getStore()
    {
        return Mage::app()->getStore();
    }

    public function getSaveUrl()
    {
        return $this->_getUrl('anonymousalert/add/stock', array(
            'product_id'    => $this->getProduct()->getId(),
            Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => $this->getEncodedUrl()
        ));
    }

    public function createBlock($block)
    {
        $error = Mage::helper('core')->__('Invalid block type: %s', $block);
        if (is_string($block)) {
            if (strpos($block, '/') !== false) {
                if (!$block = Mage::getConfig()->getBlockClassName($block)) {
                    Mage::throwException($error);
                }
            }
            $fileName = mageFindClassFile($block);
            if ($fileName!==false) {
                include_once ($fileName);
                $block = new $block(array());
            }
        }
        if (!$block instanceof Mage_Core_Block_Abstract) {
            Mage::throwException($error);
        }
        return $block;
    }

}

?>