<?php
/**
 * @package     Infosys/StorePickUp 
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */
namespace Infosys\StorePickUp\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\Config;

/**
 * Class ShippingMethods
 * 
 * Class to prepare the dropdown option of shipping method filters
 */
class ShippingMethods extends AbstractSource implements ArrayInterface
{

    /**
     * Add shipping method code to exclude from the dropdown list, separated by comma in case of multiple.
     */
    const EXCLUDE_SHIPPING_METHOD_CODE = 'adminshipping';

    /**
     * In store pickup shipping method code
     */
    const INSTORE_SHIPPING_METHOD_CODE = 'dealerstore_pickup';
    /**
     * @var scopeConfig
     */
    protected $scopeConfig; 
    /**
     * @var shipconfig
     */
    protected $shipconfig;

    /**
     * Constructor function
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $shipconfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $shipconfig
        ) {
            $this->shipconfig = $shipconfig;
            $this->scopeConfig = $scopeConfig;
    }

     /**
     * Get Active Shipping Method Options
     *
     * @return array
     */
    public function getShippingMethods() {
        $activeCarriers = $this->shipconfig->getActiveCarriers();
        $excludeOptions = explode(',',self::EXCLUDE_SHIPPING_METHOD_CODE);
        foreach($activeCarriers as $carrierCode => $carrierModel) {
            $options = array();
            if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                foreach ($carrierMethods as $methodCode => $method) {
                    if(in_array($methodCode,$excludeOptions)){
                        break;
                    }
                    if($carrierCode == 'dealerstore'){
                        $methodCode = 'pickup';
                    }
                    $code = $carrierCode . '_' . $methodCode;
                    $options[] = array('value' => $code, 'label' => $method);
                }
                $carrierTitle = $this->scopeConfig->getValue('carriers/'.$carrierCode.'/title');
            }
            if(!empty($options)) {
                $methods[] = array('value' => $options, 'label' => $carrierTitle);
            }
        }
        return $this->sortShippingMethods($methods);
    }
    
    /**
    * Get all options
    *
    * @return array
    */
    public function toOptionArray()
    {
        return $this->getShippingMethods();
    }

    /**
     * Return all options
     * 
     * @return array
     */
    public function getAllOptions()
    {
        return $this->getShippingMethods();
    } 

     /**
     * Return sorted Shipping Methods
     * 
     * @return array
     */
    public function sortShippingMethods($methods)
    {
        usort($methods, [$this, "compareMethods"]);
        return $methods;
    }

     /**
     * Callback function for sortShippingMethod function
     * 
     * @return integer
     */
    public function compareMethods($methods, $current)
    {
        if (isset($current['value'][0]['value']) &&  $current['value'][0]['value'] == self::INSTORE_SHIPPING_METHOD_CODE) {
            return 1;
        }
        return -1;
    }
}