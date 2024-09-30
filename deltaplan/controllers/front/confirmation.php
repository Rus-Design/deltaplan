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

class DeltaplanConfirmationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $cartId = Tools::getValue('cart_id');
        $secureKey = Tools::getValue('secure_key');
        $cart = new Cart((int) $cartId);
        $paymentStatusApproved = Configuration::get('PS_OS_PAYMENT');
        $paymentStatusError = Configuration::get('PS_OS_ERROR');
        $getAllValues = Tools::getAllValues();
        $currencyId = (int) Context::getContext()->currency->id;
        $orderId = Order::getOrderByCartId((int) $cart->id);
        $moduleId = $this->module->id;
        if (isset($getAllValues['status'])) {
            if ($getAllValues['status'] == 'success') {
                $this->module->validateOrder($cartId, $paymentStatusApproved, $cart->getOrderTotal(), $this->module->displayName, null, array(), $currencyId, false, $secureKey);
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cartId . '&id_module=' . $moduleId . '&id_order=' . $orderId . '&key=' . $secureKey);
            } else {
                $this->module->validateOrder($cartId, $paymentStatusError, $cart->getOrderTotal(), $this->module->displayName, null, array(), $currencyId, false, $secureKey);
                Tools::redirect($this->context->link->getModuleLink('deltaplan', 'error', ['cart_id'=>$cart->id, 'secure_key'=>$cart->secure_key], true));
            }
        } else {
            $deltaplanApi = new DeltaplanApi(Configuration::get('DELTAPLAN_TOKEN'),Configuration::get('DELTAPLAN_MERCHANT_ID'));
            $getPayment = $deltaplanApi->getPayment(strval($orderId));

            if ($getPayment->status == 'success') {
                $this->module->validateOrder($cartId, $paymentStatusApproved, $cart->getOrderTotal(), $this->module->displayName, null, array(), $currencyId, false, $secureKey);
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cartId . '&id_module=' . $moduleId . '&id_order=' . $orderId . '&key=' . $secureKey);
            } else {
                $this->module->validateOrder($cartId, $paymentStatusError, $cart->getOrderTotal(), $this->module->displayName, null, array(), $currencyId, false, $secureKey);
                Tools::redirect($this->context->link->getModuleLink('deltaplan', 'error', ['cart_id'=>$cart->id, 'secure_key'=>$cart->secure_key], true));
            }
        }
    }
}