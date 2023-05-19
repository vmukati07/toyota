<?php

/**
 * @package     Infosys/DealerMarkupPrice
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DealerMarkupPrice\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use ShipperHQ\Shipper\Helper\Data;
use ShipperHQ\Shipper\Model\Quote\AddressDetailFactory;
use ShipperHQ\Shipper\Model\Quote\ItemDetailFactory as QuoteItemDetail;
use ShipperHQ\Shipper\Model\Order\DetailFactory;
use ShipperHQ\Shipper\Model\Order\ItemDetailFactory as OrderItemDetail;
use ShipperHQ\Shipper\Model\Order\GridDetailFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use ShipperHQ\Shipper\Helper\CarrierGroup as CoreCarrierGroup;

/**
 * Carrier Group Processing helper
 */
class CarrierGroup extends CoreCarrierGroup
{
    /**
     * @var AddressDetailFactory
     */
    private AddressDetailFactory $addressDetailFactory;

    /**
     * @var Data
     */
    private Data $shipperDataHelper;

    /**
     * Initialize dependencies
     *
     * @param AddressDetailFactory $addressDetailFactory
     * @param QuoteItemDetail $itemDetailFactory
     * @param DetailFactory $orderDetailFactory
     * @param OrderItemDetail $orderItemDetailFactory
     * @param GridDetailFactory $orderGridDetailFactory
     * @param Data $shipperDataHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    public function __construct(
        AddressDetailFactory $addressDetailFactory,
        QuoteItemDetail $itemDetailFactory,
        DetailFactory $orderDetailFactory,
        OrderItemDetail $orderItemDetailFactory,
        GridDetailFactory $orderGridDetailFactory,
        Data $shipperDataHelper,
        CartRepositoryInterface $quoteRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->shipperDataHelper = $shipperDataHelper;
        $this->addressDetailFactory = $addressDetailFactory;
        parent::__construct(
            $addressDetailFactory,
            $itemDetailFactory,
            $orderDetailFactory,
            $orderItemDetailFactory,
            $orderGridDetailFactory,
            $shipperDataHelper,
            $quoteRepository,
            $searchCriteriaBuilder,
            $orderStatusHistoryRepository
        );
    }

    /**
     * Save the carrier group shipping details for single carriergroup orders
     *
     * @param object $shippingAddress
     * @param string $shippingMethod
     * @param array $additionalDetail
     * @return bool
     */
    public function saveCarrierGroupInformation(
        $shippingAddress,
        $shippingMethod,
        array $additionalDetail = []
    ) {
        $foundRate = false;
        foreach ($shippingAddress->getAllShippingRates() as $rate) {
            if ($rate->getCode() == $shippingMethod) {
                $foundRate = $rate;
            }
        }

        if ($foundRate && $foundRate->getCarriergroupShippingDetails() != '') {
            $shipDetails = $this->shipperDataHelper->decodeShippingDetails(
                $foundRate->getCarriergroupShippingDetails()
            );
            if (array_key_exists('carrierGroupId', $shipDetails)) {
                $arrayofShipDetails = [];
                $arrayofShipDetails[] = $shipDetails;
            } else {
                $arrayofShipDetails = $shipDetails;
            }

            $shippingAddress
                ->setCarrierId($foundRate->getCarrierId())
                ->setCarrierType($foundRate->getCarrierType())
                ->save();

            $addressDetail = $this->addressDetailFactory->create();
            $thisAddressDetail = $addressDetail->loadByCarrierGroupIdAndAddress(
                $foundRate->getCarriergroupId(),
                $shippingAddress->getId()
            );
            if (!$thisAddressDetail) {
                $thisAddressDetail = $addressDetail;
            }

            $update = [
                'quote_address_id' => $shippingAddress->getId(),
                'carrier_group_id' => $foundRate->getCarriergroupId(),
                'carrier_type' => $foundRate->getCarrierType(),
                'carrier_group' => $foundRate->getCarriergroup(),
                'carrier_id' => $foundRate->getCarrierId(),
                'dispatch_date' => $foundRate->getShqDispatchDate() ?
                    date('Y-m-d', strtotime($foundRate->getShqDispatchDate())) :
                    '',
                'delivery_date' => $foundRate->getShqDeliveryDate() ?
                    date('Y-m-d', strtotime($foundRate->getShqDeliveryDate())) :
                    ''
            ];

            $update = array_merge($update, $additionalDetail);

            foreach ($arrayofShipDetails as $key => $detail) {
                //records destination type returned on rate - not type from address validation or user selection
                if (isset($detail['destination_type'])) {
                    $update['destination_type'] = $detail['destination_type'];
                }
                //SHQ18-69 include additional fields in carrier_group_detail
                $arrayofShipDetails[$key] = $this->getArrayMerge($detail, $additionalDetail);
            }

            $encodedShipDetails = $this->shipperDataHelper->encode($arrayofShipDetails);
            $update['carrier_group_detail'] = $encodedShipDetails;
            $update['carrier_group_html'] = $this->getCarriergroupShippingHtml($encodedShipDetails);

            $existing = $thisAddressDetail->getData();
            $data = array_merge($existing, $update);
            $thisAddressDetail->setData($data);
            $thisAddressDetail->save();

            //save selected shipping options to items
            $this->setShippingOnItems($arrayofShipDetails, $shippingAddress);
        }

        //Removing carrier details from shipperhq quote address when shipping method is store pickup or flatrate
        if ($shippingMethod == 'flatrate_flatrate' || $shippingMethod == 'dealerstore_pickup') {
            $addressDetail = $this->addressDetailFactory->create();
            $existingQuoteAddress = $addressDetail->loadByAddress($shippingAddress->getId());
            if ($existingQuoteAddress) {
                foreach ($existingQuoteAddress as $address) {
                    $addressDetail->load($address->getId())->delete();
                }
            }
        }
        return true;
    }

    /**
     * Get Array merge Data function
     *
     * @param array $detail
     * @param array $additionalDetail
     * @return array
     */
    public function getArrayMerge($detail, $additionalDetail): array
    {
        return array_merge($detail, $additionalDetail);
    }
}
