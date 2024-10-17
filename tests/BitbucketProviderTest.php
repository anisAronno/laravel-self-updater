<?php

namespace AnisAronno\LaravelSelfUpdater\Tests;

use AnisAronno\LaravelSelfUpdater\Services\VCSProvider\BitbucketProvider;
use Mockery;

class BitbucketProviderTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = Mockery::mock(BitbucketProvider::class, ['https://bitbucket.org/user/repo'])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
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
        $method = new \ReflectionMethod(BitbucketProvider::class, 'extractUserAndRepo');
        $method->setAccessible(true);
        $result = $method->invoke($this->provider);
        $this->assertEquals(['user', 'repo'], $result);
    }
}
