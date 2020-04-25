<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Teknasyon\HuaweiMobileService\InAppPurchase\Resource;

use Teknasyon\HuaweiMobileService\Resource;

/**
 * The "products" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $products = $androidpublisherService->products;
 *  </code>
 */
class CancelledPurchases extends Resource
{
    /**
     * Acknowledges a purchase of an inapp item. (products.acknowledge)
     *
     * @param string                                                             $packageName The package name of the application the inapp
     *                                                                                        product was sold in (for example, 'com.some.thing').
     * @param string                                                             $productId   The inapp product SKU (for example,
     *                                                                                        'com.some.thing.inapp1').
     * @param string                                                             $token       The token provided to the user's device when the
     *                                                                                        subscription was purchased.
     * @param Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest $postBody
     * @param array                                                              $optParams   Optional parameters.
     */
    public function acknowledge(
        $packageName,
        $productId,
        $token,
        Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest $postBody,
        $optParams = array()
    ) {
        $params = array(
            'packageName' => $packageName,
            'productId' => $productId,
            'token' => $token,
            'postBody' => $postBody
        );
        $params = array_merge($params, $optParams);
        return $this->call('acknowledge', array($params));
    }

    /**
     * Checks the purchase and consumption status of an inapp item. (products.get)
     *
     * @param string $packageName The package name of the application the inapp
     *                            product was sold in (for example, 'com.some.thing').
     * @param string $productId   The inapp product SKU (for example,
     *                            'com.some.thing.inapp1').
     * @param string $token       The token provided to the user's device when the inapp
     *                            product was purchased.
     * @param array  $optParams   Optional parameters.
     *
     * @return Google_Service_AndroidPublisher_ProductPurchase
     */
    public function get($packageName, $productId, $token, $optParams = array())
    {
        $params = array('packageName' => $packageName, 'productId' => $productId, 'token' => $token);
        $params = array_merge($params, $optParams);
        return $this->call('get', array($params), "Google_Service_AndroidPublisher_ProductPurchase");
    }
}