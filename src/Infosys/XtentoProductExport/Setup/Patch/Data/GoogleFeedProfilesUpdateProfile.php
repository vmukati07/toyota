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
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Patch to update Google feed profiles
 */
class GoogleFeedProfilesUpdateProfile implements DataPatchInterface
{
	const XTENTO_EXPORT_PROFILE = "xtento_productexport_profile";

	const XTENTO_EXPORT_DESTINATION = "xtento_productexport_destination";

	private ModuleDataSetupInterface $moduleDataSetup;

	private StoreRepositoryInterface $storeRepository;

	protected ResourceConnection $resource;

	protected AemBaseConfigProvider $aemBaseConfigProvider;

	protected CollectionFactory $categoryFactory;

	/**
	 * Initialize dependencies
	 *
	 * @param ModuleDataSetupInterface $moduleDataSetup
	 * @param StoreRepositoryInterface $storeRepository
	 * @param ResourceConnection $resource
	 * @param AemBaseConfigProvider $aemBaseConfigProvider
	 * @param CollectionFactory $categoryFactory
	 */
	public function __construct(
		ModuleDataSetupInterface $moduleDataSetup,
		StoreRepositoryInterface $storeRepository,
		ResourceConnection $resource,
		AemBaseConfigProvider $aemBaseConfigProvider,
		CollectionFactory $categoryFactory
	) {
		$this->moduleDataSetup = $moduleDataSetup;
		$this->storeRepository = $storeRepository;
		$this->_connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
		$this->aemBaseConfigProvider = $aemBaseConfigProvider;
		$this->categoryFactory = $categoryFactory;
	}

	/**
	 * Update Google feed profiles
	 */
	public function apply()
	{
		$this->moduleDataSetup->startSetup();

		//update profiles
		$this->updateGoogleFeedProfiles();
		$this->moduleDataSetup->endSetup();
	}

	/**
	 * Get all stores
	 *
	 * @return array
	 */
	public function getAllStores(): ?array
	{
		$storeList = $this->storeRepository->getList();
		return $storeList;
	}

	/**
	 * Update Google profiles
	 *
	 * @return void
	 */
	public function updateGoogleFeedProfiles()
	{
		$stores = $this->getAllStores();
		$data = [];
		foreach ($stores as $store) {
			$storeId = $store->getStoreId();
			$storeName = $store->getName();
			$googleCategory = $this->categoryMappingData();
			$categories = $this->categoryFactory->create()
				->addAttributeToSelect('*')
				->setStore($storeId);
			$googleCatData = array();
			foreach ($categories as $category) {
				if (array_key_exists($category->getUrlKey(), $googleCategory)) {
					$id = $category->getEntityId();
					$googleCatData[$id] = $googleCategory[$category->getUrlKey()];
				}
			}
			$categoryMapping = json_encode($googleCatData);
			$formattedStoreName = str_replace(' ', '_', strtolower($storeName));
			$fileName = "google_export_" . $formattedStoreName . ".xml";
			$title = $storeName . " data feed";
			$desc = "Google data feed for " . $storeName;
			$storeId = (int) $storeId;
			$link = $this->aemBaseConfigProvider->getAemDomain($storeId);

			$output_format = '<?xml version="1.0"?>
                <files> 
                <file filename="' . $fileName . '"> 
                <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
                <xsl:output method="xml" indent="yes" encoding="UTF-8"/><xsl:template match="/"><!-- IMPORTANT: ADJUST THIS! Use your currency, three letter code -->
                <xsl:variable name="currency"><xsl:text>USD</xsl:text></xsl:variable><rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
                    <channel>
                        <title>' . $title . '</title>
                        <link>' . $link . '</link>
                        <description>' . $desc . '</description>
						<xsl:for-each select="objects/object">            
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
                                </xsl:element>
								<xsl:element name="g:google_product_category"><xsl:value-of select="xtento_mapped_category"/></xsl:element>                                
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
                                </xsl:element> 
								<xsl:choose>
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
                                </xsl:element>
								<xsl:element name="g:price">
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
                                </xsl:if> 
								<xsl:element name="g:brand"><xsl:if test="string(brand)"><xsl:value-of select="brand"/></xsl:if></xsl:element>
                                <xsl:element name="g:gtin"><xsl:choose><xsl:when test="string(ean)"><xsl:value-of select="ean"/></xsl:when><xsl:otherwise><xsl:value-of select="upc"/></xsl:otherwise></xsl:choose></xsl:element> 
                                <xsl:element name="g:mpn"><xsl:if test="string(sku)"><xsl:value-of select="sku"/></xsl:if></xsl:element>
                                <xsl:element name="g:shipping_weight"><xsl:if test="string(weight)"><xsl:value-of select="weight"/><xsl:text> lbs</xsl:text></xsl:if></xsl:element>
								<xsl:element name="g:shipping_height"><xsl:if test="string(ship_height)"><xsl:value-of select="ship_height"/><xsl:text> in</xsl:text></xsl:if></xsl:element>
								<xsl:element name="g:shipping_width"><xsl:if test="string(ship_width)"><xsl:value-of select="ship_width"/><xsl:text> in</xsl:text></xsl:if></xsl:element>
								<xsl:element name="g:shipping_length"><xsl:if test="string(ship_length)"><xsl:value-of select="ship_length"/><xsl:text> in</xsl:text></xsl:if></xsl:element>
								<xsl:element name="g:shipping_label"><xsl:if test="string(google_shipping_label)"><xsl:value-of select="google_shipping_label"/></xsl:if></xsl:element>
                            </xsl:element>
                        </xsl:for-each>
                    </channel>
                </rss>
                </xsl:template>
                </xsl:stylesheet>
                </file>
                </files>';

			$conditionUpdate = "store_id =" . $storeId. " AND enabled=1";

			$profileData = [
				'xsl_template' => $output_format,
				'category_mapping' => $categoryMapping
			];

			$data[] = $profileData;
			if (!empty($data)) {
				$this->_connection->update(self::XTENTO_EXPORT_PROFILE, $profileData, $conditionUpdate);
			}
		}
	}

	/**
	 * get google product category for mapping
	 *
	 * @return array
	 */
	public function categoryMappingData()
	{
		return $mappCategory = [
			"parts-electrical-hev-control-computer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-control-computer-bev-or-fcev"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-hev-inverter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"all-products"  =>  "Vehicles & Parts",
			"parts"  =>  "Vehicles & Parts > Vehicle Parts & Accessories",
			"parts-body"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-convertible-parts"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-engine-fuel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Fluids",
			"parts-engine-fuel-engine-overhaul-gasket-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-oil-filter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-engine-fuel-radiator-water-outlet"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-water-pump"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-electrical"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-fcv-cooling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-engine-fuel-manifold"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-carburetor-assembly"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-drive-chassis"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-drive-chassis-clutch-master-cylinder"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-clutch-release-cylinder"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-clutch-pedal-flexible-hose"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transaxle-or-transmission-assy-gasket-kit-mtm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-extension-housing-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transaxle-or-transmission-assy-gasket-kit-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-assembly-gasket-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-propeller-shaft-universal-joint"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-front-drive-shaft"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-equipment-drive-shaft"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-rear-axle-housing-differential"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-rear-axle-shaft-hub"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-front-axle-hub"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-disc-wheel-wheel-cap"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels",
			"parts-drive-chassis-front-axle-arm-steering-knuckle"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-brake-booster-vacuum-tube"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-front-steering-gear-link"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-vane-pump-reservoir-power-steering"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-front-disc-brake-caliper-dust-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-rear-disc-brake-caliper-dust-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-rear-drum-brake-wheel-cylinder-backing-plate"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-brake-tube-clamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-rear-spring-shock-absorber"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"parts-drive-chassis-front-spring-shock-absorber"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"parts-drive-chassis-brake-master-cylinder"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-body-cowl-panel-windshield-glass"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-back-door-panel-glass"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-roof-panel-back-window-glass"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-front-panel-windshield-glass"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-mudguard-spoiler"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor",
			"parts-body-spoiler-side-mudguard"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"parts-drive-chassis-transaxle-assy-hv-or-ev-or-fcv"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-electrical-abs-vsc"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-electrical-hv-inverter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-fcv-stack-converter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-standard-tool"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Repair & Specialty Tools",
			"parts-electrical-battery-battery-cable"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Repair & Specialty Tools",
			"parts-engine-fuel-crankshaft-piston"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-cylinder-block"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-cylinder-head"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-fuel-injection-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-engine-fuel-ignition-coil-spark-plug"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-ignition-coil-spark-plug-glow-plug"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-caution-plate-name-plate-engine"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decals",
			"parts-body-caution-plate-exterior-interior"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decals",
			"parts-body-caution-plate"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decals",
			"parts-electrical-heating-air-conditioning-water-piping"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-electrical-ev-cooling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-drive-chassis-shift-lever-retainer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Shift Knobs",
			"parts-engine-fuel-timing-gear-cover-rear-end-plate"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-engine-oil-pump"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-engine-fuel-short-block-assembly"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engines",
			"parts-engine-fuel-engine-oil-cooler"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-engine-fuel-camshaft-valve"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-ventilation-hose"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-drive-chassis-front-axle-housing-differential"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-case-extension-housing"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-clutch-housing-transmission-case-mtm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transmission-case-oil-pan-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-extension-housing-mtm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-gear-shift-fork-lever-shaft-mtm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-valve-body-valve-lever"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-winch"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Towing",
			"parts-drive-chassis-power-take-off-case-gear"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-engine-fuel-mounting"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-air-cleaner"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-body-frame"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-electrical-heating-air-conditioning-compressor"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-engine-fuel-alternator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-drive-chassis-torque-converter-front-oil-pump-chain-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-timing-chain"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-timing-belt"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-drive-chassis-oil-cooler-tube-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-oil-cooler-tube-cvt"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-electrical-dc"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-dc-dc-converter-charger-ev-or-fcv"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-body-front-fender-apron-dash-panel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-engine-fuel-exhaust-gas-recirculation-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Exhaust",
			"parts-electrical-heating-air-conditioning-cooler-piping"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-electrical-wiring-clamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-inverter-cooling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-body-front-bumper-bumper-stay"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-radiator-grille"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-vacuum-piping"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-engine-fuel-exhaust-pipe"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Exhaust",
			"parts-electrical-ev-motor"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-engine-fuel-manifold-air-injection-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-electrical-cruise-control-auto-drive"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-fcv-intake-exhaust"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Exhaust",
			"parts-electrical-heating-air-conditioning-vacuum-piping"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-body-rear-floor-panel-rear-floor-member"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-electrical-switch-relay-computer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-body-suspension-crossmember-under-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"parts-engine-fuel-partial-engine-assembly"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engines",
			"parts-engine-fuel-distributor"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-body-floor-side-member"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-engine-fuel-carburetor"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-body-accelerator-link"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-body-fuel-tank-tube"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-engine-fuel-fuel-pump-pipe"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-engine-fuel-injection-nozzle"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-engine-fuel-fuel-filter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-engine-fuel-lpg-or-cng-regulator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-starter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-drive-chassis-transaxle-assy-cvt"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-electrical-electronic-fuel-injection-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-electrical-heating-air-conditioning-heater-unit-blower"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-electrical-heating-air-conditioning-cooler-unit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-engine-fuel-vacuum-pump"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-drive-chassis-clutch-release-fork"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-brake-pedal-bracket"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-pump-actuator-sequential-or-multi-mode-manual-transaxle"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-front-drum-brake-wheel-cylinder-backing-plate"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-torque-converter-front-oil-pump-cvt"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-drive-chassis-oil-cooler-tube-hv-or-ev-or-fcv"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-valve-body-oil-strainer-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-oil-pump-oil-cooler-pipe-mtm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-transmission-gear-mtm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-control-shaft-crossshaft"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-lever-shift-rod"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-gear"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-planetary-gear-reverse-piston-counter-gear-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-speedometer-driven-gear-mtm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Sensors & Gauges",
			"parts-drive-chassis-speedometer-driven-gear-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Sensors & Gauges",
			"parts-electrical-overdrive-electronic-controlled-transmission"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-steering-column-shaft"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-body-console-box-bracket"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-drive-chassis-power-take-off-lever-link"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-center-support-planetary-sun-gear-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-overdrive-gear-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-direct-clutch-low-brake-support"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-brake-band-multiple-disc-clutch-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-transfer-oil-pump"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-brake-no-3-1st-reverse-brake-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-drive-chassis-front-drive-clutch-gear"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-throttle-link-valve-lever-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-rear-oil-pump-governor-atm"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-valve-body-oil-strainer-hv-or-ev-or-fcv"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-body-roof-headlining-silencer-pad"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-electrical-interior-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-drive-chassis-diaphragm-cylinder-transfer-vacuum-actuator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-parking-brake-cable"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"parts-body-spare-wheel-carrier"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Tire Accessories",
			"parts-body-package-tray-panel-luggage-compartment-mat"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"parts-drive-chassis-power-steering-tube"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-height-control-auto-leveler"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"parts-drive-chassis-clutch-booster"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-steering-wheel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-body-floor-pan-lower-back-panel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-inside-trim-board"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-body-hood-front-fender"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-side-member"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-electrical-electronic-height-control"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-electronic-modulated-suspension"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-body-instrument-panel-glove-compartment"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-body-moulding"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-cab-mounting-body-mounting"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"parts-body-front-floor-panel-front-floor-member"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-rear-bumper-bumper-stay"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-mat-carpet"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"parts-body-floor-insulator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-deck-board-deck-trim-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Covers",
			"parts-body-floor-mat-silencer-pad"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"parts-electrical-fog-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-headlamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-rear-combination-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-front-turn-signal-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-reflex-reflector"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment",
			"parts-body-rear-moulding"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-rear-body-side-panel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-electrical-air-bag"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment > Motor Vehicle Airbag Parts",
			"parts-body-rear-ventilator-roof-ventilator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-body-seat-seat-track"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Seating",
			"parts-body-emblem-name-plate-exterior-interior"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decals",
			"parts-body-emblem-name-plate"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decals",
			"parts-body-rear-door-panel-glass"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-radiator-support-wind-guide"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-body-hood-lock-hinge"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks",
			"parts-body-engine-hood-lock"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks",
			"parts-body-luggage-compartment-door-lock"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks",
			"parts-electrical-windshield-washer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-rear-washer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-battery-carrier"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Repair & Specialty Tools",
			"parts-body-side-moulding"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-front-door-window-regulator-hinge"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-electrical-radio-receiver-amplifier-condenser"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle Amplifiers",
			"parts-electrical-heating-air-conditioning-control-air-duct"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-electrical-navigation-front-monitor-display"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle A/V Players & In-Dash Systems",
			"parts-body-armrest-visor"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-electrical-telephone-mayday"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security",
			"parts-body-front-door-panel-glass"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-electrical-indicator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Sensors & Gauges",
			"parts-body-lock-cylinder-set"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks",
			"accessories"  =>  "Vehicles & Parts > Vehicle Parts & Accessories",
			"accessories-interior"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-interior-vehicle-security"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security",
			"accessories-interior-vehicle-security-security-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Automotive Alarm Systems",
			"accessories-interior-floor-mats-interior-protection"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-carpet-floor-mats"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-exterior"  =>  "Vehicles & Parts > Vehicle Parts & Accessories",
			"accessories-exterior-body-and-paint-protection"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Paint > Motor Vehicle Body Paint",
			"accessories-exterior-body-and-paint-protection-car-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Covers",
			"accessories-exterior-exterior-styling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts",
			"accessories-exterior-exterior-styling-graphics"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decals",
			"accessories-exterior-towing"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Towing",
			"accessories-exterior-towing-ball-mount"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Towing",
			"accessories-exterior-towing-tow-hitch"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Towing",
			"accessories-interior-driver-convenience"  =>  "Vehicles & Parts > Vehicle Parts & Accessories",
			"accessories-interior-driver-convenience-first-aid-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security",
			"accessories-exterior-interior-styling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-exterior-interior-styling-interior-emblem"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Emblems & Hood Ornaments",
			"accessories-exterior-exterior-products"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts",
			"accessories-exterior-exterior-products-running-boards"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-truck-bed-products"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-truck-bed-products-rear-step-bumper"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-exterior-body-and-paint-protection-rear-bumper-applique"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-wheels"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels",
			"accessories-exterior-wheels-wheel-locks"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Wheel Clamps",
			"accessories-exterior-wheels-wheels"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels",
			"accessories-exterior-exterior-products-roof-rack"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Motor Vehicle Carrying Racks > Vehicle Cargo Racks",
			"accessories-exterior-body-and-paint-protection-rear-bumper-protector"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-styling-rear-spoiler"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-exterior-body-and-paint-protection-body-side-moldings"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor",
			"accessories-interior-cargo-management"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo",
			"accessories-interior-cargo-management-cargo-net"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Motor Vehicle Cargo Nets",
			"accessories-exterior-exterior-styling-rear-wind-deflector"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"accessories-exterior-exterior-styling-fender-flares"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-body-and-paint-protection-hood-protector"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-interior-interior-styling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-interior-interior-styling-interior-applique"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-products-fog-lights"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-exterior-exterior-styling-exterior-emblem"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Emblems & Hood Ornaments",
			"accessories-performance"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts",
			"accessories-performance-exterior-styling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts",
			"accessories-performance-exterior-styling-body-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-interior-driver-convenience-remote-engine-starter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Remote Keyless Systems",
			"accessories-exterior-cargo-management"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo",
			"accessories-exterior-cargo-management-cargo-hooks"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo",
			"accessories-exterior-wheels-wheel-covers"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels",
			"accessories-exterior-exterior-styling-mirror-caps"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Mirrors",
			"accessories-exterior-body-and-paint-protection-mudguards"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-wheels-wheel-inserts"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Wheel Parts",
			"accessories-interior-driver-convenience-armrest"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-interior-driver-convenience-cruise-control"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation"  =>  "Electronics > GPS Navigation Systems",
			"accessories-interior-audio-entertainment-navigation-historical-audio"  =>  "Electronics > GPS Navigation Systems",
			"accessories-exterior-towing-towing-wire-harnesses-and-adapters"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Towing",
			"accessories-performance-wheels"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels",
			"accessories-performance-wheels-wheels"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels",
			"accessories-exterior-exterior-styling-body-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-interior-driver-convenience-center-console-box"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-interior-audio-entertainment-navigation-satellite-radio"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-interior-driver-convenience-coin-holder-ashtray-cup"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-interior-driver-convenience-back-up-camera"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle Parking Cameras",
			"accessories-interior-driver-convenience-digital-clock"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation-subwoofer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle Subwoofers",
			"accessories-interior-audio-entertainment-navigation-interface-kit-for-ipod"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation-rear-seat-entertainment"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-interior-audio-entertainment-navigation-extension-box"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation-navigation-headunit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-interior-driver-convenience-auto-dimming-mirror"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Mirrors",
			"accessories-exterior-exterior-products-activity-mount"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Motor Vehicle Trailers > Utility & Cargo Trailers",
			"accessories-interior-floor-mats-interior-protection-all-weather-floor-liners"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-cargo-liner"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-interior-styling-door-sill-protectors"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor",
			"accessories-exterior-truck-bed-products-tonneau-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Covers > Tonneau Covers",
			"accessories-exterior-truck-bed-products-tailgate-lock"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Door Locks & Parts > Vehicle Door Locks & Locking Systems",
			"accessories-interior-video"  =>  "Electronics > Audio > Audio Accessories > Audio & Video Receiver Accessories",
			"accessories-interior-video-dashcam"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Dashboard Accessories",
			"accessories-exterior-wheels-lug-nuts"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Wheel Parts",
			"accessories-exterior-exterior-styling-exhaust-tip"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Exhaust",
			"accessories-interior-floor-mats-interior-protection-carpet-cargo-mat"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-carpet-trunk-mat"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-all-weather-floor-mats"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-cargo-tray"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-all-weather-cargo-mat"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-performance-exterior-styling-graphics"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decals",
			"accessories-exterior-exterior-styling-exterior-applique"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-body-and-paint-protection-front-skid-plate"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-products-tube-steps"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-exterior-exterior-styling-sport-bumper-trim"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-interior-cargo-management-seat-back-storage"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Vehicle Organizers",
			"accessories-exterior-body-and-paint-protection-front-end-mask"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-performance-interior-styling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-performance-interior-styling-seat-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Seating",
			"accessories-interior-interior-styling-seat-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Seating",
			"accessories-exterior-exterior-styling-spare-tire-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Tire Accessories",
			"accessories-interior-wheels"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels",
			"accessories-interior-wheels-wheel-locks"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Wheel Clamps",
			"accessories-interior-audio-entertainment-navigation-navigation-upgrade-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-performance-exterior-styling-front-grille"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-exterior-exterior-styling-front-grille"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-exterior-driver-convenience"  =>  "Vehicles & Parts > Vehicle Parts & Accessories",
			"accessories-exterior-driver-convenience-kick-sensor"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-exterior-exterior-products-rock-rails"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-performance-performance-suspension-chassis"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-performance-performance-suspension-chassis-jounce-bumper"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-interior-cargo-management-cargo-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Vehicle Organizers",
			"accessories-exterior-truck-bed-products-storage-box"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-truck-bed-products-bed-liner"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-exterior-wheels-spare-tire-lock"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Wheel Clamps",
			"accessories-exterior-truck-bed-products-mini-tie-down"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Vehicle Organizers",
			"accessories-exterior-exterior-products-cross-bars"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment > Motor Vehicle Roll Cages & Bars",
			"accessories-exterior-truck-bed-products-bed-cleats"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-truck-bed-products-d-rings"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-truck-bed-products-bed-rail"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-interior-exterior-products"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts",
			"accessories-interior-exterior-products-cross-bars"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment > Motor Vehicle Roll Cages & Bars",
			"accessories-exterior-exterior-products-ski-rack"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Motor Vehicle Carrying Racks > Vehicle Ski & Snowboard Racks",
			"accessories-exterior-exterior-products-bike-rack"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Motor Vehicle Carrying Racks > Vehicle Bicycle Racks",
			"accessories-exterior-wheels-center-cap"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Wheel Parts",
			"accessories-interior-audio-entertainment-navigation-wireless-headphones"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation-base-audio-headunit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-exterior-audio-entertainment-navigation"  =>  "Electronics > GPS Navigation Systems",
			"accessories-exterior-audio-entertainment-navigation-base-audio-headunit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-exterior-exterior-products-off-road-lights"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-exterior-exterior-styling-rear-window-spoiler"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"accessories-exterior-truck-bed-products-bed-mat"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-exterior-truck-bed-products-bed-extender"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Truck Bed Storage Boxes & Organizers",
			"accessories-performance-performance-suspension-chassis-shocks-and-struts"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-exterior-cargo-management-cargo-net"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Motor Vehicle Cargo Nets",
			"accessories-exterior-truck-bed-products-bed-net"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Motor Vehicle Cargo Nets",
			"accessories-interior-driver-convenience-coin-holder"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-interior-driver-convenience-home-link"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-interior-interior-styling-shift-knob"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Shift Knobs",
			"accessories-performance-wheels-wheel-covers"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Wheel Systems > Motor Vehicle Rims & Wheels > Automotive Rims & Wheels",
			"accessories-exterior-truck-bed-products-bed-step"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-exterior-exterior-styling-fender-vent-insert"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-performance-performance-engine"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engines",
			"accessories-performance-performance-engine-exhaust"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Exhaust",
			"accessories-interior-interior-styling-illuminated-door-sills"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-products-license-plate-frame"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle License Plate Frames",
			"accessories-exterior-performance-suspension-chassis"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-exterior-performance-suspension-chassis-suspension-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-interior-interior-styling-interior-light-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-exterior-exterior-styling-sunroof-wind-deflector"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"accessories-interior-driver-convenience-emergency-assistance-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment",
			"accessories-interior-driver-convenience-key-glove"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks",
			"accessories-interior-driver-convenience-owner-portfolio"  =>  "Media > Product Manuals > Vehicle Service Manuals",
			"accessories-interior-cargo-management-cargo-tote"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Vehicle Organizers",
			"accessories-performance-interior-styling-shift-knob"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Shift Knobs",
			"accessories-interior-audio-entertainment-navigation-audio-multimedia-cable"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-exterior-vehicle-security"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security",
			"accessories-exterior-vehicle-security-security-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Automotive Alarm Systems",
			"accessories-performance-performance-suspension-chassis-chassis-brace"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-interior-driver-convenience-key-finder"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks",
			"accessories-interior-driver-convenience-wind-screen"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-interior-exterior-styling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts",
			"accessories-interior-exterior-styling-rear-spoiler"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-exterior-truck-bed-products-bed-cargo-divider"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Vehicle Organizers",
			"accessories-exterior-exterior-products-camera-mount"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle Parking Cameras",
			"accessories-exterior-exterior-styling-lower-rocker-panel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-towing-trailer-ball"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Towing",
			"accessories-interior-driver-convenience-wireless-charger"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-performance-performance-suspension-chassis-performance-springs"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-performance-performance-suspension-chassis-suspension-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-interior-truck-bed-products"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Truck Bed Storage Boxes & Organizers",
			"accessories-interior-truck-bed-products-bed-lighting"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-exterior-truck-bed-products-bed-lighting"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-interior-cargo-management-cargo-organizer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Vehicle Organizers",
			"accessories-exterior-body-and-paint-protection-paint-protection-film"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Paint > Motor Vehicle Body Paint",
			"accessories-exterior-body-and-paint-protection-door-edge-film"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor",
			"accessories-exterior-floor-mats-interior-protection"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-exterior-floor-mats-interior-protection-all-weather-trunk-mat"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-audio-entertainment-navigation-portable-navigation-system"  =>  "Electronics > GPS Navigation Systems",
			"accessories-interior-audio-entertainment-navigation-hands-free-system"  =>  "Electronics > GPS Navigation Systems",
			"accessories-interior-driver-convenience-center-console-tray"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-exterior-cargo-management-cargo-organizer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Vehicle Organizers",
			"accessories-performance-floor-mats-interior-protection"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-performance-floor-mats-interior-protection-carpet-floor-mats"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-exterior-body-and-paint-protection-door-edge-guard"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor",
			"accessories-exterior-exterior-styling-rear-garnish"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-performance-body-and-paint-protection"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Paint > Motor Vehicle Body Paint",
			"accessories-performance-body-and-paint-protection-front-skid-plate"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-styling-splitter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-truck-bed-products-bed-rugs"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"accessories-interior-interior-styling-illuminated-trunk-sill"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-interior-interior-styling-illuminated-cargo-sills"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-products-rear-puddle-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-interior-interior-products"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-interior-interior-products-led-bulb"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-exterior-exterior-products-accent-lighting"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-exterior-truck-bed-products-bike-rack"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Motor Vehicle Carrying Racks > Vehicle Bicycle Racks",
			"accessories-interior-interior-products-console-safe"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-interior-driver-convenience-charge-cable"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics",
			"accessories-interior-driver-convenience-power-port"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"accessories-exterior-driver-convenience-ev-charger"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"accessories-performance-engine-accessories"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"accessories-performance-engine-accessories-oil-cap"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"accessories-performance-performance-suspension-chassis-strut-tie-brace"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-performance-performance-suspension-chassis-sway-bar"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-performance-performance-engine-air-intake-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"accessories-performance-engine-accessories-air-filter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"accessories-performance-engine-accessories-radiator-cap"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"accessories-performance-performance-drivetrain"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"accessories-performance-performance-drivetrain-quickshifter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"accessories-performance-performance-drivetrain-clutch"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"accessories-performance-performance-suspension-chassis-brakes"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"accessories-performance-performance-suspension-chassis-brake-pads"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Braking",
			"accessories-interior-performance-suspension-chassis"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-interior-performance-suspension-chassis-suspension-kit"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-performance-performance-engine-supercharger"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"accessories-performance-engine-accessories-oil-filter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"accessories-performance-engine-accessories-engine-cover"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"accessories-exterior-performance-suspension-chassis-sway-bar"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"accessories-exterior-truck-bed-products-camper-shell"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-exterior-styling-rear-taillight-lens"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"accessories-performance-truck-bed-products"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Truck Bed Storage Boxes & Organizers",
			"accessories-performance-truck-bed-products-bed-step"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"accessories-exterior-exterior-products-side-puddle-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-windshield-wiper"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-body-front-ventilator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-body-room-separator-glass"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-quarter-window"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-electrical-speaker"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle Speakers",
			"parts-body-rear-seat-seat-track"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Seating",
			"parts-body-roof-panel-back-panel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-seat-rail"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Seating",
			"parts-body-tool-box-license-plate-bracket"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo",
			"parts-body-slide-roller-rail"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-front-seat-seat-track"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Seating",
			"parts-body-inside-trim-board-door-opening-trim-moulding"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-body-rear-door-lock-handle"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Door Locks & Parts > Vehicle Door Locks & Locking Systems",
			"parts-body-back-door-lock-handle"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Door Locks & Parts > Vehicle Door Locks & Locking Systems",
			"parts-body-back-door-lock-hinge"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Door Locks & Parts > Vehicle Door Locks & Locking Systems",
			"parts-body-seat-belt"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment > Vehicle Seat Belts",
			"parts-body-side-window"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-electrical-center-stop-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-body-front-door-ventilator-window"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-electrical-rear-wiper"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-rear-body-top-curtain-roof-panel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-roof-side-ventilator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-body-rear-body-mounting"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-seat-belt-child-restraint-seat"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Seating",
			"parts-body-package-tray-panel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-cover-top"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Covers",
			"parts-body-rear-body-floor-fender"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-rear-body-guard-frame-tail-gate"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-room-curtain-room-rack"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-body-rear-body-assembly"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-body-rear-door-window-regulator-hinge"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-front-door-lock-handle"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Door Locks & Parts > Vehicle Door Locks & Locking Systems",
			"parts-body-ash-receptacle"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-electrical-mirror"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Mirrors",
			"parts-electrical-anti-theft-device"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks",
			"parts-electrical-wireless-door-lock"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Door Locks & Parts > Vehicle Door Locks & Locking Systems",
			"parts-body-separation-door-panel-glass"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Window Parts & Accessories",
			"parts-body-body-stripe"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Decals",
			"parts-body-front-moulding"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Frame & Body Parts",
			"parts-electrical-back-up-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-rear-license-plate-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-engine-fuel-lpg-or-cng-injection-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-urea-tank-tube"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-electrical-antenna"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-daytime-running-lamp-or-illumination-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-spot-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Repair & Specialty Tools",
			"parts-electrical-side-turn-signal-lamp-outer-mirror-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-rear-fog-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-front-marker-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-front-clearance-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-rear-side-marker-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-meter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Repair & Specialty Tools",
			"parts-electrical-headlamp-cleaner"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Cleaning > Vehicle Glass Cleaners",
			"parts-drive-chassis-transfer-vacuum-piping"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-electrical-electronic-diesel-injection-control-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-electrical-clearance-back-sonar"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment",
			"parts-electrical-passive-belt-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment > Vehicle Seat Belts",
			"parts-electrical-camera-rear-monitor-display"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Electronics > Motor Vehicle Parking Cameras",
			"parts-electrical-electronic-controlled-transmission"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-electrical-tire-pressure-warning-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-lane-keeping-assist"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment",
			"parts-electrical-pre-collision-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-traction-control"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-door-motor-door-solenoid"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks > Vehicle Door Locks & Parts",
			"parts-electrical-seat-motor-seat-heater"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Seating",
			"parts-electrical-horn"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Safety Equipment",
			"parts-electrical-air-purifier-or-ion-generator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-electrical-heating-air-conditioning-set"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Climate Control",
			"parts-electrical-automatic-light-control-system-conlight"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-active-control-suspension-electrical-parts"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Suspension Parts",
			"parts-electrical-eco-run-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-control-computer-ev-or-fcv"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-hv-control-computer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-electronic-controled-diesel-ecd"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-electrical-power-steering-computer"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-engine-fuel-fuel-pipe-clamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-engine-fuel-v-belt"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-injection-pump-assembly"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-electrical-inverter-ev-or-fcv"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-engine-fuel-air-pump"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-fuel-injection-pump-body"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Fuel Systems",
			"parts-body-floor-mat"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Carpet & Upholstery",
			"parts-engine-fuel-intercooler-sub-radiator"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-electrical-spot-lamp-search-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Repair & Specialty Tools",
			"parts-electrical-night-view"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Maintenance, Care & Decor > Vehicle Decor > Vehicle Dashboard Accessories",
			"parts-electrical-cornering-lamp"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Lighting",
			"parts-electrical-junction-box"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"accessories-interior-performance-engine"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engines",
			"accessories-interior-performance-engine-air-intake-system"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"accessories-exterior-exterior-products-cat-shield"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Safety & Security > Vehicle Alarms & Locks",
			"accessories-exterior-interior-products"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"accessories-exterior-interior-products-divider"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Vehicle Storage & Cargo > Vehicle Organizers",
			"parts-interior-styling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-interior-styling-steering-wheel"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Interior Fittings",
			"parts-drive-chassis-transaxle-assy-hev-or-bev-or-fcev"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Transmission & Drivetrain Parts",
			"parts-engine-accessories"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Parts",
			"parts-engine-accessories-oil-filter"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-oil-cooler-tube-hev-or-bev-or-fcev"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Engine Oil Circulation",
			"parts-electrical-bev-cooling"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems",
			"parts-electrical-dc-dc-converter-charger-bev-or-fcev"  =>  "Vehicles & Parts > Vehicle Parts & Accessories > Motor Vehicle Parts > Motor Vehicle Power & Electrical Systems"
		];
	}

	/**
	 * Dependencies function
	 *
	 * @return array
	 */
	public static function getDependencies()
	{
		return [GoogleFeedProfilesUpdate::class];
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
