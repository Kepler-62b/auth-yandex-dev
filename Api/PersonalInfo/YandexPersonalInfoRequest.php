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

namespace BaksDev\Auth\Yandex\Api\PersonalInfo;

use BaksDev\Auth\Yandex\Api\AuthToken\YandexOAuthTokenDTO;
use BaksDev\Auth\Yandex\Api\YandexLogin;
use DateInterval;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Обмен токена на информацию о пользователе
 * @see https://yandex.ru/dev/id/doc/ru/user-information
 */
final class YandexPersonalInfoRequest extends YandexLogin
{
    public function get(YandexOAuthTokenDTO $token): YandexPersonalInfoDTO|false
    {
        $cache = $this->getCacheInit('auth-yandex');

        $key = 'yandex-personal-info'.$token->getAccessToken();

        $content = $cache->get($key, function(ItemInterface $item) use ($token) {

            $item->expiresAfter(DateInterval::createFromDateString('1 seconds'));

            /** Делаем запрос на данные пользователя */
            $response = $this
                ->token($token)
                ->TokenHttpClient()
                ->request(
                    'GET',
                    '/info',
                    ['query' => ['format' => 'json']],
                );

            if($response->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    message: 'Ошибка получения информации о пользователе',
                    context: [
                        $token,
                        $response,
                        self::class.':'.__LINE__,
                    ]);

                return false;
            }

            $content = $response->toArray(false);

            $item->expiresAfter(DateInterval::createFromDateString('1 day'));

            return $content;
        });

        return false !== $content ? new YandexPersonalInfoDTO(...$content) : false;
    }
}
