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
 *
 */

declare(strict_types=1);

namespace BaksDev\Auth\Yandex\Api\AuthToken;

use BaksDev\Auth\Yandex\Api\YandexOAuth;
use DateInterval;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Получение кода подтверждения для обмена на OAuth-токен
 * @see https://yandex.ru/dev/id/doc/ru/codes/code-url?tabs=defaultTabsGroup-jrbbntfw_%25d0%2597%25d0%25b0%25d0%25bf%25d1%2580%25d0%25be%25d1%2581%2520%25d0%25b2%25d1%258b%25d0%25bf%25d0%25be%25d0%25bb%25d0%25bd%25d0%25b5%25d0%25bd%2520%25d1%2583%25d1%2581%25d0%25bf%25d0%25b5%25d1%2588%25d0%25bd%25d0%25be%2CdefaultTabsGroup-mq43soab_%25d0%2597%25d0%25b0%25d0%25bf%25d1%2580%25d0%25be%25d1%2581%2520%25d0%25b2%25d1%258b%25d0%25bf%25d0%25be%25d0%25bb%25d0%25bd%25d0%25b5%25d0%25bd%2520%25d1%2583%25d1%2581%25d0%25bf%25d0%25b5%25d1%2588%25d0%25bd%25d0%25be#token
 */
final class YandexOAuthTokenRequest extends YandexOAuth
{
    /** $code - Код подтверждения от Яндекс API */
    public function get(string $code): YandexOAuthTokenDTO|false
    {
        $cache = $this->getCacheInit('auth-yandex');
        $key = 'yandex-oauth-'.$code;

        $content = $cache->get($key, function(ItemInterface $item) use ($code) {

            $item->expiresAfter(DateInterval::createFromDateString('1 seconds'));

            $body = http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            /** Делаем запрос для получения токена Яндекс OAuth */
            $response = $this->TokenHttpClient()
                ->request(
                    'POST',
                    '/token',
                    ['body' => $body],
                );

            if($response->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    message: 'Ошибка получения токена Яндекс OAuth',
                    context: [
                        $body,
                        $response->toArray(false),
                        self::class.':'.__LINE__,
                    ]);

                return false;
            }

            $content = $response->toArray(false);

            $item->expiresAfter(DateInterval::createFromDateString($content['expires_in'].' seconds'));

            return $content;

        });

        return false !== $content ? new YandexOAuthTokenDTO(...$content) : false;
    }
}
