<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for DeliveryFeeCharges
 */
class DeliveryFeeCharge implements ResolverInterface
{
	public const DELIVERY_FEE = 'delivery_fee';
	public const DELIVERY_FEE_STATE = 'delivery_fee_state';

	/**
	 * Return the label and value
	 *
	 * @param Field $field
	 * @param ContextInterface $context
	 * @param ResolveInfo $info
	 * @param array|null $value
	 * @param array|null $args
	 * @return Value|mixed|void
	 */
	public function resolve(
		Field $field,
		$context,
		ResolveInfo $info,
		array $value = null,
		array $args = null
	) {
		$quote = $value['model'];

		if ($quote) {
			return [
				[
					"label" => $quote->getData(self::DELIVERY_FEE_STATE) ?
						__("%1 Delivery Fee", $quote->getData(self::DELIVERY_FEE_STATE)) :
						__("Delivery Fee"),
					"amount" => [
						"currency" => 'USD',
						"value" => $quote->getData(self::DELIVERY_FEE)
					]
				]
			];
		}
	}
}
