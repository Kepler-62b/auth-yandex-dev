<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Auth\Yandex\Api;

use BaksDev\Core\Cache\AppCacheInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\Cache\CacheInterface;

abstract class YandexOAuth
{
    private string $Authorization;

    public function __construct(
        #[Autowire(env: 'APP_ENV')] private readonly string $environment,
        #[Autowire(env: 'YANDEX_CLIENT_ID')] private readonly string $clientId,
        #[Autowire(env: 'YANDEX_CLIENT_SECRET')] private readonly string $clientSecret,
        #[Target('authYandexLogger')] protected readonly LoggerInterface $logger,
        private readonly AppCacheInterface $cache,
    ) {
        $this->Authorization = base64_encode($this->clientId.':'.$this->clientSecret);
    }

    /** Для перезаписи данных авторизации */
    public function forAuthorization(string $clientId,string $clientSecret): self
    {
        $this->Authorization = base64_encode($clientId.':'.$clientSecret);
        return $this;
    }

    public function TokenHttpClient(): RetryableHttpClient
    {
        return new RetryableHttpClient(
            HttpClient::create(['headers' =>
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic '.$this->Authorization,
                ]
            ])
                ->withOptions([
                    'base_uri' => 'https://oauth.yandex.ru/',
                    'verify_host' => false,
                ]),
        );
    }

    /**
     * Метод проверяет что окружение является PROD,
     * тем самым позволяет выполнять операции запроса на сторонний сервис
     * ТОЛЬКО в PROD окружении
     */
    protected function isExecuteEnvironment(): bool
    {
        return $this->environment === 'prod';
    }

    protected function getCacheInit(string $namespace): CacheInterface
    {
        return $this->cache->init($namespace);
    }

    protected function getAuthorization(): string
    {
        return $this->Authorization;
    }
}
