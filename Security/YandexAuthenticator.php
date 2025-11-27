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

namespace BaksDev\Auth\Yandex\Security;

use BaksDev\Auth\Email\Messenger\CreateAccount\CreateAccountMessage;
use BaksDev\Auth\Email\Type\Email\AccountEmail;
use BaksDev\Auth\Yandex\Api\AuthToken\YandexOAuthTokenDTO;
use BaksDev\Auth\Yandex\Api\AuthToken\YandexOAuthTokenRequest;
use BaksDev\Auth\Yandex\Api\PersonalInfo\YandexPersonalInfoDTO;
use BaksDev\Auth\Yandex\Api\PersonalInfo\YandexPersonalInfoRequest;
use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\Messenger\CreateUserProfileDispatcher\CreateUserProfileMessage;
use BaksDev\Auth\Yandex\Repository\ORM\AccountYandexEventByCid\AccountYandexEventByCidInterface;
use BaksDev\Auth\Yandex\UseCase\Public\New\Invariable\AccountYandexInvariableDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\NewAccountYandexDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\NewAccountYandexHandler;
use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
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
use Symfony\Contracts\Translation\TranslatorInterface;

final class YandexAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        #[Target('authYandexLogger')] private readonly LoggerInterface $logger,
        private readonly AppCacheInterface $cache,
        private readonly UrlGeneratorInterface $urlGenerator,
        protected readonly MessageDispatchInterface $messageDispatch,
        private readonly GetUserByIdInterface $userByIdRepository,
        private readonly AccountYandexEventByCidInterface $accountYandexEventByCidRepository,
        private readonly NewAccountYandexHandler $newAccountYandexHandler,
        private readonly YandexOAuthTokenRequest $yandexAuthTokenRequest,
        private readonly YandexPersonalInfoRequest $yandexPersonalInfoRequest,
        private readonly TranslatorInterface $translator,
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
        /** Получаем токен Яндекс OAuth для запроса пользовательских данных */
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

        return new SelfValidatingPassport(
            new UserBadge('auth-yandex-'.$yandexUserId, function()
            use (
                $request,
                $yandexUserId,
                $YandexPersonalInfoDTO
            ) {

                $accountYandexEvent = $this->accountYandexEventByCidRepository->find($yandexUserId);

                /** Если аккаунт создан НО НЕ АКТИВНЫЙ в нашем приложении */
                if(
                    true === $accountYandexEvent instanceof AccountYandexEvent &&
                    true === $accountYandexEvent->getStatus()->isInactive()
                )
                {
                    $this->logger->warning(
                        message: 'Попытка аутентификации неактивного Яндекс аккаунта',
                        context: [
                            self::class.':'.__LINE__,
                            $accountYandexEvent->getAccount(),
                            'Yid' => $accountYandexEvent->getInvariable()->getYid(),
                        ]
                    );

                    return null;
                }

                /**
                 * Если аккаунта нет - создаем:
                 * - User
                 * - Account
                 * - UserProfile
                 * - Account (при наличии email в информации о пользователе)
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
                     * Бросаем сообщение СИНХРОННО для создания профиля
                     * @see CreateUserProfileDispatcher
                     */
                    $CreateUserProfile = $this->messageDispatch->dispatch(
                        message: new CreateUserProfileMessage(
                            $AccountYandex->getId(),
                            $AccountYandex->getEvent(),
                            $YandexPersonalInfoDTO
                        ));

                    /** Сообщение об ошибке создания профиля */
                    if(false === $CreateUserProfile)
                    {
                        $request->getSession()->getFlashBag()->add(
                            $this->translator->trans('login.error.header', domain: 'public.profile'),
                            $this->translator->trans('login.error.message', domain: 'public.profile'),
                        );
                    }

                    if(null !== $YandexPersonalInfoDTO->getDefaultEmail())
                    {
                        /**
                         * Бросаем сообщение для создания аккаунта с email
                         * @see CreateAccountDispatcher
                         */
                        $this->messageDispatch->dispatch(
                            message: new CreateAccountMessage(
                                $AccountYandex->getId(),
                                new AccountEmail($YandexPersonalInfoDTO->getDefaultEmail())
                            ),
                            transport: 'auth-email',
                        );
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
