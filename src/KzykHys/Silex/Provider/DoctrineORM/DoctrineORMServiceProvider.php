<?php

namespace KzykHys\Silex\Provider\DoctrineORM;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\ORM\Tools\SchemaTool;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Console\Application as Console;

/**
 * Provides Doctrine ORM to Silex
 *
 * @author Kazuyuki Hayashi <hayashi@valnur.net>
 */
class DoctrineORMServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        $app['orm.default.parameters'] = array(
            'orm.cache.dir'       => __DIR__ . '/../../../../../cache/orm',
            'orm.entity.path'     => array(),
            'orm.proxy.dir'       => __DIR__ . '/../../../../../cache/proxies',
            'orm.proxy.namespace' => 'Doctrine\\ORM\\Proxies',
        );

        foreach ($app['orm.default.parameters'] as $key => $value) {
            if (!isset($app[$key])) {
                $app[$key] = $value;
            }
        }

        /**
         * @param  Application $app
         *
         * @return \Doctrine\Common\Cache\Cache
         */
        $app['orm.cache.driver'] = function (Application $app) {
            if (extension_loaded('apc')) {
                return new ApcCache();
            } else {
                return new FilesystemCache($app['orm.cache.dir']);
            }
        };

        /**
         * @param  Application $app
         *
         * @return \Doctrine\ORM\Configuration
         */
        $app['orm.config'] = $app->share(function (Application $app) {
            $config = new Configuration();
            $config->setMetadataCacheImpl($app['orm.cache.driver']);
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($app['orm.entity.path']));
            $config->setQueryCacheImpl($app['orm.cache.driver']);
            $config->setProxyDir($app['orm.proxy.dir']);
            $config->setProxyNamespace($app['orm.proxy.namespace']);
            $config->setAutoGenerateProxyClasses($app['debug']);

            return $config;
        });

        /**
         * @param  Application $app
         *
         * @throws \RuntimeException
         *
         * @return \Doctrine\ORM\EntityManager
         */
        $app['orm.em'] = $app->share(function (Application $app) {
            if (!isset($app['db'])) {
                throw new \RuntimeException('DoctrineServiceProvider is not registered');
            }

            return EntityManager::create($app['db'], $app['orm.config'], $app['db.event_manager']);
        });

        /**
         * @param  Application $app
         *
         * @return \Doctrine\ORM\Tools\SchemaTool
         */
        $app['orm.schema_tool'] = $app->share(function (Application $app) {
            return new SchemaTool($app['orm.em']);
        });

        /*
         * Register console commands for doctrine if `kzykhys/console-service-provider` is registered
         */
        if (isset($app['console.commands'])) {
            $app['console'] = $app->share($app->extend('console', function (Console $console, Application $app) {
                $console->getHelperSet()->set(new ConnectionHelper($app['db']), 'db');
                $console->getHelperSet()->set(new EntityManagerHelper($app['orm.em']), 'em');

                return $console;
            }));

            $app['console.commands'] = $app->share($app->extend('console.commands', function (array $commands) {
                $create         = new CreateCommand();
                $update         = new UpdateCommand();
                $drop           = new DropCommand();
                $generateEntity = new GenerateEntitiesCommand();
                $generateProxy  = new GenerateProxiesCommand();

                $commands[] = $create->setName('doctrine:schema:create');
                $commands[] = $update->setName('doctrine:schema:update');
                $commands[] = $drop->setName('doctrine:schema:drop');
                $commands[] = $generateEntity->setName('doctrine:generate:entities');
                $commands[] = $generateProxy->setName('doctrine:generate:proxies');

                return $commands;
            }));
        }
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registers
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
    }

}