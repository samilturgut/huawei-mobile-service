<?php

/**
 * Copyright 2020. Huawei Technologies Co., Ltd. All rights reserved.
 *
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 *
 */

namespace Teknasyon\HuaweiMobileService;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class HuaweiClient
{

    const OAUTH2_TOKEN_URI = 'https://oauth-login.cloud.huawei.com/oauth2/v2/token';
    const ACCESS_TOKEN_KEY = 'accessTokenKey';
    const ACCESS_TOKEN_LOCK_KEY = 'accessTokenLockKey';

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Client|null
     */
    private $client = null;


    public function __construct($config, $redis = null, $logger = null)
    {
        $this->config = array_merge(
            [
                'client_id' => '',
                'client_secret' => '',
            ],
            $config
        );
        $this->redis = $redis;
        $this->logger = $logger;
    }


    public function execute(Request $request, $expectedClass = "")
    {
        $request = $this->authorize($request);

        $xxx = $this->client->send($request);

        return $xxx;
    }

    private function authorize(Request $request)
    {
        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            $request = $request->withHeader('Authorization', $this->buildAuthorization($accessToken));
        }

        return $request;
    }

    private function getAccessToken()
    {

        $accessToken = $this->redis->get($this->getRedisKey(self::ACCESS_TOKEN_KEY));
        if ($accessToken) {
            return $accessToken;
        }

        $lockTryCount = 0;
        do {
            $accessTokenLock = $this->redis->setnx(
                $this->getRedisKey(self::ACCESS_TOKEN_LOCK_KEY),
                date('Y-m-d H:i:s')
            );

            if ($accessTokenLock) {
                $this->redis->expire($this->getRedisKey(self::ACCESS_TOKEN_LOCK_KEY), 30);
                $newAccessTokenInfo = $this->requestAccessTokenFromHuawei();
                if (is_array($newAccessTokenInfo) && count($newAccessTokenInfo) > 0) {
                    $this->redis->set(
                        $this->getRedisKey(self::ACCESS_TOKEN_KEY),
                        $newAccessTokenInfo[0],
                        $this->getExpireTime($newAccessTokenInfo[1])
                    );
                    $this->redis->del($this->getRedisKey(self::ACCESS_TOKEN_LOCK_KEY));
                    return $newAccessTokenInfo[0];
                } else {
                    $this->redis->del($this->getRedisKey(self::ACCESS_TOKEN_LOCK_KEY));
                    return null;
                }
            }

            $accessToken = $this->redis->get($this->getRedisKey(self::ACCESS_TOKEN_KEY));
            if ($accessToken) {
                return $accessToken;
            }
            $lockTryCount++;
            if ($lockTryCount > 20) {
                return null;
            }
            sleep(1);
        } while (1);
        return null;
    }

    public function requestAccessTokenFromHuawei()
    {
        $tryCount = 0;
        do {
            $requestParams = array(
                'form_params' => array(
                    "grant_type" => "client_credentials",
                    "client_id" => $this->config['hwAppId'],
                    "client_secret" => $this->config['hwAppSecret']
                )
            );

            $response = $this->client->post(self::OAUTH2_TOKEN_URI, $requestParams);
            $responseStatus = $response->getStatusCode();
            $responseBody = $response->getBody();

            $result = json_decode($responseBody, true);

            if ($responseStatus == 200 && isset($result['access_token']) && $result['access_token'] != '') {
                $this->log(
                    '[HW_PUSH_MESSAGE] Request: ' . json_encode($requestParams) . ', Response: ' . $responseBody,
                    Logger::DEBUG
                );
                return [$result['access_token'], $result['expires_in']];
            } else {
                $this->log(
                    '[HW_PUSH_MESSAGE] Resfresh Token Failed! HttpStatusCode: ' . $responseStatus .
                    ', Request: ' . json_encode($requestParams) . ', Response: ' . $responseBody,
                    Logger::ERROR
                );
            }

            $tryCount++;
            if ($tryCount > 4) {
                break;
            }
            usleep(500000);
        } while (1);
        return null;
    }

    /**
     * @param $expiresIn
     *
     * @return int
     */
    private function getExpireTime($expiresIn)
    {
        /**
         * 3600 - 60
         * expire time dolmasına 1 dakika kala token yenilenmesi için 60 çıkardık.
         **/
        return intval($expiresIn) - 60;
    }

    /**
     * @param $accessToken
     *
     * @return string
     */
    public function buildAuthorization($accessToken)
    {
        $oriString = "APPAT:" . $accessToken;
        $authHead = "Basic " . base64_encode(utf8_encode($oriString)) . "";
        return $authHead;
    }

    /**
     * @param string $msg
     * @param int    $level
     */
    public function log($msg, $level = Logger::INFO)
    {
        if ($this->logger) {
            $this->logger->log($level, $msg);
        }
    }

    /**
     * Set the Logger object
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface implementation
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param $redisKey
     *
     * @return string
     */
    private function getRedisKey($redisKey)
    {
        return 'hms_' . $this->config['client_id'] . $redisKey;
    }


}