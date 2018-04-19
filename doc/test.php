#!/usr/bin/php
<?php

/**
 * Usage: run in cli
 *  - enter domain to test against in first param
 *  - type of test in second param
 *
 * example:
 * ./test.php magento.sharespine.se info_v2
 *
 * Prerequisites
 * - Set up an API-user in magento with user & pass as configured below
 * - Add access to "Core / Sharespine Plugboard" in the users role
 */

$username = "plugboard";
$password = "plugboard";

$domain = $argv[1];
$type = $argv[2];


switch ($type){
    case "info_v1":
        $proxy = getSoap($domain);
        $sessionId = $proxy->login($username, $password);
        $result = $proxy->call($sessionId, 'plugboard.info');
        break;

    case "info_v2":
        $proxy = getSoap($domain, true);
        $sessionId = $proxy->login($username, $password);
        $result = $proxy->plugboardInfo($sessionId);
        break;

    case "info_wsi":
        // NOTE: You have to activate WSI-mode in magento config

        $proxy = getSoap($domain, true);

        $session = $proxy->login(array(
                "username"=>$username,
                "apiKey" => $password
        ));

        $result = $proxy->plugboardInfo(array(
            "sessionId"=>$session->result
        ));
        break;

    case "productlist_v2":

        $proxy = getSoap($domain, true);

        $sessionId = $proxy->login($username, $password);
        $result = $proxy->catalogProductList($sessionId, null, null, null, 1);

        break;

    case "productlist_wsi":

        // NOTE: You have to activate WSI-mode in magento config
        $proxy = getSoap($domain, true);

        $session = $proxy->login(array(
                "username"=>$username,
                "apiKey" => $password
        ));

        $result = $proxy->catalogProductList(array(
            "sessionId"=>$session->result,
            "filters" => null,
            "store" => null,
            "attributes"=> null,
            "extendedInfo"=>1
        ));


        break;

    case "productinfo_v2":

        $proxy = getSoap($domain, true);

        $sessionId = $proxy->login($username, $password);
        $result = $proxy->catalogProductInfo($sessionId, 1, null, null, null, 1);

        break;

    case "getconfig_v2":

        $proxy = getSoap($domain, true);

        $sessionId = $proxy->login($username, $password);
        $result = $proxy->plugboardConfig($sessionId, array(
            "general/locale/firstday",
            "general/locale/weekend",
            "general/region"
        ), 1);

        break;

    case "productoptions_v2":

        $proxy = getSoap($domain, true);

        $sessionId = $proxy->login($username, $password);
        $result = $proxy->plugboardProductoptions($sessionId, 1);

        break;

    case "productoptions_wsi":

        // NOTE: You have to activate WSI-mode in magento config
        $proxy = getSoap($domain, true);

        $session = $proxy->login(array(
            "username"=>$username,
            "apiKey" => $password
        ));

        $result = $proxy->plugboardProductoptions(array(
            "sessionId"=>$session->result,
            "store" => 1
        ));


        break;

    case "getconfig_wsi":

        $proxy = getSoap($domain, true);

        $session = $proxy->login(array(
            "username"=>$username,
            "apiKey" => $password
        ));
        $result = $proxy->plugboardConfig(array(
            "sessionId"=>$session->result,
            "paths" => array(
                "general/locale/firstday",
                "general/locale/weekend",
                "general/region"
            ),
            "store" => 1
        ));

        break;

    case "cart_add_v2":

        $proxy = getSoap($domain, true);
        $sessionId = $proxy->login($username, $password);

        // create a cart
        $cartId = $proxy->shoppingCartCreate($sessionId, 1);

        // load the customer list and select the first customer from the list
        $customerList = $proxy->customerCustomerList($sessionId, array());
        $customer = (array) $customerList[0];
        $customer['mode'] = 'customer';
        $proxy->shoppingCartCustomerSet($sessionId, $cartId, $customer);

        // load the product list and select the first product from the list
        $productList = $proxy->catalogProductList($sessionId);
        $product = (array) $productList[0];
        $product['qty'] = 1;
        $product['custom_price'] = 80; // NEW ATTRIBUTE!

        $proxy->shoppingCartProductAdd($sessionId, $cartId, array($product));

        $address[0] = $address[1] = array(
            'firstname' => $customer['firstname'],
            'lastname' => $customer['lastname'],
            'street' => 'street address',
            'city' => 'city',
            'region' => 'region',
            'telephone' => 'phone number',
            'postcode' => 'postcode',
            'country_id' => 'SE',
            'is_default_shipping' => 1,
            'is_default_billing' => 1
        );

        $address[0]["mode"] = 'shipping';
        $address[1]["mode"] = 'billing';

        // add customer address
        $proxy->shoppingCartCustomerAddresses($sessionId, $cartId, $address);

        $paymentMethod =  array(
            'method' => 'plugboard_cdon'
        );
        // add payment method
        $proxy->shoppingCartPaymentMethod($sessionId, $cartId, $paymentMethod);

        // add shipping method
        // IMPORTANT: Has to run last, otherwise the price is overwritten!
        $proxy->shoppingCartShippingMethod($sessionId, $cartId, 'sharespine_plugboard', null, 39.2);

        // place the order
        $result = $proxy->shoppingCartOrder($sessionId, $cartId, null, null);

        break;

    case "control":
        // if this method does not work, you have an issue with Magento, not the module
        $proxy = getSoap($domain, true);
        $sessionId = $proxy->login($username, $password);
        $result = $proxy->magentoInfo($sessionId);
        break;

    default:
        echo "You didn't choose a type!";
}

if(isset($result)){
    print_r($result);
}

echo "\n";


/**************************************************
 * get a soap client
 * @param $domain
 * @param bool $v2
 * @param bool $cache
 *
 * @return SoapClient
 */
function getSoap($domain, $v2 = false){

    $options = array(WSDL_CACHE_NONE);
    //$options = array();

    if($v2) $proxy = new SoapClient('http://'.$domain.'/index.php/api/v2_soap?wsdl=1',$options);
    else $proxy = new SoapClient('http://'.$domain.'/index.php/api/soap?wsdl=1',$options);

    return $proxy;
}
