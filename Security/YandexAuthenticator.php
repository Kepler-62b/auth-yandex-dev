<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

declare(strict_types=1);

namespace BaksDev\Auth\Yandex\Security;

use BaksDev\Auth\Yandex\Api\AuthToken\YandexOAuthTokenDTO;
use BaksDev\Auth\Yandex\Api\AuthToken\YandexOAuthTokenRequest;
use BaksDev\Auth\Yandex\Api\PersonalInfo\YandexPersonalInfoDTO;
use BaksDev\Auth\Yandex\Api\PersonalInfo\YandexPersonalInfoRequest;
use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\Repository\ORM\AccountYandexEventByCid\AccountYandexEventByCidInterface;
use BaksDev\Auth\Yandex\UseCase\Public\New\Invariable\AccountYandexInvariableDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\NewAccountYandexDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\NewAccountYandexHandler;
use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Users\Profile\TypeProfile\Type\Id\Choice\TypeProfileUser;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\Status\UserProfileStatusActive;
use BaksDev\Users\Profile\UserProfile\Type\UserProfileStatus\UserProfileStatus;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileDTO;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileHandler;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Repository\GetUserById\GetUserByIdInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class YandexAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        #[Target('authYandexLogger')] private readonly LoggerInterface $logger,
        private readonly AppCacheInterface $cache,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly GetUserByIdInterface $userByIdRepository,
        private readonly AccountYandexEventByCidInterface $accountYandexEventByCidRepository,
        private readonly NewAccountYandexHandler $newAccountYandexHandler,
        private readonly UserProfileHandler $userProfileHandler,
        private readonly YandexOAuthTokenRequest $yandexAuthTokenRequest,
        private readonly YandexPersonalInfoRequest $yandexPersonalInfoRequest,
    ) {}

    public function supports(Request $request): ?bool
    {
        $authUrl = $this->urlGenerator->generate("auth-yandex:public.auth");

        if(
            $request->getPathInfo() === $authUrl &&
            $request->headers->get('Referer') === 'https://oauth.yandex.ru/'
            && true === $request->query->has('error')
        )
        {
            $this->logger->critical(
                message: 'Ошибка получения кода подтверждения от Яндекс OAuth',
                context: [
                    $request->query->get('error_description'),
                    self::class.':'.__LINE__,
                ],
            );

            return false;
        }

        return $request->getPathInfo() === $authUrl
            && $request->headers->get('Referer') === 'https://oauth.yandex.ru/'
            && true === $request->query->has('code');
    }

    public function authenticate(Request $request): Passport
    {
        /** Получаем токен Яндекс OAuth */
        $YandexAuthTokenDTO = $this->yandexAuthTokenRequest->get($request->query->get('code'));

        if(false === $YandexAuthTokenDTO instanceof YandexOAuthTokenDTO)
        {
            return new SelfValidatingPassport(
                new UserBadge('error', function() {
                    return null;
                }),
            );
        }

        /** Получаем данные пользователя */
        $YandexPersonalInfoDTO = $this->yandexPersonalInfoRequest->get($YandexAuthTokenDTO);

        if(false === $YandexPersonalInfoDTO instanceof YandexPersonalInfoDTO)
        {
            return new SelfValidatingPassport(
                new UserBadge('error', function() {
                    return null;
                }),
            );
        }

        /** Уникальный идентификатор пользователя в Яндекс */
        $yandexUserId = $YandexPersonalInfoDTO->getId();

        $yandexAccountLogin = $YandexPersonalInfoDTO->getLogin();

        return new SelfValidatingPassport(
            new UserBadge('auth-yandex-'.$yandexUserId, function()
            use (
                $request,
                $yandexUserId,
                $yandexAccountLogin
            ) {

                $accountYandexEvent = $this->accountYandexEventByCidRepository->find($yandexUserId);

                /** Если аккаунт не активный в нашем приложении */
                if(
                    true === $accountYandexEvent instanceof AccountYandexEvent &&
                    true === $accountYandexEvent->getStatus()->isInactive()
                )
                {
                    return null;
                }

                /**
                 * Если аккаунта нет - создаю:
                 * - User
                 * - Account
                 * - UserProfile
                 */
                if(false === $accountYandexEvent instanceof AccountYandexEvent)
                {
                    $NewAccountYandexDTO = new NewAccountYandexDTO();

                    /** Invariable */
                    $AccountYandexInvariableDTO = new AccountYandexInvariableDTO();
                    $AccountYandexInvariableDTO->setYid($yandexUserId);
                    $NewAccountYandexDTO->setInvariable($AccountYandexInvariableDTO);

                    $AccountYandex = $this->newAccountYandexHandler->handle($NewAccountYandexDTO);

                    if(false === $AccountYandex instanceof AccountYandex)
                    {
                        $this->logger->critical(
                            message: sprintf(
                                '%s: Ошибка создания аккаунта',
                                $AccountYandex
                            ),
                            context: [self::class.':'.__LINE__,]
                        );

                        return null;
                    }

                    /**
                     * Создаем профиль пользователя
                     */

                    $UserProfileDTO = new UserProfileDTO();
                    $UserProfileDTO->setSort(100);
                    $UserProfileDTO->setType(new TypeProfileUid(TypeProfileUser::class));
                    $UserProfileDTO->getPersonal()->setUsername($yandexAccountLogin);

                    $InfoDTO = $UserProfileDTO->getInfo();
                    $InfoDTO->setUsr($AccountYandex->getId());
                    $InfoDTO->setUrl($yandexAccountLogin);
                    $InfoDTO->setStatus(new UserProfileStatus(UserProfileStatusActive::class));

                    $UserProfile = $this->userProfileHandler->handle($UserProfileDTO);

                    if(false === $UserProfile instanceof UserProfile)
                    {
                        $this->logger->critical(
                            message: sprintf(
                                '%s: Ошибка при создании профиля пользователя',
                                $UserProfile
                            ),
                            context: [self::class.':'.__LINE__]
                        );

                        return null;
                    }
                }

                /** Сбрасываем кеш ролей пользователя */
                $cache = $this->cache->init('UserGroup');
                $cache->clear();

                /** Удаляем авторизацию доверенности пользователя */
                $Session = $request->getSession();
                $Session->remove('Authority');

                /** UserId нового пользователя или текущего */
                $userUid =
                    false === $accountYandexEvent instanceof AccountYandexEvent && true === isset($AccountYandex)
                        ? $AccountYandex->getId()
                        : $accountYandexEvent->getAccount();

                $User = $this->userByIdRepository->get($userUid);

                if(false === $User instanceof User)
                {
                    $this->logger->critical(
                        message: 'Пользователь не найден',
                        context: [self::class.':'.__LINE__,]
                    );

                    return null;
                }

                return $User;
            }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
