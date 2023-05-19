<?php
/**
 * @package     Infosys/AemBase
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AemBase\Plugin\Magento\Sitemap\Model\ResourceModel\Catalog;

use Infosys\AemBase\Model\AemBaseConfigProvider;
use Infosys\AemBase\Model\HtmlExtensionRemover;
use Magento\Framework\DataObject;

/**
 * Plugin responsible for altering the sitemap URLs for AEM for Products
 */
class Product
{
    /** @var AemBaseConfigProvider */
	protected AemBaseConfigProvider $configProvider;

    /** @var HtmlExtensionRemover */
	private HtmlExtensionRemover $htmlExtensionRemover;

    /** @var int */
    private int $storeId;

	/**
	 * @param AemBaseConfigProvider $configProvider
	 * @param HtmlExtensionRemover $htmlExtensionRemover
	 */
    public function __construct(
        AemBaseConfigProvider $configProvider,
		HtmlExtensionRemover $htmlExtensionRemover
    ) {
        $this->configProvider = $configProvider;
        $this->htmlExtensionRemover = $htmlExtensionRemover;
    }

	/**
	 * Cache the $storeId getCollection was called with for use in afterGetCollection
	 *
	 * @param \Infosys\AemBase\Rewrite\Magento\Sitemap\Model\ResourceModel\Catalog\Product $subject
	 * @param $storeId
	 * @return array
	 */
    public function beforeGetCollection(
	    \Infosys\AemBase\Rewrite\Magento\Sitemap\Model\ResourceModel\Catalog\Product $subject,
		$storeId
    ) {
    	$this->storeId = (int) $storeId;

    	return [$storeId];
    }

	/**
	 * Replace the URLs in items in the collection with the AEM path for Products
	 *
	 * @param \Infosys\AemBase\Rewrite\Magento\Sitemap\Model\ResourceModel\Catalog\Product $subject
	 * @param $result
	 * @return mixed
	 */
    public function afterGetCollection(
	    \Infosys\AemBase\Rewrite\Magento\Sitemap\Model\ResourceModel\Catalog\Product $subject,
        $result
    ) {
    	$aemProductPath = $this->configProvider->getAemProductPath($this->storeId);

        foreach ($result as $product) {
            /** @var DataObject $product */
            $productPath = $aemProductPath . $this->htmlExtensionRemover->execute($product->getData('url_key'));
            $product->setData('url', $productPath);
        }

        return $result;
    }
}
