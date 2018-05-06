# Container [![Travis Status for Ejz/Container](https://travis-ci.org/Ejz/Container.svg?branch=master)](https://travis-ci.org/Ejz/Container)

PSR-11 compatible Dependency Injection container.

### Quick start

```bash
$ mkdir myproject && cd $_
$ curl -sS 'https://getcomposer.org/installer' | php
$ php composer.phar require ejz/container:~1.0
```

Bind an interface to an implementation:

```php
<?php

require 'vendor/autoload.php';

$container = new \Ejz\Container();
$container->setDefinitions([
    ObjectInterface::class => ObjectClass::class,
]);
$mi = $container->get(ObjectInterface::class);
echo get_class($mi), "\n";
// will output "ObjectClass"
```

Bind using a Closure:

```php
$container->setDefinitions([
    ObjectInterface::class => function () {
        return $this->get(ObjectClass::class);
    },
]);
```

Parameterize your Closure:

```php
$container->setDefinitions([
    SessionInterface::class => MySession::class,
    MyInterface::class => function (SessionInterface $session) {
        return $this->get(MyClass::class, [
            'session' => $session,
        ]);
    },
]);
```

### Authors

- [Evgeny Cernisev](https://ejz.ru) | [GitHub](https://github.com/Ejz) | <ejz@ya.ru>

### License

[Container](https://github.com/Ejz/Container) is licensed under the [WTFPL License](https://en.wikipedia.org/wiki/WTFPL) (see [LICENSE](LICENSE)).

