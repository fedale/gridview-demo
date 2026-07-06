<?php

namespace App\Tests;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Phase B (lazy grouping): the `?_children=<id>` fragment endpoint backing the
 * gridview-grouping Stimulus controller's on-demand fetch. See
 * vendor/fedale/gridview-bundle/docs/GROUPING-LAZY-plan.md.
 */
class GridviewGroupingLazyTest extends WebTestCase
{
    public function testChildrenEndpointReturnsThatUsersPostsAsAFragment(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $post = $em->getRepository(Post::class)->findOneBy([]);
        if ($post === null) {
            $this->markTestSkipped('No Post entity found in database');
        }
        $user = $post->getAuthor();

        $client->request('GET', sprintf('/gridview/users?_children=%d', $user->getId()));

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString($post->getTitle(), (string) $response->getContent());
        // A fragment, not the surrounding grid: no toolbar/table chrome.
        $this->assertStringNotContainsString('data-gridview', (string) $response->getContent());
    }

    public function testChildrenEndpointOnAParentWithNoPostsRendersEmptyMessage(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $users = $em->getRepository(User::class)->findAll();
        $withoutPosts = null;
        foreach ($users as $candidate) {
            if ($candidate->getPosts()->isEmpty()) {
                $withoutPosts = $candidate;
                break;
            }
        }
        if ($withoutPosts === null) {
            $this->markTestSkipped('Every User in the database has at least one Post');
        }

        $client->request('GET', sprintf('/gridview/users?_children=%d', $withoutPosts->getId()));

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('No related records', (string) $response->getContent());
    }
}
