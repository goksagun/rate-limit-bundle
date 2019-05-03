Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require goksagun/rate-limit-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Goksagun\RateLimitBundle\RateLimitBundle(),
        ];

        // ...
    }

    // ...
}
```

Step 3: Add the Bundle config file (for symfony version 2, 3)
----------------------------------

Then, add the bundle configuration yml file `rate_limit.yml` into 
`app/config` directory:

```yml
rate_limit:
    enabled: true
    paths:
         - { path: /limit, limit: 100, period: 60 }
         - { path: /limit-dynamic-increment, limit: 1000, period: 60, increment: 10 }
```

Import new config file to `config.yml` into `app/config` directory:

```yml
imports:
    ...
    - { resource: rate_limit.yml }
```