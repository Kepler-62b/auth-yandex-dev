<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Auth\Yandex\Api\AuthToken;

use BaksDev\Auth\Yandex\Api\YandexAuth;
use DateInterval;
use Symfony\Contracts\Cache\ItemInterface;

final class YandexAuthTokenRequest extends YandexAuth
{
    /**
     */
    public function get(string $code)
    {
        /** Кешируем результат запроса */
        $cache = $this->getCacheInit('auth-yandex');
        $key = 'auth-yandex-'.$this->getAuthorization();

        $content = $cache->get($key, function(ItemInterface $item) use ($code) {

            $item->expiresAfter(DateInterval::createFromDateString('1 seconds'));

            $body = http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            /** Делаем запрос на данные пользователя */
            $response = $this->TokenHttpClient()
                ->request(
                    'POST',
                    '/token',
                    ['body' => $body],
                );

            if($response->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    message: 'Ошибка получения токена',
                    context: [
                        $response,
                        self::class.':'.__LINE__,
                    ]);

                return false;
            }

            $content = $response->toArray(false);

            $item->expiresAfter(DateInterval::createFromDateString($content['expires_in'].' seconds'));

            return $content;

        });

        return $content;
    }
}
