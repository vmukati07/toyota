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
use Magento\Catalog\Helper\Category;

/**
 * Patch to update Google feed profiles
 */
class GoogleFeedProfilesUpdate implements DataPatchInterface
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
	 */
	public function __construct(
		ModuleDataSetupInterface $moduleDataSetup,
		StoreRepositoryInterface $storeRepository,
		ResourceConnection $resource,
		AemBaseConfigProvider $aemBaseConfigProvider,
		CollectionFactory $categoryFactory,
		Category $categoryHelper
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
								<xsl:element name="g:google_product_category"><xsl:value-of select="google_product_category"/></xsl:element>                                
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

			$conditions = '{"type":"Xtento\\\ProductExport\\\Model\\\Export\\\Condition\\\Combine","attribute":null,"operator":null,"value":"1","is_value_processed":null,"aggregator":"all","conditions":[{"type":"Xtento\\\ProductExport\\\Model\\\Export\\\Condition\\\Product\\\Found","attribute":null,"operator":null,"value":"1","is_value_processed":null,"aggregator":"all","conditions":[{"type":"Xtento\\\ProductExport\\\Model\\\Export\\\Condition\\\Product","attribute":"customer_copy_comments","operator":"!=","value":"","is_value_processed":false},{"type":"Xtento\\\ProductExport\\\Model\\\Export\\\Condition\\\Product","attribute":"weight","operator":">","value":0,"is_value_processed":false}]}]}';
			$conditionUpdate = "store_id =" . $storeId. " AND enabled=1";

			$profileData = [
				'xsl_template' => $output_format,
				'conditions_serialized' => $conditions,
				'category_mapping' => $categoryMapping,
				'export_filter_product_feed_image' => 1
			];

			$data[] = $profileData;
			if (!empty($data)) {
				$this->_connection->update(self::XTENTO_EXPORT_PROFILE, $profileData, $conditionUpdate);
			}
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

	/**
	 * get google product category for mapping
	 *
	 * @return array
	 */
	public function categoryMappingData()
	{
		return $mappCategory = [
			"parts-electrical-hev-control-computer" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-control-computer-bev-or-fcev" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-hev-inverter" => "Motor Vehicle Power & Electrical Systems",
			"all-products" => "Vehicles & Parts",
			"parts" => "Vehicle Parts & Accessories",
			"parts-body" => "Motor Vehicle Frame & Body Parts",
			"parts-body-convertible-parts" => "Motor Vehicle Frame & Body Parts",
			"parts-engine-fuel" => "Vehicle Fluids",
			"parts-engine-fuel-engine-overhaul-gasket-kit" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-oil-filter" => "Motor Vehicle Engine Oil Circulation",
			"parts-engine-fuel-radiator-water-outlet" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-water-pump" => "Motor Vehicle Engine Parts",
			"parts-electrical" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-fcv-cooling" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-engine-fuel-manifold" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-carburetor-assembly" => "Motor Vehicle Engine Parts",
			"parts-drive-chassis" => "Motor Vehicle Frame & Body Parts",
			"parts-drive-chassis-clutch-master-cylinder" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-clutch-release-cylinder" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-clutch-pedal-flexible-hose" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transaxle-or-transmission-assy-gasket-kit-mtm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-extension-housing-atm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transaxle-or-transmission-assy-gasket-kit-atm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-assembly-gasket-kit" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-propeller-shaft-universal-joint" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-front-drive-shaft" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-equipment-drive-shaft" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-rear-axle-housing-differential" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-rear-axle-shaft-hub" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-front-axle-hub" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-disc-wheel-wheel-cap" => "Automotive Rims & Wheels",
			"parts-drive-chassis-front-axle-arm-steering-knuckle" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-brake-booster-vacuum-tube" => "Motor Vehicle Braking",
			"parts-drive-chassis-front-steering-gear-link" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-vane-pump-reservoir-power-steering" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-front-disc-brake-caliper-dust-cover" => "Motor Vehicle Braking",
			"parts-drive-chassis-rear-disc-brake-caliper-dust-cover" => "Motor Vehicle Braking",
			"parts-drive-chassis-rear-drum-brake-wheel-cylinder-backing-plate" => "Motor Vehicle Braking",
			"parts-drive-chassis-brake-tube-clamp" => "Motor Vehicle Braking",
			"parts-drive-chassis-rear-spring-shock-absorber" => "Motor Vehicle Suspension Parts",
			"parts-drive-chassis-front-spring-shock-absorber" => "Motor Vehicle Suspension Parts",
			"parts-drive-chassis-brake-master-cylinder" => "Motor Vehicle Braking",
			"parts-body-cowl-panel-windshield-glass" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-back-door-panel-glass" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-roof-panel-back-window-glass" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-front-panel-windshield-glass" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-mudguard-spoiler" => "Vehicle Decor",
			"parts-body-spoiler-side-mudguard" => "Vehicle Decor Accessory Sets",
			"parts-drive-chassis-transaxle-assy-hv-or-ev-or-fcv" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-electrical-abs-vsc" => "Motor Vehicle Braking",
			"parts-electrical-hv-inverter" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-fcv-stack-converter" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-standard-tool" => "Vehicle Repair & Specialty Tools",
			"parts-electrical-battery-battery-cable" => "Vehicle Repair & Specialty Tools",
			"parts-engine-fuel-crankshaft-piston" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-cylinder-block" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-cylinder-head" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-fuel-injection-system" => "Motor Vehicle Fuel Systems",
			"parts-engine-fuel-ignition-coil-spark-plug" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-ignition-coil-spark-plug-glow-plug" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-caution-plate-name-plate-engine" => "Vehicle Decals",
			"parts-body-caution-plate-exterior-interior" => "Vehicle Decals",
			"parts-body-caution-plate" => "Vehicle Decals",
			"parts-electrical-heating-air-conditioning-water-piping" => "Motor Vehicle Climate Control",
			"parts-electrical-ev-cooling" => "Motor Vehicle Power & Electrical Systems",
			"parts-drive-chassis-shift-lever-retainer" => "Vehicle Shift Knobs",
			"parts-engine-fuel-timing-gear-cover-rear-end-plate" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-engine-oil-pump" => "Motor Vehicle Engine Oil Circulation",
			"parts-engine-fuel-short-block-assembly" => "Motor Vehicle Engines",
			"parts-engine-fuel-engine-oil-cooler" => "Motor Vehicle Engine Oil Circulation",
			"parts-engine-fuel-camshaft-valve" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-ventilation-hose" => "Motor Vehicle Engine Parts",
			"parts-drive-chassis-front-axle-housing-differential" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-case-extension-housing" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-clutch-housing-transmission-case-mtm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transmission-case-oil-pan-atm" => "Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-extension-housing-mtm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-gear-shift-fork-lever-shaft-mtm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-valve-body-valve-lever" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-winch" => "Motor Vehicle Towing",
			"parts-drive-chassis-power-take-off-case-gear" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-engine-fuel-mounting" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-air-cleaner" => "Motor Vehicle Engine Parts",
			"parts-body-frame" => "Motor Vehicle Frame & Body Parts",
			"parts-electrical-heating-air-conditioning-compressor" => "Motor Vehicle Climate Control",
			"parts-engine-fuel-alternator" => "Motor Vehicle Engine Parts",
			"parts-drive-chassis-torque-converter-front-oil-pump-chain-atm" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-timing-chain" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-timing-belt" => "Motor Vehicle Engine Parts",
			"parts-drive-chassis-oil-cooler-tube-atm" => "Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-oil-cooler-tube-cvt" => "Motor Vehicle Engine Oil Circulation",
			"parts-electrical-dc" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-dc-dc-converter-charger-ev-or-fcv" => "Motor Vehicle Power & Electrical Systems",
			"parts-body-front-fender-apron-dash-panel" => "Motor Vehicle Frame & Body Parts",
			"parts-engine-fuel-exhaust-gas-recirculation-system" => "Motor Vehicle Exhaust",
			"parts-electrical-heating-air-conditioning-cooler-piping" => "Motor Vehicle Climate Control",
			"parts-electrical-wiring-clamp" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-inverter-cooling" => "Motor Vehicle Climate Control",
			"parts-body-front-bumper-bumper-stay" => "Motor Vehicle Frame & Body Parts",
			"parts-body-radiator-grille" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-vacuum-piping" => "Motor Vehicle Fuel Systems",
			"parts-engine-fuel-exhaust-pipe" => "Motor Vehicle Exhaust",
			"parts-electrical-ev-motor" => "Motor Vehicle Power & Electrical Systems",
			"parts-engine-fuel-manifold-air-injection-system" => "Motor Vehicle Engine Parts",
			"parts-electrical-cruise-control-auto-drive" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-fcv-intake-exhaust" => "Motor Vehicle Exhaust",
			"parts-electrical-heating-air-conditioning-vacuum-piping" => "Motor Vehicle Climate Control",
			"parts-body-rear-floor-panel-rear-floor-member" => "Motor Vehicle Frame & Body Parts",
			"parts-electrical-switch-relay-computer" => "Motor Vehicle Power & Electrical Systems",
			"parts-body-suspension-crossmember-under-cover" => "Motor Vehicle Suspension Parts",
			"parts-engine-fuel-partial-engine-assembly" => "Motor Vehicle Engines",
			"parts-engine-fuel-distributor" => "Motor Vehicle Engine Parts",
			"parts-body-floor-side-member" => "Motor Vehicle Frame & Body Parts",
			"parts-engine-fuel-carburetor" => "Motor Vehicle Engine Parts",
			"parts-body-accelerator-link" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-body-fuel-tank-tube" => "Motor Vehicle Fuel Systems",
			"parts-engine-fuel-fuel-pump-pipe" => "Motor Vehicle Fuel Systems",
			"parts-engine-fuel-injection-nozzle" => "Motor Vehicle Fuel Systems",
			"parts-engine-fuel-fuel-filter" => "Motor Vehicle Fuel Systems",
			"parts-engine-fuel-lpg-or-cng-regulator" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-starter" => "Motor Vehicle Power & Electrical Systems",
			"parts-drive-chassis-transaxle-assy-cvt" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-electrical-electronic-fuel-injection-system" => "Motor Vehicle Fuel Systems",
			"parts-electrical-heating-air-conditioning-heater-unit-blower" => "Motor Vehicle Climate Control",
			"parts-electrical-heating-air-conditioning-cooler-unit" => "Motor Vehicle Climate Control",
			"parts-engine-fuel-vacuum-pump" => "Motor Vehicle Fuel Systems",
			"parts-drive-chassis-clutch-release-fork" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-brake-pedal-bracket" => "Motor Vehicle Braking",
			"parts-drive-chassis-pump-actuator-sequential-or-multi-mode-manual-transaxle" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-front-drum-brake-wheel-cylinder-backing-plate" => "Motor Vehicle Braking",
			"parts-drive-chassis-torque-converter-front-oil-pump-cvt" => "Motor Vehicle Engine Parts",
			"parts-drive-chassis-oil-cooler-tube-hv-or-ev-or-fcv" => "Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-valve-body-oil-strainer-atm" => "Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-oil-pump-oil-cooler-pipe-mtm" => "Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-transmission-gear-mtm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-control-shaft-crossshaft" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-lever-shift-rod" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-gear" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-planetary-gear-reverse-piston-counter-gear-atm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-speedometer-driven-gear-mtm" => "Motor Vehicle Sensors & Gauges",
			"parts-drive-chassis-speedometer-driven-gear-atm" => "Motor Vehicle Sensors & Gauges",
			"parts-electrical-overdrive-electronic-controlled-transmission" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-steering-column-shaft" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-body-console-box-bracket" => "Motor Vehicle Interior Fittings",
			"parts-drive-chassis-power-take-off-lever-link" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-center-support-planetary-sun-gear-atm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-overdrive-gear-atm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-transfer-direct-clutch-low-brake-support" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-brake-band-multiple-disc-clutch-atm" => "Motor Vehicle Braking",
			"parts-drive-chassis-transfer-oil-pump" => "Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-brake-no-3-1st-reverse-brake-atm" => "Motor Vehicle Braking",
			"parts-drive-chassis-front-drive-clutch-gear" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-throttle-link-valve-lever-atm" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-rear-oil-pump-governor-atm" => "Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-valve-body-oil-strainer-hv-or-ev-or-fcv" => "Motor Vehicle Engine Oil Circulation",
			"parts-body-roof-headlining-silencer-pad" => "Motor Vehicle Frame & Body Parts",
			"parts-electrical-interior-lamp" => "Motor Vehicle Lighting",
			"parts-drive-chassis-diaphragm-cylinder-transfer-vacuum-actuator" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-parking-brake-cable" => "Motor Vehicle Braking",
			"parts-body-spare-wheel-carrier" => "Motor Vehicle Tire Accessories",
			"parts-body-package-tray-panel-luggage-compartment-mat" => "Motor Vehicle Carpet & Upholstery",
			"parts-drive-chassis-power-steering-tube" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-height-control-auto-leveler" => "Motor Vehicle Suspension Parts",
			"parts-drive-chassis-clutch-booster" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-drive-chassis-steering-wheel" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-body-floor-pan-lower-back-panel" => "Motor Vehicle Frame & Body Parts",
			"parts-body-inside-trim-board" => "Motor Vehicle Interior Fittings",
			"parts-body-hood-front-fender" => "Motor Vehicle Frame & Body Parts",
			"parts-body-side-member" => "Motor Vehicle Frame & Body Parts",
			"parts-electrical-electronic-height-control" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-electronic-modulated-suspension" => "Motor Vehicle Power & Electrical Systems",
			"parts-body-instrument-panel-glove-compartment" => "Motor Vehicle Interior Fittings",
			"parts-body-moulding" => "Motor Vehicle Frame & Body Parts",
			"parts-body-cab-mounting-body-mounting" => "Motor Vehicle Suspension Parts",
			"parts-body-front-floor-panel-front-floor-member" => "Motor Vehicle Frame & Body Parts",
			"parts-body-rear-bumper-bumper-stay" => "Motor Vehicle Frame & Body Parts",
			"parts-body-mat-carpet" => "Motor Vehicle Carpet & Upholstery",
			"parts-body-floor-insulator" => "Motor Vehicle Frame & Body Parts",
			"parts-body-deck-board-deck-trim-cover" => "Vehicle Covers",
			"parts-body-floor-mat-silencer-pad" => "Motor Vehicle Carpet & Upholstery",
			"parts-electrical-fog-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-headlamp" => "Motor Vehicle Lighting",
			"parts-electrical-rear-combination-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-front-turn-signal-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-reflex-reflector" => "Vehicle Safety Equipment",
			"parts-body-rear-moulding" => "Motor Vehicle Frame & Body Parts",
			"parts-body-rear-body-side-panel" => "Motor Vehicle Frame & Body Parts",
			"parts-electrical-air-bag" => "Motor Vehicle Airbag Parts",
			"parts-body-rear-ventilator-roof-ventilator" => "Motor Vehicle Climate Control",
			"parts-body-seat-seat-track" => "Motor Vehicle Seating",
			"parts-body-emblem-name-plate-exterior-interior" => "Vehicle Decals",
			"parts-body-emblem-name-plate" => "Vehicle Decals",
			"parts-body-rear-door-panel-glass" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-radiator-support-wind-guide" => "Motor Vehicle Engine Parts",
			"parts-body-hood-lock-hinge" => "Vehicle Alarms & Locks",
			"parts-body-engine-hood-lock" => "Vehicle Alarms & Locks",
			"parts-body-luggage-compartment-door-lock" => "Vehicle Alarms & Locks",
			"parts-electrical-windshield-washer" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-rear-washer" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-battery-carrier" => "Vehicle Repair & Specialty Tools",
			"parts-body-side-moulding" => "Motor Vehicle Frame & Body Parts",
			"parts-body-front-door-window-regulator-hinge" => "Motor Vehicle Window Parts & Accessories",
			"parts-electrical-radio-receiver-amplifier-condenser" => "Motor Vehicle Amplifiers",
			"parts-electrical-heating-air-conditioning-control-air-duct" => "Motor Vehicle Climate Control",
			"parts-electrical-navigation-front-monitor-display" => "Motor Vehicle A/V Players & In-Dash Systems",
			"parts-body-armrest-visor" => "Motor Vehicle Interior Fittings",
			"parts-electrical-telephone-mayday" => "Vehicle Safety & Security",
			"parts-body-front-door-panel-glass" => "Motor Vehicle Window Parts & Accessories",
			"parts-electrical-indicator" => "Motor Vehicle Sensors & Gauges",
			"parts-body-lock-cylinder-set" => "Vehicle Alarms & Locks",
			"accessories" => "Vehicle Parts & Accessories",
			"accessories-interior" => "Motor Vehicle Interior Fittings",
			"accessories-interior-vehicle-security" => "Vehicle Safety & Security",
			"accessories-interior-vehicle-security-security-system" => "Automotive Alarm Systems",
			"accessories-interior-floor-mats-interior-protection" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-carpet-floor-mats" => "Motor Vehicle Carpet & Upholstery",
			"accessories-exterior" => "Vehicle Parts & Accessories",
			"accessories-exterior-body-and-paint-protection" => "Motor Vehicle Body Paint",
			"accessories-exterior-body-and-paint-protection-car-cover" => "Vehicle Covers",
			"accessories-exterior-exterior-styling" => "Motor Vehicle Parts",
			"accessories-exterior-exterior-styling-graphics" => "Vehicle Decals",
			"accessories-exterior-towing" => "Motor Vehicle Towing",
			"accessories-exterior-towing-ball-mount" => "Motor Vehicle Towing",
			"accessories-exterior-towing-tow-hitch" => "Motor Vehicle Towing",
			"accessories-interior-driver-convenience" => "Vehicle Parts & Accessories",
			"accessories-interior-driver-convenience-first-aid-kit" => "Vehicle Safety & Security",
			"accessories-exterior-interior-styling" => "Motor Vehicle Interior Fittings",
			"accessories-exterior-interior-styling-interior-emblem" => "Vehicle Emblems & Hood Ornaments",
			"accessories-exterior-exterior-products" => "Motor Vehicle Parts",
			"accessories-exterior-exterior-products-running-boards" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-truck-bed-products" => "Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-truck-bed-products-rear-step-bumper" => "Motor Vehicle Frame & Body Parts",
			"accessories-exterior-body-and-paint-protection-rear-bumper-applique" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-wheels" => "Automotive Rims & Wheels",
			"accessories-exterior-wheels-wheel-locks" => "Vehicle Wheel Clamps",
			"accessories-exterior-wheels-wheels" => "Automotive Rims & Wheels",
			"accessories-exterior-exterior-products-roof-rack" => "Vehicle Cargo Racks",
			"accessories-exterior-body-and-paint-protection-rear-bumper-protector" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-styling-rear-spoiler" => "Motor Vehicle Frame & Body Parts",
			"accessories-exterior-body-and-paint-protection-body-side-moldings" => "Vehicle Decor",
			"accessories-interior-cargo-management" => "Vehicle Storage & Cargo",
			"accessories-interior-cargo-management-cargo-net" => "Motor Vehicle Cargo Nets",
			"accessories-exterior-exterior-styling-rear-wind-deflector" => "Motor Vehicle Window Parts & Accessories",
			"accessories-exterior-exterior-styling-fender-flares" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-body-and-paint-protection-hood-protector" => "Vehicle Decor Accessory Sets",
			"accessories-interior-interior-styling" => "Motor Vehicle Interior Fittings",
			"accessories-interior-interior-styling-interior-applique" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-products-fog-lights" => "Motor Vehicle Lighting",
			"accessories-exterior-exterior-styling-exterior-emblem" => "Vehicle Emblems & Hood Ornaments",
			"accessories-performance" => "Motor Vehicle Parts",
			"accessories-performance-exterior-styling" => "Motor Vehicle Parts",
			"accessories-performance-exterior-styling-body-kit" => "Motor Vehicle Frame & Body Parts",
			"accessories-interior-driver-convenience-remote-engine-starter" => "Vehicle Remote Keyless Systems",
			"accessories-exterior-cargo-management" => "Vehicle Storage & Cargo",
			"accessories-exterior-cargo-management-cargo-hooks" => "Vehicle Storage & Cargo",
			"accessories-exterior-wheels-wheel-covers" => "Automotive Rims & Wheels",
			"accessories-exterior-exterior-styling-mirror-caps" => "Motor Vehicle Mirrors",
			"accessories-exterior-body-and-paint-protection-mudguards" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-wheels-wheel-inserts" => "Motor Vehicle Wheel Parts",
			"accessories-interior-driver-convenience-armrest" => "Motor Vehicle Interior Fittings",
			"accessories-interior-driver-convenience-cruise-control" => "Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation" => "GPS Navigation Systems",
			"accessories-interior-audio-entertainment-navigation-historical-audio" => "GPS Navigation Systems",
			"accessories-exterior-towing-towing-wire-harnesses-and-adapters" => "Motor Vehicle Towing",
			"accessories-performance-wheels" => "Automotive Rims & Wheels",
			"accessories-performance-wheels-wheels" => "Automotive Rims & Wheels",
			"accessories-exterior-exterior-styling-body-kit" => "Motor Vehicle Frame & Body Parts",
			"accessories-interior-driver-convenience-center-console-box" => "Motor Vehicle Interior Fittings",
			"accessories-interior-audio-entertainment-navigation-satellite-radio" => "Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-interior-driver-convenience-coin-holder-ashtray-cup" => "Motor Vehicle Interior Fittings",
			"accessories-interior-driver-convenience-back-up-camera" => "Motor Vehicle Parking Cameras",
			"accessories-interior-driver-convenience-digital-clock" => "Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation-subwoofer" => "Motor Vehicle Subwoofers",
			"accessories-interior-audio-entertainment-navigation-interface-kit-for-ipod" => "Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation-rear-seat-entertainment" => "Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-interior-audio-entertainment-navigation-extension-box" => "Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation-navigation-headunit" => "Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-interior-driver-convenience-auto-dimming-mirror" => "Motor Vehicle Mirrors",
			"accessories-exterior-exterior-products-activity-mount" => "Utility & Cargo Trailers",
			"accessories-interior-floor-mats-interior-protection-all-weather-floor-liners" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-cargo-liner" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-interior-styling-door-sill-protectors" => "Vehicle Maintenance, Care & Decor",
			"accessories-exterior-truck-bed-products-tonneau-cover" => "Tonneau Covers",
			"accessories-exterior-truck-bed-products-tailgate-lock" => "Vehicle Door Locks & Locking Systems",
			"accessories-interior-video" => "Audio & Video Receiver Accessories",
			"accessories-interior-video-dashcam" => "Vehicle Dashboard Accessories",
			"accessories-exterior-wheels-lug-nuts" => "Motor Vehicle Wheel Parts",
			"accessories-exterior-exterior-styling-exhaust-tip" => "Motor Vehicle Exhaust",
			"accessories-interior-floor-mats-interior-protection-carpet-cargo-mat" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-carpet-trunk-mat" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-all-weather-floor-mats" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-cargo-tray" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-floor-mats-interior-protection-all-weather-cargo-mat" => "Motor Vehicle Carpet & Upholstery",
			"accessories-performance-exterior-styling-graphics" => "Vehicle Decals",
			"accessories-exterior-exterior-styling-exterior-applique" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-body-and-paint-protection-front-skid-plate" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-products-tube-steps" => "Motor Vehicle Frame & Body Parts",
			"accessories-exterior-exterior-styling-sport-bumper-trim" => "Vehicle Decor Accessory Sets",
			"accessories-interior-cargo-management-seat-back-storage" => "Vehicle Organizers",
			"accessories-exterior-body-and-paint-protection-front-end-mask" => "Vehicle Decor Accessory Sets",
			"accessories-performance-interior-styling" => "Motor Vehicle Interior Fittings",
			"accessories-performance-interior-styling-seat-cover" => "Motor Vehicle Seating",
			"accessories-interior-interior-styling-seat-cover" => "Motor Vehicle Seating",
			"accessories-exterior-exterior-styling-spare-tire-cover" => "Motor Vehicle Tire Accessories",
			"accessories-interior-wheels" => "Automotive Rims & Wheels",
			"accessories-interior-wheels-wheel-locks" => "Vehicle Wheel Clamps",
			"accessories-interior-audio-entertainment-navigation-navigation-upgrade-kit" => "Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-performance-exterior-styling-front-grille" => "Motor Vehicle Frame & Body Parts",
			"accessories-exterior-exterior-styling-front-grille" => "Motor Vehicle Frame & Body Parts",
			"accessories-exterior-driver-convenience" => "Vehicle Parts & Accessories",
			"accessories-exterior-driver-convenience-kick-sensor" => "Motor Vehicle Electronics",
			"accessories-exterior-exterior-products-rock-rails" => "Vehicle Decor Accessory Sets",
			"accessories-performance-performance-suspension-chassis" => "Motor Vehicle Suspension Parts",
			"accessories-performance-performance-suspension-chassis-jounce-bumper" => "Motor Vehicle Suspension Parts",
			"accessories-interior-cargo-management-cargo-cover" => "Vehicle Organizers",
			"accessories-exterior-truck-bed-products-storage-box" => "Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-truck-bed-products-bed-liner" => "Motor Vehicle Carpet & Upholstery",
			"accessories-exterior-wheels-spare-tire-lock" => "Vehicle Wheel Clamps",
			"accessories-exterior-truck-bed-products-mini-tie-down" => "Vehicle Organizers",
			"accessories-exterior-exterior-products-cross-bars" => "Motor Vehicle Roll Cages & Bars",
			"accessories-exterior-truck-bed-products-bed-cleats" => "Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-truck-bed-products-d-rings" => "Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-truck-bed-products-bed-rail" => "Motor Vehicle Frame & Body Parts",
			"accessories-interior-exterior-products" => "Motor Vehicle Parts",
			"accessories-interior-exterior-products-cross-bars" => "Motor Vehicle Roll Cages & Bars",
			"accessories-exterior-exterior-products-ski-rack" => "Vehicle Ski & Snowboard Racks",
			"accessories-exterior-exterior-products-bike-rack" => "Vehicle Bicycle Racks",
			"accessories-exterior-wheels-center-cap" => "Motor Vehicle Wheel Parts",
			"accessories-interior-audio-entertainment-navigation-wireless-headphones" => "Motor Vehicle Electronics",
			"accessories-interior-audio-entertainment-navigation-base-audio-headunit" => "Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-exterior-audio-entertainment-navigation" => "GPS Navigation Systems",
			"accessories-exterior-audio-entertainment-navigation-base-audio-headunit" => "Motor Vehicle A/V Players & In-Dash Systems",
			"accessories-exterior-exterior-products-off-road-lights" => "Motor Vehicle Lighting",
			"accessories-exterior-exterior-styling-rear-window-spoiler" => "Motor Vehicle Window Parts & Accessories",
			"accessories-exterior-truck-bed-products-bed-mat" => "Motor Vehicle Carpet & Upholstery",
			"accessories-exterior-truck-bed-products-bed-extender" => "Truck Bed Storage Boxes & Organizers",
			"accessories-performance-performance-suspension-chassis-shocks-and-struts" => "Motor Vehicle Suspension Parts",
			"accessories-exterior-cargo-management-cargo-net" => "Motor Vehicle Cargo Nets",
			"accessories-exterior-truck-bed-products-bed-net" => "Motor Vehicle Cargo Nets",
			"accessories-interior-driver-convenience-coin-holder" => "Motor Vehicle Interior Fittings",
			"accessories-interior-driver-convenience-home-link" => "Motor Vehicle Electronics",
			"accessories-interior-interior-styling-shift-knob" => "Vehicle Shift Knobs",
			"accessories-performance-wheels-wheel-covers" => "Automotive Rims & Wheels",
			"accessories-exterior-truck-bed-products-bed-step" => "Motor Vehicle Frame & Body Parts",
			"accessories-exterior-exterior-styling-fender-vent-insert" => "Vehicle Decor Accessory Sets",
			"accessories-performance-performance-engine" => "Motor Vehicle Engines",
			"accessories-performance-performance-engine-exhaust" => "Motor Vehicle Exhaust",
			"accessories-interior-interior-styling-illuminated-door-sills" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-products-license-plate-frame" => "Vehicle License Plate Frames",
			"accessories-exterior-performance-suspension-chassis" => "Motor Vehicle Suspension Parts",
			"accessories-exterior-performance-suspension-chassis-suspension-kit" => "Motor Vehicle Suspension Parts",
			"accessories-interior-interior-styling-interior-light-kit" => "Motor Vehicle Lighting",
			"accessories-exterior-exterior-styling-sunroof-wind-deflector" => "Motor Vehicle Window Parts & Accessories",
			"accessories-interior-driver-convenience-emergency-assistance-kit" => "Vehicle Safety Equipment",
			"accessories-interior-driver-convenience-key-glove" => "Vehicle Alarms & Locks",
			"accessories-interior-driver-convenience-owner-portfolio" => "Vehicle Service Manuals",
			"accessories-interior-cargo-management-cargo-tote" => "Vehicle Organizers",
			"accessories-performance-interior-styling-shift-knob" => "Vehicle Shift Knobs",
			"accessories-interior-audio-entertainment-navigation-audio-multimedia-cable" => "Motor Vehicle Electronics",
			"accessories-exterior-vehicle-security" => "Vehicle Safety & Security",
			"accessories-exterior-vehicle-security-security-system" => "Automotive Alarm Systems",
			"accessories-performance-performance-suspension-chassis-chassis-brace" => "Motor Vehicle Suspension Parts",
			"accessories-interior-driver-convenience-key-finder" => "Vehicle Alarms & Locks",
			"accessories-interior-driver-convenience-wind-screen" => "Motor Vehicle Frame & Body Parts",
			"accessories-interior-exterior-styling" => "Motor Vehicle Parts",
			"accessories-interior-exterior-styling-rear-spoiler" => "Motor Vehicle Frame & Body Parts",
			"accessories-exterior-truck-bed-products-bed-cargo-divider" => "Vehicle Organizers",
			"accessories-exterior-exterior-products-camera-mount" => "Motor Vehicle Parking Cameras",
			"accessories-exterior-exterior-styling-lower-rocker-panel" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-towing-trailer-ball" => "Motor Vehicle Towing",
			"accessories-interior-driver-convenience-wireless-charger" => "Motor Vehicle Electronics",
			"accessories-performance-performance-suspension-chassis-performance-springs" => "Motor Vehicle Suspension Parts",
			"accessories-performance-performance-suspension-chassis-suspension-kit" => "Motor Vehicle Suspension Parts",
			"accessories-interior-truck-bed-products" => "Truck Bed Storage Boxes & Organizers",
			"accessories-interior-truck-bed-products-bed-lighting" => "Motor Vehicle Lighting",
			"accessories-exterior-truck-bed-products-bed-lighting" => "Motor Vehicle Lighting",
			"accessories-interior-cargo-management-cargo-organizer" => "Vehicle Organizers",
			"accessories-exterior-body-and-paint-protection-paint-protection-film" => "Motor Vehicle Body Paint",
			"accessories-exterior-body-and-paint-protection-door-edge-film" => "Vehicle Maintenance, Care & Decor",
			"accessories-exterior-floor-mats-interior-protection" => "Motor Vehicle Carpet & Upholstery",
			"accessories-exterior-floor-mats-interior-protection-all-weather-trunk-mat" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-audio-entertainment-navigation-portable-navigation-system" => "GPS Navigation Systems",
			"accessories-interior-audio-entertainment-navigation-hands-free-system" => "GPS Navigation Systems",
			"accessories-interior-driver-convenience-center-console-tray" => "Motor Vehicle Interior Fittings",
			"accessories-exterior-cargo-management-cargo-organizer" => "Vehicle Organizers",
			"accessories-performance-floor-mats-interior-protection" => "Motor Vehicle Carpet & Upholstery",
			"accessories-performance-floor-mats-interior-protection-carpet-floor-mats" => "Motor Vehicle Carpet & Upholstery",
			"accessories-exterior-body-and-paint-protection-door-edge-guard" => "Vehicle Maintenance, Care & Decor",
			"accessories-exterior-exterior-styling-rear-garnish" => "Vehicle Decor Accessory Sets",
			"accessories-performance-body-and-paint-protection" => "Motor Vehicle Body Paint",
			"accessories-performance-body-and-paint-protection-front-skid-plate" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-styling-splitter" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-truck-bed-products-bed-rugs" => "Motor Vehicle Carpet & Upholstery",
			"accessories-interior-interior-styling-illuminated-trunk-sill" => "Vehicle Decor Accessory Sets",
			"accessories-interior-interior-styling-illuminated-cargo-sills" => "Vehicle Decor Accessory Sets",
			"accessories-exterior-exterior-products-rear-puddle-lamp" => "Motor Vehicle Lighting",
			"accessories-interior-interior-products" => "Motor Vehicle Interior Fittings",
			"accessories-interior-interior-products-led-bulb" => "Motor Vehicle Lighting",
			"accessories-exterior-exterior-products-accent-lighting" => "Motor Vehicle Lighting",
			"accessories-exterior-truck-bed-products-bike-rack" => "Vehicle Bicycle Racks",
			"accessories-interior-interior-products-console-safe" => "Motor Vehicle Interior Fittings",
			"accessories-interior-driver-convenience-charge-cable" => "Motor Vehicle Electronics",
			"accessories-interior-driver-convenience-power-port" => "Motor Vehicle Power & Electrical Systems",
			"accessories-exterior-driver-convenience-ev-charger" => "Motor Vehicle Power & Electrical Systems",
			"accessories-performance-engine-accessories" => "Motor Vehicle Engine Parts",
			"accessories-performance-engine-accessories-oil-cap" => "Motor Vehicle Engine Oil Circulation",
			"accessories-performance-performance-suspension-chassis-strut-tie-brace" => "Motor Vehicle Suspension Parts",
			"accessories-performance-performance-suspension-chassis-sway-bar" => "Motor Vehicle Suspension Parts",
			"accessories-performance-performance-engine-air-intake-system" => "Motor Vehicle Engine Parts",
			"accessories-performance-engine-accessories-air-filter" => "Motor Vehicle Engine Parts",
			"accessories-performance-engine-accessories-radiator-cap" => "Motor Vehicle Engine Parts",
			"accessories-performance-performance-drivetrain" => "Motor Vehicle Transmission & Drivetrain Parts",
			"accessories-performance-performance-drivetrain-quickshifter" => "Motor Vehicle Transmission & Drivetrain Parts",
			"accessories-performance-performance-drivetrain-clutch" => "Motor Vehicle Transmission & Drivetrain Parts",
			"accessories-performance-performance-suspension-chassis-brakes" => "Motor Vehicle Braking",
			"accessories-performance-performance-suspension-chassis-brake-pads" => "Motor Vehicle Braking",
			"accessories-interior-performance-suspension-chassis" => "Motor Vehicle Suspension Parts",
			"accessories-interior-performance-suspension-chassis-suspension-kit" => "Motor Vehicle Suspension Parts",
			"accessories-performance-performance-engine-supercharger" => "Motor Vehicle Engine Parts",
			"accessories-performance-engine-accessories-oil-filter" => "Motor Vehicle Engine Oil Circulation",
			"accessories-performance-engine-accessories-engine-cover" => "Motor Vehicle Engine Parts",
			"accessories-exterior-performance-suspension-chassis-sway-bar" => "Motor Vehicle Suspension Parts",
			"accessories-exterior-truck-bed-products-camper-shell" => "Truck Bed Storage Boxes & Organizers",
			"accessories-exterior-exterior-styling-rear-taillight-lens" => "Motor Vehicle Lighting",
			"accessories-performance-truck-bed-products" => "Truck Bed Storage Boxes & Organizers",
			"accessories-performance-truck-bed-products-bed-step" => "Motor Vehicle Frame & Body Parts",
			"accessories-exterior-exterior-products-side-puddle-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-windshield-wiper" => "Motor Vehicle Power & Electrical Systems",
			"parts-body-front-ventilator" => "Motor Vehicle Climate Control",
			"parts-body-room-separator-glass" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-quarter-window" => "Motor Vehicle Window Parts & Accessories",
			"parts-electrical-speaker" => "Motor Vehicle Speakers",
			"parts-body-rear-seat-seat-track" => "Motor Vehicle Seating",
			"parts-body-roof-panel-back-panel" => "Motor Vehicle Frame & Body Parts",
			"parts-body-seat-rail" => "Motor Vehicle Seating",
			"parts-body-tool-box-license-plate-bracket" => "Vehicle Storage & Cargo",
			"parts-body-slide-roller-rail" => "Motor Vehicle Frame & Body Parts",
			"parts-body-front-seat-seat-track" => "Motor Vehicle Seating",
			"parts-body-inside-trim-board-door-opening-trim-moulding" => "Motor Vehicle Interior Fittings",
			"parts-body-rear-door-lock-handle" => "Vehicle Door Locks & Locking Systems",
			"parts-body-back-door-lock-handle" => "Vehicle Door Locks & Locking Systems",
			"parts-body-back-door-lock-hinge" => "Vehicle Door Locks & Locking Systems",
			"parts-body-seat-belt" => "Vehicle Seat Belts",
			"parts-body-side-window" => "Motor Vehicle Window Parts & Accessories",
			"parts-electrical-center-stop-lamp" => "Motor Vehicle Lighting",
			"parts-body-front-door-ventilator-window" => "Motor Vehicle Window Parts & Accessories",
			"parts-electrical-rear-wiper" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-rear-body-top-curtain-roof-panel" => "Motor Vehicle Frame & Body Parts",
			"parts-body-roof-side-ventilator" => "Motor Vehicle Climate Control",
			"parts-body-rear-body-mounting" => "Motor Vehicle Frame & Body Parts",
			"parts-body-seat-belt-child-restraint-seat" => "Motor Vehicle Seating",
			"parts-body-package-tray-panel" => "Motor Vehicle Frame & Body Parts",
			"parts-body-cover-top" => "Vehicle Covers",
			"parts-body-rear-body-floor-fender" => "Motor Vehicle Frame & Body Parts",
			"parts-body-rear-body-guard-frame-tail-gate" => "Motor Vehicle Frame & Body Parts",
			"parts-body-room-curtain-room-rack" => "Motor Vehicle Interior Fittings",
			"parts-body-rear-body-assembly" => "Motor Vehicle Frame & Body Parts",
			"parts-body-rear-door-window-regulator-hinge" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-front-door-lock-handle" => "Vehicle Door Locks & Locking Systems",
			"parts-body-ash-receptacle" => "Motor Vehicle Interior Fittings",
			"parts-electrical-mirror" => "Motor Vehicle Mirrors",
			"parts-electrical-anti-theft-device" => "Vehicle Alarms & Locks",
			"parts-electrical-wireless-door-lock" => "Vehicle Door Locks & Locking Systems",
			"parts-body-separation-door-panel-glass" => "Motor Vehicle Window Parts & Accessories",
			"parts-body-body-stripe" => "Vehicle Decals",
			"parts-body-front-moulding" => "Motor Vehicle Frame & Body Parts",
			"parts-electrical-back-up-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-rear-license-plate-lamp" => "Motor Vehicle Lighting",
			"parts-engine-fuel-lpg-or-cng-injection-system" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-urea-tank-tube" => "Motor Vehicle Engine Parts",
			"parts-electrical-antenna" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-daytime-running-lamp-or-illumination-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-spot-lamp" => "Vehicle Repair & Specialty Tools",
			"parts-electrical-side-turn-signal-lamp-outer-mirror-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-rear-fog-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-front-marker-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-front-clearance-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-rear-side-marker-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-meter" => "Vehicle Repair & Specialty Tools",
			"parts-electrical-headlamp-cleaner" => "Vehicle Glass Cleaners",
			"parts-drive-chassis-transfer-vacuum-piping" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-electrical-electronic-diesel-injection-control-system" => "Motor Vehicle Fuel Systems",
			"parts-electrical-clearance-back-sonar" => "Vehicle Safety Equipment",
			"parts-electrical-passive-belt-system" => "Vehicle Seat Belts",
			"parts-electrical-camera-rear-monitor-display" => "Motor Vehicle Parking Cameras",
			"parts-electrical-electronic-controlled-transmission" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-electrical-tire-pressure-warning-system" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-lane-keeping-assist" => "Vehicle Safety Equipment",
			"parts-electrical-pre-collision-system" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-traction-control" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-door-motor-door-solenoid" => "Vehicle Door Locks & Parts",
			"parts-electrical-seat-motor-seat-heater" => "Motor Vehicle Seating",
			"parts-electrical-horn" => "Vehicle Safety Equipment",
			"parts-electrical-air-purifier-or-ion-generator" => "Motor Vehicle Climate Control",
			"parts-electrical-heating-air-conditioning-set" => "Motor Vehicle Climate Control",
			"parts-electrical-automatic-light-control-system-conlight" => "Motor Vehicle Lighting",
			"parts-electrical-active-control-suspension-electrical-parts" => "Motor Vehicle Suspension Parts",
			"parts-electrical-eco-run-system" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-control-computer-ev-or-fcv" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-hv-control-computer" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-electronic-controled-diesel-ecd" => "Motor Vehicle Fuel Systems",
			"parts-electrical-power-steering-computer" => "Motor Vehicle Power & Electrical Systems",
			"parts-engine-fuel-fuel-pipe-clamp" => "Motor Vehicle Fuel Systems",
			"parts-engine-fuel-v-belt" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-injection-pump-assembly" => "Motor Vehicle Fuel Systems",
			"parts-electrical-inverter-ev-or-fcv" => "Motor Vehicle Power & Electrical Systems",
			"parts-engine-fuel-air-pump" => "Motor Vehicle Engine Parts",
			"parts-engine-fuel-injection-pump-body" => "Motor Vehicle Fuel Systems",
			"parts-body-floor-mat" => "Motor Vehicle Carpet & Upholstery",
			"parts-engine-fuel-intercooler-sub-radiator" => "Motor Vehicle Engine Parts",
			"parts-electrical-spot-lamp-search-lamp" => "Vehicle Repair & Specialty Tools",
			"parts-electrical-night-view" => "Vehicle Dashboard Accessories",
			"parts-electrical-cornering-lamp" => "Motor Vehicle Lighting",
			"parts-electrical-junction-box" => "Motor Vehicle Power & Electrical Systems",
			"accessories-interior-performance-engine" => "Motor Vehicle Engines",
			"accessories-interior-performance-engine-air-intake-system" => "Motor Vehicle Engine Parts",
			"accessories-exterior-exterior-products-cat-shield" => "Vehicle Alarms & Locks",
			"accessories-exterior-interior-products" => "Motor Vehicle Interior Fittings",
			"accessories-exterior-interior-products-divider" => "Vehicle Organizers",
			"parts-interior-styling" => "Motor Vehicle Interior Fittings",
			"parts-interior-styling-steering-wheel" => "Motor Vehicle Interior Fittings",
			"parts-drive-chassis-transaxle-assy-hev-or-bev-or-fcev" => "Motor Vehicle Transmission & Drivetrain Parts",
			"parts-engine-accessories" => "Motor Vehicle Engine Parts",
			"parts-engine-accessories-oil-filter" => "Motor Vehicle Engine Oil Circulation",
			"parts-drive-chassis-oil-cooler-tube-hev-or-bev-or-fcev" => "Motor Vehicle Engine Oil Circulation",
			"parts-electrical-bev-cooling" => "Motor Vehicle Power & Electrical Systems",
			"parts-electrical-dc-dc-converter-charger-bev-or-fcev" => "Motor Vehicle Power & Electrical Systems"
		];
	}
}
