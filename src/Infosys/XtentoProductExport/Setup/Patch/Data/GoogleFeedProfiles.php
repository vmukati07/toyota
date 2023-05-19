<?php
/**
 * @package     Infosys/XtentoProductExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Infosys\AemBase\Model\AemBaseConfigProvider;

/**
 * Patch to create Google feed profiles
 */
class GoogleFeedProfiles implements DataPatchInterface
{
    const XTENTO_EXPORT_PROFILE = "xtento_productexport_profile";

    const XTENTO_EXPORT_DESTINATION = "xtento_productexport_destination";

    private ModuleDataSetupInterface $moduleDataSetup;

    private StoreRepositoryInterface $storeRepository;

    protected ResourceConnection $resource;

    protected AemBaseConfigProvider $aemBaseConfigProvider;

    /**
     * Initialize dependencies
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StoreRepositoryInterface $storeRepository
     * @param ResourceConnection $resource
     * @param AemBaseConfigProvider $aemBaseConfigProvider
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StoreRepositoryInterface $storeRepository,
        ResourceConnection $resource,
        AemBaseConfigProvider $aemBaseConfigProvider
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->storeRepository = $storeRepository;
        $this->_connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->aemBaseConfigProvider = $aemBaseConfigProvider;
    }

    /**
     * Create Google feed profiles
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        //create profiles
        $this->createGoogleFeedProfiles();
        $this->moduleDataSetup->endSetup();
    }

    /**
     * Get all stores
     *
     * @return array
     */
    public function getAllStores() : ?array
    {
        $storeList = $this->storeRepository->getList();
        return $storeList;
    }

    /**
     * Create Google profiles
     *
     * @return void
     */
    public function createGoogleFeedProfiles()
    {
        $stores = $this->getAllStores();
        $data = [];
        
        foreach ($stores as $store) {
            $storeId = $store->getStoreId();
            $storeName = $store->getName();
            $formattedStoreName = str_replace(' ', '_', strtolower($storeName));
            $fileName = "google_export_".$formattedStoreName.".xml";
            $title = $storeName." data feed";
            $desc = "Google data feed for ".$storeName;
            $link = $this->aemBaseConfigProvider->getAemPath();

            //check if any export destination available
            $query = $this->_connection->select()->from(self::XTENTO_EXPORT_DESTINATION, 'destination_id');
            $destination_id = $this->_connection->fetchCol($query);
            
            $output_format = '<?xml version="1.0"?>
                <files> 
                <file filename="'.$fileName.'"> 
                <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
                <xsl:output method="xml" indent="yes" encoding="UTF-8"/><xsl:template match="/"><!-- IMPORTANT: ADJUST THIS! Use your currency, three letter code -->
                <xsl:variable name="currency"><xsl:text>USD</xsl:text></xsl:variable><rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
                    <channel>
                        <title>'.$title.'</title>
                        <link>'.$link.'</link>
                        <description>'.$desc.'</description>        <xsl:for-each select="objects/object">
                        
                            <xsl:element name="item">
                                <xsl:element name="g:id"><xsl:value-of select="sku"/></xsl:element> 
                                <xsl:element name="title"><xsl:value-of select="name"/></xsl:element>            
                                <xsl:element name="description">
                                    <xsl:choose>
                                    <xsl:when test="string(customer_copy_comments)">
                                    <xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text><xsl:value-of select="substring(php:functionString(\'str_replace\',php:functionString(\'chr\',34),\'\',php:functionString(\'strip_tags\',customer_copy_comments)), 1, 10000)"/><xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
                                    </xsl:when>
                                    <xsl:otherwise>
                                    <xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text><xsl:value-of select="substring(php:functionString(\'str_replace\',php:functionString(\'chr\',34),\'\',php:functionString(\'strip_tags\',parent_item/customer_copy_comments)), 1, 10000)"/><xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
                                    </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:element>                <xsl:element name="g:google_product_category"><xsl:value-of select="google_product_category"/></xsl:element>
                                
                                <!-- Forming the Product link based on the AEM Path and AEM Product Path - START -->
                                <xsl:element name="link">
                                    <xsl:value-of select="php:functionString(\'Infosys\AemBase\Helper\Xsl::getProductPath\', store_id, url_key)"/>
                                </xsl:element>
                                <!-- Forming the Product link based on the AEM Path and AEM Product Path - END -->
                                <xsl:variable name="img">
                                    <xsl:choose>
                                    <xsl:when test="string(image) and not(contains(image,\'no_selection\'))"><xsl:value-of select="image"/></xsl:when>
                                    <xsl:otherwise>
                                    <xsl:value-of select="parent_item/image" />
                                    </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:variable>
                                <xsl:element name="g:image_link">
                                    <xsl:value-of select="$img" /> 
                                </xsl:element>                 <xsl:choose>
                                    <xsl:when test="string(images/image[2]/url)">
                                    <xsl:for-each select="images/image">
                                        <xsl:if test="not(url = $img) and not(position() > 9)">
                                            <xsl:element name="g:additional_image_link">
                                                <xsl:value-of select="url" />    
                                            </xsl:element>
                                        </xsl:if>
                                    </xsl:for-each>
                                    </xsl:when>
                                    <xsl:otherwise>
                                    <xsl:for-each select="parent_item/images/image">
                                        <xsl:if test="not(url = $img) and not(position() > 9)">
                                            <xsl:element name="g:additional_image_link">
                                                <xsl:value-of select="url" />    
                                            </xsl:element>
                                        </xsl:if>
                                    </xsl:for-each>
                                    </xsl:otherwise>
                                </xsl:choose>       
                                <xsl:element name="g:availability">
                                        <xsl:choose>
                            <xsl:when test="type_id=\'configurable\' and count(child_products/child_product[qty > 0 or stock/qty > 0]) = 0"><xsl:text>out of stock</xsl:text></xsl:when>
                                        <xsl:when test="stock/manage_stock = 0 or stock/qty  > 0 or qty > 0 or count(child_products/child_product[stock/qty > 0 or qty > 0]) > 0"><xsl:text>in stock</xsl:text></xsl:when>
                                        <xsl:otherwise><xsl:text>out of stock</xsl:text></xsl:otherwise>
                                        </xsl:choose>
                                </xsl:element>                <xsl:element name="g:price">
                        <xsl:choose>
                        <xsl:when test="string(original_price)"><xsl:value-of select="php:functionString(\'number_format\', sum(original_price), 2, \'.\', \'\')"/><xsl:value-of select="concat(\' \', $currency)"/></xsl:when>
                        <xsl:otherwise>
                        <xsl:value-of select="php:functionString(\'number_format\', sum(price), 2, \'.\', \'\')"/><xsl:value-of select="concat(\' \', $currency)"/>
                        </xsl:otherwise>
                        </xsl:choose>
                        </xsl:element>
                                
                                <xsl:if test="special_price > 0 and special_price_active = 1">
                                    <xsl:element name="g:sale_price"><xsl:value-of select="php:functionString(\'number_format\', sum(special_price), 2, \'.\', \'\')"/><xsl:value-of select="concat(\' \', $currency)"/></xsl:element>
                                    <xsl:if test="string(special_from_date) and string(special_to_date)">
                                        <xsl:element name="g:sale_price_effective_date">
                                        <xsl:value-of select="concat(php:functionString(\'substr\',special_from_date,0,10),\'T\',php:functionString(\'substr\',special_from_date,11,5),\'+0100/\',php:functionString(\'substr\',special_to_date,0,10),\'T\',php:functionString(\'substr\',special_to_date,11,5),\'+0100\')" />
                                        </xsl:element>
                                    </xsl:if>
                                </xsl:if>                <xsl:element name="g:brand"><xsl:if test="string(ship_length)"><xsl:value-of select="brand"/></xsl:if></xsl:element>
                                <xsl:element name="g:gtin"><xsl:choose><xsl:when test="string(ean)"><xsl:value-of select="ean"/></xsl:when><xsl:otherwise><xsl:value-of select="upc"/></xsl:otherwise></xsl:choose></xsl:element> 
                                <xsl:element name="g:mpn"><xsl:choose><xsl:when test="string(part_number)"><xsl:value-of select="part_number"/></xsl:when><xsl:otherwise><xsl:value-of select="sku"/></xsl:otherwise></xsl:choose></xsl:element>        <xsl:element name="g:shipping_weight"><xsl:if test="string(weight)"><xsl:value-of select="weight"/><xsl:text> lbs</xsl:text></xsl:if></xsl:element>
                        <xsl:element name="g:shipping_height"><xsl:if test="string(ship_height)"><xsl:value-of select="ship_height"/></xsl:if></xsl:element>
                        <xsl:element name="g:shipping_width"><xsl:if test="string(ship_width)"><xsl:value-of select="ship_width"/></xsl:if></xsl:element>
                        <xsl:element name="g:shipping_length"><xsl:if test="string(ship_length)"><xsl:value-of select="ship_length"/></xsl:if></xsl:element>
                        <xsl:element name="g:shipping_label"><xsl:if test="string(google_shipping_label)"><xsl:value-of select="google_shipping_label"/></xsl:if></xsl:element>
                            </xsl:element>
                        </xsl:for-each>
                    </channel>
                </rss>
                </xsl:template>
                </xsl:stylesheet>
                </file>
                </files>';
                
            $conditions = '{"type":"Xtento\\\ProductExport\\\Model\\\Export\\\Condition\\\Combine","attribute":null,"operator":null,"value":"1","is_value_processed":null,"aggregator":"all","conditions":[{"type":"Xtento\\\ProductExport\\\Model\\\Export\\\Condition\\\Product\\\Found","attribute":null,"operator":null,"value":"1","is_value_processed":null,"aggregator":"all","conditions":[{"type":"Xtento\\\ProductExport\\\Model\\\Export\\\Condition\\\Product","attribute":"customer_copy_comments","operator":"!=","value":"","is_value_processed":false}]}]}';
            $profileData = [
                'entity' => 'product',
                'enabled' => 1,
                'name' => 'Google Shopping Feed - '.$storeName,
                'store_id' => $storeId,
                'output_type' => 'xsl',
                'xsl_template' => $output_format,
                'export_url_remove_store' => 1,
                'export_filter_instock_only' => 0,
                'export_filter_product_visibility' => 4,
                'export_filter_product_status' => 1,
                'conditions_serialized' => $conditions,
                'cronjob_enabled' => 0,
                'cronjob_frequency' => '0 0 * * *',
                'taxonomy_source' => 'google_en_US',
                'export_filter_include_in_feed' => 1
            ];

            if (!empty($destination_id)) {
                $profileData['destination_ids'] = $destination_id[0];
            }

            $data[] = $profileData;
        }

        //insert profiles data
        if (!empty($data)) {
            $this->_connection->insertMultiple(
                self::XTENTO_EXPORT_PROFILE,
                $data
            );
        }
    }
    
    /**
     * Dependencies function
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Aliases function
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
