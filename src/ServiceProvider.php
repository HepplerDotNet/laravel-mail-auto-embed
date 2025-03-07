<?php

namespace HepplerDotNet\LaravelMailAutoEmbed;

use HepplerDotNet\LaravelMailAutoEmbed\Contracts\Listeners\EmbedImages;
use HepplerDotNet\LaravelMailAutoEmbed\Listeners\SymfonyEmbedImages;
use Illuminate\Foundation\Application;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([$this->getConfigPath() => config_path('mail-auto-embed.php')], 'config');

        $this->app->singleton(EmbedImages::class, function ($app) {
            return new SymfonyEmbedImages($app['config']->get('mail-auto-embed'));
        });

        Event::listen(function (MessageSending $event) {
            $this->app->make(EmbedImages::class)->beforeSendPerformed($event);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'mail-auto-embed');
    }

    /**
     * @return string
     */
    protected function getConfigPath()
    {
        return __DIR__.'/../config/mail-auto-embed.php';
    }
}
