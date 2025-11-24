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

namespace BaksDev\Auth\Yandex\UseCase\Public\New;

use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEventInterface;
use BaksDev\Auth\Yandex\Type\Event\AccountYandexEventUid;
use BaksDev\Auth\Yandex\UseCase\Public\New\Invariable\AccountYandexInvariableDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\Status\AccountYandexStatusDTO;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Объект для создания аккаунта Yandex
 * @see AccountYandexEvent
 */
final class NewAccountYandexDTO implements AccountYandexEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\IsNull]
    private ?AccountYandexEventUid $id = null;

    /**
     * Идентификатор пользователя в Yandex
     */
    #[Assert\Valid]
    private AccountYandexInvariableDTO $invariable;

    /**
     * Идентификатор пользователя в Yandex
     */
    #[Assert\Valid]
    private AccountYandexStatusDTO $status;

    public function __construct() {
        $this->status = new AccountYandexStatusDTO();
    }

    /**
     * Идентификатор события
     */
    public function getEvent(): ?AccountYandexEventUid
    {
        return $this->id;
    }

    /**
     * Идентификатор пользователя в Yandex
     */
    public function getInvariable(): AccountYandexInvariableDTO
    {
        return $this->invariable;
    }

    public function setInvariable(AccountYandexInvariableDTO $invariable): self
    {
        $this->invariable = $invariable;
        return $this;
    }

    /**
     * Статус аккаунта
     */
    public function setStatus(AccountYandexStatusDTO $status): NewAccountYandexDTO
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): AccountYandexStatusDTO
    {
        return $this->status;
    }
}