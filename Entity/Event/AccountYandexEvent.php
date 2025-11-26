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

namespace BaksDev\Auth\Yandex\Entity\Event;

use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\Invariable\AccountYandexInvariable;
use BaksDev\Auth\Yandex\Entity\Event\Modify\AccountYandexModify;
use BaksDev\Auth\Yandex\Entity\Event\Status\AccountYandexStatus;
use BaksDev\Auth\Yandex\Type\Event\AccountYandexEventUid;
use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'account_yandex_event')]
class AccountYandexEvent extends EntityEvent
{
    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: AccountYandexEventUid::TYPE)]
    private AccountYandexEventUid $id;

    /**
     * Идентификатор пользователя
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: UserUid::TYPE, nullable: false)]
    private ?UserUid $account = null;

    /**
     * Постоянная величина
     */
    #[ORM\OneToOne(targetEntity: AccountYandexInvariable::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private AccountYandexInvariable $invariable;

    /**
     * Статус аккаунта
     */
    #[ORM\OneToOne(targetEntity: AccountYandexStatus::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private AccountYandexStatus $status;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(targetEntity: AccountYandexModify::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private AccountYandexModify $modify;


    public function __construct()
    {
        $this->id = new AccountYandexEventUid();
        $this->modify = new AccountYandexModify($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): AccountYandexEventUid
    {
        return $this->id;
    }

    public function setId(AccountYandexEventUid $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Идентификатор UserUid
     */
    public function setMain(AccountYandex|UserUid $account): void
    {
        $this->account = $account instanceof AccountYandex ? $account->getId() : $account;
    }

    public function getAccount(): ?UserUid
    {
        return $this->account;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof AccountYandexEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof AccountYandexEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getStatus(): AccountYandexStatus
    {
        return $this->status;
    }

    public function getInvariable(): AccountYandexInvariable
    {
        return $this->invariable;
    }
}
