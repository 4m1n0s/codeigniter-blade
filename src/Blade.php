<?php

namespace Aminos\CodeigniterBlade;

use ArrayAccess;
use Config\Paths;
use Illuminate\Support\Arr;
use Illuminate\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\View\FileViewFinder;
use Illuminate\Container\Container;
use Illuminate\View\DynamicComponent;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory as ViewFactory;


class Blade implements ViewFactory
{
    private Factory $factory;

    public function __construct(string $viewPath, string $cachePath)
    {
        $this->setupContainer((array)$viewPath, $cachePath);
    }

    private function setupContainer(array $viewPath, $cachePath)
    {
        $container = new class extends Container {
            protected $terminatingCallbacks = [];

            public function terminating($callback)
            {
                $this->terminatingCallbacks[] = $callback;

                return $this;
            }

            public function terminate()
            {
                $index = 0;

                while ($index < count($this->terminatingCallbacks)) {
                    $this->call($this->terminatingCallbacks[$index]);

                    $index++;
                }
            }
        };

        $container->singletonIf('config', function () {
            return new class implements ArrayAccess {
                protected $items = [];

                public function __construct(array $items = [])
                {
                    $this->items = $items;
                }

                public function has($key)
                {
                    return Arr::has($this->items, $key);
                }

                public function get($key, $default = null)
                {
                    return Arr::get($this->items, $key, $default);
                }

                public function set($key, $value = null)
                {
                    $keys = is_array($key) ? $key : [$key => $value];

                    foreach ($keys as $key => $value) {
                        Arr::set($this->items, $key, $value);
                    }
                }

                public function offsetExists($key): bool
                {
                    return $this->has($key);
                }

                public function offsetGet($key): mixed
                {
                    return $this->get($key);
                }

                public function offsetSet($key, $value): void
                {
                    $this->set($key, $value);
                }

                public function offsetUnset($key): void
                {
                    $this->set($key, null);
                }
            };
        });

        $container['config']->set([
            'view.paths' => $viewPath,
            'view.compiled' => $cachePath,
        ]);

        $container->singletonIf('files', function () {
            return new Filesystem();
        });

        $container->singletonIf('events', function () {
            return new Dispatcher();
        });

        $container->singletonIf(ViewFactory::class, function () {
            return $this;
        });

        $container->singletonIf(Application::class, function () {
            return new class {
                public function getNamespace()
                {
                    $composer = json_decode(file_get_contents(config(Paths::class)->appDirectory . '/../composer.json'), true);

                    return count($composer['autoload']['psr-4'] ?? []) ? array_keys($composer['autoload']['psr-4'])[0] : null;
                }
            };
        });

        $container->singleton('view', function ($app) {
            $resolver = $app['view.engine.resolver'];

            $finder = $app['view.finder'];

            $factory = new Factory($resolver, $finder, $app['events']);

            $factory->setContainer($app);

            $factory->share('app', $app);

            return $factory;
        });

        $container->bind('view.finder', function ($app) {
            return new FileViewFinder($app['files'], $app['config']['view.paths']);
        });

        $container->singleton('blade.compiler', function ($app) {
            return tap(new BladeCompiler(
                $app['files'],
                $app['config']['view.compiled'],
            ), function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });

        $container->singleton('view.engine.resolver', function ($app) {
            $resolver = new EngineResolver;

            $resolver->register('file', function () use ($app) {
                return new FileEngine($app['files']);
            });

            $resolver->register('php', function () use ($app) {
                return new PhpEngine($app['files']);
            });

            $resolver->register('blade', function () use ($app) {
                $compiler = new CompilerEngine($app['blade.compiler'], $app['files']);

                $app->terminating(static function () use ($compiler) {
                    $compiler->forgetCompiledOrNotExpired();
                });

                return $compiler;
            });

            return $resolver;
        });

        Container::setInstance($container);

        $this->factory = $container->get('view');

        Facade::setFacadeApplication($container);

        $container->terminate();
    }

    public function exists($view)
    {
        return $this->factory->exists($view);
    }

    public function file($path, $data = [], $mergeData = [])
    {
        return $this->factory->file($path, $data, $mergeData);
    }

    public function render($view, $data = [], $mergeData = [])
    {
        return $this->make($view, $data, $mergeData)->render();
    }

    public function make($view, $data = [], $mergeData = [])
    {
        return $this->factory->make($view, $data, $mergeData);
    }

    public function share($key, $value = null)
    {
        return $this->factory->share($key, $value);
    }

    public function composer($views, $callback)
    {
        return $this->factory->composer($views, $callback);
    }

    public function creator($views, $callback)
    {
        return $this->factory->creator($views, $callback);
    }

    public function addNamespace($namespace, $hints)
    {
        $this->factory->addNamespace($namespace, $hints);

        return $this;
    }

    public function replaceNamespace($namespace, $hints)
    {
        $this->factory->replaceNamespace($namespace, $hints);

        return $this;
    }
}