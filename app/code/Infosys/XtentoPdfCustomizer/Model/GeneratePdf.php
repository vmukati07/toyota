<?php

/**
 * @package     Infosys_XtentoPdfCustomizer
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\XtentoPdfCustomizer\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject\Factory as DataObject;
use Magento\Framework\ObjectManagerInterface;
use Xtento\PdfCustomizer\Model\PdfTemplate;
use Xtento\PdfCustomizer\Model\Source\TemplateType;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Xtento\PdfCustomizer\Helper\Variable\Processors\Output as OutputHelper;
use Xtento\PdfCustomizer\Helper\Variable\Processors\ProductOutput as ProductOutputHelper;
use Xtento\PdfCustomizer\Helper\Data as DataHelper;
use Xtento\PdfCustomizer\Model\PdfTemplateFactory;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DirectoryList;
use Xtento\XtCore\Helper\Utils;
use Xtento\PdfCustomizer\Helper\GeneratePdf as CoreGeneratePdf;

/**
 * Helper class to generate PDFs from code calls - for YOU, the developer :)
 * See mainly: generatePdfForCollection, generatePdfForObject
 *
 * Class GeneratePdf
 *
 * Infosys\XtentoPdfCustomizer\Helper
 */
class GeneratePdf extends CoreGeneratePdf
{
    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var OutputHelper
     */
    protected $outputHelper;

    /**
     * @var ProductOutputHelper
     */
    protected $productOutputHelper;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var PdfTemplateFactory
     */
    protected $pdfTemplateFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var DataObject
     */
    protected $dataObject;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Utils
     */
    protected $utilsHelper;

    /**
     * GeneratePdf constructor.
     *
     * @param Context $context
     * @param DateTime $dateTime
     * @param OutputHelper $outputHelper
     * @param ProductOutputHelper $productOutputHelper
     * @param DataHelper $dataHelper
     * @param PdfTemplateFactory $pdfTemplateFactory
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param DataObject $dataObject
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param ObjectManagerInterface $objectManager
     * @param File $file
     * @param DirectoryList $directoryList
     * @param Utils $utilsHelper
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        OutputHelper $outputHelper,
        ProductOutputHelper $productOutputHelper,
        DataHelper $dataHelper,
        PdfTemplateFactory $pdfTemplateFactory,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        DataObject $dataObject,
        CustomerRepositoryInterface $customerRepositoryInterface,
        ObjectManagerInterface $objectManager,
        File $file,
        DirectoryList $directoryList,
        Utils $utilsHelper
    ) {
        $this->dateTime = $dateTime;
        $this->outputHelper = $outputHelper;
        $this->productOutputHelper = $productOutputHelper;
        $this->dataHelper = $dataHelper;
        $this->pdfTemplateFactory = $pdfTemplateFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->dataObject = $dataObject;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->objectManager = $objectManager;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->utilsHelper = $utilsHelper;
        parent::__construct(
            $context,
            $dateTime,
            $outputHelper,
            $productOutputHelper,
            $dataHelper,
            $pdfTemplateFactory,
            $extensibleDataObjectConverter,
            $dataObject,
            $customerRepositoryInterface,
            $objectManager,
            $file,
            $directoryList,
            $utilsHelper
        );
    }
    
    /**
     * Process Object Method
     *
     * @param object $object
     * @param int $templateId
     *
     * @return bool|object
     */
    protected function processObject($object, $templateId = null)
    {
        if ($id = $object->getPdfOriginalId()) {
            $object->setId($id);
        }

        if ($templateId === null) {
            $types = array_flip(TemplateType::TYPES);
            $entityType = $object->getEntityType();
            // Get default template if no template ID has been specified
            $templateId = $this->dataHelper->getDefaultTemplate(
                $object,
                $types[$entityType]
            )->getId();
        }

        if ($templateId instanceof PdfTemplate) {
            $templateModel = $templateId; // For PDF Preview
        } else {
            $templateModel = $this->pdfTemplateFactory->create()->load($templateId);
        }

        if (!$templateModel->getId()) {
            return false;
        }

        if ($templateModel->getTemplateType() == TemplateType::TYPE_PRODUCT) {
            $helper = $this->productOutputHelper;
        } else {
            $helper = $this->outputHelper;
        }
        $helper->setSource($object);
        $helper->setTemplate($templateModel);

        // Get customer
        /* $customerId = false;
        if ($object->getCustomerId()) {
            $customerId = $object->getCustomerId();
        }
        if ($object->getOrder() && $object->getOrder()->getCustomerId()) {
            $customerId = $object->getOrder()->getCustomerId();
        }
        if ($templateModel->getTemplateType() != TemplateType::TYPE_PRODUCT && $customerId) {
            $pseudoCustomer = $this->getCustomer($customerId);
            $helper->setCustomer($pseudoCustomer);
        }
        */

        /*
         * Removed customer data fetch.
         * This will cause customer data to be looked up the same as a guest order
         *
         * Customers are shared across websites
         * The website ID for the customer is the website it was created on.
         * because of this, the dealer may not be authorized to view the customer data.
         */
        
        return $templateModel;
    }
}
