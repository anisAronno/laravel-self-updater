<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use Illuminate\Support\Facades\Config;

class ConfigTest extends TestCase
{
    public function testAutoUpdaterConfig()
    {
        $this->assertEquals('https://github.com/user/repo', Config::get('auto-updater.release_url'));
        $this->assertEquals('test-purchase-key', Config::get('auto-updater.purchase_key'));
        $this->assertEquals(['.env', '.git', 'storage', 'tests'], Config::get('auto-updater.exclude_items'));
        $this->assertEquals(['web'], Config::get('auto-updater.middleware'));
        $this->assertFalse(Config::get('auto-updater.require_composer_install'));
        $this->assertFalse(Config::get('auto-updater.require_composer_update'));
    }
}
