<?php
/**
 * @package     Infosys/PaymentConfigGraphQL
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */
declare(strict_types=1);

namespace Infosys\PaymentConfigGraphQL\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Resolver class for dealer payment configuration
 */
class StorePaymentConfigV2 implements ResolverInterface
{
    protected ScopeConfigInterface $scopeConfig;

    /**
     * Initialize dependencies
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $paymentData = [];

        $stripe_mode = $this->scopeConfig->getValue(
            'payment/stripe_payments_basic/stripe_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    
        if ($stripe_mode == 'test') {
            $paymentData = [
                'pub_api_key' => $this->scopeConfig->getValue(
                    'payment/stripe_payments_basic/stripe_test_pk',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                )
            ];
        } else {
            $paymentData = [
                'pub_api_key' => $this->scopeConfig->getValue(
                    'payment/stripe_payments_basic/stripe_live_pk',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                )
            ];
        }

        return $paymentData;
    }
}
