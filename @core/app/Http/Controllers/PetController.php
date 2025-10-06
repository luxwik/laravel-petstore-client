<?php

namespace App\Http\Controllers;

use App\Enums\PetStatus;
use App\Services\PetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PetController extends Controller
{
    protected PetService $pets;

    public function __construct(PetService $pets)
    {
        $this->pets = $pets;
    }

    /**
     * List pets by status (defaults to 'available').
     */
    public function index(Request $request): View
    {
        $status = $request->input('status', PetStatus::AVAILABLE->value);

        $sort = $request->query('sort', 'id');
        $dir  = $request->query('dir',  'asc');

        $allowedSorts = ['id', 'name', 'status'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $dir = $dir === 'desc' ? 'desc' : 'asc';

        $result = $this->pets->listByStatus($status);

        $currentStatusLabel = PetStatus::from($status)->label();

        $pets = array_map(function ($pet) {
            $raw = $pet['status'] ?? null;
            $pet['status_label'] =
                $raw && in_array($raw, PetStatus::values(), true)
                ? PetStatus::from($raw)->label()
                : ($raw ?? '-');
            return $pet;
        }, $result['data'] ?? []);

        usort($pets, function ($a, $b) use ($sort, $dir) {
            switch ($sort) {
                case 'name':
                    $cmp = strnatcasecmp($a['name'] ?? '', $b['name'] ?? '');
                    break;
                case 'status':
                    $cmp = strnatcasecmp($a['status_label'] ?? '', $b['status_label'] ?? '');
                    break;
                case 'id':
                default:
                    $cmp = ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
                    break;
            }
            return $dir === 'desc' ? -$cmp : $cmp;
        });

        return view('pets.index', [
            'pets'        => $pets,
            'status'      => $status,
            'statusLabel' => $currentStatusLabel,
            'statuses'    => PetStatus::options(),
            'error'       => $result['error'] ?? null,
            'sort'        => $sort,
            'dir'         => $dir,
        ]);
    }

    /**
     * Show the form for creating a new pet.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view('pets.create', [
            'statuses' => PetStatus::options(),
        ]);
    }

    /**
     * Store a newly created pet in the Petstore API.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'status'   => ['required', Rule::in(PetStatus::values())],
            'category' => ['nullable', 'string', 'max:255'],
            'tags'     => ['nullable', 'string'],
        ]);

        $tags = collect(explode(',', (string) $data['tags']))
            ->map(fn($tag) => trim($tag))
            ->filter()
            ->map(fn($tag) => ['name' => $tag])
            ->values()
            ->all();

        $payload = array_filter([
            'name'     => $data['name'],
            'status'   => $data['status'],
            'category' => !empty($data['category']) ? ['name' => $data['category']] : null,
            'tags'     => $tags,
        ], fn($v) => !is_null($v));

        $result = $this->pets->create($payload);

        if (isset($result['error'])) {
            return back()->withInput()->with('error', $result['error']);
        }

        $petId = $result['id'] ?? null;

        if (!$petId) {
            return redirect()
                ->route('pets.index')
                ->with('success', __('Zwierzak został dodany, ale nie otrzymano ID. Dodaj zdjęcie z poziomu listy.'));
        }

        return redirect()
            ->route('pets.show', ['pet' => $petId])
            ->with('success', __('Zwierzak został dodany. Teraz możesz przesłać zdjęcie.'));
    }

    /**
     * Upload pet photo using Petstore /pet/{petId}/uploadImage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $petId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function uploadPhoto(Request $request, int $petId): RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'max:5120'],
            'additionalMetadata' => ['nullable', 'string', 'max:255'],
        ]);

        // Zapis na dysku public
        $stored = $data['photo']->store('pets', 'public'); // "pets/xxx.jpg"
        $absolutePath = Storage::disk('public')->path($stored);

        // Publiczny URL tylko do UI (Twoja ścieżka bazowa z @core/public)
        $publicUrl = rtrim(config('app.url'), '/') . '/@core/public/storage/' . ltrim($stored, '/');

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return back()
                ->withInput()
                ->with('error', __('Nie można odczytać zapisanego pliku obrazu.'))
                ->with('error_details', $absolutePath);
        }

        // Wyślij lokalny plik do API (multipart)
        $res = $this->pets->uploadImage(
            $petId,
            $absolutePath,
            $data['additionalMetadata'] ?? null
        );

        // Obsługa błędów z serwisu
        if (!is_array($res) || isset($res['error'])) {
            return back()
                ->withInput()
                ->with('error', $res['error'] ?? __('Nie udało się wysłać obrazu zwierzęcia.'))
                ->with('error_details', $res['details'] ?? null);
        }

        // Opcjonalne wyświetlenie komunikatu z API
        $apiMessage = data_get($res, 'data.message');

        return back()
            ->with('success', __('Zdjęcie zostało przesłane.'))
            ->with('uploaded_photo_url', $publicUrl)
            ->with('api_message', $apiMessage);
    }




    /**
     * Display the specified pet by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function show(int $id): RedirectResponse|View
    {
        $result = $this->pets->getById($id);

        if (isset($result['error'])) {
            return redirect()->route('pets.index')->with('error', $result['error']);
        }

        $pet = $result['data'] ?? $result ?? null;

        if (!$pet) {
            return redirect()->route('pets.index')->with('error', __('Nie znaleziono zwierzaka.'));
        }

        if (isset($pet['status']) && in_array($pet['status'], PetStatus::values(), true)) {
            $pet['status_label'] = PetStatus::from($pet['status'])->label();
        }

        $categoryName = null;
        if (isset($pet['category'])) {
            if (is_array($pet['category'])) {
                $categoryName = $pet['category']['name'] ?? null;
            } elseif (is_string($pet['category'])) {
                $categoryName = $pet['category'];
            }
        }

        $tagNames = [];
        if (!empty($pet['tags']) && is_array($pet['tags'])) {
            $tagNames = collect($pet['tags'])
                ->map(function ($t) {
                    if (is_array($t)) {
                        return $t['name'] ?? null;
                    }
                    if (is_string($t)) {
                        return $t;
                    }
                    return null;
                })
                ->filter()
                ->values()
                ->all();
        }

        $photoUrls = [];
        if (!empty($pet['photoUrls']) && is_array($pet['photoUrls'])) {
            $photoUrls = array_values(array_filter($pet['photoUrls'], fn($u) => is_string($u) && $u !== ''));
        }

        return view('pets.show', [
            'pet'         => $pet,
            'category'    => $categoryName,
            'tagNames'    => $tagNames,
            'photoUrls'   => $photoUrls,
        ]);
    }

    /**
     * Show the form for editing the specified pet.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function edit(int $id): RedirectResponse|View
    {
        $result = $this->pets->getById($id);

        if (isset($result['error'])) {
            return redirect()->route('pets.index')->with('error', $result['error']);
        }

        $pet = $result['data'] ?? $result ?? null;
        if (!$pet) {
            return redirect()->route('pets.index')->with('error', __('Nie znaleziono zwierzaka.'));
        }

        $category = null;
        if (isset($pet['category'])) {
            $category = is_array($pet['category'])
                ? ($pet['category']['name'] ?? null)
                : (string) $pet['category'];
        }

        $tagsCsv = collect($pet['tags'] ?? [])
            ->map(function ($t) {
                if (is_array($t)) {
                    return $t['name'] ?? null;
                }
                return is_string($t) ? $t : null;
            })
            ->filter()
            ->implode(', ');

        $photoUrls = array_values(array_filter(
            (array) ($pet['photoUrls'] ?? []),
            fn($u) => is_string($u) && $u !== ''
        ));

        return view('pets.edit', [
            'pet'       => $pet,
            'statuses'  => PetStatus::options(),
            'category'  => $category,
            'tagsCsv'   => $tagsCsv,
            'photoUrls' => $photoUrls,
        ]);
    }

    /**
     * Update the specified pet in the Petstore API.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'status'   => ['required', Rule::in(PetStatus::values())],
            'category' => ['nullable', 'string', 'max:255'],
            'tags'     => ['nullable', 'string'],
        ]);

        $payload = [
            'id'     => $id,
            'name'   => $data['name'],
            'status' => $data['status'],
        ];

        if (!empty($data['category'])) {
            $payload['category'] = ['name' => $data['category']];
        }

        if (!empty($data['tags'])) {
            $tags = collect(explode(',', $data['tags']))
                ->map(fn($t) => trim($t))
                ->filter()
                ->map(fn($t) => ['name' => $t])
                ->values()
                ->all();

            $payload['tags'] = $tags;
        }

        $result = $this->pets->update($payload);

        if (isset($result['error'])) {
            return back()->withInput()->with('error', $result['error']);
        }

        return redirect()->route('pets.show', ['id' => $id])->with('success', __('Zwierzak został zaktualizowany.'));
    }

    /**
     * Remove the specified pet from the Petstore API.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $result = $this->pets->delete($id);

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return redirect()->route('pets.index')->with('success', __('Zwierzak został usunięty.'));
    }
}
