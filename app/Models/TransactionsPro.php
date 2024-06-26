<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\NinjaForms\Models;

use BeycanPress\CryptoPay\Models\AbstractTransaction;

class TransactionsPro extends AbstractTransaction
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
