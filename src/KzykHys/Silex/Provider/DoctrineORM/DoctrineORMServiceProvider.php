<?php

namespace KzykHys\Silex\Provider\DoctrineORM;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Silex\Application;
use Silex\ServiceProviderInterface;

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