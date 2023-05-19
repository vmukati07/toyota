<?php

/**
 * @package     Infosys/PaymentWebsiteAssociation
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\PaymentWebsiteAssociation\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Paypal\Model\Payflow\Service\Request\SecureToken;
use Magento\Framework\Exception\LocalizedException;
use Magento\PaypalGraphQl\Model\Resolver\Store\Url;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Resolver for generating PayflowProToken
 */
class PayflowProToken implements ResolverInterface
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var SecureToken
     */
    private $secureTokenService;

    /**
     * @var Url
     */
    private $urlService;

    /**
     * @param GetCartForUser $getCartForUser
     * @param SecureToken $secureTokenService
     * @param Url $urlService
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        SecureToken $secureTokenService,
        Url $urlService
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->secureTokenService = $secureTokenService;
        $this->urlService = $urlService;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $cartId = $args['input']['cart_id'] ?? '';
        $urls = $args['input']['urls'] ?? null;

        $customerId = $context->getUserId();

        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();

        $storeId = (int)$store->getId();

        $cart = $this->getCartForUser->execute($cartId, $customerId, $storeId);

        try {
            $tokenDataObject = $this->secureTokenService->requestToken($cart, $urls);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        return [
            'result' => $tokenDataObject->getData("result"),
            'secure_token' => $tokenDataObject->getData("securetoken"),
            'secure_token_id' => $tokenDataObject->getData("securetokenid"),
            'response_message' => $tokenDataObject->getData("respmsg"),
            'result_code' => $tokenDataObject->getData("result_code")
        ];
    }
}
