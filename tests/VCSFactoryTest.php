<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Services\VCSProvider\BitbucketProvider;
use AnisAronno\LaravelAutoUpdater\Services\VCSProvider\CustomProvider;
use AnisAronno\LaravelAutoUpdater\Services\VCSProvider\GitHubProvider;
use AnisAronno\LaravelAutoUpdater\Services\VCSProvider\GitLabProvider;
use AnisAronno\LaravelAutoUpdater\Services\VCSProvider\VCSFactory;

class VCSFactoryTest extends TestCase
{
    public function testCreateGitHubProvider()
    {
        $provider = VCSFactory::create('https://github.com/user/repo', null);
        $this->assertInstanceOf(GitHubProvider::class, $provider);
    }

    public function testCreateGitLabProvider()
    {
        $provider = VCSFactory::create('https://gitlab.com/user/repo', null);
        $this->assertInstanceOf(GitLabProvider::class, $provider);
    }

    public function testCreateBitbucketProvider()
    {
        $provider = VCSFactory::create('https://bitbucket.org/user/repo', null);
        $this->assertInstanceOf(BitbucketProvider::class, $provider);
    }

    public function testCreateCustomProvider()
    {
        $provider = VCSFactory::create('https://custom-vcs.com/user/repo', 'purchase-key');
        $this->assertInstanceOf(CustomProvider::class, $provider);
    }
}
