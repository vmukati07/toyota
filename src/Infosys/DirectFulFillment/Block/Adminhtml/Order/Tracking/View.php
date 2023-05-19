<?php

/**
 * @package   Infosys/DirectFulFillment
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\DirectFulFillment\Block\Adminhtml\Order\Tracking;

use Magento\Framework\App\ObjectManager;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Infosys\DirectFulFillment\Model\FreightRecoveryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Shipment tracking control form
 *
 * @api
 * @since 100.0.2
 */
class View extends \Magento\Shipping\Block\Adminhtml\Order\Tracking
{
    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $_carrierFactory;

    protected FreightRecoveryFactory $freightRecoveryFactory;

    protected ScopeConfigInterface $config;

    /**
     * Constructor function
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Shipping\Model\Config $shippingConfig
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param FreightRecoveryFactory $freightRecoveryFactory
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        FreightRecoveryFactory $freightRecoveryFactory,
        ScopeConfigInterface $config,
        array $data = [],
        ?ShippingHelper $shippingHelper = null
    ) {
        $data['shippingHelper'] = $shippingHelper ?? ObjectManager::getInstance()->get(ShippingHelper::class);
        parent::__construct($context, $shippingConfig, $registry, $data);
        $this->_carrierFactory = $carrierFactory;
        $this->freightRecoveryFactory = $freightRecoveryFactory;
        $this->config = $config;
    }

    /**
     * Prepares layout of block
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        $onclick = "saveTrackingInfo($('shipment_tracking_info').parentNode, '" . $this->getSubmitUrl() . "')";
        $this->addChild(
            'save_button',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => __('Add'), 'class' => 'save', 'onclick' => $onclick]
        );
    }

    /**
     * Retrieve save url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('adminhtml/*/addTrack/', ['shipment_id' => $this->getShipment()->getId()]);
    }

    /**
     * Retrieve save button html
     *
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }

    /**
     * Retrieve remove url
     *
     * @param \Magento\Sales\Model\Order\Shipment\Track $track
     * @return string
     */
    public function getRemoveUrl($track)
    {
        return $this->getUrl(
            'adminhtml/*/removeTrack/',
            ['shipment_id' => $this->getShipment()->getId(), 'track_id' => $track->getId()]
        );
    }

    /**
     * Get carrier title
     *
     * @param string $code
     *
     * @return \Magento\Framework\Phrase|string|bool
     */
    public function getCarrierTitle($code)
    {
        $carrier = $this->_carrierFactory->create($code);
        return $carrier ? $carrier->getConfigData('title') : __('Custom Value');
    }

    /**
     * Function to get Dealer Shipping Cost
     *
     * @return float
     */
    public function getDealerShippingCost(): ?float
    {
        $shippingCost = 0.0;
        $shipmentId = $this->getShipment()->getId();
        $freightRecovery = $this->freightRecoveryFactory->create();
        $collection = $freightRecovery->getCollection()->addFieldToSelect('*')
            ->addFieldToFilter('shipment_id', ['eq' => $shipmentId]);
        if ($collection->count()) {
            $shippingCost = $collection->getFirstItem()->getFreightRecovery();
        }
        return $shippingCost;
    }

    /**
     * Method to get shipment tracking link
     *
     * @param string $carrier
     * @param string $number
     * @return string|null
     */
    public function getShipmentTrackingLink($carrier, $number): ?string
    {
        $tracking = $this->config->getValue(
            'shipment_tracking_config/shipment_tracking_links/' . $carrier,
            ScopeInterface::SCOPE_STORE
        );
        return $tracking . $number;
    }
}
