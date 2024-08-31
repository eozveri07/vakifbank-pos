# PHP Payment Integration Example

This repository contains example code for integrating a payment gateway using PHP. The code handles 3D Secure transactions and interacts with a payment gateway to process credit card payments.

## Files

- **payment-init.php**: This file initializes a payment request. It collects the user's credit card information, processes it, and sends a request to the payment gateway's 3D Secure enrollment API.

- **payment-response.php**: This file handles the response from the payment gateway after the 3D Secure authentication. It processes the returned data and sends a request to finalize the payment transaction.

## Usage

### Prerequisites

- PHP 7.4 or higher
- cURL and SimpleXML extensions enabled

### How to Use

1. **Setup Merchant Information**: Replace the placeholders (`YOUR_MERCHANT_ID`, `YOUR_MERCHANT_PASSWORD`, `YOUR_TERMINAL_NO`) with your actual merchant credentials provided by the payment gateway.

2. **Customize URLs**: Update the success and failure URLs in the `payment-init.php` file to point to your actual success and failure pages.

3. **Form Handling**: The `payment-init.php` file expects the following POST parameters from an HTML form:
   - `pan`: The credit card number
   - `cvv`: The CVV/CVC code of the credit card
   - `expiryDate`: The expiry date in `MM/YYYY` format
   - `purchaseAmount`: The amount to be charged

4. **Process the Payment**: The user will be redirected to the payment gateway's 3D Secure page. After authentication, they will be redirected back to your site, where `payment-response.php` will handle the final payment processing.

5. **Handle Responses**: Depending on the result of the payment processing, the user will be redirected to either the success or failure page.

### Security Considerations

- Do **not** store sensitive information like credit card details in plain text. Consider using tokenization or encryption for storing payment-related information securely.
- Ensure that all communications with the payment gateway are done over HTTPS.

### Contributing

Feel free to fork this repository and submit pull requests to improve the code or add new features.

