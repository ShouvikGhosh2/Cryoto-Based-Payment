(($) => {
    $(document).ready(() => {
        const { 
            paidTransaction,
            alreadyPaidMessage,
            paymentCompletedMessage
        } = ninja_forms_field_cryptopay;
        const helpers = window.cpHelpers || window.cplHelpers
        const app = window.CryptoPayApp || window.CryptoPayLiteApp
        const modal = window.CryptoPayModal || window.CryptoPayLiteModal

        const order = {}
        const params = {}

        let startedApp;
        let notPaidYet = true;

        const getFormById = (formId) => {
            return nfRadio.channel('app').request('get:form', formId);
        }

        const submitForm = (formId) => {
            const model = getFormById(formId);
            nfRadio.channel('form-' + model.get('id')).request('submit', model);
        }

        const addExtraData = (formId, key, value) => {
            nfRadio.channel('form-' + formId).request('add:extra', key, value);
        }

        app.events.add('confirmationCompleted', async ({transaction}) => {
            modal.close();
            notPaidYet = false;
            helpers.closePopup();
            await helpers.sleep(100);
            $('.overlay').remove();
            addExtraData(
                params.formId,
                'cryptopay_transaction_hash',
                transaction.id || transaction.hash
            );
            helpers.successPopup(paymentCompletedMessage)
            submitForm(params.formId);
        })

        const formController = Marionette.Object.extend({
            initialize: function(e) {
                this.listenTo(Backbone.Radio.channel('form'), 'render:view', this.registerHandlers);
            },
            registerHandlers: function(e) {
                const formId = e.model.get('id');
                if (paidTransaction && formId == paidTransaction.formId) {
                    addExtraData(
                        formId,
                        'cryptopay_transaction_hash',
                        paidTransaction.hash
                    );
                    const element = $('.nf-field-container.cryptopay-container');
                    element.find('.nf-after-field').append(alreadyPaidMessage)
                } else {
                    this.listenTo(Backbone.Radio.channel('fields'), 'change:model', this.fieldModelChange);
                    this.listenTo(Backbone.Radio.channel('forms'), 'after:submitValidation', this.validated);
                    Backbone.Radio.channel('form-' + formId).reply('maybe:submit', this.maybeSubmit, this, formId);
                }
            },
            fieldModelChange: function(model) {
                if ('cryptopay' != model.get('type')) {
                    return;
                }

                params.formId = model.get('formID');
                const form = getFormById(params.formId);
                order.amount = parseFloat(model.attributes.value);
                order.currency = form.get('settings').currency;
                
                if (startedApp) {
                    startedApp.reStart(order, params);
                } else {
                    startedApp = app.start(order, params);
                }
            },
            maybeSubmit: function(event) {
                if (notPaidYet && this.checkError(event)) {
                    modal.open();
                    return false;
                }
                return true;
            },
            checkError(event) {
                return !event.attributes.errors.length && !Object.values(event.attributes.fieldErrors).length;
            }
        });

        new formController();
    });
})(jQuery);