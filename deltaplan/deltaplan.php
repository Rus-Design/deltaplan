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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Deltaplan extends PaymentModule
{

    public function __construct()
    {
        $this->name = 'deltaplan';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Andrey U';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '';
        $this->_postErrors = [];

        parent::__construct();
        
        $this->displayName = $this->l('Deltaplan');
        $this->description = $this->l('Accept crypto payments with Deltaplan');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $ow_status = Configuration::get('DELTAPLAN_STATE_WAITING');
        if ($ow_status === false) {
            $orderState = new OrderState();
        } else {
            $orderState = new OrderState((int)$ow_status);
        }
        
        $orderState->name = array();
        
        foreach (Language::getLanguages() as $language) {
            if (Tools::strtolower($language['iso_code']) == 'ru') {
                $orderState->name[$language['id_lang']] = 'Ожидание завершения оплаты (Deltaplan)';
            } else {
                $orderState->name[$language['id_lang']] = 'Awaiting for payment (Deltaplan)';
            }
        }
        
        $orderState->send_email = false;
        $orderState->color = '#4169E1';
        $orderState->hidden = false;
        $orderState->module_name = $this->name;
        $orderState->delivery = false;
        $orderState->logable = false;
        $orderState->invoice = false;
        $orderState->unremovable = false;
        $orderState->save();
        
        Configuration::updateValue('DELTAPLAN_STATE_WAITING', (int)$orderState->id);

        copy(_PS_MODULE_DIR_ . 'deltaplan/views/img/logo.gif', _PS_IMG_DIR_ . 'os/' . (int)$orderState->id . '.gif');
        
        return parent::install() &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayOrderConfirmation') &&
            $this->registerHook('displayPaymentReturn');
    }

    public function uninstall()
    {
        Configuration::deleteByName('DELTAPLAN_STATE_WAITING');
        Configuration::deleteByName('DELTAPLAN_CANCEL_STATE');
        Configuration::deleteByName('DELTAPLAN_TEST');
        Configuration::deleteByName('DELTAPLAN_TOKEN');
        Configuration::deleteByName('DELTAPLAN_MERCHANT_ID');
        
        $orderStateId = Configuration::get('DELTAPLAN_STATE_WAITING');
        if ($orderStateId) {
            $orderState = new OrderState();
            $orderState->id = $orderStateId;
            $orderState->delete();
            unlink(_PS_IMG_DIR_ . 'os/' . (int)$orderState->id . '.gif');
        }

        return $this->unregisterHook('paymentOptions') &&
            $this->unregisterHook('displayPaymentReturn') &&
            $this->unregisterHook('actionOrderStatusPostUpdate') &&
            parent::uninstall();
    }

    public function getContent()
    {
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        if (((bool)Tools::isSubmit('submitDeltaplanModule')) == true) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->postProcess();
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                foreach ($this->_postErrors as $err) {
                    $output .= $this->displayError($err);
                }
            }
        } else {
            $output .= '<br />';
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        return $output.$this->renderForm();
    }

    public function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDeltaplanModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    public function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Token'),
                        'name' => 'DELTAPLAN_TOKEN',
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Merchant ID'),
                        'name' => 'DELTAPLAN_MERCHANT_ID',
                        'required' => true
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Test'),
                        'name' => 'DELTAPLAN_TEST',
                        'desc' => $this->l('Test mode ON or OFF'),
                        'values' => [
                            [
                                'id' => 'test_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ],
                            [
                                'id' => 'test_off',
                                'value' => 0,
                                'label' => $this->l('No')
                                ]
                            ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save')
                    ]
                    ]
                ];
    }

    public function getConfigFieldsValues()
    {
        return array(
            'DELTAPLAN_TOKEN' => Configuration::get('DELTAPLAN_TOKEN'),
            'DELTAPLAN_MERCHANT_ID' => Configuration::get('DELTAPLAN_MERCHANT_ID'),
            'DELTAPLAN_TEST' => Configuration::get('DELTAPLAN_TEST')
        );
    }

    protected function _postValidation()
    {
        if (Tools::isSubmit('submitDeltaplanModule')) {
            if (!Tools::getValue('DELTAPLAN_TOKEN')) {
                $this->_postErrors[] = $this->l('Token required');
            } elseif (!Tools::getValue('DELTAPLAN_MERCHANT_ID')) {
                $this->_postErrors[] = $this->l('Merchant ID required');
            }
        }
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFieldsValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function hookPaymentOptions($params)
    {
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart)) {
            return [];
        }

        $option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setCallToActionText($this->l('Deltaplan'))
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', array(), true));
        return [$option];
    }

    private function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        /** @var array $currencies_module */
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (empty($currencies_module)) {
            return false;
        }

        foreach ($currencies_module as $currency_module) {
            if ($currency_order->id == $currency_module['id_currency']) {
                return true;
            }
        }

        return false;
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'transaction' => $transaction,
            'transactionsLink' => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/displayPaymentReturn.tpl');
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
    
    }

    public function hookDisplayOrderConfirmation(array $params)
    {
        if ($this->active == false) {
            return false;
        }

        $order = $params['order'];
        $currency = new Currency($order->id_currency);

        if (strcasecmp($order->module, 'deltaplan') != 0) {
            return false;
        }

        if ($order->getCurrentOrderState()->id != (int)Configuration::get('PS_OS_ERROR')) {
            $this->context->smarty->assign('status', 'ok');
        }

        $this->context->smarty->assign(
            array(
                'id_order' => $order->id,
                'params' => $params,
                'total' => Tools::displayPrice($order->getOrdersTotalPaid(), $currency, false),
            )
        );

        return $this->context->smarty->fetch('module:deltaplan/views/templates/hook/displayOrderConfirmation.tpl');
    }
}