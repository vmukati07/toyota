# Infosys_PayflowPro

## Overview:

Create a custom endpoint in Magento that will accept PayPal’s post back data and output it as JSON so the AEM application can load in the data via an iframe.

## Sample Request:

curl --location --request POST 'https://mcstaging.toyota.com/paypal/payflow/postback' \
--form 'test="test"' \
--form 'test2="test"' \
--form 'test3="test"'

## Sample Response:

{"test":"test","test2":"test","test3":"test"}