<?php

namespace Sr\Repositories\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Sr\Models\SearchData;

class SearchDataRepository
{
    const BLOG_TAG_TITLE_FIELD_WEIGHT = 10;
    const BLOG_POST_TITLE_FIELD_WEIGHT = 10;
    const BLOG_POST_SHORT_TITLE_FIELD_WEIGHT = 5;
    const BLOG_POST_INTRO_TEXT_FOR_FEED_FIELD_WEIGHT = 5;
    const BLOG_POST_CONTENT_FIELD_WEIGHT = 5;
    const SUMMARY_TITLE_RU_FIELD_WEIGHT = 10;
    const SUMMARY_TITLE_EN_FIELD_WEIGHT = 10;
    const SUMMARY_AUTHORS_FIELD_WEIGHT = 5;
    const SUMMARY_TAG_TITLE_FIELD_WEIGHT = 10;
    const DIGEST_TITLE_FIELD_WEIGHT = 10;
    const DIGEST_DESCRIPTION_SHORT_RU_FIELD_WEIGHT = 5;
    const DIGEST_DESCRIPTION_FULL_RU_FIELD_WEIGHT = 5;
    const SITE_PAGE_TITLE_FIELD_WEIGHT = 10;
    const SITE_PAGE_CONTENT_FIELD_WEIGHT = 5;
    const SITE_PAGE_KEYWORDS_FIELD_WEIGHT = 5;
    const SITE_PAGE_VACANCY_TITLE_FIELD_WEIGHT = 10;
    const SITE_PAGE_VACANCY_DESCRIPTION_FIELD_WEIGHT = 5;

    /**
     * @var string|array|null
     */
    public $searchableType = null;

    private SearchData $searchData;

    public function __construct()
    {
        $this->searchData = app(SearchData::class);
    }

    public function prepareSearchableValueData(string $value): string
    {
        $replacements = [
            // компонент <no-auth-message> - два вида как он может быть в тексте ((
            '&lt;-- Не отображать последующий текст --&gt;' => '',
            '&lt;&mdash; Не&nbsp;отображать последующий текст &mdash;&gt;' => '',
            '&nbsp;' => ' ',
            '&mdash;' => ' ',
            '&ndash;' => ' ',
            '&laquo;' => '',
            '&raquo;' => '',
            '&rdquo;' => '',
            '&ldquo;' => '',
            '&amp;' => '',
            "\r\n" => ' ',
            "\n" => ' ',
            "\r" => ' ',
            '  ' => ' ',
        ];

        return trim(
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                strip_tags(html_entity_decode($value))
            )
        );
    }

    public function updateOrCreate(string $searchableValue, array $searchableData)
    {
        $searchable_value = $this->prepareSearchableValueData($searchableValue);

        $this->searchData->updateOrCreate($searchableData, compact('searchable_value'));
    }

    public function update(array $searchableData): ?SearchData
    {
        $searchableData['searchable_value'] = $this->prepareSearchableValueData($searchableData['searchable_value']);

        $this->searchData->update($searchableData);

        return $this->searchData->fresh();
    }

    public function deleteBySearchableParams($searchableIdOrUrl = null, $searchableType = null, ?string $searchableField = null)
    {
        try {
            $this->searchData->query()
                ->when(
                    $searchableIdOrUrl && is_string($searchableIdOrUrl),
                    fn($q) => $q->where('searchable_url', $searchableIdOrUrl)
                )
                ->when(
                    $searchableIdOrUrl && is_numeric($searchableIdOrUrl),
                    fn($q) => $q->where('searchable_id', $searchableIdOrUrl)
                )
                ->when(
                    $searchableType && is_string($searchableType),
                    fn($q) => $q->where('searchable_type', $searchableType)
                )
                ->when(
                    $searchableType && is_array($searchableType),
                    fn($q) => $q->whereIn('searchable_type', $searchableType)
                )
                ->when(
                    $searchableField,
                    fn($q) => $q->where('searchable_field', $searchableField)
                )
                ->delete();
        } catch (\Exception $e) {
            Log::error(
                sprintf(
                    "Ошибка удаления записи таблицы поиска с searchable_id или searchable_url: %d и searchable_type: %s и searchable_field: %s. %s",
                    $searchableIdOrUrl ?? 'не задано',
                    $searchableType ?? 'не задано',
                    $searchableField ?? 'не задано',
                    $e->getMessage()
                ),
                $e->getTrace()
            );
        }
    }

    public function getPaginated(string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $searchTerm = '%'. $search .'%';

        return $this->searchData->newQuery()
            ->when($search, fn($q) =>
                $q->where('searchable_id', 'like', $searchTerm)
                    ->orWhere('searchable_url', 'like', $searchTerm)
                    ->orWhere('searchable_type', 'like', $searchTerm)
                    ->orWhere('searchable_field', 'like', $searchTerm)
            )
            ->paginate($perPage);
    }

    public function getById(int $searchDataId): ?SearchData
    {
        return $this->searchData = $this->searchData->find($searchDataId);
    }

    public function setSearchableType($searchableType = null): SearchDataRepository
    {
        $this->searchableType = $searchableType;

        return $this;
    }

    /**
     * @param int|string $searchableParam
     *
     * @return Collection
     */
    public function getBySearchableIdOrUrl($searchableParam): Collection
    {
        return $this->searchData
            ->filterBySearchableType($this->searchableType)
            ->where('searchable_id', $searchableParam)
            ->orWhere('searchable_url', $searchableParam)
            ->get();
    }
}
