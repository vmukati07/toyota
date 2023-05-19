# Module Infosys CoreChargeAttribute

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CoreChargeAttribute`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content by running `php bin/magento setup:static-content:deploy -f`
	- Flush the cache by running `php bin/magento cache:flush`
   --
## Main Functionalities
	1. In this module, we have created the new custom product attribute with Price type. This attribute used to add the custom price to cart totals or order totals.

	2. We are displaying the core charge value on below listed page,
		2.1	Mini cart
		2.2	Cart
		2.3	Checkout
		2.4	Order Success
		2.5	Email templates – order related emails.

	3. We are importing the core charge attribute through product import. We have one column in product import file i.e. core_charge.

	4. core_charge attribute value will differ depends upon the product.

	5. We are displaying the core charge details on order related Backend pages. Ex – sales page, Invoice, Credit Memo and Sales order view page.

	6. We are using the Infoys/CoreChargeAttribute module for core charge attribute custom code changes. Mostly extended the admin blocks for add the core charge label into order total section. Also extended the total class file for adding the custom price into final total value.

	7. we have altered the sales_order, sales_order_item, quote, quote_item table with core charge attribute related column.
		
	8. GraphQl Request for OrderSuccessDetails with core_charge attribute:
	   
{
  orderSuccessDetails(orderId : "OrderId"){
       items {
        id
        number
        order_date
        status
        email
        shipping_method
        core_charge_details{
            totalCoreCharge
            individual{
                sku
                part_number
                quantity
                core_charge
            }
        }
        vin_details{
          vin_number
          vehicle_name
        }
        dealerInformation{
          dealer_name
          street_address
            region
            city
            country
            postcode
            phone_number
        }
        dealer_savings {
            subtotal_excluding_dealer_discount
            dealer_discount
        }
        items {
          product_name
          product_sku
          product_url_key
          vin_number
          vehicle_name
          fitment_notice
          fitment_status
          product_price 
          product_image
          core_charge
          part_number
          product_sale_price {
            value
          }
          product_sale_price {
            value
            currency
          }
          quantity_ordered
          quantity_invoiced
          quantity_shipped
        }
        carrier
        shipments {
          id
          number
          items {
            product_name
            quantity_shipped
          }
        }
        total {
          base_grand_total {
            value
            currency
          }
          grand_total {
            value
            currency
          }
          total_tax {
            value
          }
          subtotal {
            value
            currency
          }
          taxes {
            amount {
              value
              currency
            }
            title
            rate
          }
          total_shipping {
            value
          }
          shipping_handling {
            amount_including_tax {
              value
            }
            amount_excluding_tax {
              value
            }
            total_amount {
              value
            }
            taxes {
              amount {
                value
              }
              title
              rate
            }
          }
          discounts {
            amount {
              value
              currency
            }
            label
          }
        }
      }
    
    }
}

## Issues Fixed

	1. EP-5299: AC Admin - Use CSS to condense order page display
		- Added new class 'orderdata' in sections of sales order view page for remove whitespace using CSS.(Infosys\CoreChargeAttribute\view\adminhtml\templates\order\view\view.phtml)