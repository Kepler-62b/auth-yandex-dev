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

namespace BaksDev\Auth\Yandex\UseCase\Public\New;

use BaksDev\Auth\Yandex\Entity\AccountYandex;
use BaksDev\Auth\Yandex\Entity\Event\AccountYandexEvent;
use BaksDev\Auth\Yandex\Messenger\AccountYandexMessage;
use BaksDev\Auth\Yandex\Repository\DBAL\ExistAccountYandexByYid\ExistAccountYandexByYidInterface;
use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Users\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

final class NewAccountYandexHandler extends AbstractHandler
{
    public function __construct(
        #[Target('authYandexLogger')] private readonly LoggerInterface $logger,
        private readonly ExistAccountYandexByYidInterface $existAccountYandexByYidRepository,

        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }

    public function handle(NewAccountYandexDTO $command): string|AccountYandex
    {
        $Yid = $command->getInvariable()->getYid();

        /** Проверка аккаунта Яндекс на уникальность */
        $isExistsAccount = $this->existAccountYandexByYidRepository->isExist($Yid);

        if(true === $isExistsAccount)
        {
            $this->logger->warning(
                message: sprintf('Попытка создания аккаунта Яндекс с существующим yid: %s', $Yid),
                context: [
                    self::class.':'.__LINE__,
                    $command,
                ],
            );

            return uniqid();
        }

        /** Создаем нового пользователя */
        $User = new User();

        $this
            ->setCommand($command)
            ->preEventPersistOrUpdate(new AccountYandex($User->getId()), new AccountYandexEvent);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->persist($User);
        $this->flush();

        /** Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new AccountYandexMessage($this->main->getId(), $this->main->getEvent()),
            transport: 'auth-yandex'
        );

        return $this->main;
    }
}
