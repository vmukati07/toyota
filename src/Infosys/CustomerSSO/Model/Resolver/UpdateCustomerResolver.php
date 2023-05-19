<?php
/**
 * @package Infosys/CustomerSSO
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */
declare(strict_types=1);

namespace Infosys\CustomerSSO\Model\Resolver;
 
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Infosys\CustomerSSO\Model\DCS;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Infosys\CustomerSSO\Logger\DCSLogger;

/**
 * Update Customer GraphQL request processing
 */
class UpdateCustomerResolver implements ResolverInterface
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $json;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var DCS
     */
    protected $dcs;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var DSCLogger
     */
    protected DCSLogger $dcsLogger;

    /**
     * Constuctor function
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param DCS $dcs
     * @param GetCustomer $getCustomer
     * @param ScopeConfigInterface $scopeConfig
     * @param DCSLogger $dcsLogger
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Psr\Log\LoggerInterface $logger,
        DCS $dcs,
        GetCustomer $getCustomer,
        ScopeConfigInterface $scopeConfig,
        DCSLogger $dcsLogger
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->customerRepository = $customerRepository;
        $this->_logger = $logger;
        $this->dcs = $dcs;
        $this->getCustomer = $getCustomer;
        $this->scopeConfig = $scopeConfig;
        $this->dcsLogger = $dcsLogger;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }
        if (empty($args['input']['email'])) {
            throw new GraphQlInputException(__('"email" should be specified'));
        }

        if (empty($args['input']['password'])) {
            throw new GraphQlInputException(__('"password" should be specified'));
        }

        $customer = $this->getCustomer->execute($context);
        $email = $customer->getEmail();
        $new_email = $args['input']['email'];
        $password = $args['input']['password'];
        $customerData = ['new_email' => $new_email, 'password' => $password, 'email' => $email];

        //update customer api call
        try {
            $result = $this->dcs->updateCustomerEmail($customerData);
            if (isset($result['message'])) {
                $output = ['message' => $result['message']];
            } else if(isset($result['status']['messages'][0]['description'])){
                $output = ['message' => $result['status']['messages'][0]['description']];
            } else {
                $output = ['message' =>'Something went wrong, please try again.'];
            }
        } catch (\Exception $e) {
            $output = ['message' =>$e->getMessage()];
            $this->dcsLogger->error('Update Customer Email Resolver Error: ' . $e);
        }

        return $output;
    }
    
    /**
     * Get store config value
     *
     * @param string $path
     * @param int $storeId
     * @return void
     */
    protected function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
