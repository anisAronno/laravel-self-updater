<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Services\VCSProvider\CustomProvider;
use Mockery;

class CustomProviderTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = Mockery::mock(CustomProvider::class, ['https://custom-vcs.com/user/repo', 'purchase-key'])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetLatestRelease()
    {
        $expectedData = ['version' => '1.0.0'];
        $this->provider->shouldReceive('fetchReleaseData')->withNoArgs()->once()->andReturn($expectedData);
        $result = $this->provider->getLatestRelease();
        $this->assertEquals($expectedData, $result);
    }

    public function testGetReleaseByVersion()
    {
        $version = '1.0.0';
        $expectedData = ['version' => $version];
        $this->provider->shouldReceive('fetchReleaseData')->with($version)->once()->andReturn($expectedData);
        $result = $this->provider->getReleaseByVersion($version);
        $this->assertEquals($expectedData, $result);
    }

    public function testExtractUserAndRepo()
    {
        $method = new \ReflectionMethod(CustomProvider::class, 'extractUserAndRepo');
        $method->setAccessible(true);
        $result = $method->invoke($this->provider);
        $this->assertEquals(['user', 'repo'], $result);
    }
}
