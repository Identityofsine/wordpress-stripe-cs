# RESTful Stripe + WooCommerce PaymentIntent Plugin

RSWPP is a plugin designed to serve as a bridge between front-end applications (such as React, NextJS, etc.) and a RESTful Wordpress + WooCommerce platform.

This is intended to be used for a headless setup of the Wordpress Monolithic System.

Please note that this plugin **requires WooCommerce** to function properly. It's essential that the tables remain untouched by developers. In the future, a more robust and secure system for querying products will be developed.

This plugin leverages WordPress' REST API to achieve its functionality.

## Functionality 

- **Creating PaymentIntents:** Facilitates the creation of PaymentIntents using Stripe's API.
- **Price Calculation:** Accepts a JSON body containing a list of items (currently without quantities) and performs price calculations on the server-side.
- **Database Query:** Queries the database for product prices.
- **Robust Exception Handling:** Incorporates robust exception handling to ensure smooth operation.

## Planned Functionality

- **Automatic Tax Calculation:** Automatic tax calculation based on location and addition to the price.
- **Shipping Cost Calculation:** Computation of shipping costs using various APIs (primarily FedEx).
- **Currency Handling:** Adaptation for different currencies and appropriate responses.
- **PaymentIntent Retrieval:** Retrieval of PaymentIntents by their respective IDs.
- **Discount Application:** Application of discounts to the subtotal and verification of conditions.

## Usage

Currently, the only functional endpoint for this plugin is a POST request at:

```
http(s)://example.com/wp-json/ih-api/client
```

> **Please note:** This endpoint is subject to change in the future.

The plugin expects a request with a JSON body like the following:

```json
{
    "data" : {
        "id": 0, // PaymentIntent ID or cart ID
        "items": [24, 25], // Array of item numbers (quantities to be added in the future)
        "discount_code": "", // Discount code (if applicable)
        "shipping_method": 1, // Shipping method
        "currency": "USD" // Currency code
    }
}
```

A successful POST request will yield a response similar to this:

```json
{
    "status": "success",
    "secret": "pi_CLIENT_SECRET",
    "amount": 8500 // Price (in cents, multiplied by 100)
}
```

However, keep in mind that errors are also possible, resulting in either a `400 Bad Request` or `500 Internal Server Error` response:

```json
{
    "status": "failure",
    "message": "Something went wrong",
    "exception": "Price is less than or equal to 0"
}
```

Feel free to explore and enhance this plugin according to your needs!