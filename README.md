# Nimiq Payment Validator

**nimiq-payment-validator** is a PHP library designed to interact seamlessly with the Nimiq cryptocurrency network, with a particular focus on transaction validation. Whether you're building an application that requires precise payment handling or integrating Nimiq transactions into your existing system, the library provides the tools you need to ensure accurate and reliable transaction processing.

## Table of Contents

1. [Features](#features)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
    - [Validating a Transaction](#validating-a-transaction)
    - [Handling Payment Results](#handling-payment-results)
5. [Payment States](#payment-states)
6. [Payment Strategies](#payment-strategies)
    - [PaidStrategy](#paidstrategy)
    - [OverpaidStrategy](#overpaidstrategy)
    - [UnderpaidStrategy](#underpaidstrategy)
7. [API Gateway](#api-gateway)
    - [Default: NimiqWatchApiGateway](#default-nimiqwatchapigateway)
    - [Custom API Gateways](#custom-api-gateways)
8. [Examples](#examples)
    - [Basic Transaction Validation](#basic-transaction-validation)
    - [Customizing Payment Strategies](#customizing-payment-strategies)
9. [Testing](#testing)
10. [Contributing](#contributing)

---

## Features

- **Transaction Validation:** Verify the integrity and correctness of Nimiq transactions.
- **Payment State Determination:** Automatically categorize transactions into states such as PAID, OVERPAID, UNDERPAID, FAILED, or NOT_FOUND based on defined strategies and thresholds.
- **Flexible Strategies:** Utilize default payment strategies or define custom ones to suit your application's needs.
- **Extensible API Gateways:** Interact with the Nimiq network using the default NimiqWatch API or integrate other gateways as required.
- **Comprehensive Logging:** Track and log transaction validation processes for monitoring and debugging.
- **Unit Tested:** Ensures reliability with extensive PHPUnit test coverage.

## Installation

Install via Composer:

```bash
composer require hostme/nimiq-payment-validator
```

## Configuration

Configure library by setting up the API gateway, receiver address, and payment thresholds. The library uses the **NimiqWatchApiGateway** by default but allows for customization.

### Setting Up the Default API Gateway

```php
use HostMe\NimiqLib\Validator\Gateway\NimiqWatchApiGateway;
use GuzzleHttp\Client;
use HostMe\NimiqLib\Validator\TransactionValidator;
use Psr\Log\LoggerInterface;

// Initialize Guzzle HTTP client
$httpClient = new Client();

// Initialize the API gateway (default is 'main' network)
$apiGateway = new NimiqWatchApiGateway('main', null, $httpClient);

// Initialize logger (e.g., Monolog)
$logger = new \Monolog\Logger('nimiq_logger');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('path/to/your.log', \Monolog\Logger::INFO));

// Define receiver's Nimiq address
$receiverAddress = 'NQ01 RECEIVER';

// Initialize Transaction Validator with default strategies and thresholds
$validator = new TransactionValidator(
    $apiGateway,
    $receiverAddress,
    [], // Empty array to use default strategies
    $logger
);
```

### Customizing Payment Thresholds and Strategies

You can customize the thresholds for overpaid and underpaid transactions and define custom payment strategies.

```php
use HostMe\NimiqLib\Payment\Strategy\UnderpaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\OverpaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\PaidStrategy;
use HostMe\NimiqLib\Payment\PaymentStateComputer;

// Define custom thresholds
$underpaidThreshold = 200.0; // e.g., 200 units
$overpaidThreshold = 200.0;  // e.g., 200 units

// Initialize payment strategies
$strategies = [
    new UnderpaidStrategy($underpaidThreshold),
    new OverpaidStrategy($overpaidThreshold),
    new PaidStrategy(),
];

// Initialize PaymentStateComputer with custom strategies
$paymentStateComputer = new PaymentStateComputer($strategies);

// Initialize Transaction Validator with custom PaymentStateComputer
$validator = new TransactionValidator(
    $apiGateway,
    $receiverAddress,
    $strategies,
    $logger
);
```

## Usage

### Validating a Transaction

Use the `validateTransaction` method to validate a transaction by its hash and expected amount.

```php
use HostMe\NimiqLib\Model\PaymentResult;

$transactionHash = 'ABCD1234...'; // Replace with actual transaction hash
$expectedAmount = '500000'; // Expected amount in smallest units (e.g., uloki)

try {
    // Validate the transaction
    $paymentResult = $validator->validateTransaction($transactionHash, $expectedAmount);
    
    // Handle the result
    echo "Payment State: " . $paymentResult->getState();
    if ($paymentResult->getMessage()) {
        echo " - " . $paymentResult->getMessage();
    }
} catch (\HostMe\NimiqLib\Exception\InvalidTransactionHashException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Handling Payment Results

The `PaymentResult` object provides both the payment state and an optional message to explain the state.

```php
use HostMe\NimiqLib\Model\PaymentState;

if ($paymentResult->getState() === PaymentState::OVERPAID) {
    echo "Payment exceeded the required amount: " . $paymentResult->getMessage();
} elseif ($paymentResult->getState() === PaymentState::UNDERPAID) {
    echo "Payment is less than the required amount: " . $paymentResult->getMessage();
} elseif ($paymentResult->getState() === PaymentState::PAID) {
    echo "Payment is exact.";
} else {
    echo "Payment validation failed: " . $paymentResult->getMessage();
}
```

## Payment States

The library categorizes transaction validation results into the following states:

- **PAID:** The transaction amount exactly matches the expected amount.
- **OVERPAID:** The transaction amount exceeds the expected amount but does not exceed the defined overpaid threshold.
- **UNDERPAID:** The transaction amount is less than the expected amount but does not fall below the defined underpaid threshold.
- **FAILED:** The transaction amount exceeds the overpaid or underpaid thresholds, or other validation failures occur.
- **NOT_FOUND:** The transaction hash does not correspond to any transaction in the network.

### PaymentState Class

```php
namespace HostMe\NimiqLib\Model;

class PaymentState
{
    public const PAID = 'PAID';
    public const OVERPAID = 'OVERPAID';
    public const UNDERPAID = 'UNDERPAID';
    public const FAILED = 'FAILED';
    public const NOT_FOUND = 'NOT_FOUND';
}
```

## Payment Strategies

The library utilizes a strategy pattern to determine the payment state based on transaction details and predefined thresholds. The library includes three default strategies:

### PaidStrategy

**Description:** Identifies transactions where the received amount exactly matches the expected amount.

### OverpaidStrategy

**Description:** Identifies transactions where the received amount exceeds the expected amount but does not exceed the defined overpaid threshold.

### UnderpaidStrategy

**Description:** Identifies transactions where the received amount is less than the expected amount but does not fall below the defined underpaid threshold.

**Note:** Ensure that the logic within the `matches` method aligns with your application's requirements. The current implementation marks a transaction as **OVERPAID** or **UNDERPAID** only if the overpaid or underpaid amount is **within** the defined thresholds. Transactions exceeding these thresholds are marked as **FAILED**.

## API Gateway

The library interacts with the Nimiq network through API gateways. By default, it uses the **NimiqWatchApiGateway**, but you can integrate custom gateways as needed.

### Default: NimiqWatchApiGateway

**Description:** Utilizes the NimiqWatch API to fetch transaction details based on transaction hashes.

**Implementation:**

```php
use HostMe\NimiqLib\Validator\Gateway\NimiqWatchApiGateway;
use GuzzleHttp\ClientInterface;

// Initialize Guzzle HTTP client
$httpClient = new \GuzzleHttp\Client();

// Initialize the API gateway for the main network
$apiGateway = new NimiqWatchApiGateway('main', null, $httpClient);

// For the test network, specify 'test' and a custom API domain if needed
$testApiGateway = new NimiqWatchApiGateway('test', null, $httpClient);
```

**Parameters:**

- **network:** `'main'` or `'test'` to specify the Nimiq network.
- **apiDomain:** Optional custom API domain. Defaults based on the network.
- **httpClient:** An instance of `GuzzleHttp\ClientInterface`.
- **rateLimit:** Optional rate limit in milliseconds between requests. Default is `1000` ms.

### Custom API Gateways

To integrate a custom API gateway, implement the `ApiGatewayInterface`:

```php
use HostMe\NimiqLib\Validator\Gateway\ApiGatewayInterface;
use HostMe\NimiqLib\Model\Transaction;

class CustomApiGateway implements ApiGatewayInterface
{
    public function getTransactionByHash(string $transactionHash): ?Transaction
    {
        // Implement your custom API interaction here
    }
}
```

**Usage:**

```php
$customApiGateway = new CustomApiGateway();
$validator = new TransactionValidator(
    $customApiGateway,
    'NQ01 RECEIVER',
    $strategies,
    $logger
);
```

## Examples

### Basic Transaction Validation

```php
<?php

require 'vendor/autoload.php';

use HostMe\NimiqLib\Validator\Gateway\NimiqWatchApiGateway;
use HostMe\NimiqLib\Validator\TransactionValidator;
use HostMe\NimiqLib\Payment\Strategy\UnderpaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\OverpaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\PaidStrategy;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Initialize components
$httpClient = new Client();
$apiGateway = new NimiqWatchApiGateway('main', null, $httpClient);
$logger = new Logger('nimiq_logger');
$logger->pushHandler(new StreamHandler('path/to/your.log', Logger::INFO));

$receiverAddress = 'NQ01 RECEIVER';

// Define payment strategies with thresholds
$underpaidThreshold = 100.0; // e.g., 100 units
$overpaidThreshold = 100.0;  // e.g., 100 units

$strategies = [
    new UnderpaidStrategy($underpaidThreshold),
    new OverpaidStrategy($overpaidThreshold),
    new PaidStrategy(),
];

// Initialize Transaction Validator
$validator = new TransactionValidator(
    $apiGateway,
    $receiverAddress,
    $strategies,
    $logger
);

// Transaction details
$transactionHash = 'ABCD1234...'; // Replace with actual transaction hash
$expectedAmount = '500000'; // Expected amount in smallest units

try {
    // Validate the transaction
    $paymentResult = $validator->validateTransaction($transactionHash, $expectedAmount);
    
    // Handle the result
    echo "Payment State: " . $paymentResult->getState() . PHP_EOL;
    if ($paymentResult->getMessage()) {
        echo "Message: " . $paymentResult->getMessage() . PHP_EOL;
    }
} catch (\HostMe\NimiqLib\Exception\InvalidTransactionHashException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
```

### Customizing Payment Strategies

```php
<?php

use HostMe\NimiqLib\Payment\Strategy\UnderpaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\OverpaidStrategy;
use HostMe\NimiqLib\Payment\Strategy\PaidStrategy;
use HostMe\NimiqLib\Payment\PaymentStateComputer;

// Define custom thresholds
$underpaidThreshold = 200.0;
$overpaidThreshold = 200.0;

// Initialize custom strategies
$strategies = [
    new UnderpaidStrategy($underpaidThreshold),
    new OverpaidStrategy($overpaidThreshold),
    new PaidStrategy(),
];

// Initialize PaymentStateComputer
$paymentStateComputer = new PaymentStateComputer($strategies);

// Use PaymentStateComputer in TransactionValidator as shown previously
```

## Testing

Library comes with comprehensive PHPUnit tests to ensure reliability and correctness. To run the tests:

1. **Install Development Dependencies:**

   Ensure that development dependencies are installed via Composer:

   ```bash
   composer install --dev
   ```

2. **Run PHPUnit:**

   Execute the test suite using PHPUnit:

   ```bash
   ./vendor/bin/phpunit
   ```

3. **View Coverage Report (Optional):**

   To generate a code coverage report:

   ```bash
   ./vendor/bin/phpunit --coverage-html coverage
   ```

   Open the generated `coverage/index.html` in your browser to view the detailed report.

**Test Structure:**

- **Model Tests:** Verify the integrity of model classes (`Transaction`, `PaymentState`, `PaymentResult`).
- **Strategy Tests:** Ensure each payment strategy correctly identifies transaction states based on amounts and thresholds.
- **PaymentStateComputer Tests:** Confirm that the payment state computer accurately determines the payment state using defined strategies.
- **API Gateway Tests:** Mock API responses to test the behavior of API gateways under various scenarios.
- **TransactionValidator Tests:** Validate the end-to-end transaction validation process, including handling of different payment states and error conditions.

## Contributing

Contributions are welcome! Whether it's reporting a bug, suggesting a feature, or submitting a pull request, your input helps improve it.
