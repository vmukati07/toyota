<?php

/**
 * @package     Infosys_XtentoPdfCustomizer
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\XtentoPdfCustomizer\Model\Variable\Processors;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject\Factory as DataObject;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Pdf\InvoiceFactory as InvoicePdfFactory;
use Magento\Tax\Model\Config;
use Magento\Weee\Helper\Data;
use Xtento\PdfCustomizer\Helper\Variable\Formatted;
use Xtento\PdfCustomizer\Helper\Variable\Processors\Tax;
use Xtento\PdfCustomizer\Helper\Variable\Processors\Totals as CoreTotals;
use Xtento\PdfCustomizer\Model\Source\TemplateType;
use Xtento\PdfCustomizer\Model\Template\Processor;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\AreaList;

/**
 * Class Totals
 *
 * Infosys\XtentoPdfCustomizer\Model\Variable\Processors
 */
class Totals extends CoreTotals
{
    /**
     * @var boolean
     */
    protected static $totalRenderers = false;

    /**
     * @var Formatted
     */
    private Formatted $formatted;

    /**
     * @var Processor
     */
    public $processor;

    /**
     * @var DataObject
     */
    private DataObject $dataObject;

    /**
     * @var InvoicePdfFactory
     */
    protected $abstractPdfFactory;

    /**
     * @var Tax
     */
    protected $taxHelper;

    /**
     * @var Config
     */
    protected $taxConfig;

    /**
     * @var Data
     */
    protected $weeeHelper;

    /**
     * @var Emulation
     */
    protected Emulation $emulation;

    /**
     * @var AreaList
     */
    protected AreaList $areaList;

    /**
     * Totals constructor.
     *
     * @param Context $context
     * @param Processor $processor
     * @param Formatted $formatted
     * @param DataObject $dataObject
     * @param InvoicePdfFactory $abstractPdfFactory
     * @param Tax $taxHelper
     * @param Config $taxConfig
     * @param Data $weeeHelper
     * @param Emulation $emulation
     * @param AreaList $areaList
     */
    public function __construct(
        Context $context,
        Processor $processor,
        Formatted $formatted,
        DataObject $dataObject,
        InvoicePdfFactory $abstractPdfFactory,
        Tax $taxHelper,
        Config $taxConfig,
        Data $weeeHelper,
        Emulation $emulation,
        AreaList $areaList
    ) {
        $this->formatted = $formatted;
        $this->processor = $processor;
        $this->dataObject = $dataObject;
        $this->abstractPdfFactory = $abstractPdfFactory;
        $this->taxHelper = $taxHelper;
        $this->taxConfig = $taxConfig;
        $this->weeeHelper = $weeeHelper;
        $this->emulation = $emulation;
        $this->areaList = $areaList;
        parent::__construct(
            $context,
            $processor,
            $formatted,
            $dataObject,
            $abstractPdfFactory,
            $taxHelper,
            $taxConfig,
            $weeeHelper
        );
    }

    /**
     * Process Method
     *
     * @param object $source
     * @param object $templateModel
     * @return string
     */
    //phpcs:ignore
    public function process($source, $templateModel)
    {
        $templateHtml = $templateModel->getTemplateHtml();
        if ($templateModel->getTemplateType() == TemplateType::TYPE_SHIPMENT) {
            return $templateHtml;
        }

        $templateTotalParts = $this->formatted->getTemplateAreas(
            $templateHtml,
            '##totals_start##',
            '##totals_end##'
        );

        if (empty($templateTotalParts)) {
            return $templateHtml;
        }

        // Get totals renderers
        if (self::$totalRenderers === false) {
            $pdfClass = $this->abstractPdfFactory->create();
            // Make function _getTotalsList() accessible
            $reflectionMethod = new \ReflectionMethod($pdfClass, '_getTotalsList');
            $reflectionMethod->setAccessible(true);
            self::$totalRenderers = $reflectionMethod->invoke($pdfClass);
            $reflectionMethod->setAccessible(false);
        }

        // Count totals
        $totalsCount = 0;
        foreach (self::$totalRenderers as $totalRenderer) {
            if ($source instanceof Order) {
                $order = $source->setOrderId($source->getId());
            } else {
                $order = $source->getOrder();
            }
            /** @var $totalRenderer \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal */
            $totalRenderer->setOrder($order)->setSource($source);
            if ($totalRenderer->canDisplay()) {
                $totalsCount++;
            }
        }

        $this->emulation->startEnvironmentEmulation(
            $source->getStoreId(),
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );
        $area = $this->areaList->getArea(\Magento\Framework\App\Area::AREA_FRONTEND);
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);

        // Compile totals
        $totals = [];
        $totalsCounter = 0;
        foreach (self::$totalRenderers as $totalRenderer) {
            if ($source instanceof Order) {
                $source->setOrderId($source->getId())->setOrder($source);
                $order = $source;
            } else {
                $order = $source->getOrder();
            }
            /** @var $totalRenderer \Magento\Sales\Model\Order\Pdf\Total\DefaultTotal */
            $totalRenderer->setOrder($order)->setSource($source);
            if ($totalRenderer->canDisplay()) {
                $totalsForDisplay = $totalRenderer->getTotalsForDisplay();
                if (empty($totalsForDisplay)) {
                    continue;
                }
                if ($totalRenderer->getSourceField() == 'tax_amount'
                    && $this->taxConfig->displaySalesFullSummary($order->getStore())) {
                    // Custom tax renderer
                    $totalTaxAmount = 0;
                    $totalBaseTaxAmount = 0;
                    $taxRates = $this->taxHelper->getTaxRates($source);
                    foreach ($taxRates as $taxRate) {
                        $totalsCounter++;
                        $label = rtrim($totalsForDisplay[count($totalsForDisplay) - 1]['label'], ':');
                        $taxPercent = $taxRate['title'];
                        $totalTaxAmount += $taxRate['tax_amount'];
                        $totalBaseTaxAmount += $taxRate['base_tax_amount'];
                        $totals[] = [
                            'label' => (string) __($label),
                            'amount' => $taxRate['tax_amount'],
                            'base_amount' => $taxRate['base_tax_amount'],
                            'tax_percent' => $taxPercent,
                            'amount_prefix' => '',
                            'is_bold' => false,
                            'is_grand_total' => false,
                            'is_subtotal' => false,
                            'is_tax' => true,
                            'is_first' => $totalsCounter === 1,
                            'is_last' => $totalsCounter === $totalsCount,
                        ];
                    }
                    if (floatval($totalTaxAmount) != floatval($source->getTaxAmount())) {
                        $missingAmount = abs(floatval($totalTaxAmount) - floatval($source->getTaxAmount()));
                        if ($missingAmount > 1e-6) {
                            $totalsCounter++;
                            $totals[] = [
                                'label' => (string) __('Tax'),
                                'amount' => $missingAmount,
                                'base_amount' => abs($totalBaseTaxAmount - $source->getBaseTaxAmount()),
                                'tax_percent' => __('Other'),
                                'amount_prefix' => '',
                                'is_bold' => false,
                                'is_grand_total' => false,
                                'is_subtotal' => false,
                                'is_tax' => true,
                                'is_first' => $totalsCounter === 1,
                                'is_last' => $totalsCounter === $totalsCount,
                            ];
                        }
                    }
                } elseif ($totalRenderer->getSourceField() == 'grand_total'
                    && $this->taxConfig->displaySalesTaxWithGrandTotal($order->getStore())) {
                    $tempCounter = 0;
                    foreach ($totalsForDisplay as $totalForDisplay) {
                        $tempCounter++;
                        $totalsCounter++;
                        $maxToDisplay = count($totalsForDisplay) - 1;
                        if ($this->taxConfig->displaySalesFullSummary($order->getStore())) {
                            $maxToDisplay = count($totalsForDisplay);
                        }
                        if ($tempCounter > 1 && $tempCounter < $maxToDisplay) {
                            if ($tempCounter == 2 && $this->taxConfig->displaySalesFullSummary($order->getStore())) {
                                // Display custom tax rates
                                $taxRates = $this->taxHelper->getTaxRates($source);
                                foreach ($taxRates as $taxRate) {
                                    $totalsCounter++;
                                    $taxPercent = $taxRate['title'];
                                    $totals[] = [
                                        'label' => (string) __('Tax'),
                                        'amount' => $taxRate['tax_amount'],
                                        'base_amount' => $taxRate['base_tax_amount'],
                                        'tax_percent' => $taxPercent,
                                        'amount_prefix' => '',
                                        'is_bold' => false,
                                        'is_grand_total' => false,
                                        'is_subtotal' => false,
                                        'is_tax' => true,
                                        'is_first' => $totalsCounter === 1,
                                        'is_last' => $totalsCounter === $totalsCount,
                                    ];
                                }
                            }
                            continue;
                        }
                        $label = str_replace(' ()', '', rtrim($totalForDisplay['label'], ':'));
                        $amount = $source->getDataUsingMethod($totalRenderer->getSourceField());
                        if ($totalRenderer->getSourceField() === null
                            || $totalRenderer->getSourceField() === '_'
                            || $amount === null
                            || $amount === false
                            || is_array($amount)) {
                            $amount = $totalRenderer->getAmount();
                        }
                        if ($totalRenderer->getSourceField() == 'weee_amount') {
                            $amount = $this->weeeHelper->getTotalAmounts($source->getAllItems(), $source->getStore());
                        }
                        $baseAmount = $source->getDataUsingMethod('base_' . $totalRenderer->getSourceField());
                        if ($totalRenderer->getSourceField() == 'adjustment_negative'
                            || $totalRenderer->getSourceField() == 'discount_amount') {
                            $amount = abs($amount) * -1;
                            $baseAmount = abs($baseAmount) * -1;
                        }
                        $totals[] = [
                            'label' => (string) __($label),
                            'amount' => $totalForDisplay['amount'] ? $totalForDisplay['amount'] : $amount,
                            'base_amount' => $baseAmount,
                            'tax_percent' => 0,
                            'amount_prefix' => $totalRenderer->getAmountPrefix(),
                            'is_bold' => count($totalsForDisplay) === $tempCounter ? 1 : false,
                            'is_grand_total' => count($totalsForDisplay) === $tempCounter ? 1 : false,
                            'is_subtotal' => ($totalRenderer->getSourceField() == 'subtotal') ? 1 : false,
                            'is_first' => $totalsCounter === 1,
                            'is_tax' => false,
                            'is_last' => $totalsCounter === $totalsCount,
                        ];
                    }
                } else {
                    foreach ($totalsForDisplay as $totalForDisplay) {
                        $totalsCounter++;
                        $label = str_replace(' ()', '', rtrim($totalForDisplay['label'], ':'));
                        $amount = $source->getDataUsingMethod($totalRenderer->getSourceField());
                        if ($totalRenderer->getSourceField() === null
                            || $totalRenderer->getSourceField() === '_'
                            || $amount === null
                            || $amount === false
                            || is_array($amount)) {
                            $amount = $totalRenderer->getAmount();
                        }
                        if ($totalRenderer->getSourceField() == 'weee_amount') {
                            $amount = $this->weeeHelper->getTotalAmounts($source->getAllItems(), $source->getStore());
                        }
                        $baseAmount = $source->getDataUsingMethod('base_' . $totalRenderer->getSourceField());
                        if ($totalRenderer->getSourceField() == 'adjustment_negative'
                            || $totalRenderer->getSourceField() == 'discount_amount') {
                            $amount = abs($amount) * -1;
                            $baseAmount = abs($baseAmount) * -1;
                        }
                        $totals[] = [
                            'label' => (string) __($label),
                            'amount' => $totalForDisplay['amount'] ? $totalForDisplay['amount'] : $amount,
                            'base_amount' => $baseAmount,
                            'tax_percent' => 0,
                            'amount_prefix' => $totalRenderer->getAmountPrefix(),
                            'is_bold' => ($totalRenderer->getSourceField() == 'grand_total') ? 1 : false,
                            'is_grand_total' => ($totalRenderer->getSourceField() == 'grand_total') ? 1 : false,
                            'is_subtotal' => ($totalRenderer->getSourceField() == 'subtotal') ? 1 : false,
                            'is_first' => $totalsCounter === 1,
                            'is_tax' => false,
                            'is_last' => $totalsCounter === $totalsCount,
                        ];
                    }
                }
            }
        }
        // Rearrange the previous array data in new order with key
        $finalTotalArray = [];
        foreach ($totals as $totalsArray) {
            $wordMatch = "Discount";
            $searchString = $totalsArray['label'];
            if (strpos($searchString, $wordMatch) !== false) {
                $finalTotalArray[$wordMatch] = $totalsArray;
            } else {
                $finalTotalArray[$totalsArray['label']] = $totalsArray;
            }
        }
        
        if (isset($finalTotalArray['Discount']['base_amount'])) {
            $baseAmount = $finalTotalArray['Subtotal']['base_amount'] + $finalTotalArray['Discount']['base_amount'];
        } else {
            $baseAmount = $finalTotalArray['Subtotal']['base_amount'];
        }
        $finalTotalArray['Discount Subtotal'] = [
            'label' => (string) __('Discount Subtotal'),
            'amount' => $baseAmount,
            'base_amount' => $baseAmount,
            'tax_percent' => 0,
            'amount_prefix' => 0,
            'is_bold' => false,
            'is_grand_total' => false,
            'is_subtotal' => false,
            'is_first' => 0,
            'is_tax' => false,
            'is_last' => 0,
        ];

        $finalTotalArray['Delivery Fee'] = [
            'label' => (string) __('Delivery Fee'),
            'amount' => $source->getOrder()->getDeliveryFee() ?: 0,
            'base_amount' => $source->getOrder()->getDeliveryFee() ?: 0,
            'tax_percent' => 0,
            'amount_prefix' => 0,
            'is_bold' => false,
            'is_grand_total' => false,
            'is_subtotal' => false,
            'is_first' => 0,
            'is_tax' => false,
            'is_last' => 0,
        ];

        if (isset($finalTotalArray['Discount Subtotal'])) {
            // add new tax array in previous array and old array from discount subtotal array
            $arrDS = $finalTotalArray;
            $posDS = 2;
            $valDS = $finalTotalArray['Discount Subtotal'];
            $resultNew = array_merge(array_slice($arrDS, 0, $posDS), ["DST" => $valDS], array_slice($arrDS, $posDS));
            unset($resultNew['Discount Subtotal']);
        } else {
            $result = $finalTotalArray;
        }
        
        if (isset($finalTotalArray['Delivery Fee'])) {
            // add new tax array in previous array and old array from total array
            $arr = $resultNew;
            $pos = count($resultNew) -2 ;
            $val = $finalTotalArray['Delivery Fee'];
            $resultDF = array_merge(array_slice($arr, 0, $pos), ["DF" => $val], array_slice($arr, $pos));
            unset($resultDF['Delivery Fee']);
        } else {
            $result = $finalTotalArray;
        }

        if (isset($finalTotalArray['Tax'])) {
            // add new tax array in previous array and old array from total array
            $arr = $resultDF;
            $pos = count($resultDF) -1 ;
            $val = $finalTotalArray['Tax'];
            $result = array_merge(array_slice($arr, 0, $pos), ["TaxNew" => $val], array_slice($arr, $pos));
            unset($result['Tax']);
        } else {
            $result = $finalTotalArray;
        }

        $this->emulation->stopEnvironmentEmulation();

        foreach ($templateTotalParts as $templateTotalPart) {
            $totalsHtml = '';
            foreach ($result as $total) {
                $templateParts = $this->dataObject->create(
                    [
                        'template_html_full' => null,
                        'template_html' => $templateTotalPart,
                    ]
                );
                $processedTemplate = $this->variableTotalProcessor($source, $total, $templateParts);
                $totalsHtml .= $processedTemplate['body'];
            }
            $templateHtml = str_replace($templateTotalParts, $totalsHtml, $templateHtml);
        }

        $templateHtml = str_replace(['##totals_start##', '##totals_end##'], '', $templateHtml);
        
        return $templateHtml;
    }
}
