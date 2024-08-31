<?php

// Terminate the existing session and start a new one
if (session_status() == PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy(); 

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

session_start();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $merchantId = "YOUR_MERCHANT_ID";
    $merchantPassword = "YOUR_MERCHANT_PASSWORD";

    $verifyEnrollmentRequestId = uniqid();
    $_SESSION['verifyEnrollmentRequestId'] = $verifyEnrollmentRequestId;

    $pan = $_POST['pan'];
    $cvv = $_POST['cvv'];
    $expiryDate = $_POST['expiryDate'];

    // Convert expiryDate from MM/YYYY format to YYMM format
    $expiryDateParts = explode('/', $expiryDate);
    $month = $expiryDateParts[0];
    $year = substr($expiryDateParts[1], -2); // Get the last two digits of the year
    $expiryDate = $year . $month; // Convert to YYMM format

    $purchaseAmount = $_POST['purchaseAmount'];
    $purchaseAmount = number_format((float)$purchaseAmount, 2, '.', '');

    $_SESSION['pan'] = $pan;
    $_SESSION['cvv'] = $cvv;
    $_SESSION['expiryDate'] = $expiryDate;
    $_SESSION['purchaseAmount'] = $purchaseAmount;

    $currency = "949"; // Example currency code
    $brandName = "100"; // Example card type / Brand Name / 100: Visa, 200: MasterCard, 300: American Express, 400: Diners Club, 500: JCB
    $successUrl = "https://yourdomain.com/payment-response";
    $failureUrl = "https://yourdomain.com/payment-failure";

    $data = [
        'MerchantId' => $merchantId,
        'MerchantPassword' => $merchantPassword,
        'VerifyEnrollmentRequestId' => $verifyEnrollmentRequestId,
        'Pan' => $pan,
        'ExpiryDate' => $expiryDate,
        'PurchaseAmount' => $purchaseAmount,
        'Currency' => $currency,
        'BrandName' => $brandName,
        'SuccessUrl' => $successUrl,
        'FailureUrl' => $failureUrl
    ];

    $ch = curl_init();
   // Test API:  curl_setopt($ch, CURLOPT_URL, "https://3dsecuretest.bank.com/MPIAPI/MPI_Enrollment.aspx");
   // Production API: curl_setopt($ch, CURLOPT_URL, "https://3dsecure.bank.com/MPIAPI/MPI_Enrollment.aspx");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('Curl error: ' . curl_error($ch)); // Log the error
        $errorMessage = '3D transaction failed, please ensure your card supports 3D Secure and you entered the correct information.';
    } else {
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            error_log('XML parse error: ' . print_r(libxml_get_errors(), true)); // Log the error
            $errorMessage = '3D transaction failed, please ensure your card supports 3D Secure and you entered the correct information.';
        } else {
            $status = (string)$xml->Message->VERes->Status;

            if ($status == 'Y') {
                $acsUrl = (string)$xml->Message->VERes->ACSUrl;
                $paReq = (string)$xml->Message->VERes->PaReq;
                $termUrl = (string)$xml->Message->VERes->TermUrl;
                $md = (string)$xml->Message->VERes->MD;

                echo '<form name="downloadForm" action="' . $acsUrl . '" method="POST">';
                echo '<input type="hidden" name="PaReq" value="' . $paReq . '">';
                echo '<input type="hidden" name="TermUrl" value="' . $termUrl . '">';
                echo '<input type="hidden" name="MD" value="' . $md . '">';
                echo '<noscript>';
                echo '<center>';
                echo '<h1>Processing your 3-D Secure Transaction</h1>';
                echo '<h2>JavaScript is currently disabled or is not supported by your browser.</h2>';
                echo '<h3>Please click Submit to continue the processing of your 3-D Secure transaction.</h3>';
                echo '<input type="submit" value="Submit">';
                echo '</center>';
                echo '</noscript>';
                echo '</form>';
                echo '<script>document.downloadForm.submit();</script>';
            } else {
                $errorCode = (string)$xml->MessageErrorCode;
                error_log("3D Secure transaction failed: " . $status . " - Error Code: " . $errorCode); // Log the error
                $errorMessage = '3D transaction failed, please ensure your card supports 3D Secure and you entered the correct information.';
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <style>
      /* You can define your form CSS here */

    </style>
</head>
<body>
    <form method="POST" action="">
        <h1>Payment Information</h1>
        <h2>Enter Your Card Details</h2>

        <div class="input-group">
            <label for="pan">Card Number</label>
            <input type="text" id="pan" name="pan" required maxlength="16">
        </div>

        <div class="input-group">
            <label for="customer">Name on Card</label>
            <input type="text" id="customer" name="customer" required>
        </div>

        <div class="inline-group">
            <div>
                <label for="expiryDate">Expiry Date</label>
                <input type="text" id="expiryDate" name="expiryDate" required maxlength="7" placeholder="MM/YYYY">
            </div>
            <div>
                <label for="cvv">CVV</label>
                <input type="text" id="cvv" name="cvv" required maxlength="4" placeholder="CVV">
            </div>
        </div>
<br>
        <div class="input-group">
            <label for="purchaseAmount">Amount</label>
            <input type="text" id="purchaseAmount" name="purchaseAmount" value="" required placeholder="Amount">
        </div>

        <input type="submit" value="Start Payment">
        <?php if (!empty($errorMessage)): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
    </form>

    <script>
        document.getElementById('expiryDate').addEventListener('input', function(e) {
            let value = e.target.value;
            if (value.length === 2 && !value.includes('/')) {
                e.target.value = value + '/';
            }
        });
    </script>

</body>
</html>
