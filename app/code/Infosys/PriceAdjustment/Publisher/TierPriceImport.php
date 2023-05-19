<?php

/**
 * @package     Infosys/PriceAdjustment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\PriceAdjustment\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;

/**
 *  Provide a single point of entry to publish messages to the topic
 */
class TierPriceImport
{
    public const TOPIC_NAME = 'magento.tier-price.import';

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
     * Publish the provided data as a message to the topic
     *
     * @param $data
     */
    public function publish($data): void
    {
        $this->publisher->publish(self::TOPIC_NAME, $data);
    }
}
