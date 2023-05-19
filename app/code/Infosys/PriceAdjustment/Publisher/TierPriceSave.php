<?php
/**
 * @package     Infosys/PriceAdjustment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */
declare(strict_types=1);

namespace Infosys\PriceAdjustment\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Responsible for providing a single point of entry for publish to topic
 */
class TierPriceSave
{
	public const TOPIC_NAME = "magento.tier-price.save";

	private PublisherInterface $publisher;

	/**
	 * @param PublisherInterface $publisher
	 */
	public function __construct(
		PublisherInterface $publisher
	) {
		$this->publisher = $publisher;
	}

	/**
	 * Publish the message to the topic
	 *
	 * @param $data
	 */
	public function publish($data): void
	{
		$this->publisher->publish(self::TOPIC_NAME, $data);
	}
}
