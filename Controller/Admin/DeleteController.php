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

namespace BaksDev\Auth\Yandex\Controller\Admin;

use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\UseCase\Admin\Delete\AccountYandexDeleteDTO;
use BaksDev\Auth\Yandex\UseCase\Admin\Delete\AccountYandexDeleteForm;
use BaksDev\Auth\Yandex\UseCase\Admin\Delete\AccountYandexDeleteHandler;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_ACCOUNT_YANDEX_DELETE')]
final class DeleteController extends AbstractController
{
    #[Route('/admin/account/yandex/delete/{id}', name: 'admin.delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity] AccountYandexEvent $AccountYandexEvent,
        AccountYandexDeleteHandler $AccountYandexDeleteHandler,
    ): Response
    {
        $AccountYandexDeleteDTO = new AccountYandexDeleteDTO();
        $AccountYandexEvent->getDto($AccountYandexDeleteDTO);

        $form =
            $this->createForm(AccountYandexDeleteForm::class, $AccountYandexDeleteDTO, [
                'action' => $this->generateUrl('auth-yandex:admin.delete',
                    ['id' => $AccountYandexDeleteDTO->getEvent()]
                ),
            ])->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('account_yandex_delete'))
        {
            $this->refreshTokenForm($form);

            $handle = $AccountYandexDeleteHandler->handle($AccountYandexDeleteDTO);

            $this->addFlash
            (
                'page.delete',
                $handle instanceof AccountYandex ? 'success.delete' : 'danger.delete',
                'auth-yandex.admin',
                $handle
            );

            return $this->redirectToRoute('auth-yandex:admin.index');
        }

        return $this->render([
            'form' => $form->createView(),
        ]);
    }
}
