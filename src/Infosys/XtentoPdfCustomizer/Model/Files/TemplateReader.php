<?php

/**
 * @package     Infosys_XtentoPdfCustomizer
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\XtentoPdfCustomizer\Model\Files;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Xtento\PdfCustomizer\Model\Source\TemplateType;
use Xtento\PdfCustomizer\Model\Files\TemplateReader as CoreTemplateReader;

/**
 * Class TemplateReader
 *
 * Infosys\XtentoPdfCustomizer\Model\Files
 */
class TemplateReader extends CoreTemplateReader
{

    public const PDF_TEMPLATES_DIR = 'pdftemplates';

    public const HTML = 'html';

    public const CSS = 'css';

    public const PREVIEW = 'preview';

    /**
     * @var ModuleDirReader
     */
    private ModuleDirReader $moduleDirReader;

    /**
     * @var File
     */
    private File $file;

    /**
     * TemplateReader constructor.
     *
     * @param File $file
     * @param ModuleDirReader $moduleDirReader
     */
    public function __construct(
        File $file,
        ModuleDirReader $moduleDirReader
    ) {
        $this->file = $file;
        $this->moduleDirReader = $moduleDirReader;
    }

    /**
     * Template sLocation function
     *
     * @return string
     */
    private function templatesLocation()
    {
        $viewDir = $this->moduleDirReader->getModuleDir(
            Dir::MODULE_VIEW_DIR,
            'Xtento_PdfCustomizer'
        );
        return $viewDir . DIRECTORY_SEPARATOR . self::PDF_TEMPLATES_DIR;
    }

    /**
     * Template Location function
     *
     * @return string
     */
    private function customTemplatesLocation()
    {
        /** Infosys_XtentoPdfCustomizer File Path */
        $viewDir = $this->moduleDirReader->getModuleDir(
            Dir::MODULE_VIEW_DIR,
            'Infosys_XtentoPdfCustomizer'
        );
        return $viewDir . DIRECTORY_SEPARATOR . self::PDF_TEMPLATES_DIR;
    }

    /**
     * Html Templates function
     *
     * @return array
     */
    public function htmlTemplates()
    {
        $path = $this->templatesLocation() . DIRECTORY_SEPARATOR . self::HTML;
        $files = $this->file->readDirectory($path);
        $fileNames = [];

        foreach ($files as $file) {
            //@codingStandardsIgnoreLine
            $fileNames[] = basename($file);
        }

        return $fileNames;
    }

    /**
     * Create Insert Array function
     *
     * @param string $templateName
     * @return array
     */
    public function createInsertArray($templateName)
    {
        /**
         * Here we are adding custom template condition by creating a new method to get data from custom module
         */
        if ($templateName == "simple2_order_portrait") {
            $templatesLocation = $this->customTemplatesLocation();
        } else {
            $templatesLocation = $this->templatesLocation();
        }

        $htmlPath = $templatesLocation . DIRECTORY_SEPARATOR . self::HTML . DIRECTORY_SEPARATOR;
        $htmlContents = $this->file->fileGetContents($htmlPath . $templateName . '.html');

        $cssPath = $templatesLocation . DIRECTORY_SEPARATOR . self::CSS . DIRECTORY_SEPARATOR;
        $cssContents = $this->file->fileGetContents($cssPath . $templateName . '.css');

        $thumbnailPath = $templatesLocation . DIRECTORY_SEPARATOR . self::PREVIEW . DIRECTORY_SEPARATOR;
        
        try {
            $thumbnailImage = base64_encode($this->file->fileGetContents($thumbnailPath . $templateName . '.jpg'));
        } catch (\Exception$e) {
            $thumbnailImage = base64_encode($this->file->fileGetContents($thumbnailPath . '_placeholder.jpg'));
        }

        $name = ucwords(str_replace('_', ' ', $templateName));
        $name = preg_replace('/(.*)(\d)(.*)/', '$1$3 (Variant $2)', $name);
        $name = str_replace(['Portrait', 'Landscape'], ['(Portrait)', '(Landscape)'], $name);
        $typeString = explode('_', $templateName);

        $type = array_flip(TemplateType::TYPES)[$typeString[1]];

        $orientation = 1;
        if ($typeString[2] === 'landscape') {
            $orientation = 2;
        }

        $top = 50;
        $bottom = 20;
        $right = 20;
        $left = 20;

        if (preg_match('/^(stylish|design)/', $typeString[0])) {
            $top = 0;
            $bottom = 15;
            $right = 0;
            $left = 0;

            if ($typeString[1] == 'product') {
                $bottom = 0;
            }
        }

        if ($typeString[1] !== 'product') {
            $filename = $typeString[1] . '_{{var increment_id}}.pdf';
        } else {
            $filename = $templateName . '.pdf';
        }

        $data = [
            'store_id'             => 0,
            'is_active'            => 1,
            'template_name'        => $name,
            'template_description' => $name,
            'template_default'     => 1,
            'template_type'        => $type,
            'template_html'        => $htmlContents,
            'template_css'         => $cssContents,
            'template_file_name'   => $filename,
            'template_paper_form'  => 1,
            'template_custom_form' => 0,
            'template_custom_h'    => 25,
            'template_custom_w'    => 25,
            'template_custom_t'    => $top,
            'template_custom_b'    => $bottom,
            'template_custom_l'    => $left,
            'template_custom_r'    => $right,
            'template_paper_ori'   => $orientation,
            'thumbnail'            => $thumbnailImage,
            'customer_group_id'    => '0',
            'creation_time'        => time(),
            'update_time'          => time(),
            'attachments'          => '',
        ];

        return $data;
    }
}
