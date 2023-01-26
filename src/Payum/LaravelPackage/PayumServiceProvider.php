<?php
namespace Payum\LaravelPackage;

use Illuminate\Support\ServiceProvider;
use Payum\Core\Bridge\Symfony\ReplyToSymfonyResponseConverter;
use Payum\Core\Bridge\Symfony\Security\HttpRequestVerifier;
use Payum\Core\PayumBuilder;
use Payum\Core\Registry\StorageRegistryInterface;
use Payum\Core\Storage\StorageInterface;
use Payum\LaravelPackage\Action\GetHttpRequestAction;
use Payum\LaravelPackage\Action\ObtainCreditCardAction;
use Payum\LaravelPackage\Security\TokenFactory;

class PayumServiceProvider extends ServiceProvider
{
    /**
     * Version Specific provider
     */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        $this->defineRoutes();
    }

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->registerServices();
    }

    /**
     * {@inheritDoc}
     */
    public function provides()
    {
        return [
            'payum',
            'payum.builder',
            'payum.converter.reply_to_http_response',
        ];
    }

    protected function registerServices()
    {
        $this->app->bind('payum.builder', function($app) {
            $builder = new PayumBuilder();
            $builder
                ->setTokenFactory(function(StorageInterface $tokenStorage, StorageRegistryInterface $registry) {
                    return new TokenFactory($tokenStorage, $registry);
                })
                ->setHttpRequestVerifier(function(StorageInterface $tokenStorage) {
                    return new HttpRequestVerifier($tokenStorage);
                })
                ->setCoreGatewayFactory(function(array $defaultConfig) {
                    $factory = new CoreGatewayFactory($defaultConfig);
                    $factory->setContainer($this->app);

                    return $factory;
                })
                ->setCoreGatewayFactoryConfig([
                    'payum.action.get_http_request' => 'payum.action.get_http_request',
                    'payum.action.obtain_credit_card' => 'payum.action.obtain_credit_card',
                ])
                ->setGenericTokenFactoryPaths([
                    'capture' => 'payum_capture_do',
                    'notify' => 'payum_notify_do',
                    'authorize' => 'payum_authorize_do',
                    'refund' => 'payum_refund_do',
                ])
            ;

            return $builder;
        });

        $this->app->singleton('payum', function($app) {
            return $app['payum.builder']->getPayum();
        });

        $this->app->singleton('payum.converter.reply_to_http_response', function($app) {
            return new ReplyToSymfonyResponseConverter();
        });

        $this->app->singleton('payum.action.get_http_request', function($app) {
            return new GetHttpRequestAction();
        });

        $this->app->singleton('payum.action.obtain_credit_card', function($app) {
            return new ObtainCreditCardAction();
        });
    }

    /**
     * Define all package routes with Laravel router
     */
    protected function defineRoutes()
    {
        $route = $this->app->make('router');

        $route->get('/payment/authorize/{payum_token}', array(
            'as' => 'payum_authorize_do',
            'uses' => 'Payum\LaravelPackage\Controller\AuthorizeController@doAction'
        ));


        $route->post('/payment/authorize/{payum_token}', array(
            'as' => 'payum_authorize_do_post',
            'uses' => 'Payum\LaravelPackage\Controller\AuthorizeController@doAction'
        ));

        $route->get('/payment/capture/{payum_token}', array(
            'as' => 'payum_capture_do',
            'uses' => 'Payum\LaravelPackage\Controller\CaptureController@doAction'
        ));

        $route->post('/payment/capture/{payum_token}', array(
            'as' => 'payum_capture_do_post',
            'uses' => 'Payum\LaravelPackage\Controller\CaptureController@doAction'
        ));

        $route->get('/payment/refund/{payum_token}', array(
            'as' => 'payum_refund_do',
            'uses' => 'Payum\LaravelPackage\Controller\RefundController@doAction'
        ));

        $route->post('/payment/refund/{payum_token}', array(
            'as' => 'payum_refund_do_post',
            'uses' => 'Payum\LaravelPackage\Controller\RefundController@doAction'
        ));

        $route->get('/payment/notify/{payum_token}', array(
            'as' => 'payum_notify_do',
            'uses' => 'Payum\LaravelPackage\Controller\NotifyController@doAction'
        ));

        $route->post('/payment/notify/{payum_token}', array(
            'as' => 'payum_notify_do_post',
            'uses' => 'Payum\LaravelPackage\Controller\NotifyController@doAction'
        ));

        $route->get('/payment/notify/unsafe/{gateway_name}', array(
            'as' => 'payum_notify_do_unsafe',
            'uses' => 'Payum\LaravelPackage\Controller\NotifyController@doUnsafeAction'
        ));

        $route->post('/payment/notify/unsafe/{gateway_name}', array(
            'as' => 'payum_notify_do_unsafe_post',
            'uses' => 'Payum\LaravelPackage\Controller\NotifyController@doUnsafeAction'
        ));
    }
}
