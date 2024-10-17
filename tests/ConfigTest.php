<?php

namespace AnisAronno\LaravelSelfUpdater\Tests;

use Illuminate\Support\Facades\Config;

class ConfigTest extends TestCase
{
    public function testSelfUpdaterConfig()
    {
        $this->assertEquals('https://github.com/anisAronno/laravel-starter', Config::get('self-updater.release_url'));
        $this->assertEquals('test-purchase-key', Config::get('self-updater.purchase_key'));
        $this->assertEquals(['.env', '.git', 'storage', 'tests'], Config::get('self-updater.exclude_items'));
        $this->assertEquals(['web'], Config::get('self-updater.middleware'));
        $this->assertFalse(Config::get('self-updater.require_composer_install'));
        $this->assertFalse(Config::get('self-updater.require_composer_update'));
    }
}
