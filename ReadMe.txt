=== Trelis Crypto Payments ===
Contributors: ronantrelis
Donate link: https://shop.trelis.com/product/woocommerce-plugin-donation/
Tags: crypto, payment, ethereum, USDC, ether, eth, cryptocurrency, non-custodial, payments, payment gateway, metamask
Requires at least: 6.1
Tested up to: 6.1
Stable tag: 1.0.20
Requires PHP: 7.4
License: GPL-3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

Accept USDC or Ether payments directly to your wallet with no Trelis fees. Your customers pay by connecting any major Ethereum wallet.

== Description ==

= Non-custodial crypto payments made easy, with no Trelis fees =

Accept USDC or ETH directly to your wallet. Allow customers to pay by connecting any major Ethereum wallet (Metamask, Coinbase Wallet, Rainbow Wallet, Ledger, Trezor). 

= What are the benefits for me (the store owner)? =

* **Accept payments within mins.** Install the plugin and add api keys from Trelis.com
* **Get started for free.** Trelis does not charge transaction fees
* **Increase conversions** from crypto-native customers
* **Avoid chargebacks.**
* **Get immediate access to funds** deposited directly deposited to your wallet

= What are the benefits for my customers? =

* **Pay with any major Ethereum wallet.** Metamask, Coinbase, Rainbow, Ledger, Trezor
* **Gasless payments.** Customers can pay in USDC, no ETH required!
* **1% Discount** Customers get a 1% discount with Trelis Prime.

== Getting Started == 

1. Install the Trelis Crypto Payments plugin
1. Navigate to WooCommerce -> Settings -> Payments -> TrelisPay -> Manage
1. Navigate to [Trelis.com](Trelis.com) and connect your Ethereum wallet. This wallet will receive payments. It is highly recommended to use a cold wallet. You are solely responsible for custody of your funds. To offer gasless USDC payments, purchase gas credits from your dashboard.
1. Navigate to the api screen to create a new api key.
1. Copy the api webhook url from Wordpress and enter it on Trelis.com
1. Copy the apiKey, apiSecret and webhook secret from Trelis.com and enter them into Wordpress.
1. Press "Save changes" on Wordpress to confirm changes. Your plugin is now configured to accept Ethereum payments.
1. If you already have products priced in US dollars, customers can now pay with USDC on Ethereum. Alternately you can update products or create new products that are priced in USDC or ETH.

== Frequently Asked Questions ==

= How much are transaction fees? =

If gasless payments are turned on, the merchant pays for Ethereum transaction fees (gas). Otherwise, the customers pays for gas. Trelis does not charge a transaction fee on top of this.

= What currencies are supported? =

* Trelis supports WooCommerce stores with the following currencies: USD, EUR, BRL, GBP, CNY, JPY, INR, CAD, RUB, KRW, AUD, MXN, IDR, SAR, CHF, TWD, PLN, TRY, SEK, ARS, NOK, THB, ILS, NGN, AED, MYR, EGP, ZAR, SGD, PHP, VND, DKK, BDT, HKD, COP, PKR, CLP, IQD, CZK, RON, NZD .
* Customers will be charged in USDC.
* Alternately, this plugin allows merchants to directly price products in USDC or ETH.
* This plugin **will not work** for products priced in other currencies.

= What is the maximum payment amount? =

* The maximum payment amount is 100 USDC or 0.1 ETH. Reach out to [Support](https://docs.trelis.com/support) to inquire about our enterprise offerings.

= What are the terms and conditions of using Trelis Crypto Payments? =

* Users of Trelis Crypto Payments plugin with Trelis' api must agree to Trelis' [Terms of Service](https://docs.trelis.com/terms-of-service) as a condition of use.

= Why aren't payments showing as gasless? =

* Merchants can only offer gasless payments to customers if they have prepaid gas credits. Gas credits can be purchased at Trelis.com and must be purchased from the same account from which the api keys are generated. Further, payments will default to standard (i.e. customer pays for gas) if gas costs exceed 5% of the transaction amount.

== Screenshots ==

1. Payment gateways (see Trelis at the bottom)
2. Configuring the Trelis plugin with api keys
3. Sample checkout page offering Trelis Pay
4. Payment screen ("Trelis Art" will be replaced by your store name)
5. Supported Ethereum wallets

== Changelog ==

= Unreleased =
* Allow for recurring subscription payments

= 1.0.20 =
* Fix logo on checkout

= 1.0.19 =
* Add support for Spanish

= 1.0.18 =
* Provide payment in USDC for stores using all major currencies.
* Add option for Trelis prime (1% customer discount)

= 1.0.17 =
* Allow merchants to offer gasless payments

= 1.0.16 =
* First version live (stable release)

= 1.0.14 =
* Version submitted for WooCommerce review.

== Upgrade Notice ==

* There are no active upgrade notices.