<?php

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GrowBitTech\Framework\Config\_Global;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

return [

    _Global::class => function (ContainerInterface $container) {
        $data = require __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Global.php';

        return new _Global($data);
    },

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        // Should be set to 0 in production
        error_reporting(E_ALL);

        // Should be set to '0' in production
        ini_set('display_errors', '1');

        return AppFactory::create();
    },

    EntityManager::class => function (ContainerInterface $container) {
        $settings = $container->get(_Global::class);

        $cache = $settings->get('dev_mode') ?
            DoctrineProvider::wrap(new ArrayAdapter()) :
            DoctrineProvider::wrap(new FilesystemAdapter(directory: $settings->get('cache_dir')));

        $paths = [];
        foreach (scandir($path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Modules') as $dir) {
            if ($dir == '.' || $dir == '..') {
                continue;
            }
            $paths[] = dirname($path, 3).DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Entities'.DIRECTORY_SEPARATOR;
        }

        $config = Setup::createAttributeMetadataConfiguration(
            $paths,
            $settings->get('dev_mode'),
            null,
            $cache
        );

        $entityManager = EntityManager::create($settings->get('db'), $config);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();

        try {
            $schemaTool->createSchema($classes);
        } catch (\Exception $e) {
            $schemaTool->updateSchema($classes);
        }

        return $entityManager;
    },
];
