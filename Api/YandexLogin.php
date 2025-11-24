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

use BaksDev\Auth\Yandex\Api\AuthToken\YandexOAuthTokenDTO;
use BaksDev\Core\Cache\AppCacheInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\Cache\CacheInterface;

abstract class YandexLogin
{
    private YandexOAuthTokenDTO|false $token = false;

    public function __construct(
        #[Autowire(env: 'APP_ENV')] private readonly string $environment,
        #[Target('authYandexLogger')] protected readonly LoggerInterface $logger,
        private readonly AppCacheInterface $cache,
    ) {
    }

    /** Yandex OAuth token для запроса */
    public function token(YandexOAuthTokenDTO $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function TokenHttpClient(): RetryableHttpClient
    {
        if(false === $this->token instanceof YandexOAuthTokenDTO)
        {
            throw new InvalidArgumentException('Не передан Yandex OAuth token');
        }

        return new RetryableHttpClient(
            HttpClient::create(['headers' =>
                [
                    'Authorization' => 'OAuth '.$this->token->getAccessToken(),
                ]
            ])
                ->withOptions([
                    'base_uri' => 'https://login.yandex.ru/',
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

    protected function getToken(): YandexOAuthTokenDTO
    {
        return $this->token;
    }
}
