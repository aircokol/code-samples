<?php

namespace App\Observers;

use Sr\Managers\Search\SearchDataManager;
use Sr\Models\Blog\BlogPost;

class BlogPostObserver
{
    /**
     * Handle the BlogPost "created" event.
     *
     * @param  BlogPost  $blogPost
     *
     * @return void
     */
    public function created(BlogPost $blogPost)
    {
        /** @var SearchDataManager $searchDataManager */
        $searchDataManager = app(SearchDataManager::class);
        $searchDataManager->handleBlogPostCreatedEvent($blogPost);
    }

    /**
     * Handle the BlogPost "updated" event.
     *
     * @param  BlogPost  $blogPost
     *
     * @return void
     */
    public function updated(BlogPost $blogPost)
    {
        /** @var SearchDataManager $searchDataManager */
        $searchDataManager = app(SearchDataManager::class);
        $searchDataManager->handleBlogPostUpdatedEvent($blogPost);
    }

    /**
     * Handle the BlogPost "deleted" event.
     *
     * @param  BlogPost  $blogPost
     *
     * @return void
     */
    public function deleted(BlogPost $blogPost)
    {
        /** @var SearchDataManager $searchDataManager */
        $searchDataManager = app(SearchDataManager::class);
        $searchDataManager->handleBlogPostDeletedEvent($blogPost);
    }
}
