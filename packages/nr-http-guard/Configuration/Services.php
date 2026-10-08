<?php

declare (strict_types=1);
use Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Http\RequestFactory;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $replacement = RequestFactoryCompatibility::replacementClass();
    $inner = $replacement . '.inner';
    $services
        ->set($replacement)
        ->decorate(RequestFactory::class, $inner)
        ->autowire()
        ->autoconfigure()
        ->public()
        ->arg('$originalFactory', service($inner));
    $services->alias(RequestFactoryInterface::class, $replacement)->public();
};
