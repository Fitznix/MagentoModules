<?php
/**
 * AnonymousAlert observer
 *
 * @category   YourSite
 * @package    YourSite_AnonymousAlert
 * @author     Harl
 */
class YourSite_AnonymousAlert_Model_Observer
{
    /**
     * Error email template configuration
     */
    const XML_PATH_ERROR_TEMPLATE   = 'catalog/anonymousalert_cron/error_email_template';

    /**
     * Error email identity configuration
     */
    const XML_PATH_ERROR_IDENTITY   = 'catalog/anonymousalert_cron/error_email_identity';

    /**
     * 'Send error emails to' configuration
     */
    const XML_PATH_ERROR_RECIPIENT  = 'catalog/anonymousalert_cron/error_email';

    /**
     * Website collection array
     *
     * @var array
     */
    protected $_websites;

    /**
     * Warning (exception) errors array
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Retrieve website collection array
     *
     * @return array
     */
    protected function _getWebsites()
    {
        if (is_null($this->_websites)) {
            try {
                $this->_websites = Mage::app()->getWebsites();
            }
            catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
            }
        }
        return $this->_websites;
    }


    /**
     * Process stock emails
     *
     * @param Mage_ProductAlert_Model_Email $email
     * @return Mage_ProductAlert_Model_Observer
     */
    protected function _processStock(YourSite_AnonymousAlert_Model_Email $email)
    {
       $email->setType('stock');

        foreach ($this->_getWebsites() as $website) {
            /* @var $website Mage_Core_Model_Website */

           if (!$website->getDefaultGroup() || !$website->getDefaultGroup()->getDefaultStore()) {
                continue;
            }
            try {
                $collection = Mage::getModel('anonymousalert/stock')
                    ->getCollection()
					->addWebsiteFilter($website->getId())
                    ->addStatusFilter(0)
					->setOrder('alert_email', 'asc');
            }
            catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
				Mage::logException($e);
                return $this;
            }
			
			$alertEmail = null;
            $email->setWebsite($website);
            foreach ($collection as $alert) {
                try {
                	if (!$alertEmail || $alertEmail != $alert->getAlertEmail()) {
                        if ($alertEmail) {
                            $email->send();
                        }
                        $alertEmail = $alert->getAlertEmail();
                        $email->clean();
                        $email->setAlertEmail($alertEmail);
                    }
                    else {
                        $alertEmail = $alertEmail;
                    }
					
                    $product = Mage::getModel('catalog/product')
                        ->setStoreId($website->getDefaultStore()->getId())
                        ->load($alert->getProductId());
                    /* @var $product Mage_Catalog_Model_Product */
                  if (!$product) {
                        continue;
                    }

                    if ($product->isSalable()) {
                        $email->addStockProduct($product);

                        $alert->setSendDate(Mage::getModel('core/date')->gmtDate());
                        $alert->setSendCount($alert->getSendCount() + 1);
                        $alert->setStatus(1);
                        $alert->save();
                    }
                }
                catch (Exception $e) {
                    $this->_errors[] = $e->getMessage();
					Mage::logException($e);
                }
            }
			if ($alertEmail) {
                try {
                   $email->send();
                }
                catch (Exception $e) {
                    $this->_errors[] = $e->getMessage();
					Mage::logException($e);
                }
			} 
        }
        return $this;
    }

    /**
     * Send email to administrator if error
     *
     * @return Mage_ProductAlert_Model_Observer
     */
   protected function _sendErrorEmail()
    {
        if (count($this->_errors)) {
            if (!Mage::getStoreConfig(self::XML_PATH_ERROR_TEMPLATE)) {
                return $this;
            }

            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);

            $emailTemplate = Mage::getModel('core/email_template');
            /* @var $emailTemplate Mage_Core_Model_Email_Template */
            $emailTemplate->setDesignConfig(array('area'  => 'backend'))
                ->sendTransactional(
                    Mage::getStoreConfig(self::XML_PATH_ERROR_TEMPLATE),
                    Mage::getStoreConfig(self::XML_PATH_ERROR_IDENTITY),
                    Mage::getStoreConfig(self::XML_PATH_ERROR_RECIPIENT),
                    null,
                    array('warnings' => join("\n", $this->_errors))
                );

            $translate->setTranslateInline(true);
            $this->_errors[] = array();
        }
        return $this;
    }

    /**
     * Run process send product alerts
     *
     * @return Mage_ProductAlert_Model_Observer
     */
   public function process()
    {
        $email = Mage::getModel('anonymousalert/email');
        /* @var $email Mage_ProductAlert_Model_Email */
        $this->_processStock($email);
        $this->_sendErrorEmail();

        return $this;
    }
   
}
