<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Infosys\CustomerSSO\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\CustomerGraphQl\Model\Customer\UpdateCustomerAccount;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Infosys\CustomerSSO\Model\DCS;
use Infosys\CustomerSSO\Logger\DCSLogger;

/**
 * Update customer data resolver
 */
class UpdateCustomer implements ResolverInterface
{
    /**
     * DCS API Response Success
     */
    const API_SUCCESS = 'REQUEST_COMPLETED_SUCCESSFULLY';

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var UpdateCustomerAccount
     */
    private $updateCustomerAccount;

    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @var DCS
     */
    protected $dcs;

    /**
     * @var DSCLogger
    */
    protected DCSLogger $dcsLogger;

    /**
     * @param GetCustomer $getCustomer
     * @param UpdateCustomerAccount $updateCustomerAccount
     * @param ExtractCustomerData $extractCustomerData
     * @param DCS $dcs
     * @param DCSLogger $dcsLogger
     */
    public function __construct(
        GetCustomer $getCustomer,
        UpdateCustomerAccount $updateCustomerAccount,
        ExtractCustomerData $extractCustomerData,
        DCS $dcs,
        DCSLogger $dcsLogger
    ) {
        $this->getCustomer = $getCustomer;
        $this->updateCustomerAccount = $updateCustomerAccount;
        $this->extractCustomerData = $extractCustomerData;
        $this->dcs = $dcs;
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
        if (isset($args['input']['date_of_birth'])) {
            $args['input']['dob'] = $args['input']['date_of_birth'];
        }
        if (empty($args['input']['customer_password'])) {
            throw new GraphQlInputException(__('"password" should be specified'));
        }

        $customer = $this->getCustomer->execute($context);
        $lastName = $telephoneNumber = $firstName = '';
       
        if (isset($args['input']['customer_password'])) {
            $password = $args['input']['customer_password'];
            $email = $customer->getEmail();
          
            if (isset($args['input']['firstname'])) {
                $firstName =  $args['input']['firstname'];
            }
            if (isset($args['input']['lastname'])) {
                $lastName =  $args['input']['lastname'];
                
            }
            if (isset($args['input']['phone_number'])) {
                $telephoneNumberStr =  $args['input']['phone_number'];
                $newPhone = preg_replace('/[^0-9]/', '', $telephoneNumberStr);
                if (preg_match('/^[0-9]{10}$/', $newPhone)) {
                    $areaCode = substr($newPhone, 0, 3);
                    $phoneNumber = substr($newPhone, 3, 7);
                    $args['input']['phone_number'] = $newPhone;
                } else {
                    throw new GraphQlInputException(__('Phone number is not a 10 digit number.'));
                }
            }
            $customerData = [   'email' => $email,
                                'firstName' => $firstName,
                                'lastName' => $lastName,
                                'areaCode' => $areaCode,
                                'phoneNumber' => $phoneNumber,
                                'currentPassword' => $password
                            ];
            
            $customerData = array_filter($customerData);
            try {
                $result = $this->dcs->updateCustomerDetails($customerData);
                if (isset($result['message'])) {
                    $output['message'] = $result['message'];
                } else if(isset($result['status']['messages'][0]['description'])){
                    $output['message'] = $result['status']['messages'][0]['description'];
                } else {
                    $output['message'] = 'Something went wrong, please try again.';
                }
                if ((isset($result['message']) && $result['message'] == self::API_SUCCESS) || (isset($result['status']['messages'][0]['description']) && $result['status']['messages'][0]['description'] == self::API_SUCCESS) ) {
                    $this->updateCustomerAccount->execute(
                        $customer,
                        $args['input'],
                        $context->getExtensionAttributes()->getStore()
                    );
                }
                $data = $this->extractCustomerData->execute($customer);
                $output['customer'] =  $data;
            } catch (\Exception $e) {
                $output = ['message' =>$e->getMessage()];
                $this->dcsLogger->error('Update Customer Resolver Error: ' . $e);
            }
        }
        return $output;
    }
}
