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

namespace BaksDev\Auth\Yandex\Repository\DBAL\ExistAccountYandexByYid;

use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\Entity\Event\Invariable\AccountYandexInvariable;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Проверяет существование записи с аккаунтом Яндекс по идентификатору из Invariable
 */
final readonly class ExistAccountYandexByYidRepository implements ExistAccountYandexByYidInterface
{
    public function __construct(
        private DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function isExist(string $yid): bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(AccountYandexEvent::class, 'account_yandex_event');

        $dbal
            ->join(
                'account_yandex_event',
                AccountYandexInvariable::class,
                'account_yandex_invariable',
                '
                    account_yandex_invariable.main = account_yandex_event.account AND
                    account_yandex_invariable.event = account_yandex_event.id AND 
                    account_yandex_invariable.yid = :yid'
            )->setParameter(
                key: 'yid',
                value: $yid,
                type: Types::STRING
            );

        return $dbal->fetchExist();
    }
}