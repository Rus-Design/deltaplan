<?php
/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Andrey Ushakov <info@rus-design.com>
*  @copyright  2024 rus-design
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of rus-design
*/

use Deltaplan\DeltaplanApi;

class DeltaplanRedirectModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        if ($id_cart = Tools::getValue('id_cart')) {
            $cart = new Cart($id_cart);
            if (!Validate::isLoadedObject($cart)) {
                $cart = $this->context->cart;
            }
        } else {
            $cart = $this->context->cart;
        }

        $currency = new Currency((int)($cart->id_currency));
        $currencyCode = trim($currency->iso_code);

        $orderId = Order::getOrderByCartId($cart->id);

        $data = [
            'currency' => $currencyCode,
            'description' => $this->module->l('Order #') . $orderId . $this->module->l(' on ') . Configuration::get('PS_SHOP_NAME'),
            'id' => strval($orderId),
            'isTest' => Configuration::get('DELTAPLAN_TEST'),
            'orderId' => 'order#' . $orderId,
            'price' => $cart->getOrderTotal(),
            'redirectUrl' => $this->context->link->getModuleLink('deltaplan', 'confirmation', ['order_id' => $orderId, 'cart_id'=>$cart->id, 'secure_key'=>$cart->secure_key], true)
        ];

        $deltaplanApi = new DeltaplanApi(Configuration::get('DELTAPLAN_TOKEN'),Configuration::get('DELTAPLAN_MERCHANT_ID'));
        $createPayment = $deltaplanApi->createPayment($data);

        if ($createPayment->status == 'token_error' || isset($createPayment->errors)) {
            Tools::redirectLink($this->context->link->getPageLink('order', null, null, 'step=3'));
        } else {
            if (isset($createPayment->paymentUrl) && $createPayment->type == 'payment' && ($createPayment->status == 'pending' || $createPayment->status == 'inProgress')) {
                Tools::redirectLink($createPayment->paymentUrl);
            } else {
                Tools::redirectLink($this->context->link->getModuleLink('deltaplan', 'confirmation', ['cart_id'=>$cart->id, 'secure_key'=>$cart->secure_key], true));
            }
        }

        if ($cart->id_customer == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
    }
}