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

namespace BaksDev\Auth\Yandex\UseCase\Admin\Edit;

use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEventInterface;
use BaksDev\Auth\Yandex\Type\Event\AccountYandexEventUid;
use BaksDev\Auth\Yandex\UseCase\Admin\Edit\Status\AccountYandexStatusDTO;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Объект для РЕДАКТИРОВАНИЯ аккаунта Yandex
 * @see AccountYandexEvent
 */
final class EditAccountYandexDTO implements AccountYandexEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private readonly AccountYandexEventUid $id;

    /**
     * Идентификатор профиля
     */
    #[Assert\Uuid]
    private readonly UserUid $account;

    /**
     * Идентификатор пользователя в Yandex
     */
    #[Assert\Valid]
    private AccountYandexStatusDTO $status;

    public function __construct(AccountYandexEventUid $id)
    {
        $this->id = $id;
    }

    /**
     * Идентификатор события
     */
    public function getEvent(): ?AccountYandexEventUid
    {
        return $this->id;
    }

    /**
     * Идентификатор профиля
     */
    public function getAccount(): UserUid
    {
        return $this->account;
    }

    /**
     * Статус аккаунта
     */
    public function setStatus(AccountYandexStatusDTO $status): EditAccountYandexDTO
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): AccountYandexStatusDTO
    {
        return $this->status;
    }

}