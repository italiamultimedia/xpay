# italiamultimedia/xpay

An XPay (Nexi) implementation.

Currently implemented functionality: 

- [Pagamento semplice](https://ecommerce.nexi.it/specifiche-tecniche/codicebase.html)
- [Pagamento ricorrente](https://ecommerce.nexi.it/specifiche-tecniche/pagamentoricorrente/introduzione.html)

---

## Pagamento semplice

### Extend class `AbstractSimplePaymentService`

- implement `SimplePaymentServiceInterface`:
  - `createCancelUrl`;
  - `createNotificationUrl`;
  - `createReturnUrl`;

```php
final class SimplePaymentService extends AbstractSimplePaymentService implements SimplePaymentServiceInterface
{
    protected function createCancelUrl(string $orderId): string
    {
        ...
    }

    protected function createNotificationUrl(string $orderId): string
    {
        ...
    }

    protected function createReturnUrl(string $orderId): string
    {
        ...
    }
}
```

### Extend class `AbstractRequestInputService`

- optionally implement your own validation rules;

```php
final class RequestInputService extends AbstractRequestInputService implements RequestInputServiceInterface
{
    public const KEY_LANGUAGE = 'lang';
    
    public const KEY_ORDER_ID = 'orderId';
    
    protected function getValidationRule(string $key): string
    {
        return match ($key) {
            self::KEY_ORDER_ID => '/^[a-f0-9]{42}$/',
            default => parent::getValidationRule($key),
        };
    }
    
    protected function validateInput(string $key, string $value): bool
    {
        if ($key === self::KEY_LANGUAGE) {
            return $this->validateLanguageCode($value);
        }

        return parent::validateInput($key, $value);
    }
    
    private function validateLanguageCode(string $value): bool
    {
        if (!in_array($value, ['en', 'it'], true)) {
            throw new UnexpectedValueException('Invalid data.');
        }

        return true;
    }
}
```

### Payment request

- [Avvio pagamento](https://ecommerce.nexi.it/specifiche-tecniche/codicebase.html)

```php
<!doctype html>
<html>
    <head>
        ...
    </head>
    <body>
        <form id="payment_form" method="POST" action="<?=$paymentService->getSimplePaymentStartUrl()?>">
            <?php
            foreach (
                $paymentService->createPaymentRequestParameters(
                    $languageCode,
                    $orderId,
                    $orderInformation->total,
                ) as $key => $value
            ) { ?>
                <input type="hidden" name="<?=$key?>" value="<?=$value?>" />
            <?php } ?>
            <button type="submit" class="btn btn-primary">
                <?=$languageCode === 'it' ? 'Acquista ora' : 'Buy now'?>
            </button>
        </form>
        <script>
            document.getElementById('payment_form').submit();
        </script>
    </body>
</html>
```

### Return page

```php
// Validate order (get info from storage)
...

/**
 * Special situation: "mac" can be missing from the request.
 * Eg. try to pay already paid transaction.
 * In that situation we don't want to have a transaction error, however we also can not trust the request.
 * Simply do not process transaction.
 */
$processTransaction = true;
try {
    // Try to get mac
    $requestInputService->getValidatedString(RequestInput::MAC);
} catch (OutOfBoundsException) {
    $processTransaction = false;
}

if ($processTransaction) {
    // Validate transaction. Uses input data (_GET or _POST).
    $requestInputService->validateInputMac();

    // Store transaction result.
    ...
}

// Redirect back to website.
...
```

---

## Pagamento ricorrente

### Extend class `AbstractRecurringPaymentService`

- implement `RecurringPaymentServiceInterface`:
    - `createCancelUrl`;
    - `createNotificationUrl`;
      - `createReturnUrl`;  

```php
final class RecurringPaymentService extends AbstractRecurringPaymentService implements RecurringPaymentServiceInterface
{
    protected function createCancelUrl(string $orderId): string
    {
        ...
    }

    protected function createNotificationUrl(string $orderId): string
    {
        ...
    }

    protected function createReturnUrl(string $orderId): string
    {
        ...
    }
}
```

### Extend class `AbstractRequestInputService`

- optionally implement your own validation rules;
- see the same section in "Pagamento semplice"

### First payment: Payment request

- [Primo pagamento](https://ecommerce.nexi.it/specifiche-tecniche/pagamentoricorrente/primopagamento.html)

```php
<!doctype html>
<html>
    <head>
        ...
    </head>
    <body>
        <form id="payment_form" method="POST" action="<?=$paymentService->getRecurringPaymentInitialUrl()?>">
            <?php
            foreach (
                $paymentService->createInitialPaymentRequestParameters(
                    $languageCode,
                    $numContratto,
                    $orderInformation->total,
                ) as $key => $value
            ) { ?>
                <input type="hidden" name="<?=$key?>" value="<?=$value?>" />
            <?php } ?>
            <button type="submit" class="btn btn-primary">
                <?=$languageCode === 'it' ? 'Acquista ora' : 'Buy now'?>
            </button>
        </form>
        <script>
            document.getElementById('payment_form').submit();
        </script>
    </body>
</html>
```

### First payment: Return page

- see the same section in "Pagamento semplice"


### Subsequent payments

- [Pagamenti successivi](https://ecommerce.nexi.it/specifiche-tecniche/pagamentoricorrente/pagamentisuccessivi.html)

- Use `SubsequentPaymentService`, method `executeSubsequentPayment(string $numeroContratto, float $orderTotal, string $scadenza): AbstractResponseData`.
- Can also use `SubsequentPaymentService`.`getResponse`, returns `Psr\Http\Message\ResponseInterface` if run after `executeSubsequentPayment`, null otherwise. 
- Check if the result is `PositiveResponseData` or `NegativeResponseData` and act accordingly.

---

## Development

```shell
# Lint
composer check:lint && \
# Code style
composer check:phpcs && \
# PHPStan
composer check:phpstan && \
# Phan
composer check:phan && \
# PHPMD
composer check:phpmd && \
# Psalm
composer check:psalm
```
