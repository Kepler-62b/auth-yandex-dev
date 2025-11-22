<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Auth\Yandex\BaksDevAuthYandexBundle;
use BaksDev\Auth\Yandex\Type\Event\AccountYandexEventType;
use BaksDev\Auth\Yandex\Type\Event\AccountYandexEventUid;
use Symfony\Config\DoctrineConfig;

return static function(ContainerConfigurator $container, DoctrineConfig $doctrine) {

    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    /** Types */
    $doctrine->dbal()->type(AccountYandexEventUid::TYPE)->class(AccountYandexEventType::class);

    $emDefault = $doctrine->orm()->entityManager('default')->autoMapping(true);

    $emDefault->mapping('auth-yandex')
        ->type('attribute')
        ->dir(BaksDevAuthYandexBundle::PATH.'Entity')
        ->isBundle(false)
        ->prefix(BaksDevAuthYandexBundle::NAMESPACE.'\\Entity')
        ->alias('auth-yandex');
};
