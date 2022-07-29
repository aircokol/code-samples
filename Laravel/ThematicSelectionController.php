<?php

namespace App\Http\Controllers;

use App\Http\Requests\ThematicSelectionRequest;
use App\Managers\Statistic\Assistant;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Sr\Exceptions\ThematicSelections\DeleteThematicSelectionException;
use Sr\Managers\ThematicSelectionManager;
use Sr\Models\Summary\SummaryTransformer;
use Sr\Models\Summary\ThematicSelection;

class ThematicSelectionController extends Controller
{
    /**
     * @var mixed
     */
    private array $tabs;

    public function __construct()
    {
        $this->tabs = [
            [
                'name' => 'Основные данные',
                'value' => 'thematic_selection_data'
            ],
            [
                'name' => 'Аудио',
                'value' => 'audio'
            ],
            [
                'name' => 'Саммари',
                'value' => 'thematic_selection_summaries'
            ],
        ];
    }

    public function index(): View
    {
        $search = request('search');

        $query = ThematicSelection::query()
            ->when($search, function($query) use ($search) {
                $query->where('id', (int)$search)
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->orderBy('sort_order');

        $thematicSelections = $query->paginate(25);
        $total_count = $thematicSelections->total();

        return view('templates.thematic_selections.index', compact('thematicSelections', 'total_count', 'search'));
    }

    public function create(): View
    {
        $thematicSelection = new ThematicSelection([
            'is_publish' => 0,
        ]);
        $thematicSelection->load('summaries');
        $thematicSelection->sort_order = 1 + (int)data_get(ThematicSelection::latest('sort_order')->first(), 'sort_order', 0);
        $thematicSelection = $this->fillFromOldValues($thematicSelection);
        $page_title = 'Добавить';
        $tabs = $this->tabs;
        $route_save = route('thematic_selections.store');
        $audioFileExists = false;
        $audioDeleteRoute = null;
        $summaries = [];

        return view('templates.thematic_selections.edit', compact(
            'page_title', 'tabs', 'route_save', 'thematicSelection', 'audioFileExists', 'audioDeleteRoute', 'summaries'
        ));
    }

    public function store(ThematicSelectionRequest $request, ThematicSelectionManager $thematicSelectionManager)
    {
        $requestData = $request->except('image', 'audio_name', 'summary_compilations');
        $requestData['is_publish'] = $requestData['is_publish'] ?? 0;

        DB::beginTransaction();
        try {
            $thematicSelection = new ThematicSelection();

            $thematicSelection->fill($requestData);
            $thematicSelection->save();

            $thematicSelectionManager->setThematicSelection($thematicSelection);

            if ($request->hasFile('image')) {
                $thematicSelection->image = $thematicSelectionManager->uploadImage(
                    $request->file('image'),
                    $thematicSelection->id
                );
            }

            if ($request->hasFile('audio_name')) {
                $thematicSelection->audio_name = $thematicSelectionManager->uploadAudio(
                    $request->file('audio_name'),
                    $thematicSelection->id
                );
            }

            $thematicSelection->save();

            if ($request->get('summary_compilations') !== null) {
                $thematicSelectionManager->saveSummaries( $request->get('summary_compilations') );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Ошибка создания Тематической подборки: " . $e->getMessage(), $e->getTrace());
            return redirect()->back()->withInput()->withErrorStatus('Ошибка создания Тематической подборки');
        }

        return redirect()->route('thematic_selections.edit', ['thematic_selection' => $thematicSelection->id]);
    }

    public function edit(int $thematicSelectionId, ThematicSelectionManager $thematicSelectionManager): View
    {
        $thematicSelection = ThematicSelection::with(['summaries' => fn($q) => $q->orderBy('pivot_sort')])
            ->findOrFail($thematicSelectionId);
        $thematicSelection = $this->fillFromOldValues($thematicSelection);
        $page_title = 'Редактировать';
        $tabs = $this->tabs;
        $route_save = route('thematic_selections.update', ['thematic_selection' => $thematicSelection->id]);
        $audioFileExists = $thematicSelectionManager->setThematicSelection($thematicSelection)->isAudioFileExists();
        $audioDeleteRoute = route('thematic_selections.delete-audio', ['thematic_selection' => $thematicSelection->id]);
        $summaries = (new SummaryTransformer)->transformInMinimalSet($thematicSelection->summaries);

        return view('templates.thematic_selections.edit', compact(
            'page_title', 'tabs', 'route_save', 'thematicSelection', 'audioFileExists', 'audioDeleteRoute', 'summaries'
        ));
    }

    public function update(int $thematicSelectionId, ThematicSelectionRequest $request, ThematicSelectionManager $thematicSelectionManager)
    {
        $thematicSelection = $thematicSelectionManager->setThematicSelectionById($thematicSelectionId);

        DB::beginTransaction();

        try {
            $thematicSelection->fill($request->except('image', 'audio_name', 'summary_compilations'));
            $thematicSelection->save();

            if ($request->hasFile('image')) {
                $thematicSelection->image = $thematicSelectionManager->uploadImage(
                    $request->file('image'),
                    $thematicSelection->id
                );
            }

            if ($request->hasFile('audio_name')) {
                $thematicSelection->audio_name = $thematicSelectionManager->uploadAudio(
                    $request->file('audio_name'),
                    $thematicSelection->id
                );
            }

            $thematicSelection->save();

            if ($request->get('summary_compilations') !== null) {
                $thematicSelectionManager->saveSummaries( $request->get('summary_compilations') );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка обновления Тематической подборки: '.$e->getMessage(), $e->getTrace());

            return redirect()->back()->withInput()->withErrorStatus('Ошибка обновления Тематической подборки');
        }

        return redirect()->route('thematic_selections.edit', ['thematic_selection' => $thematicSelection->id]);
    }

    public function destroy(int $thematicSelectionId, ThematicSelectionManager $thematicSelectionManager): RedirectResponse
    {
        try {
            $thematicSelectionManager->setThematicSelectionById($thematicSelectionId);
            $thematicSelectionManager->delete();

        } catch (DeleteThematicSelectionException $e) {
            return redirect()->back()->withErrorStatus($e->getMessage());
        }

        return redirect()->route('thematic_selections.index');
    }

    public function deleteAudio(int $thematicSelectionId, ThematicSelectionManager $thematicSelectionManager): JsonResponse
    {
        try {
            $thematicSelectionManager->setThematicSelectionById($thematicSelectionId);
            $thematicSelectionManager->deleteAudio();

        } catch (Exception $e) {
            return response()->json('error', 500);
        }

        return response()->json('success');
    }

    public function search(Request $request): array
    {
        $search = $request->get('search');

        if (! $search) {
            return [];
        }

        $challenges = ThematicSelection::where('title', 'like', '%'.$search.'%')
            ->orWhere('id',  (int)$search)
            ->orderBy('title')
            ->get(['id', 'title']);

        return $challenges->toArray();
    }

    public function updateSortOrder()
    {
        (new Assistant)->updateSortingOrder(ThematicSelection::class);
        return;
    }
}
