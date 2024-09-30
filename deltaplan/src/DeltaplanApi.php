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

namespace Deltaplan;

class DeltaplanApi {

    protected $token = '';
    protected $merchantId = '';
    protected $url = 'https://crypto.deltaplan.pro/api/merchant/v1/';

    public function __construct($token, $merchantId) {
        $this->token = $token;
        $this->merchantId = $merchantId;
    }

    public function createPayment($data)
    {
        $createPaymentUrl = $this->url . 'merchant/'.$this->merchantId.'/payment';
        $method = 'POST';
        return $this->sendRequest($createPaymentUrl, $data, $method);
    }

    public function getPayment($paymentId)
    {
        $getPaymentUrl = $this->url . 'merchant/'.$this->merchantId.'/payment/'.$paymentId;
        $method = 'GET';
        return $this->sendRequest($getPaymentUrl, [], $method);
    }

    protected function sendRequest($url, $data, $method)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-type: application/json",
            "Authorization: Bearer $this->token"
        ]);

        if ($method == 'POST') {
            $fields = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $response = curl_exec($ch);
        $response_decode = json_decode($response);

        curl_close($ch);

        return $response_decode;
    }
}