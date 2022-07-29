<?php

namespace Sr\Managers\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Sr\Models\Blog\BlogPost;
use Sr\Models\Blog\BlogTag;
use Sr\Models\DigestOfSummaries;
use Sr\Models\SearchData;
use Sr\Models\Summary;
use Sr\Models\Summary\SummaryAuthorNames;
use Sr\Models\Summary\SummaryTag;
use Sr\Models\Vacancies;
use Sr\Repositories\Search\SearchDataRepository;
use Throwable;

class SearchDataManager
{
    public int $perPage = 15;

    private SearchDataRepository $searchDataRepository;

    public SearchData $searchData;

    public function __construct()
    {
        $this->perPage = config('pagination.max_count_item_on_page');
        $this->searchDataRepository = app(SearchDataRepository::class);
    }

    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;
    }

    public function getPaginated(string $search = null): LengthAwarePaginator
    {
        return $this->searchDataRepository->getPaginated($search, $this->perPage);
    }

    public function getById(int $searchDataId): ?SearchData
    {
        return $this->searchDataRepository->getById($searchDataId);
    }

    /**
     * @param  int|string  $searchableParam
     * @param  string|array|null  $searchableType
     *
     * @return Collection
     */
    public function getBySearchableIdOrUrl($searchableParam, $searchableType = null): Collection
    {
        return $this->searchDataRepository
            ->setSearchableType($searchableType)
            ->getBySearchableIdOrUrl($searchableParam);
    }

    /**
     * @throws Throwable
     */
    public function updateById(int $searchDataId, array $data): ?SearchData
    {
        $this->searchData = $this->searchDataRepository->getById($searchDataId);

        throw_if(! $this->searchData, new ModelNotFoundException('Данные поиска не найдены для ID: ' . $searchDataId));

        return $this->searchDataRepository->update($data);
    }

    public function handleBlogTagUpdatedEvent(BlogTag $blogTag): void
    {
        $updatedFields = $blogTag->getDirty();

        if (! key_exists('title', $updatedFields)) return;

        $data = [
            'searchable_id' => $blogTag->id,
            'searchable_type' => 'blog_tag',
            'searchable_field' => 'title',
            'searchable_field_weight' => SearchDataRepository::BLOG_TAG_TITLE_FIELD_WEIGHT,
        ];

        $this->searchDataRepository->updateOrCreate($updatedFields['title'], $data);
    }

    public function handleBlogTagCreatedEvent(BlogTag $blogTag)
    {
        $title = $blogTag->title;

        $data = [
            'searchable_id' => $blogTag->id,
            'searchable_type' => 'blog_tag',
            'searchable_field' => 'title',
            'searchable_field_weight' => SearchDataRepository::BLOG_TAG_TITLE_FIELD_WEIGHT,
        ];

        $this->searchDataRepository->updateOrCreate($title, $data);
    }

    public function handleBlogTagDeletedEvent(BlogTag $blogTag)
    {
        $this->searchDataRepository->deleteBySearchableParams($blogTag->id, 'blog_tag');
    }

    public function handleBlogPostUpdatedEvent(BlogPost $blogPost)
    {
        $updatedFields = $blogPost->getDirty();

        if (key_exists('title', $updatedFields)) {
            $data = [
                'searchable_id' => $blogPost->id,
                'searchable_type' => 'blog_post',
                'searchable_field' => 'title',
                'searchable_field_weight' => SearchDataRepository::BLOG_POST_TITLE_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($updatedFields['title'], $data);
        }

        if (key_exists('short_title', $updatedFields)) {
            if (empty($updatedFields['short_title'])) {
                $this->searchDataRepository->deleteBySearchableParams($blogPost->id, 'blog_post', 'short_title');
            } else {
                $data = [
                    'searchable_id' => $blogPost->id,
                    'searchable_type' => 'blog_post',
                    'searchable_field' => 'short_title',
                    'searchable_field_weight' => SearchDataRepository::BLOG_POST_SHORT_TITLE_FIELD_WEIGHT,
                ];

                $this->searchDataRepository->updateOrCreate($updatedFields['short_title'], $data);
            }
        }

        if (key_exists('intro_text_for_feed', $updatedFields)) {
            if (empty($updatedFields['intro_text_for_feed'])) {
                $this->searchDataRepository->deleteBySearchableParams($blogPost->id, 'blog_post', 'intro_text_for_feed');
            } else {
                $data = [
                    'searchable_id' => $blogPost->id,
                    'searchable_type' => 'blog_post',
                    'searchable_field' => 'intro_text_for_feed',
                    'searchable_field_weight' => SearchDataRepository::BLOG_POST_INTRO_TEXT_FOR_FEED_FIELD_WEIGHT,
                ];

                $this->searchDataRepository->updateOrCreate($updatedFields['intro_text_for_feed'], $data);
            }
        }

        if (key_exists('content', $updatedFields)) {
            if (empty($updatedFields['content'])) {
                $this->searchDataRepository->deleteBySearchableParams($blogPost->id, 'blog_post', 'content');
            } else {
                $data = [
                    'searchable_id' => $blogPost->id,
                    'searchable_type' => 'blog_post',
                    'searchable_field' => 'content',
                    'searchable_field_weight' => SearchDataRepository::BLOG_POST_CONTENT_FIELD_WEIGHT,
                ];

                $this->searchDataRepository->updateOrCreate($updatedFields['content'], $data);
            }
        }
    }

    public function handleBlogPostCreatedEvent(BlogPost $blogPost)
    {
        $title = $blogPost->title;
        $shortTitle = $blogPost->short_title;
        $introTextForFeed = $blogPost->intro_text_for_feed;
        $content = $blogPost->content;

        $data = [
            'searchable_id' => $blogPost->id,
            'searchable_type' => 'blog_post',
            'searchable_field' => 'title',
            'searchable_field_weight' => SearchDataRepository::BLOG_POST_TITLE_FIELD_WEIGHT,
        ];

        $this->searchDataRepository->updateOrCreate($title, $data);

        if (!empty($shortTitle)) {
            $data = [
                'searchable_id' => $blogPost->id,
                'searchable_type' => 'blog_post',
                'searchable_field' => 'short_title',
                'searchable_field_weight' => SearchDataRepository::BLOG_POST_SHORT_TITLE_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($shortTitle, $data);
        }

        if (!empty($introTextForFeed)) {
            $data = [
                'searchable_id' => $blogPost->id,
                'searchable_type' => 'blog_post',
                'searchable_field' => 'intro_text_for_feed',
                'searchable_field_weight' => SearchDataRepository::BLOG_POST_INTRO_TEXT_FOR_FEED_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($introTextForFeed, $data);
        }

        if (!empty($content)) {
            $data = [
                'searchable_id' => $blogPost->id,
                'searchable_type' => 'blog_post',
                'searchable_field' => 'content',
                'searchable_field_weight' => SearchDataRepository::BLOG_POST_CONTENT_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($content, $data);
        }
    }

    public function handleBlogPostDeletedEvent(BlogPost $blogPost)
    {
        $this->searchDataRepository->deleteBySearchableParams($blogPost->id, 'blog_post');
    }

    public function handleSummaryCreatedEvent(Summary $summary)
    {
        $summary->load('authors');

        $titleRu = $summary->titleLongRu ? $summary->titleLongRu->title : $summary->book_long_name;
        $titleEn = $summary->titleLongEn ? $summary->titleLongEn->title : $summary->book_long_name;
        $summary->authors->transform(fn ($author) => implode(', ', $author->getAllFullNames()));
        $authors = implode(', ', $summary->authors->toArray());

        if (!empty($titleRu)) {
            $data = [
                'searchable_id' => $summary->book_id,
                'searchable_type' => 'summary',
                'searchable_field' => 'title_ru',
                'searchable_field_weight' => SearchDataRepository::SUMMARY_TITLE_RU_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($titleRu, $data);
        }

        if (!empty($titleEn)) {
            $data = [
                'searchable_id' => $summary->book_id,
                'searchable_type' => 'summary',
                'searchable_field' => 'title_en',
                'searchable_field_weight' => SearchDataRepository::SUMMARY_TITLE_EN_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($titleEn, $data);
        }

        if (!empty($authors)) {
            $data = [
                'searchable_id' => $summary->book_id,
                'searchable_type' => 'summary',
                'searchable_field' => 'summary_authors',
                'searchable_field_weight' => SearchDataRepository::SUMMARY_AUTHORS_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($authors, $data);
        }
    }

    public function handleSummaryUpdatedEvent(Summary $summary)
    {
        $summary->load('authors');

        $titleRu = $summary->titleLongRu ? $summary->titleLongRu->title : $summary->book_long_name;
        $titleEn = $summary->titleLongEn ? $summary->titleLongEn->title : $summary->book_long_name;
        $summary->authors->transform(fn ($author) => implode(', ', $author->getAllFullNames()));
        $authors = implode(', ', $summary->authors->toArray());

        if (empty($titleRu)) {
            $this->searchDataRepository->deleteBySearchableParams($summary->book_id, 'summary', 'title_ru');
        } else {
            $data = [
                'searchable_id' => $summary->book_id,
                'searchable_type' => 'summary',
                'searchable_field' => 'title_ru',
                'searchable_field_weight' => SearchDataRepository::SUMMARY_TITLE_RU_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($titleRu, $data);
        }

        if (empty($titleEn)) {
            $this->searchDataRepository->deleteBySearchableParams($summary->book_id, 'summary', 'title_en');
        } else {
            $data = [
                'searchable_id' => $summary->book_id,
                'searchable_type' => 'summary',
                'searchable_field' => 'title_en',
                'searchable_field_weight' => SearchDataRepository::SUMMARY_TITLE_EN_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($titleEn, $data);
        }

        if (empty($authors)) {
            $this->searchDataRepository->deleteBySearchableParams($summary->book_id, 'summary', 'summary_authors');
        } else {
            $data = [
                'searchable_id' => $summary->book_id,
                'searchable_type' => 'summary',
                'searchable_field' => 'summary_authors',
                'searchable_field_weight' => SearchDataRepository::SUMMARY_AUTHORS_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($authors, $data);
        }
    }

    public function handleSummaryDeletedEvent(Summary $summary)
    {
        $this->searchDataRepository->deleteBySearchableParams($summary->book_id, 'summary');
    }

    public function handleSummaryAuthorNameChangeEvent(SummaryAuthorNames $summaryAuthorName)
    {
        $summaryAuthor = $summaryAuthorName->author;

        foreach ($summaryAuthor->summaries as $summary) {
            $summary->load('authors');
            $summary->authors->transform(fn ($author) => implode(', ', $author->getAllFullNames()));
            $authors = implode(', ', $summary->authors->toArray());

            if (empty($authors)) {
                $this->searchDataRepository->deleteBySearchableParams($summary->book_id, 'summary', 'summary_authors');
            } else {
                $data = [
                    'searchable_id' => $summary->book_id,
                    'searchable_type' => 'summary',
                    'searchable_field' => 'summary_authors',
                    'searchable_field_weight' => SearchDataRepository::SUMMARY_AUTHORS_FIELD_WEIGHT,
                ];

                $this->searchDataRepository->updateOrCreate($authors, $data);
            }
        }
    }

    public function handleSummaryTagUpdatedEvent(SummaryTag $summaryTag): void
    {
        $updatedFields = $summaryTag->getDirty();

        if (! key_exists('title', $updatedFields)) return;

        $data = [
            'searchable_id' => $summaryTag->id,
            'searchable_type' => 'summary_tag',
            'searchable_field' => 'title',
            'searchable_field_weight' => SearchDataRepository::SUMMARY_TAG_TITLE_FIELD_WEIGHT,
        ];

        $this->searchDataRepository->updateOrCreate($updatedFields['title'], $data);
    }

    public function handleSummaryTagCreatedEvent(SummaryTag $summaryTag)
    {
        $title = $summaryTag->title;

        $data = [
            'searchable_id' => $summaryTag->id,
            'searchable_type' => 'summary_tag',
            'searchable_field' => 'title',
            'searchable_field_weight' => SearchDataRepository::SUMMARY_TAG_TITLE_FIELD_WEIGHT,
        ];

        $this->searchDataRepository->updateOrCreate($title, $data);
    }

    public function handleSummaryTagDeletedEvent(SummaryTag $summaryTag)
    {
        $this->searchDataRepository->deleteBySearchableParams($summaryTag->id, 'summary_tag');
    }

    public function handleDigestOfSummariesCreatedEvent(DigestOfSummaries $digestOfSummaries)
    {
        if (! $digestOfSummaries->is_published) {
            return;
        }

        $titleRu = $digestOfSummaries->title_ru;
        $descriptionShortRu = $digestOfSummaries->description_short_ru;
        $descriptionFullRu = $digestOfSummaries->description_full_ru;

        $data = [
            'searchable_id' => $digestOfSummaries->id,
            'searchable_type' => 'digest',
            'searchable_field' => 'title_ru',
            'searchable_field_weight' => SearchDataRepository::DIGEST_TITLE_FIELD_WEIGHT,
        ];

        $this->searchDataRepository->updateOrCreate($titleRu, $data);

        if (!empty($descriptionShortRu)) {
            $data = [
                'searchable_id' => $digestOfSummaries->id,
                'searchable_type' => 'digest',
                'searchable_field' => 'description_short_ru',
                'searchable_field_weight' => SearchDataRepository::DIGEST_DESCRIPTION_SHORT_RU_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($descriptionShortRu, $data);
        }

        if (!empty($descriptionFullRu)) {
            $data = [
                'searchable_id' => $digestOfSummaries->id,
                'searchable_type' => 'digest',
                'searchable_field' => 'description_full_ru',
                'searchable_field_weight' => SearchDataRepository::DIGEST_DESCRIPTION_FULL_RU_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($descriptionFullRu, $data);
        }
    }

    public function handleDigestOfSummariesUpdatedEvent(DigestOfSummaries $digestOfSummaries)
    {
        if (! $digestOfSummaries->is_published) {
            $this->handleDigestOfSummariesDeletedEvent($digestOfSummaries);
            return;
        }

        if ($this->getBySearchableIdOrUrl($digestOfSummaries->id, 'digest')->isEmpty()) {
            // SearchData has no data from this Digest before
            $updatedFields = $digestOfSummaries->only([
                'title_ru',
                'description_short_ru',
                'description_full_ru',
            ]);
        } else {
            $updatedFields = $digestOfSummaries->getDirty();
        }

        if (key_exists('title_ru', $updatedFields)) {
            $data = [
                'searchable_id' => $digestOfSummaries->id,
                'searchable_type' => 'digest',
                'searchable_field' => 'title_ru',
                'searchable_field_weight' => SearchDataRepository::DIGEST_TITLE_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($updatedFields['title_ru'], $data);
        }

        if (key_exists('description_short_ru', $updatedFields)) {
            if (empty($updatedFields['description_short_ru'])) {
                $this->handleDigestOfSummariesDeletedEvent($digestOfSummaries, 'description_short_ru');
            } else {
                $data = [
                    'searchable_id' => $digestOfSummaries->id,
                    'searchable_type' => 'digest',
                    'searchable_field' => 'description_short_ru',
                    'searchable_field_weight' => SearchDataRepository::DIGEST_DESCRIPTION_SHORT_RU_FIELD_WEIGHT,
                ];

                $this->searchDataRepository->updateOrCreate($updatedFields['description_short_ru'], $data);
            }
        }

        if (key_exists('description_full_ru', $updatedFields)) {
            if (empty($updatedFields['description_full_ru'])) {
                $this->handleDigestOfSummariesDeletedEvent($digestOfSummaries, 'description_full_ru');
            } else {
                $data = [
                    'searchable_id' => $digestOfSummaries->id,
                    'searchable_type' => 'digest',
                    'searchable_field' => 'description_full_ru',
                    'searchable_field_weight' => SearchDataRepository::DIGEST_DESCRIPTION_FULL_RU_FIELD_WEIGHT,
                ];

                $this->searchDataRepository->updateOrCreate($updatedFields['description_full_ru'], $data);
            }
        }
    }

    public function handleDigestOfSummariesDeletedEvent(
        DigestOfSummaries $digestOfSummaries,
        ?string $searchableField = null
    ) {
        $this->searchDataRepository->deleteBySearchableParams($digestOfSummaries->id, 'digest', $searchableField);
    }

    public function handleVacanciesCreatedEvent(Vacancies $vacancy)
    {
        $searchableType = 'site_page_vacancy';
        $searchableUrl = '/about/job/' . $vacancy->url_alias;
        $data = [
            'searchable_id' => $vacancy->id,
            'searchable_url' => $searchableUrl,
            'searchable_type' => $searchableType,
            'searchable_field' => 'title',
            'searchable_field_weight' => SearchDataRepository::SITE_PAGE_VACANCY_TITLE_FIELD_WEIGHT,
        ];

        $this->searchDataRepository->updateOrCreate($vacancy->title, $data);

        if (!empty($vacancy->description)) {
            $data = [
                'searchable_id' => $vacancy->id,
                'searchable_url' => $searchableUrl,
                'searchable_type' => $searchableType,
                'searchable_field' => 'description',
                'searchable_field_weight' => SearchDataRepository::SITE_PAGE_VACANCY_DESCRIPTION_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($vacancy->description, $data);
        }
    }

    public function handleVacanciesDeletedEvent(Vacancies $vacancy, ?string $searchableField = null)
    {
        $this->searchDataRepository->deleteBySearchableParams($vacancy->id, 'site_page_vacancy', $searchableField);
    }

    public function handleVacanciesUpdatedEvent(Vacancies $vacancy)
    {
        $searchableType = 'site_page_vacancy';
        $searchableUrl = '/about/job/' . $vacancy->url_alias;
        $updatedFields = $vacancy->getDirty();

        if (key_exists('title', $updatedFields)) {
            $data = [
                'searchable_id' => $vacancy->id,
                'searchable_url' => $searchableUrl,
                'searchable_type' => $searchableType,
                'searchable_field' => 'title',
                'searchable_field_weight' => SearchDataRepository::SITE_PAGE_VACANCY_TITLE_FIELD_WEIGHT,
            ];

            $this->searchDataRepository->updateOrCreate($updatedFields['title'], $data);
        }

        if (key_exists('description', $updatedFields)) {
            if (empty($updatedFields['description'])) {
                $this->handleVacanciesDeletedEvent($vacancy, 'description');
            } else {
                $data = [
                    'searchable_id' => $vacancy->id,
                    'searchable_url' => $searchableUrl,
                    'searchable_type' => $searchableType,
                    'searchable_field' => 'description',
                    'searchable_field_weight' => SearchDataRepository::SITE_PAGE_VACANCY_DESCRIPTION_FIELD_WEIGHT,
                ];

                $this->searchDataRepository->updateOrCreate($updatedFields['description'], $data);
            }
        }
    }
}
