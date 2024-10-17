---
title: Add TestCase parent class and implement abstract method
---
# Introduction

This document will walk you through the implementation of the <SwmToken path="/tests/TestCase.php" pos="8:6:6" line-data="use Orchestra\Testbench\TestCase as Orchestra;">`TestCase`</SwmToken> parent class and the abstract methods within it.

The feature introduces a new <SwmToken path="/tests/TestCase.php" pos="8:6:6" line-data="use Orchestra\Testbench\TestCase as Orchestra;">`TestCase`</SwmToken> parent class and implements several abstract methods to facilitate testing in the Laravel Auto Updater package.

We will cover:

1. Why we need a <SwmToken path="/tests/TestCase.php" pos="8:6:6" line-data="use Orchestra\Testbench\TestCase as Orchestra;">`TestCase`</SwmToken> parent class.
2. How we set up the package providers.
3. How we configure the environment for testing.
4. The implementation of abstract methods.

# Why we need a <SwmToken path="/tests/TestCase.php" pos="8:6:6" line-data="use Orchestra\Testbench\TestCase as Orchestra;">`TestCase`</SwmToken> parent class

<SwmSnippet path="/tests/TestCase.php" line="1">

---

The <SwmToken path="/tests/TestCase.php" pos="8:6:6" line-data="use Orchestra\Testbench\TestCase as Orchestra;">`TestCase`</SwmToken> parent class is essential for setting up a consistent testing environment. It extends the Orchestra Testbench <SwmToken path="/tests/TestCase.php" pos="8:6:6" line-data="use Orchestra\Testbench\TestCase as Orchestra;">`TestCase`</SwmToken> to leverage its utilities for Laravel package testing.

```
<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\LaravelAutoUpdaterServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Mockery;
use Orchestra\Testbench\TestCase as Orchestra;
```

---

</SwmSnippet>

# Setting up the package providers

<SwmSnippet path="/tests/TestCase.php" line="10">

---

We need to specify the package providers required for our tests. This is done in the <SwmToken path="/tests/TestCase.php" pos="19:5:5" line-data="    protected function getPackageProviders($app)">`getPackageProviders`</SwmToken> method.

```
abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpConfig();
        Mockery::getConfiguration()->allowMockingNonExistentMethods(true);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelAutoUpdaterServiceProvider::class,
        ];
    }
```

---

</SwmSnippet>

# Configuring the environment for testing

# Implementing abstract methods

<SwmSnippet path="/tests/TestCase.php" line="38">

---

We implement several abstract methods to extend the functionality of the <SwmToken path="/tests/TestCase.php" pos="8:6:6" line-data="use Orchestra\Testbench\TestCase as Orchestra;">`TestCase`</SwmToken> class. These methods include <SwmToken path="/tests/TestCase.php" pos="38:5:5" line-data="    public function artisan($command, $parameters = [])">`artisan`</SwmToken>, <SwmToken path="/tests/TestCase.php" pos="43:5:5" line-data="    public function be(Authenticatable $user, $driver = null)">`be`</SwmToken>, <SwmToken path="/tests/TestCase.php" pos="52:5:5" line-data="    public function call($method, $uri, $parameters = [], $files = [], $server = [], $content = null, $changeHistory = true)">`call`</SwmToken>, and <SwmToken path="/tests/TestCase.php" pos="57:5:5" line-data="    public function seed($class = &#39;DatabaseSeeder&#39;)">`seed`</SwmToken>.

```
    public function artisan($command, $parameters = [])
    {
        return parent::artisan($command, $parameters);
    }

    public function be(Authenticatable $user, $driver = null)
    {
        $this->app['auth']->guard($driver)->setUser($user);

        $this->app->instance('user', $user);

        return $this;
    }

    public function call($method, $uri, $parameters = [], $files = [], $server = [], $content = null, $changeHistory = true)
    {
        return parent::call($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    public function seed($class = 'DatabaseSeeder')
    {
        $this->artisan('db:seed', ['--class' => $class]);
    }
}
```

---

</SwmSnippet>

This setup ensures that our tests have the necessary configurations and utilities to run effectively.

<SwmMeta version="3.0.0" repo-id="Z2l0aHViJTNBJTNBbGFyYXZlbC1hdXRvLXVwZGF0ZXIlM0ElM0FhbmlzQXJvbm5v" repo-name="laravel-auto-updater"><sup>Powered by [Swimm](https://app.swimm.io/)</sup></SwmMeta>
