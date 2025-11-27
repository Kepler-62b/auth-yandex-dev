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

namespace BaksDev\Auth\Yandex\UseCase\Public\New\Tests;

use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\UseCase\Public\New\Invariable\AccountYandexInvariableDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\NewAccountYandexDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\NewAccountYandexHandler;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('auth-yandex')]
#[Group('auth-yandex-usecase')]
#[When(env: 'test')]
class NewAccountYandexHandlerTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $AccountYandex = $em->getRepository(AccountYandex::class)
            ->find(UserUid::TEST);

        if($AccountYandex instanceof AccountYandex)
        {
            $em->remove($AccountYandex);
        }

        $AccountYandexEvent = $em->getRepository(AccountYandexEvent::class)
            ->findBy(['account' => UserUid::TEST]);

        foreach($AccountYandexEvent as $remove)
        {
            $em->remove($remove);
        }

        $User = $em->getReference(User::class, new UserUid(UserUid::TEST));


        if($User instanceof User)
        {
            $em->remove($User);
        }

        $em->flush();
    }

    public function testUseCase(): void
    {
        $NewAccountYandexDTO = new NewAccountYandexDTO();

        /** Invariable */
        $AccountYandexInvariableDTO = new AccountYandexInvariableDTO();
        $AccountYandexInvariableDTO->setYid('bnynw6dht8d7c5m2r0qgjqba6m');
        $NewAccountYandexDTO->setInvariable($AccountYandexInvariableDTO);

        /** @var NewAccountYandexHandler $NewAccountYandexHandler */
        $NewAccountYandexHandler = self::getContainer()->get(NewAccountYandexHandler::class);
        $handle = $NewAccountYandexHandler->handle($NewAccountYandexDTO);

        self::assertTrue(($handle instanceof AccountYandex), $handle.': Ошибка AccountYandex');
    }
}
