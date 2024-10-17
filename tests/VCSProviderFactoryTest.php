<?php

namespace AnisAronno\LaravelSelfUpdater\Tests;

use AnisAronno\LaravelSelfUpdater\Services\VCSProvider\BitbucketProvider;
use AnisAronno\LaravelSelfUpdater\Services\VCSProvider\CustomProvider;
use AnisAronno\LaravelSelfUpdater\Services\VCSProvider\GitHubProvider;
use AnisAronno\LaravelSelfUpdater\Services\VCSProvider\GitLabProvider;
use AnisAronno\LaravelSelfUpdater\Services\VCSProvider\VCSProviderFactory;
use InvalidArgumentException;

class VCSProviderFactoryTest extends TestCase
{
    public function testCreateGitHubProvider()
    {
        $provider = VCSProviderFactory::create('https://github.com/user/repo');
        $this->assertInstanceOf(GitHubProvider::class, $provider);
    }

    public function testCreateGitLabProvider()
    {
        $provider = VCSProviderFactory::create('https://gitlab.com/user/repo');
        $this->assertInstanceOf(GitLabProvider::class, $provider);
    }

    public function testCreateBitbucketProvider()
    {
        $provider = VCSProviderFactory::create('https://bitbucket.org/user/repo');
        $this->assertInstanceOf(BitbucketProvider::class, $provider);
    }

    public function testCreateCustomProvider()
    {
        $provider = VCSProviderFactory::create('https://custom-vcs.com/user/repo');
        $this->assertInstanceOf(CustomProvider::class, $provider);
    }

    public function testCreateWithInvalidUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        VCSProviderFactory::create('invalid-vcs/user/repo');
    }

    public function testCreateWithEmptyUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        VCSProviderFactory::create('');
    }

    public function testCreateWithNullUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        VCSProviderFactory::create(null);
    }

    public function testCreateWithHttpUrl()
    {
        $provider = VCSProviderFactory::create('http://github.com/user/repo');
        $this->assertInstanceOf(GitHubProvider::class, $provider);
    }

    public function testHasProvider()
    {
        VCSProviderFactory::registerProvider('anichur.com', GitHubProvider::class);
        $this->assertTrue(VCSProviderFactory::hasProvider('anichur.com'));
        $this->assertFalse(VCSProviderFactory::hasProvider('anisAronno.me'));
    }

    public function testRemoveProvider()
    {
        VCSProviderFactory::registerProvider('anichur.me', GitHubProvider::class);
        $this->assertTrue(VCSProviderFactory::hasProvider('anichur.me'));

        VCSProviderFactory::removeProvider('anichur.me');
        $this->assertFalse(VCSProviderFactory::hasProvider('anichur.me'));
    }

    public function testGetProviders()
    {
        VCSProviderFactory::registerProvider('anisAronno.com', GitHubProvider::class);
        VCSProviderFactory::registerProvider('anisAronno.org', BitbucketProvider::class);

        $providers = VCSProviderFactory::getProviders();
        $this->assertArrayHasKey('anisAronno.com', $providers);
        $this->assertArrayHasKey('anisAronno.org', $providers);
        $this->assertEquals(GitHubProvider::class, $providers['anisAronno.com']);
        $this->assertEquals(BitbucketProvider::class, $providers['anisAronno.org']);
    }
}
