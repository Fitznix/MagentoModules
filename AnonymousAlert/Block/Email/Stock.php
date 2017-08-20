<?php


/**
 * AnonymousAlert email back in stock grid
 *
 * @category   YourSite
 * @package    YourSite_AnonymousAlert
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class YourSite_AnonymousAlert_Block_Email_Stock extends YourSite_AnonymousAlert_Block_Email_Abstract
{
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->setTemplate('email/anonymousalert/stock.phtml');
    }

}
