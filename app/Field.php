<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\NinjaForms;

// phpcs:disable PSR1.Methods.CamelCapsMethodName
// phpcs:disable Squiz.NamingConventions.ValidVariableName
// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint
// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint

use BeycanPress\CryptoPay\Payment;
use BeycanPress\CryptoPay\Integrator\Helpers;
use BeycanPress\CryptoPay\Integrator\Session;
use BeycanPress\CryptoPayLite\Payment as PaymentLite;

class Field extends \NF_Abstracts_Field
{
    /**
     * @var string
     */
    protected $_name;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var string
     */
    protected $_nicename;

    /**
     * @var string
     */
    protected $_section = 'misc';

    /**
     * @var string
     */
    protected $_icon = 'bitcoin';

    /**
     * @var string
     */
    protected $_templates = 'cryptopay';

    /**
     * @var string
     */
    protected $_test_value = '';

    /**
     * @var array<string>
     */
    protected $_settings = [];

    /**
     * @var array<string>
     */
    protected $_settings_only = [
        'default',
    ];

    /**
     * @var array<string>
     */
    protected $_use_merge_tags_include = [
        'calculations'
    ];

    /**
     * @var string|null
     */
    private ?string $transactionHash;

    /**
     * constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_nicename = Helpers::exists() ? 'CryptoPay' : 'CryptoPay Lite';
        $this->_name = $this->_type = 'cryptopay';
        $this->_settings['default']['use_merge_tags'] = [
            'include' => [
                'calcs'
            ],
            /* phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude */
            // this is not a query
            'exclude' => [
                'form',
                'fields',
                'label'
            ],
        ];

        $this->transactionHash = Session::get('ninjaforms_transaction_hash');

        add_filter('ninja_forms_localize_field_' . $this->_type, [$this, 'loadScripts']);
    }

    /**
     * @param array<mixed> $field
     * @return array<mixed>
     */
    public function loadScripts(array $field): array
    {
        add_action('wp_footer', function (): void {
            Helpers::run('addStyle', 'main.min.css');
            if (Helpers::exists()) {
                Helpers::run('ksesEcho', (new Payment('ninjaforms'))->modal());
            } else {
                Helpers::run('ksesEcho', (new PaymentLite('ninjaforms'))->modal());
            }

            wp_enqueue_script(
                'ninja-forms-cryptopay',
                NINJA_FORMS_CRYPTOPAY_URL . 'assets/js/main.js',
                ['jquery', 'wp-i18n', Helpers::run('getProp', 'mainJsKey')],
                NINJA_FORMS_CRYPTOPAY_VERSION,
                true
            );

            $paidTransaction = null;

            if ($this->transactionHash) {
                $model = Helpers::run('getModelByAddon', 'ninjaforms');

                $transaction = $model->findOneBy([
                    'hash' => $this->transactionHash,
                ]);

                if ($transaction) {
                    $paidTransaction = [
                        'hash' => $transaction->getHash(),
                        'formId' => $transaction->getParams()->get('formId'),
                    ];
                }
            }

            wp_localize_script(
                'ninja-forms-cryptopay',
                'ninja_forms_field_cryptopay',
                [
                    'paidTransaction' => $paidTransaction,
                    'alreadyPaidMessage' => esc_html__('A payment has already been made for this form, but the form has not been sent. Therefore please only submit the form.', 'ninjaforms-cryptopay'), // phpcs:ignore
                    'paymentCompletedMessage' => esc_html__('Payment completed successfully.', 'ninjaforms-cryptopay')
                ]
            );
        });

        return $field;
    }

    /**
     * @param array<string> $field
     * @param array<string> $data
     * @return mixed
     */
    public function validate($field, $data): mixed
    {
        $errors = parent::validate($field, $data);
        if (!empty($errors)) {
            return $errors;
        }

        $message = esc_html__(
            'Payment is not verified. Sending form has been aborted.',
            'ninjaforms-cryptopay'
        );

        $transactionHash = $data['extra']['cryptopay_transaction_hash'] ?? '';
        $sTransactionHash = Session::get('ninjaforms_transaction_hash');

        if ($sTransactionHash !== $transactionHash) {
            return $message;
        }

        $model = Helpers::run('getModelByAddon', 'ninjaforms');

        $transaction = $model->findOneBy([
            'hash' => $transactionHash,
        ]);

        if (!$transaction) {
            return $message;
        } else {
            Session::remove('ninjaforms_transaction_hash');
            return null;
        }
    }
}
