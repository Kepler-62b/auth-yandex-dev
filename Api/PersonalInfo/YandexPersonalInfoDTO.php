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

namespace BaksDev\Auth\Yandex\Api\PersonalInfo;

/**
 * Дополнительные поля ответа зависят от прав,
 * выбранных при регистрации приложения на Яндекс OAuth и предоставленных OAuth-токеном:
 * - Отсутствие прав из секции API Яндекс ID
 * - Доступ к адресу электронной почты
 * - Доступ к портрету пользователя
 * - Доступ к дате рождения
 * - Доступ к логину, имени и фамилии, полу
 * - Доступ к номеру телефона
 *
 * @note при запросе дополнительных прав необходимо добавить в конструктор ключи,
 * соответствующие запрашиваемым доп. правам
 */
final readonly class YandexPersonalInfoDTO
{
    public function __construct(
        /** Стандартный набор полей */
        private string $id,
        private string $login,
        private string $client_id,
        private string $psuid,
        private ?string $uid = null,

        /** Дополнительные поля ответа */
        private ?string $default_email = null,
        private ?array $emails = null,
    ) {}

    /**
     * Уникальный идентификатор пользователя Яндекса
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Логин пользователя на Яндексе
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * Идентификатор приложения, для которого был выдан переданный в запросе OAuth-токен.
     * Доступен в свойствах приложения.
     * Чтобы открыть свойства, нажмите на название приложения
     */
    public function getClientId(): string
    {
        return $this->client_id;
    }

    /**
     * Идентификатор авторизованного пользователя в Яндексе.
     * Формируется на стороне Яндекса на основе пары client_id и user_id.
     */
    public function getPsuid(): string
    {
        return $this->psuid;
    }

    /** ОПЦИОНАЛЬНЫЕ ПОЛЯ */

    /**
     * Аналог id. Поле доступно только в формате JWT
     */
    public function getUid(): ?string
    {
        return $this->uid;
    }

    /**
     * E-mail по умолчанию, предназначенный для связи с пользователем
     */
    public function getDefaultEmail(): ?string
    {
        return $this->default_email;
    }

    /**
     * Массив электронных адресов пользователя.
     * В настоящее время включает единственный e-mail — e-mail по умолчанию.
     */
    public function getEmails(): ?array
    {
        return $this->emails;
    }
}
