<?php

/**
 * @package     Infosys_XtentoPdfCustomizer
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\XtentoPdfCustomizer\Model\Variable\Processors;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject\Factory as DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\ItemFactory as OrderItemFactory;
use Xtento\PdfCustomizer\Helper\AbstractPdf;
use Xtento\PdfCustomizer\Helper\Variable\Custom\Items as CustomItems;
use Xtento\PdfCustomizer\Helper\Variable\Custom\Product as CustomProduct;
use Xtento\PdfCustomizer\Helper\Variable\Formatted;
use Xtento\PdfCustomizer\Helper\Variable\Processors\Items as CoreItems;
use Xtento\PdfCustomizer\Model\Source\TemplateType;
use Xtento\PdfCustomizer\Model\Template\ProcessorFactory;
use Xtento\XtCore\Helper\Utils;
use Magento\Directory\Model\Currency;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Items
 *
 * Infosys\XtentoPdfCustomizer\Model\Variable\Processors
 * @SuppressWarnings(CouplingBetweenObjects)
 */
class Items extends CoreItems
{
    /**
     * @var Formatted
     */
    private $formatted;

    /**
     * @var CustomItems
     */
    private $customData;

    /**
     * @var Processor
     */
    public $processor;

    /**
     * @var DataObject
     */
    private $dataObject;

    /**
     * @var CustomProduct
     */
    private $customProduct;

    /**
     * @var OrderItemFactory
     */
    private $orderItemFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    private $currency;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Utils
     */
    private $utilsHelper;

    /**
     * Items constructor.
     *
     * @param Context $context
     * @param ProcessorFactory $processor
     * @param Formatted $formatted
     * @param CustomItems $customData
     * @param DataObject $dataObject
     * @param CustomProduct $customProduct
     * @param OrderItemFactory $orderItemFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Currency $currency
     * @param ObjectManagerInterface $objectManager
     * @param Utils $utilsHelper
     */
    public function __construct(
        Context $context,
        ProcessorFactory $processor,
        Formatted $formatted,
        CustomItems $customData,
        DataObject $dataObject,
        CustomProduct $customProduct,
        OrderItemFactory $orderItemFactory,
        ProductRepositoryInterface $productRepository,
        Currency $currency,
        ObjectManagerInterface $objectManager,
        Utils $utilsHelper
    ) {
        $this->formatted = $formatted;
        $this->customData = $customData;
        $this->processor = $processor;
        $this->dataObject = $dataObject;
        $this->customProduct = $customProduct;
        $this->orderItemFactory = $orderItemFactory;
        $this->productRepository = $productRepository;
        $this->currency = $currency;
        $this->objectManager = $objectManager;
        $this->utilsHelper = $utilsHelper;
        parent::__construct(
            $context,
            $processor,
            $formatted,
            $customData,
            $dataObject,
            $customProduct,
            $orderItemFactory,
            $productRepository,
            $currency,
            $objectManager,
            $utilsHelper
        );
    }

    /**
     * Variable Item Processor Method
     *
     * @param object $source
     * @param object $itemObject
     * @param object $template
     *
     * @return array|string
     */
    //phpcs:ignore
    public function variableItemProcessor($source, $itemObject, $template)
    {
        $templateHtml = $template->getTemplateHtmlFull();

        $transport = [];

        $getAllVariables = $template->getData('get_all_variables') === true;

        // Order / invoice should always be available
        $templateType = $template->getData('template_model')->getData('template_type');
        $templateTypeName = TemplateType::TYPES[$templateType]; // order, invoice, ...
        if (strstr($templateHtml, $templateTypeName) !== false) {
            $transport[$templateTypeName] = $source;
        }

        if (strstr($templateHtml, 'order') !== false) {
            if ($source->getOrder()) {
                $transport['order'] = $source->getOrder();
            } else {
                $transport['order'] = $source;
            }
        }

        $storeId = $source->getStoreId();
        if (!$storeId && $source && $source->getOrder()) {
            $storeId = $source->getOrder()->getStoreId();
        }

        // Performance improvement: Only load variables that are actually required
        // Item
        if ($getAllVariables
            || strstr($templateHtml, ' item.') !== false
            || strstr($templateHtml, '$item') !== false
            || strstr($templateHtml, ' formatted_item.') !== false
            || strstr($templateHtml, ' item_if.') !== false
        ) {
            /** @var Item $orderItem */
            $item = $this->customData->entity($itemObject)->processAndReadVariables();
        }
        if ($getAllVariables || strstr($templateHtml, ' item.') !== false) {
            $transport['item'] = $item;
        }
        if ($getAllVariables || strstr($templateHtml, ' formatted_item.') !== false) {
            $transport['formatted_item'] = $this->formatted->getFormatted($item);
        }
        if (strstr($templateHtml, ' item_if.') !== false) {
            $transport['item_if'] = $this->formatted->getZeroFormatted($item);
        }
        // Order Item
        if ($getAllVariables
            || strstr($templateHtml, ' order_item.') !== false
            || strstr($templateHtml, ' formatted_order_item.') !== false
            || strstr($templateHtml, ' order_item_if.') !== false
            || strstr($templateHtml, ' giftmessage.') !== false
        ) {
            if (!isset($item)) {
                /** @var Item $orderItem */
                $item = $this->customData->entity($itemObject)->processAndReadVariables();
            }
            $orderItem = $this->orderItem($item);
        }
        if ($getAllVariables || strstr($templateHtml, ' order_item.') !== false) {
            $transport['order_item'] = $orderItem;
        }
        if ($getAllVariables || strstr($templateHtml, ' formatted_order_item.') !== false) {
            $transport['formatted_order_item'] = $this->formatted->getFormatted($orderItem);
        }
        if (strstr($templateHtml, ' order_item_if.') !== false) {
            $transport['order_item_if'] = $this->formatted->getZeroFormatted($orderItem);
        }
        if ($getAllVariables || strstr($templateHtml, ' giftmessage.') !== false) {
            $transport['giftmessage'] = $this->formatted->getOrderGiftMessageArray($orderItem);
        }
        // Product
        $fitmentMethod = '';
        if ($getAllVariables
            || strstr($templateHtml, 'order_item_product.') !== false
            || strstr($templateHtml, ' formatted_order_item_product.') !== false
            || strstr($templateHtml, ' order_item_product_if.') !== false
        ) {
            if (!isset($orderItem)) {
                if (!isset($item)) {
                    /** @var Item $orderItem */
                    $item = $this->customData->entity($itemObject)->processAndReadVariables();
                }
                $orderItem = $this->orderItem($item);
            }
            /** Set Fitment Method value based on the vechicle name and vin number */
            if ($orderItem->getData('vehicle_name') && $orderItem->getData('vin_number')) {
                $fitmentMethod = "VIN";
            } elseif ($orderItem->getData('vehicle_name') && !$orderItem->getData('vin_number')) {
                $fitmentMethod = "Customer Selected";
            } else {
                $fitmentMethod = "None";
            }
            
            // Required to get product attributes for correct store
            $product = $orderItem->getProduct();
            if ($product && $product->getId()) {
                $productCopy = $this->productRepository->getById($product->getId(), false, $orderItem->getStoreId());
                $orderItemProduct = $this->customProduct->entity($productCopy)->processAndReadVariables();
            } else {
                $orderItemProduct = false;
            }
        }
        if ($getAllVariables || strstr($templateHtml, ' order_item_product.') !== false) {
            $transport['order_item_product'] = $orderItemProduct;
        }
        if ($getAllVariables || strstr($templateHtml, ' formatted_order_item_product.') !== false) {
            $transport['formatted_order_item_product'] = $this->formatted->getFormatted($orderItemProduct);
        }
        if (strstr($templateHtml, ' order_item_product_if.') !== false) {
            $transport['order_item_product_if'] = $this->formatted->getZeroFormatted($orderItemProduct);
        }

        if (strstr($templateHtml, 'barcode_') !== false) {
            // Barcode variables
            if (!isset($orderItem)) {
                if (!isset($item)) {
                    /** @var Item $orderItem */
                    $item = $this->customData->entity($itemObject)->processAndReadVariables();
                }
                $orderItem = $this->orderItem($item);
            }
            foreach (AbstractPdf::CODE_BAR as $code) {
                if (strstr($templateHtml, 'barcode_' . $code . '_item') !== false) {
                    $transport['barcode_' . $code . '_item'] = $this->formatted->getBarcodeFormatted($item, $code);
                }
                if (strstr($templateHtml, 'barcode_' . $code . '_order_item') !== false) {
                    $transport['barcode_' . $code . '_order_item']
                        = $this->formatted->getBarcodeFormatted($orderItem, $code);
                }
                if (strstr($templateHtml, 'barcode_' . $code . '_order_item_product') !== false) {
                    $transport['barcode_' . $code . '_order_item_product']
                        = $this->formatted->getBarcodeFormatted($orderItemProduct, $code);
                }
            }
        }

        // Load child data of configurable
        if ($getAllVariables
            || strstr($templateHtml, 'child_item.') !== false
            || strstr($templateHtml, 'child_order_item.') !== false
            || strstr($templateHtml, 'child_order_item_product.') !== false) {
            if (false === ($source instanceof Order)) {
                // Invoice, ... - load order items
                $allItems = $source->getOrder()->getItems();
                $itemId = $itemObject->getOrderItemId();
                $parentOrderItem = $this->orderItemFactory->create()->load($itemId);
            } else {
                $allItems = $source->getItems();
                $itemId = $itemObject->getId();
                $parentOrderItem = $itemObject;
            }

            if ($parentOrderItem->getProductType() == 'configurable'
                || $parentOrderItem->getProductType() == 'bundle') {
                foreach ($allItems as $tempItem) {
                    if ($tempItem->getParentItemId() === $itemId) {
                        // Is child of parent item
                        break 1;
                    }
                }
            }
            if (isset($tempItem)) {
                if ($getAllVariables || strstr($templateHtml, 'child_item.') !== false) {
                    $data = $this->customData->entity($tempItem)->processAndReadVariables();
                    $transport['child_item'] = $data;
                    if (strstr($templateHtml, 'barcode_') !== false) {
                        foreach (AbstractPdf::CODE_BAR as $code) {
                            if (strstr($templateHtml, 'barcode_' . $code . '_child_item') !== false) {
                                $transport['barcode_' . $code . '_child_item']
                                    = $this->formatted->getBarcodeFormatted($data, $code);
                            }
                        }
                    }
                    $transport['formatted_child_item'] = $this->formatted->getFormatted($data);
                }
                $tempOrderItem = $this->orderItem($tempItem);
                if ($getAllVariables || strstr($templateHtml, 'child_order_item.') !== false) {
                    $transport['child_order_item'] = $tempOrderItem;
                    if (strstr($templateHtml, 'barcode_') !== false) {
                        foreach (AbstractPdf::CODE_BAR as $code) {
                            if (strstr($templateHtml, 'barcode_' . $code . '_child_order_item') !== false) {
                                $transport['barcode_' . $code . '_child_order_item']
                                    = $this->formatted->getBarcodeFormatted($tempOrderItem, $code);
                            }
                        }
                    }
                    $transport['formatted_child_order_item'] = $this->formatted->getFormatted($tempOrderItem);
                }
                if ($getAllVariables || strstr($templateHtml, 'child_order_item_product.') !== false) {
                    $orderItemProduct = $tempOrderItem->getProduct();
                    if ($orderItemProduct && $orderItemProduct->getId()) {
                        $orderItemProductCopy = $this->productRepository->getById(
                            $orderItemProduct->getId(),
                            false,
                            $storeId
                        );
                        $data = $this->customProduct->entity($orderItemProductCopy)->processAndReadVariables();
                    } else {
                        $data = false;
                    }
                    $transport['child_order_item_product'] = $data;
                    if (strstr($templateHtml, 'barcode_') !== false) {
                        foreach (AbstractPdf::CODE_BAR as $code) {
                            if (strstr($templateHtml, 'barcode_' . $code . '_child_order_item_product') !== false) {
                                $transport['barcode_' . $code . '_child_order_item_product']
                                    = $this->formatted->getBarcodeFormatted($data, $code);
                            }
                        }
                    }
                    $transport['formatted_child_order_item_product'] = $this->formatted->getFormatted($data);
                }
            }
        }

        // Parent item
        if ($getAllVariables
            || strstr($templateHtml, 'parent_item.') !== false
            || strstr($templateHtml, 'parent_order_item.') !== false
            || strstr($templateHtml, 'parent_order_item_product.') !== false) {
            $parentItem = $itemObject->getParentItem();
            if (!$parentItem && $itemObject->getOrderItemId()) {
                $orderItem = $this->orderItemFactory->create()->load($itemObject->getOrderItemId());
                $parentItemId = $orderItem->getParentItemId();
                if ($parentItemId) {
                    $parentItem = $this->orderItemFactory->create()->load($parentItemId);
                }
            }
            if ($parentItem) {
                if ($getAllVariables || strstr($templateHtml, 'parent_item.') !== false) {
                    $data = $this->customData->entity($parentItem)->processAndReadVariables();
                    $transport['parent_item'] = $data;
                    if (strstr($templateHtml, 'barcode_') !== false) {
                        foreach (AbstractPdf::CODE_BAR as $code) {
                            if (strstr($templateHtml, 'barcode_' . $code . '_parent_item') !== false) {
                                $transport['barcode_' . $code . '_parent_item']
                                    = $this->formatted->getBarcodeFormatted($data, $code);
                            }
                        }
                    }
                    $transport['formatted_parent_item'] = $this->formatted->getFormatted($data);
                }
                $tempOrderItem = $this->orderItem($parentItem);
                if ($getAllVariables || strstr($templateHtml, 'parent_order_item.') !== false) {
                    $transport['parent_order_item'] = $tempOrderItem;
                    if (strstr($templateHtml, 'barcode_') !== false) {
                        foreach (AbstractPdf::CODE_BAR as $code) {
                            if (strstr($templateHtml, 'barcode_' . $code . '_parent_order_item') !== false) {
                                $transport['barcode_' . $code . '_parent_order_item']
                                    = $this->formatted->getBarcodeFormatted($tempOrderItem, $code);
                            }
                        }
                    }
                    $transport['formatted_parent_order_item'] = $this->formatted->getFormatted($tempOrderItem);
                }
                if ($getAllVariables || strstr($templateHtml, 'parent_order_item_product.') !== false) {
                    $data = $this->customProduct->entity($tempOrderItem->getProduct())->processAndReadVariables();
                    $transport['parent_order_item_product'] = $data;
                    if (strstr($templateHtml, 'barcode_') !== false) {
                        foreach (AbstractPdf::CODE_BAR as $code) {
                            if (strstr($templateHtml, 'barcode_' . $code . '_parent_order_item_product') !== false) {
                                $transport['barcode_' . $code . '_parent_order_item_product']
                                    = $this->formatted->getBarcodeFormatted($data, $code);
                            }
                        }
                    }
                    $transport['formatted_parent_order_item_product'] = $this->formatted->getFormatted($data);
                }
            }
        }

        // Ability to customize variables using an event. Store them using $transportObject->setCustomVariables();
        $transportObject = new \Magento\Framework\DataObject();

        /** Added fitment method as custom variable in array */
        $transportObject->setCustomVariables([
            'fitment_method' => $fitmentMethod,
        ]);

        $this->_eventManager->dispatch(
            'xtento_pdfcustomizer_build_item_transport_after',
            [
                'type' => 'sales',
                'object' => $source,
                'item' => $itemObject,
                'variables' => $transport,
                'transport' => $transportObject,
            ]
        );
        $transport = array_merge($transport, $transportObject->getCustomVariables());
        
        if ($getAllVariables) {
            return $transport;
        }

        $processor = $this->processor->create();
        $processor->setVariables($transport);
        $processor->setTemplate($template);

        return $processor->processTemplate($source->getStoreId());
    }

    /**
     * OrderItem Method
     *
     * @param object $item
     * @return mixed
     */
    private function orderItem($item)
    {
        if (!$item instanceof Item && $item->getOrderItem()) {
            $orderItem = $item->getOrderItem();
            $item = $this->customData->entity($orderItem)->processAndReadVariables();
            return $item;
        }

        return $item;
    }
}
