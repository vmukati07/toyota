#Infosys_DeliveryFee 

## Description
This module provides, for shipping carrier and shipping method pairs provided by ShipperHQ:
- The ability to globally define, on a per-US-state basis, a delivery fee (tax) to be applied to Orders with Shipping Addresses originating from those states.
- The ability to define store-scoped configurations to specify which stores charge the delivery fee.
- Additions to the graphql schema that add the delivery fee to cart responses

## Configuration
Configurations can be found at: `Adminhtml > Stores > Configuration > Toyota > Delivery Fee`.

Both global and store-scoped configurations can be found there.
