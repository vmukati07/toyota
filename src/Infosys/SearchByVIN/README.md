# Module Infosys SearchByVIN

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_SearchByVIN`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores->Toyota->VIS Configuration
	API Azure
		- Client ID: Enter Client Id
		- Client Secret: Enter Client secret key
		- Grant Type: enter type
		- Token Url: Enter URL
	VIS Integration
		- API Azure Resource: Enter API URL
		- IBM Client ID: Enter Client Id
		- BodyID: Enter Body Id
		- VIS API Url: Enter VIS URL
	Logs
		- Enable/Disable logs
	API Connection Timeout
		- VIS Connection Timeout: Set Timeout time

## Main Functionalities
	1. In this module, we have created VehicleResolver class to perform Vin search GraphQL request. 

	2. Here we are making connection to VIS url by cURL and getting the reponse. 

	3. With the help of graphQL we are storing the data which is coming by cURL request.

	4. graphQL which gatther the data are:

		type VehicleRecord @doc(description: "graphql gather Data of specific attribute information") {
		entity_id : String @doc(description: "ID of the vehicle")
		model_year: Int   @doc(description: "Model Year of the vehicle")
		model_code: String    @doc(description: "Model Code of the vehicle")
		make: String    @doc(description: "make of the vehicle")
		model_name: String    @doc(description: "model name of the vehicle")
		grade: String @doc(description: "Grade of the vehicle")
		driveline : String @doc(description: "Driveline of the vehicle")
		body_style : String @doc(description: "Body Style of the vehicle")
		vehicle_image: String  @doc(description: "Image of the vehicle")
	}
