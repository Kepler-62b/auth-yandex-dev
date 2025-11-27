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

namespace BaksDev\Auth\Yandex\Messenger\CreateUserProfileDispatcher;

use BaksDev\Users\Profile\TypeProfile\Type\Id\Choice\TypeProfileUser;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\Status\UserProfileStatusActive;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\UserProfileStatus;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileDTO;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Создает профиль пользователя при создании аккаунта для авторизации через Яндекс
 */
#[AsMessageHandler(priority: 0)]
final readonly class CreateUserProfileDispatcher
{
    public function __construct(
        #[Target('authYandexLogger')] private LoggerInterface $logger,
        private UserProfileHandler $userProfileHandler,
    ) {}

    public function __invoke(CreateUserProfileMessage $message): bool
    {
        $login = $message->getLogin();

        $UserProfileDTO = new UserProfileDTO();
        $UserProfileDTO->setSort(100);
        $UserProfileDTO->setType(new TypeProfileUid(TypeProfileUser::class));
        $UserProfileDTO->getPersonal()->setUsername($login);

        $InfoDTO = $UserProfileDTO->getInfo();
        $InfoDTO->setUsr($message->getId());
        $InfoDTO->setUrl($login.uniqid('_', false));
        $InfoDTO->setStatus(new UserProfileStatus(UserProfileStatusActive::class));

        $UserProfile = $this->userProfileHandler->handle($UserProfileDTO);

        if(false === $UserProfile instanceof UserProfile)
        {
            $this->logger->critical(
                message: sprintf(
                    'auth-yandex: %s: Ошибка при создании профиля пользователя',
                    $UserProfile
                ),
                context: [self::class.':'.__LINE__]
            );

            return false;
        }

        return true;
    }
}

