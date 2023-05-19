<?php
/**
 * @package     Infosys/RateLimiting
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\RateLimiting\Controller\Adminhtml\FastlyCdn\RateLimiting;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Config\App\Config\Type\System as SystemConfig;
use Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting\UpdatePaths as coreToggleRateLimiting;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class UpdatePaths for customize rate limiting
 */
class UpdatePaths extends coreToggleRateLimiting
{
    /**
     * @var Http
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $resultJson;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;
    /**
     * @var ConfigWriter
     */
    private $configWriter;
    /**
     * @var CacheTypeList
     */
    private $cacheTypeList;
    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var scopeConfig
     */
    protected $scopeConfig;

    /**
     * UpdateBlocking constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param ConfigWriter $configWriter
     * @param CacheTypeList $cacheTypeList
     * @param SystemConfig $systemConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        ConfigWriter $configWriter,
        CacheTypeList $cacheTypeList,
        SystemConfig $systemConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->systemConfig = $systemConfig;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $request, $resultJsonFactory, $config, $api, $vcl, $configWriter, $cacheTypeList, $systemConfig);
    }

    /**
     * Execute function
     *
     * @return void
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);

            $paths = $this->request->getParam('paths');
            if (!$paths) {
                $paths = [];
            }
            $validPaths = '';
            /* Start - Allow rate limited paths to be cached */
            $allowProtectedpathsCached = $this->scopeConfig->getValue('system/full_page_cache/fastly/fastly_rate_limiting_settings/path_protection/protected_path_cached');            
            if ($allowProtectedpathsCached == 0) {
                $snippet = $this->config->getVclSnippets(
                    Config::VCL_RATE_LIMITING_PATH,
                    Config::VCL_RATE_LIMITING_SNIPPET
                );

                foreach ($paths as $key => $value) {
                    if (empty($value['path'])) {
                        unset($paths[$key]);
                        continue;
                    }

                    if (substr($value['path'], -1) === '\\') {
                        return $result->setData([
                            'status'    => false,
                            'msg'       => $value['path'] . ' is not a valid regular expression'
                        ]);
                    }

                    $pregMatch = preg_match('{' . $value['path'] . '}', null);

                    if ($pregMatch === false) {
                        return $result->setData([
                            'status'    => false,
                            'msg'       => $value['path'] . ' is not a valid regular expression'
                        ]);
                    }
                    $validPaths .= 'req.url.path ~ "' . $value['path'] . '" || ';
                }

                $strippedValidPaths = substr($validPaths, 0, strrpos($validPaths, '||', -1));

                foreach ($snippet as $key => $value) {
                    if ($validPaths == '') {
                        $value = '';
                    } else {
                        $value = str_replace('####RATE_LIMITED_PATHS####', $strippedValidPaths, $value);
                    }

                    $snippetName = Config::FASTLY_MAGENTO_MODULE . '_rate_limiting_' . $key;
                    $snippetId = $this->api->getSnippet($currActiveVersion, $snippetName)->id;
                    $params = [
                        'name'      => $snippetId,
                        'content'   => $value
                    ];

                    $this->api->updateSnippet($params);
                }
            }
            /* End - Allow rate limited paths to be cached */
            $jsonPaths = json_encode($paths);

            $this->configWriter->save(
                Config::XML_FASTLY_RATE_LIMITING_PATHS,
                $jsonPaths,
                'default',
                '0'
            );
           
            $this->cacheTypeList->cleanType('config');
            $this->systemConfig->clean();

            return $result->setData([
                'status' => true
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
