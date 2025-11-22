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

use BaksDev\Auth\Email\Repository\AccountEventActiveByEmail\AccountEventActiveByEmailInterface;
use BaksDev\Auth\Email\Type\Email\AccountEmail;
use BaksDev\Auth\Yandex\Api\AuthToken\YandexAuthTokenRequest;
use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\Repository\ORM\AccountYandexEventByCid\AccountYandexEventByCidInterface;
use BaksDev\Auth\Yandex\UseCase\Public\New\NewAccountYandexDTO;
use BaksDev\Auth\Yandex\UseCase\Public\New\NewAccountYandexHandler;
use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Repository\GetUserById\GetUserByIdInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Translation\TranslatorInterface;

final class YandexAuthenticator extends AbstractAuthenticator
{
    private const string LOGIN_ROUTE = 'auth-yandex:public.auth';

    private const string SUCCESS_REDIRECT = 'core:public.homepage';

    public function __construct(
        #[Target('authYandexLogger')] private readonly LoggerInterface $logger,
        private readonly AppCacheInterface $cache,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,

        private readonly GetUserByIdInterface $userByIdRepository,
        private readonly AccountYandexEventByCidInterface $accountYandexEventByCidRepository,

        private readonly NewAccountYandexHandler $handler,
        private readonly YandexAuthTokenRequest $yandexAuthTokenRequest,

        private readonly AccountEventActiveByEmailInterface $accountEventActiveByEmail,

    ) {}

    public function supports(Request $request): ?bool
    {
        if($request->getPathInfo() === '/')
        {
            return false;
        }

        $authUrl = $this->urlGenerator->generate(self::LOGIN_ROUTE);

        if(
            $request->getPathInfo() === $authUrl &&
            $request->headers->get('Referer', '') === 'https://oauth.yandex.ru/'
            && true === $request->query->has('error')
        )
        {
            $this->logger->critical(
                message: 'Ошибка получения кода подтверждения',
                context: [
                    $request->query->has('error_description'),
                    self::class.':'.__LINE__,
                ],
            );

            return false;
        }

        $this->logger->info(
            message: 'supports',
            context: [
                self::class.':'.__LINE__,
            ],
        );

        return $request->getPathInfo() === $authUrl
            && $request->headers->get('Referer', '') === 'https://oauth.yandex.ru/'
            && true === $request->query->has('code');
    }

    public function authenticate(Request $request): Passport
    {
        $this->logger->info(
            message: 'Аутентификация через YandexId',
            context: [
                $request->query->all(),
                self::class.':'.__LINE__,
            ],
        );

        if(false === $request->query->has('cid'))
        {
            $this->logger->warning(
                message: 'Не найден cid пользователя',
                context: [
                    $request->query->all(),
                    self::class.':'.__LINE__,
                ],
            );

            //            return new SelfValidatingPassport(
            //                new UserBadge('error', function() {
            //                    return null;
            //                }),
            //            );

            throw new UserNotFoundException('Аккаунт неактивен');
        }

        $cid = $request->query->get('cid');

        /** Получаем паспорт */
        return new SelfValidatingPassport(
            new UserBadge( 'ekx-spb@yandex.ru', function() use ($request, $cid) {
//            new UserBadge($cid, function() use ($request, $cid) {

                $accountYandexEvent = $this->accountYandexEventByCidRepository->find($cid);

                /** Если аккаунт не активный в нашем приложении */
                if(
                    true === $accountYandexEvent instanceof AccountYandexEvent &&
                    true === $accountYandexEvent->isInactive()
                )
                {
                    //                    return null;
                    throw new UserNotFoundException('Пользователь не найден');

                }

                /** Если аккаунта нет - создаю */
                if(false === $accountYandexEvent instanceof AccountYandexEvent)
                {
                    $NewAccountYandexDTO = new NewAccountYandexDTO();
                    $NewAccountYandexDTO->setCid($cid);

                    $handle = $this->handler->handle($NewAccountYandexDTO);

                    if(false === $handle instanceof AccountYandex)
                    {
                        $this->logger->critical(
                            message: sprintf(
                                '%s: Ошибка создания аккаунта',
                                $handle
                            ),
                            context: [self::class.':'.__LINE__,]
                        );
                    }
                }

                /** Сбрасываем кеш ролей пользователя */
                $cache = $this->cache->init('UserGroup');
                $cache->clear();

                /** Удаляем авторизацию доверенности пользователя */
                $Session = $request->getSession();
                $Session->remove('Authority');

                /** UserId у нового пользователя или текущего  */
                $userUid = false === $accountYandexEvent instanceof AccountYandexEvent
                    ? $handle->getId()
                    : $accountYandexEvent->getAccount();

                /** DEBUG */
                $account = $this->accountEventActiveByEmail->getAccountEvent(new AccountEmail('ekx-spb@yandex.ru'));

                $User = $this->userByIdRepository->get($account->getAccount());

                return $User;
                /** DEBUG */

                $User = $this->userByIdRepository->get($userUid);

                if(false === $User instanceof User)
                {

                    $this->logger->critical(
                        message: 'Пользователь не найден',
                        context: [self::class.':'.__LINE__,]
                    );

                    throw new UserNotFoundException('Пользователь не найден');
                }

                return $User;
            }));
            //            badges: [
            //                new CsrfTokenBadge('authenticate', ($request->headers->get('X-App-Auth-Key') ?? ''))
            //            ]
            //        )->addBadge(new RememberMeBadge());

    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        $this->logger->info('Authentication success', [
            'user' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'firewall' => $firewallName,
            'session_id' => $request->getSession()->getId(),
        ]);

//        return new JsonResponse(['success']);

        /** Редирект на главную страницу после успешной авторизации */
        return new RedirectResponse($this->urlGenerator->generate(self::SUCCESS_REDIRECT));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        //        dd('onAuthenticationFailure');
        if($request->isXmlHttpRequest())
        {
            return new JsonResponse(
                [
                    'header' => $this->translator->trans(
                        'page.index',
                        domain: 'auth-telegram.user',
                    ),
                    'message' => $this->translator->trans(
                        'danger.code',
                        domain: 'auth-telegram.user',
                    ),
                ],
                401,
            );
        }

        return new RedirectResponse($this->getAuthFormUrl());
    }

    protected function getAuthFormUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
