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

namespace BaksDev\Auth\Yandex\Repository\DBAL\AllAccountYandex;

use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\Entity\Event\Invariable\AccountYandexInvariable;
use BaksDev\Auth\Yandex\Entity\Event\Modify\AccountYandexModify;
use BaksDev\Auth\Yandex\Entity\Event\Status\AccountYandexStatus;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Info\UserProfileInfo;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;

final class AllAccountYandexRepository implements AllAccountYandexInterface
{
    private ?SearchDTO $search = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    /** Метод возвращает пагинатор AccountYandex */
    public function findAll(): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('account_yandex.id ')
            ->addSelect('account_yandex.event ')
            ->from(AccountYandex::class, 'account_yandex');

        $dbal
            ->join(
                'account_yandex',
                AccountYandexEvent::class,
                'account_yandex_event',
                'account_yandex_event.id = account_yandex.event'
            );

        $dbal
            ->join(
                'account_yandex',
                AccountYandexInvariable::class,
                'account_yandex_invariable',
                '
                    account_yandex_invariable.main = account_yandex.id AND
                    account_yandex_invariable.event = account_yandex.event'
            );

        $dbal
            ->addSelect('account_yandex_status.value AS yandex_status')
            ->join(
                'account_yandex',
                AccountYandexStatus::class,
                'account_yandex_status',
                'account_yandex_status.event = account_yandex.event'
            );

        $dbal
            ->addSelect('account_yandex_modify.mod_date AS yandex_update')
            ->leftJoin(
                'account_yandex',
                AccountYandexModify::class,
                'account_yandex_modify',
                'account_yandex_modify.event = account_yandex.event'
            );

        /**
         * ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ
         */

        $dbal
            ->addSelect('users_profile_info.url AS users_profile_url')
            ->leftJoin(
                'account_yandex',
                UserProfileInfo::class,
                'users_profile_info',
                'users_profile_info.usr = account_yandex.id'
            );

        /** Активное событие профиля */
        $dbal->leftJoin(
            'users_profile_info',
            UserProfile::class,
            'users_profile',
            '
                users_profile.id = users_profile_info.profile AND
                users_profile.event = users_profile_info.event'
        );

        /** Personal */
        $dbal
            ->addSelect('users_profile_personal.username AS users_profile_username')
            ->leftJoin(
                'users_profile',
                UserProfilePersonal::class,
                'users_profile_personal',
                'users_profile_personal.event = users_profile.event',
            );

        /** Поиск */
        if($this->search && $this->search->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                ->addSearchLike('account_yandex.id')
                ->addSearchLike('account_yandex_invariable.yid')
                ->addSearchLike('users_profile_info.url');
        }

        return $this->paginator->fetchAllHydrate($dbal, AllAccountYandexResult::class);
    }
}
