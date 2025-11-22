<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Auth\Yandex\Api\AuthToken;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

final class YandexAuthTokenDTO
{
    private UserProfileUid $profile;

    /** Название магазина */
    private string $name;

    /** Идентификатор компании */
    private int $company;

    /** Идентификатор клиента */
    private int $client;

    /** Идентификатор кабинета */
    private int $business;

    /**
     * Модель, по которой работает магазин:
     *
     * FBS — FBS или Экспресс;
     * FBY — FBY;
     * DBS — DBS.
     *
     */
    private string $type;


    public function __construct(UserProfileUid $profile, array $data)
    {
        $this->profile = $profile;
        $this->name = $data['business']['name'];
        $this->business = $data['business']['id'];
        $this->company = $data['id'];
        $this->client = $data['clientId'];
        $this->type = $data['placementType'];
    }

    /**
     * Profile
     */
    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

    /**
     * Name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Company
     */
    public function getCompany(): int
    {
        return $this->company;
    }

    /**
     * Client
     */
    public function getClient(): int
    {
        return $this->client;
    }

    /**
     * Business
     */
    public function getBusiness(): int
    {
        return $this->business;
    }

    /**
     * Type
     */
    public function getType(): string
    {
        return $this->type;
    }
}
