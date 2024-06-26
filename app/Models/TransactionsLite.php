<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\NinjaForms\Models;

use BeycanPress\CryptoPayLite\Models\AbstractTransaction;

class TransactionsLite extends AbstractTransaction
{
    public string $addon = 'ninjaforms';

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct('ninjaforms_transaction');
    }
}
