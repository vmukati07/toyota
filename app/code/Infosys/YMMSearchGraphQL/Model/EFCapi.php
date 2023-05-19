<?php

/**
 * @package   Infosys/YMMSearchGraphQL
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\YMMSearchGraphQL\Model;

use Magento\Framework\HTTP\Client\Curl;
use Infosys\YMMSearchGraphQL\Api\EFCInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManager;
use Infosys\YMMSearchGraphQL\Logger\EFCLogger;
use Infosys\YMMSearchGraphQL\Model\Cache\YmmSearchCache;

class EFCapi implements EFCInterface
{
    const CURL_STATUS = 200;
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var EFCLogger
     */
    protected $efcLogger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var YmmSearchCache
     */
    protected $searchCache;

    /**
     * Constructor function
     *
     * @param Curl $curl
     * @param Json $json
     * @param EFCLogger $efcLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManager $storeManager
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param YmmSearchCache $searchCache
     */
    public function __construct(
        Curl $curl,
        Json $json,
        EFCLogger $efcLogger,
        ScopeConfigInterface $scopeConfig,
        StoreManager $storeManager,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        YmmSearchCache $searchCache
    ) {
        $this->_curl = $curl;
        $this->_json = $json;
        $this->efcLogger = $efcLogger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->searchCache = $searchCache;
    }

    /**
     * API to get vehicle image
     *
     * @param string $year
     * @param string $trim
     * @return array
     */
    public function getVehicleImage($year, $trim)
    {
        $responseData = [];
        $storeId = $this->storeManager->getStore()->getId();
        $apikey = $this->getConfig('searchbyYMM/general/apikey');
        $apiurl = $this->getConfig('searchbyYMM/general/apiurl');
        $imageurl = $this->getConfig('searchbyYMM/general/imageurl');
        $image_brand = $this->getConfig('searchbyYMM/general/image_brand', $storeId);
        $connectionTimeout = $this->getConfig('searchbyYMM/efc_api_timeout/efc_connection_timeout');
        $requestTimeout = $this->getConfig('searchbyYMM/efc_api_timeout/efc_request_timeout');
        $agent = 'PCO Adobe Commerce';
        $url = $apiurl . "/vehiclecontent/v2/" . $image_brand . "/NATIONAL/EN/trims?year=" . $year . "&trim=" . $trim;
        try {
            $isEfcRedisCacheEnabled = $this->getConfig('searchbyYMM/efc_redis_cache/active');
            $cacheKey  = $this->searchCache::TYPE_IDENTIFIER;
            $cacheKey = $cacheKey."-".$image_brand."-".$year."-".$trim;            
            $cacheTag  = $this->searchCache::CACHE_TAG;
            $isEFCImgCache = false;
            if($this->cache->load($cacheKey) && $isEfcRedisCacheEnabled){
                $isEFCImgCache = false;
                $data = $this->serializer->unserialize($this->cache->load($cacheKey));
                if($data){
                    $responseData = $this->getJsonDecode($data);
                    $responseData['imageurl'] = $imageurl;
                    $isEFCImgCache = true;
                    $this->efcLogger->info('EFC data served from cache '.$cacheKey);
                }
            } 
            if(!$isEFCImgCache) {
                $options = [
                    CURLOPT_USERAGENT => $agent,
                    CURLOPT_HTTPHEADER => ["x-api-key: " . $apikey],
                    CURLOPT_CONNECTTIMEOUT => $connectionTimeout
                ];
                $this->_curl->setOptions($options);
                $this->_curl->setTimeout($requestTimeout);
                $this->_curl->get($url);
                $response = $this->_curl->getBody();
                //non 200 http response
                $httpStatusCode = $this->_curl->getStatus();
                if ($httpStatusCode != self::CURL_STATUS) {
                    $this->efcLogger->error('EFC API request is failing.HTTP status code: ' . $httpStatusCode);
                    $this->efcLogger->error('EFC API response' . $response);
                }else{
                    if ($response) {
                        $this->efcLogger->info("EFC API response: " . $response);
                        $responseData = $this->getJsonDecode($response);
                        $responseData['imageurl'] = $imageurl;
                        if(!empty($imageurl) && $isEfcRedisCacheEnabled){
                            $this->cache->save(
                                $this->serializer->serialize($response),
                                $cacheKey,
                                [$cacheTag],
                                86400
                            );
                            $this->efcLogger->info('EFC data added to Cache for key '.$cacheKey.' ' . $response);
                        }                        
                    }
                }                   
            }

        } catch (\Exception $e) {
            $this->efcLogger->error('EFC API not working' . $e);
        }
        return $responseData;
    }

    /**
     * Json data
     *
     * @param array $response
     * @return array
     */
    public function getJsonDecode($response)
    {
        return $this->_json->unserialize($response);
    }

    /**
     * Get store config value for EFC API
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
