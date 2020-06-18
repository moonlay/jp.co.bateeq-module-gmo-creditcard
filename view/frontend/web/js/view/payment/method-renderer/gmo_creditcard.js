/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/quote',
    ],
    function (
        $,
        Component, 
        urlBuilder,
        url,
        quote) {
        'use strict';

        var self;

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'Moonlay_GMOCreditCard/payment/form'
            },

            initialize: function() {
                this._super();
                self = this;
            },

            getCode: function() {
                return 'gmo_creditcard';
            },

            getData: function() {
                return {
                    'method': this.item.method
                };
            },

            afterPlaceOrder: function () {
                window.location.replace(url.build('gmocreditcard/checkout/index'));
            },

            /*
             * This same validation is done server-side in InitializationRequest.validateQuote()
             */
            validate: function() {
                var billingAddress = quote.billingAddress();
                var shippingAddress = quote.shippingAddress();
                var allowedCountries = self.getAllowedCountries();
                var totals = quote.totals();
                var allowedCountriesArray = [];

                if(typeof(allowedCountries) == 'string' && allowedCountries.length > 0){
                    allowedCountriesArray = allowedCountries.split(',');
                }

                self.messageContainer.clear();

                if (!billingAddress) {
                    self.messageContainer.addErrorMessage({'message': '請求先住所を入力してください'});
                    return false;
                }

                if (!billingAddress.firstname || 
                    !billingAddress.lastname ||
                    !billingAddress.street ||
                    !billingAddress.postcode ||
                    billingAddress.firstname.length == 0 ||
                    billingAddress.lastname.length == 0 ||
                    billingAddress.street.length == 0 ||
                    billingAddress.postcode.length == 0) {
                    self.messageContainer.addErrorMessage({'message': '請求先住所に詳しく入力してください'});
                    return false;
                }

                if (allowedCountriesArray.indexOf(billingAddress.countryId) == -1 ||
                    allowedCountriesArray.indexOf(shippingAddress.countryId) == -1) {
                    self.messageContainer.addErrorMessage({'message': 'この国からの注文はGMOマルチペイメントではサポートされていませんので別の支払いオプションを選択してください。'});
                    return false;
                }

                if (totals.grand_total < 1) {
                    self.messageContainer.addErrorMessage({'message': '¥1 以下の支払いはGMOマルチペイメントではサポートされていません'});
                    return false;
                }

                return true;
            },

            getTitle: function() {
                return window.checkoutConfig.payment.gmo_creditcard.title;
            },

            getDescription: function() {
                return window.checkoutConfig.payment.gmo_creditcard.description;
            },
            
            getLogo:function(){
                var logo = window.checkoutConfig.payment.gmo_creditcard.logo;

                return logo;
            },

            getAllowedCountries: function() {
                return window.checkoutConfig.payment.gmo_creditcard.allowed_countries;
            }

        });
    }
);