<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Retrieve the information returned from the ACS (Access Control Server)
$status = isset($_POST['Status']) ? $_POST['Status'] : null;
$md = isset($_POST['Xid']) ? $_POST['Xid'] : null; 
$cavv = isset($_POST['Cavv']) ? $_POST['Cavv'] : null;
$eci = isset($_POST['Eci']) ? $_POST['Eci'] : null;

// Retrieve the VerifyEnrollmentRequestId from the session and use it as MpiTransactionId
$mpiTransactionId = isset($_SESSION['verifyEnrollmentRequestId']) ? $_SESSION['verifyEnrollmentRequestId'] : null;

// Retrieve the card information stored in the session from the initial request
$pan = isset($_SESSION['pan']) ? $_SESSION['pan'] : null;
$expiryDate = isset($_SESSION['expiryDate']) ? $_SESSION['expiryDate'] : null;
$cvv = isset($_SESSION['cvv']) ? $_SESSION['cvv'] : null;
$currencyAmount = isset($_SESSION['purchaseAmount']) ? $_SESSION['purchaseAmount'] : null;

// Update ExpiryDate format to YYYYMM
if ($expiryDate && strlen($expiryDate) === 4) {
    $expiryYear = '20' . substr($expiryDate, 0, 2); // Convert the year to 20YY format
    $expiryMonth = substr($expiryDate, 2, 2); // Leave the month unchanged
    $expiryDateFormatted = $expiryYear . $expiryMonth; // Combine into YYYYMM format
} else {
    $expiryDateFormatted = $expiryDate; // Use as is if it's already in the correct format
}

// Display the retrieved information on the screen
echo '<h3>ACS Response:</h3>';
echo '<pre>';
echo 'Status: ' . htmlspecialchars($status) . "\n";
echo 'Xid (MD): ' . htmlspecialchars($md) . "\n";
echo 'Cavv: ' . htmlspecialchars($cavv) . "\n";
echo 'Eci: ' . htmlspecialchars($eci) . "\n";
echo '</pre>';

echo '<h3>Session Information:</h3>';
echo '<pre>';
echo 'Pan: ' . htmlspecialchars($pan) . "\n";
echo 'ExpiryDate: ' . htmlspecialchars($expiryDateFormatted) . "\n";
echo 'Cvv: ' . htmlspecialchars($cvv) . "\n";
echo 'CurrencyAmount: ' . htmlspecialchars($currencyAmount) . "\n";
echo 'VerifyEnrollmentRequestId (MpiTransactionId): ' . htmlspecialchars($mpiTransactionId) . "\n";
echo '</pre>';

if (!$mpiTransactionId || !$pan || !$expiryDateFormatted || !$cvv || !$currencyAmount) {
    error_log('Required information is missing. Please check the 3D Secure verification.');
    die('Required information is missing.');
}

if ($status == 'Y') {
    if ($md && $cavv && $eci) {
        // Create the XML message to be sent to the Virtual POS (Point of Sale)
        $merchantId = "YOUR_MERCHANT_ID"; 
        $merchantPassword = "YOUR_MERCHANT_PASSWORD";
        $terminalNo = "YOUR_TERMINAL_NO";
        $currencyCode = "949"; // Example currency code
        $transactionId = uniqid(); 
        $clientIp = $_SERVER['REMOTE_ADDR']; 
        $orderId = "order_" . date("YmdHis"); 
        $orderDescription = "Transaction description";

        $xml_data = <<<XML
<VposRequest>
    <MerchantId>$merchantId</MerchantId>
    <Password>$merchantPassword</Password>
    <TerminalNo>$terminalNo</TerminalNo>
    <Pan>$pan</Pan>
    <Expiry>$expiryDateFormatted</Expiry>
    <CurrencyAmount>$currencyAmount</CurrencyAmount>
    <CurrencyCode>$currencyCode</CurrencyCode>
    <TransactionType>Sale</TransactionType>
    <TransactionId>$transactionId</TransactionId>
    <Cvv>$cvv</Cvv>
    <ECI>$eci</ECI>
    <CAVV>$cavv</CAVV>
    <MpiTransactionId>$mpiTransactionId</MpiTransactionId>
    <OrderId>$orderId</OrderId>
    <OrderDescription>$orderDescription</OrderDescription>
    <ClientIp>$clientIp</ClientIp>
    <TransactionDeviceSource>0</TransactionDeviceSource>
</VposRequest>
XML;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onlineodeme.bank.com/VposService/v3/Vposreq.aspx");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['prmstr' => $xml_data]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 59);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo '<h3>Response from the Bank:</h3>';
            echo '<pre>';
            echo htmlspecialchars($response);
            echo '</pre>';

            $xml = simplexml_load_string($response);
            if ($xml !== false) {
                if (isset($xml->ResultCode) && $xml->ResultCode == "0000") {
                    // Payment successful, redirect
                    header("Location: /payment-success");
                    exit(); // Use exit() to prevent further script execution after redirect.
                } elseif (isset($xml->ResultCode) && $xml->ResultCode == "0051") {
                    // Insufficient funds, redirect
                    header("Location: /payment-failure");
                    exit(); // Use exit() to prevent further script execution after redirect.
                } else {
                    // Another error condition
                    echo "An error occurred during the payment process: ";
                    echo '<pre>';
                    print_r($xml);
                    echo '</pre>';
                }
            } 
        }
    } else {
        echo "Required parameters are missing.";
    }
} else {
    echo "3D Secure verification failed.";
}
?>
