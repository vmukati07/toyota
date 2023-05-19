<?php
/**
 * @package     Infosys/AemBase
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AemBase\Model;

/**
 * Responsible for providing a method to trim '.html' from AEM paths
 */
class HtmlExtensionRemover
{
	/**
	 * Returns the provided URL, with '.html' dropped, if it is present
	 *
	 * @param $url
	 * @return false|string
	 */
	public function execute($url)
	{
		$extensionIndex = strpos($url, '.html');

		if ($extensionIndex === false) {
			return $url;
		}

		return substr($url,0, $extensionIndex);
	}
}
