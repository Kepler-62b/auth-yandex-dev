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

use BaksDev\Auth\Yandex\Type\Event\AccountYandexEventUid;
use BaksDev\Users\User\Type\Id\UserUid;
use DateTimeImmutable;

final readonly class AllAccountYandexResult
{
    public function __construct(
        private string $id,
        private string $event,
        private bool $yandex_status,
        private string $yandex_update,
        private ?string $users_profile_url,
        private ?string $users_profile_username,
    ) {}

    public function getId(): UserUid
    {
        return new UserUid($this->id);
    }

    public function getEvent(): AccountYandexEventUid
    {
        return new AccountYandexEventUid($this->event);
    }

    public function getYandexStatus(): bool
    {
        return true === $this->yandex_status;
    }

    public function getYandexUpdate(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->yandex_update);
    }

    public function getUsersProfileUrl(): ?string
    {
        return $this->users_profile_url;
    }

    public function getUsersProfileUsername(): ?string
    {
        return $this->users_profile_username;
    }
}