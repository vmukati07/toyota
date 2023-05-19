<?php

/**
 * @package   Infosys/CustomerSSO
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\CustomerSSO\Model;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Infosys\CustomerSSO\Api\DCSInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Infosys\CustomerSSO\Logger\DCSLogger;
use Infosys\CustomerSSO\Helper\Data;

class DCS implements DCSInterface
{
    /**
     * Curl Status for 200
     */
    const CURL_STATUS = 200;

    protected Curl $curl;

    protected ScopeConfigInterface $scopeConfig;

    protected Json $json;

    protected CustomerRepositoryInterface $customerRepository;

    protected LoggerInterface $logger;

    protected CurlFactory $curlFactory;

    protected SessionManagerInterface $session;

    protected DCSLogger $dcsLogger;

    protected Data $dcsHelper;

    /**
     * Constructor function
     *
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param DCSLogger $dcsLogger
     * @param CurlFactory $curlFactory
     * @param SessionManagerInterface $session
     * @param Data $dcsHelper
     */
    public function __construct(
        Curl $curl,
        ScopeConfigInterface $scopeConfig,
        Json $json,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        DCSLogger $dcsLogger,
        CurlFactory $curlFactory,
        SessionManagerInterface $session,
        Data $dcsHelper
    ) {
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->dcsLogger = $dcsLogger;
        $this->curlFactory = $curlFactory;
        $this->session = $session;
        $this->dcsHelper = $dcsHelper;
    }

    /**
     * Get API token based on resource
     *
     * @param string $customerData
     */
    public function getCustomerToken($customerData): string
    {
        $response = '';
        $tokenUrl = $this->getConfig('dcs/token_api/token_url');
        $grantType = $this->getConfig('dcs/token_api/grant_type');
        $clientId = $this->getConfig('dcs/token_api/client_id');
        $clientSecret = $this->getConfig('dcs/token_api/client_secret');
        $scope = $this->getConfig('dcs/token_api/scope');
        $connectionTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_connection_timeout');
        $requestTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_request_timeout');
        $params = [
            'grant_type' => $grantType,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => $scope,
            'username' => $customerData['email'],
            'password' => $customerData['password']
        ];
        $this->curl->addHeader("Content-Type", "application/x-www-form-urlencoded");
        $options =  [CURLOPT_CONNECTTIMEOUT => $connectionTimeout];
        $this->curl->setOptions($options);
        $this->curl->setTimeout($requestTimeout);
        try {
            $this->curl->post($tokenUrl, $params);
             //response will contain the output of curl request
            $response = $this->curl->getBody();
            $httpStatusCode = $this->curl->getStatus();
            if ($httpStatusCode != self::CURL_STATUS) {
                unset($params['password']);
                $this->dcsLogger->error('Token API request: ' . json_encode($params));
                $this->dcsLogger->error('Token API response: ' . $response);
                $this->dcsLogger->error('Token Url: ' . $tokenUrl);
            } else {
                if ($this->dcsHelper->isLogEnabled()) {
                    $this->dcsLogger->info('Token API response: ' . $response);
                }
            }
        } catch (\Exception $e) {
            $this->dcsLogger->error('Token API not working' . $e->getMessage());
        }
         return $response;
    }

    /**
     * API to update customer email in SSO
     *
     * @param string $customerData
     * @return array
     */
    public function updateCustomerEmail($customerData): array
    {
        $response = [];
        $customerInfo = ['email' => $customerData['new_email'], 'currentPassword' =>$customerData['password']];
        $customerInfo = $this->json->serialize($customerInfo);
        $token = $this->getCustomerToken($customerData);
        $token = $this->json->unserialize($token);
        if(isset($token['error'])){
            $response['message'] = 'Something went wrong, please try again.';
            return $response;
        }
        if (isset($token['id_token'])) {
            $token = $token['id_token'];
        }
        $url = $this->getConfig('dcs/update_customer/update_customer_api_url');
        $x_client = $this->getConfig('dcs/update_customer/x_client');
        $x_brand =  $this->getConfig('dcs/update_customer/x_brand');
        $x_version =  $this->getConfig('dcs/update_customer/x_version');
        $connectionTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_connection_timeout');
        $requestTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_request_timeout');

        try {
            $requestBody = $customerInfo;
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("X-BRAND", $x_brand);
            $this->curl->addHeader("X-CLIENT", $x_client);
            $this->curl->addHeader("X-VERSION", $x_version);
            $this->curl->addHeader("Authorization", $token);
            $options =  [CURLOPT_CONNECTTIMEOUT => $connectionTimeout, CURLOPT_CUSTOMREQUEST=> "PUT"];
            $this->curl->setOptions($options);
            $this->curl->setTimeout($requestTimeout);
            $this->curl->post($url, $requestBody);
            $response = $this->curl->getBody();
            $httpStatusCode = $this->curl->getStatus();
            if ($httpStatusCode != self::CURL_STATUS) {
                $requestBody = json_decode($requestBody, true);
                unset($requestBody['currentPassword']);
                $this->dcsLogger->error('Update customer email request: ' . json_encode($requestBody));
                $this->dcsLogger->error('Update customer email API response: ' . $response);
                $this->dcsLogger->error('Update customer email Url: ' . $url);
            } else {
                if ($this->dcsHelper->isLogEnabled()) {
                    $requestBody = json_decode($requestBody, true);
                    unset($requestBody['currentPassword']);
                    $this->dcsLogger->info('Update customer email request: ' . json_encode($requestBody));
                    $this->dcsLogger->info('Update customer email response: ' . $response);
                }
            }
            if ($response) {
                $response = $this->json->unserialize($response);
            }
        } catch (\Exception $e) {
            $this->dcsLogger->error('Update customer email API not working: ' . $e);
        }
        return $response;
    }

    /**
     * API to update customer info in SSO
     *
     * @param array $customerData
     * @return array
     */
    public function updateCustomerDetails($customerData) : array
    {
        $response = [];
        $customerInfo = ['email' => $customerData['email'], 'password' =>$customerData['currentPassword']];
        $token = $this->getCustomerToken($customerInfo);
        $token = $this->json->unserialize($token);
        if(isset($token['error'])){
            $response['message'] = 'Something went wrong, please try again.';
            return $response;
        }
        if (isset($token['id_token'])) {
            $token = $token['id_token'];
        }
        $this->session->setToken($token);
        $this->session->setPhone($customerData['areaCode'].$customerData['phoneNumber']);
        $customerData = $this->json->serialize($customerData);

        $url = $this->getConfig('dcs/update_customer/update_customer_api_url');
        $x_client = $this->getConfig('dcs/update_customer/x_client');
        $x_brand =  $this->getConfig('dcs/update_customer/x_brand');
        $x_version =  $this->getConfig('dcs/update_customer/x_version');
        $connectionTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_connection_timeout');
        $requestTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_request_timeout');
        try {
            $requestBody = $customerData;
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("X-BRAND", $x_brand);
            $this->curl->addHeader("X-CLIENT", $x_client);
            $this->curl->addHeader("X-VERSION", $x_version);
            $this->curl->addHeader("Authorization", $token);
            $options =  [CURLOPT_CONNECTTIMEOUT => $connectionTimeout, CURLOPT_CUSTOMREQUEST=> "PUT"];
            $this->curl->setOptions($options);
            $this->curl->setTimeout($requestTimeout);
            $this->curl->post($url, $requestBody);
            $response = $this->curl->getBody();
            $httpStatusCode = $this->curl->getStatus();
            if ($httpStatusCode != self::CURL_STATUS) {
                $requestBody = json_decode($requestBody, true);
                unset($requestBody['currentPassword']);
                $this->dcsLogger->error('Update customer details API request: ' . json_encode($requestBody));
                $this->dcsLogger->error('Update customer details API response: ' . $response);
                $this->dcsLogger->error('Update customer details URL: ' . $url);
                
            } else {
                if ($this->dcsHelper->isLogEnabled()) {
                    $requestBody = json_decode($requestBody, true);
                    unset($requestBody['currentPassword']);
                    $this->dcsLogger->info('Update customer details API request: ' . json_encode($requestBody));
                    $this->dcsLogger->info('Update customer details API response: ' . $response);
                }
            }
            if ($response) {
                $response = $this->json->unserialize($response);
            }
        } catch (\Exception $e) {
            $this->dcsLogger->error('Update customer detials API not working ' . $e);
        }
        
        return $response;
    }

    /**
     * API to verify customer phone with otp
     *
     * @param string $otpCode
     * @return array
     */
    public function verifyCustomerPhone($otpCode): array
    {
        $response =[];
        if ($this->dcsHelper->isLogEnabled()) {
            $this->dcsLogger->info('Validate customer phone API call');
        }
        $token = $this->session->getToken();
        $phone = $this->session->getPhone();
        $customerData = [
            'otpCode'=> $otpCode,
            'phoneNumber' => $phone
        ];
        $customerData = $this->json->serialize($customerData);
        $url = $this->getConfig('dcs/verify_phone/phone_otp_api_url');
        $x_client = $this->getConfig('dcs/verify_phone/client_get_v');
        $x_brand =  $this->getConfig('dcs/verify_phone/brand_get_v');
        $x_version =  $this->getConfig('dcs/verify_phone/version_get_v');
        $connectionTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_connection_timeout');
        $requestTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_request_timeout');

        try {
            $requestBody = $customerData;
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("X-BRAND", $x_brand);
            $this->curl->addHeader("X-CLIENT", $x_client);
            $this->curl->addHeader("X-VERSION", $x_version);
            $this->curl->addHeader("Authorization", $token);
            $options =  [CURLOPT_CONNECTTIMEOUT => $connectionTimeout, CURLOPT_CUSTOMREQUEST=> "PUT"];
            $this->curl->setOptions($options);
            $this->curl->setTimeout($requestTimeout);
            $this->curl->post($url, $requestBody);
            //response will contain the output of curl request
            $response = $this->curl->getBody();
            $httpStatusCode = $this->curl->getStatus();

            if ($httpStatusCode != self::CURL_STATUS) {
                $this->dcsLogger->error('verify customer phone API request: ' . $requestBody);
                $this->dcsLogger->error('verify customer phone API response: ' . $response);
                $this->dcsLogger->error('verify customer phone Url: ' . $url);
            } else {
                if ($this->dcsHelper->isLogEnabled()) {
                    $this->dcsLogger->info('Verify phone API request: ' . $requestBody);
                    $this->dcsLogger->info('Verify phone API response: ' .$response);
                }
            }
            if ($response) {
                $response = $this->json->unserialize($response);
            }
        } catch (\Exception $e) {
            $this->dcsLogger->error('Verify phone API not working: ' . $e);
        }
        $this->session->unsToken();
        return $response;
    }
    /**
     * API to activate customer email
     *
     * @param string $activationCode
     * @return array
     */
    public function activateCustomerEmail($activationCode): array
    {
        $response = [];
        $params = ['activationCode' => $activationCode];
        $requestBody = $this->json->serialize($params);

        $url = $this->getConfig('dcs/activate_customer/activate_customer_api_url');
        $x_client = $this->getConfig('dcs/activate_customer/x_client_active');
        $x_brand =  $this->getConfig('dcs/activate_customer/x_brand_active');
        $x_version =  $this->getConfig('dcs/activate_customer/x_version_active');
        $connectionTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_connection_timeout');
        $requestTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_request_timeout');
        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("X-BRAND", $x_brand);
            $this->curl->addHeader("X-CLIENT", $x_client);
            $this->curl->addHeader("X-VERSION", $x_version);
            $options =  [CURLOPT_CONNECTTIMEOUT => $connectionTimeout];
            $this->curl->setOptions($options);
            $this->curl->setTimeout($requestTimeout);
            $this->curl->patch($url, $requestBody);
            //response will contain the output of curl request
            $response = $this->curl->getBody();
            $httpStatusCode = $this->curl->getStatus();
            if ($httpStatusCode != self::CURL_STATUS) {
                $this->dcsLogger->error('Activate customer email API request: ' . $requestBody);
                $this->dcsLogger->error('Activate customer email API response: ' . $response);
                $this->dcsLogger->error('Activate customer email Url: ' . $url);
            } else {
                if ($this->dcsHelper->isLogEnabled()) {
                    $this->dcsLogger->info('Activate customer email API request: ' . $requestBody);
                    $this->dcsLogger->info('Activate customer email API response: ' . $response);
                }
            }
            if ($response) {
                $response = $this->json->unserialize($response);
            }
        } catch (\Exception $e) {
            $this->dcsLogger->error('Activate customer email API not working: ' . $e);
        }
        return $response;
    }

     /**
      * API to get customer email
      *
      * @param string $idToken
      * @return array
      */
    public function getCustomerEmail($idToken): array
    {
        $response = [];
        $url = $this->getConfig('dcs/get_customer/get_customer_api_url');
        $x_client = $this->getConfig('dcs/get_customer/x_client_get');
        $x_brand =  $this->getConfig('dcs/get_customer/x_brand_get');
        $x_version =  $this->getConfig('dcs/get_customer/x_version_get');
        $connectionTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_connection_timeout');
        $requestTimeout = $this->getConfig('dcs/dcs_api_timeout/dcs_request_timeout');

        try {
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("X-BRAND", $x_brand);
            $this->curl->addHeader("X-CLIENT", $x_client);
            $this->curl->addHeader("X-VERSION", $x_version);
            $this->curl->addHeader("Authorization", $idToken);
            $options =  [CURLOPT_CONNECTTIMEOUT => $connectionTimeout];
            $this->curl->setOptions($options);
            $this->curl->setTimeout($requestTimeout);
            $this->curl->get($url);
            //response will contain the output of curl request
            $response = $this->curl->getBody();
            $httpStatusCode = $this->curl->getStatus();
            if ($httpStatusCode != self::CURL_STATUS) {
                $this->dcsLogger->error('get customer email API response: ' . $response);
                $this->dcsLogger->error('get customer email Url: ' . $url);
            } else {
                if ($this->dcsHelper->isLogEnabled()) {
                    $this->dcsLogger->info('Get customer email API response: ' . $response);
                }
            }
            if ($response) {
                $response = $this->json->unserialize($response);
            }
        } catch (\Exception $e) {
            $this->dcsLogger->error('Get customer email API not working: ' . $e);
        }
        return $response;
    }

    /**
     * Get store config value for DCS
     *
     * @param string $path
     * @param int $storeId
     * @return string
     */
    protected function getConfig($path, $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
