<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\NinjaForms;

class Gateway extends \NF_Abstracts_PaymentGateway
{
    /**
     * @var string
     */
    // phpcs:ignore
    protected $_name = 'CryptoPay';

    /**
     * @var string
     */
    // phpcs:ignore
    protected $_slug = 'cryptopay';

    /**
     * @var array<mixed>
     */
    // phpcs:ignore
    protected $_settings = [];

    /**
     * GatewayLite constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array<mixed> $actionSettings
     * @param string $formId
     * @param array<mixed> $data
     * @return void
     */
    // phpcs:ignore
    public function process($actionSettings, $formId, $data): void
    {
        return;
    }
}
