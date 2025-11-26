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

namespace BaksDev\Auth\Yandex\UseCase\Admin\Delete;

use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEventInterface;
use BaksDev\Auth\Yandex\Type\Event\AccountYandexEventUid;
use BaksDev\Auth\Yandex\UseCase\Admin\Delete\Modify\ModifyDTO;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AccountYandexEvent */
final class AccountYandexDeleteDTO implements AccountYandexEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private ?AccountYandexEventUid $id = null;

    /**
     * Идентификатор пользователя
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly UserUid $account;

    /**
     * Модификатор
     */
    #[Assert\Valid]
    private ModifyDTO $modify;


    public function __construct()
    {
        $this->modify = new ModifyDTO();
    }

    /**
     * Идентификатор события
     */
    public function getEvent(): ?AccountYandexEventUid
    {
        return $this->id;
    }


    public function setId(AccountYandexEventUid $id): void
    {
        $this->id = $id;
    }

    /**
     * Modify
     */
    public function getModify(): ModifyDTO
    {
        return $this->modify;
    }

    public function setModify(ModifyDTO $modify): self
    {
        $this->modify = $modify;
        return $this;
    }

    /**
     * Account
     */
    public function getAccount(): UserUid
    {
        return $this->account;
    }
}