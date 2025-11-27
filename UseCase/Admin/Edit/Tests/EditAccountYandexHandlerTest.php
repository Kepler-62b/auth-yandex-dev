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

namespace BaksDev\Auth\Yandex\UseCase\Admin\Edit\Tests;

use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\UseCase\Admin\Edit\EditAccountYandexDTO;
use BaksDev\Auth\Yandex\UseCase\Admin\Edit\EditAccountYandexHandler;
use BaksDev\Auth\Yandex\UseCase\Admin\Edit\Status\AccountYandexStatusDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\Tests\NewAccountYandexHandlerTest;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('auth-yandex')]
#[Group('auth-yandex-usecase')]
#[When(env: 'test')]
class EditAccountYandexHandlerTest extends KernelTestCase
{
    #[DependsOnClass(NewAccountYandexHandlerTest::class)]
    public function testUseCase(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $AccountYandex = $em->getRepository(AccountYandex::class)
            ->find(UserUid::TEST);

        $AccountYandexEvent = $em->getRepository(AccountYandexEvent::class)
            ->findOneBy(['id' => $AccountYandex->getEvent()]);

        $EditAccountYandexDTO = new EditAccountYandexDTO($AccountYandexEvent->getId());
        $AccountYandexEvent->getDto($EditAccountYandexDTO);

        /** Изменяем статус */
        $AccountYandexStatusDTO = new AccountYandexStatusDTO();
        $AccountYandexStatusDTO->setValue(false);
        $EditAccountYandexDTO->setStatus($AccountYandexStatusDTO);

        /** @var EditAccountYandexHandler $EditAccountYandexHandler */
        $EditAccountYandexHandler = self::getContainer()->get(EditAccountYandexHandler::class);
        $handle = $EditAccountYandexHandler->handle($EditAccountYandexDTO);

        self::assertTrue(($handle instanceof AccountYandex), $handle.': Ошибка AccountYandex');
    }

    public static function tearDownAfterClass(): void
    {
        NewAccountYandexHandlerTest::setUpBeforeClass();
    }
}
