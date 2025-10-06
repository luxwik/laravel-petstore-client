<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class PetService
{
    protected ?string $apiKey;
    protected ?string $baseUrl;
    protected array $errors = [];

    public function __construct()
    {
        $this->apiKey  = config('services.petstore.api_key');
        $this->baseUrl = config('services.petstore.base_url');

        if (empty($this->baseUrl)) {
            $this->errors[] = 'Brakuje adresu URL Petstore. Ustaw PETSTORE_BASE_URL w pliku .env.';
        }

        // Uwaga: demo Petstore akceptuje dowolny string i większość endpointów nie wymaga api_key.
        // Używamy api_key głównie dla DELETE, ale wciąż ostrzegamy aby konfiguracja była kompletna.
        if (empty($this->apiKey)) {
            $this->errors[] = 'Brakuje klucza API Petstore. Ustaw PETSTORE_API_KEY w pliku .env.';
        }
    }

    /**
     * Buduje klienta HTTP dla zapytań do Petstore API.
     *
     * Przekaż $withAuth=true dla endpointów wymagających nagłówka api_key (np. DELETE).
     *
     * @return PendingRequest|null Zwraca klienta HTTP lub null, jeśli konfiguracja jest niepoprawna.
     */
    protected function client(bool $withAuth = false): ?PendingRequest
    {
        if (!empty($this->errors)) {
            return null;
        }

        $client = Http::baseUrl($this->baseUrl)->acceptJson();

        if ($withAuth && !empty($this->apiKey)) {
            $client = $client->withHeaders(['api_key' => $this->apiKey]);
        }

        return $client;
    }

    /**
     * Zwraca jednolitą odpowiedź błędu konfiguracji.
     */
    protected function configError(): array
    {
        return ['error' => implode(' ', $this->errors)];
    }

    /**
     * Buduje spójny payload błędu dla nieudanych odpowiedzi HTTP.
     */
    protected function failure(string $message, ?Response $response = null): array
    {
        $payload = ['error' => $message];

        if ($response) {
            $payload['status']  = $response->status();
            // Treść odpowiedzi może być JSON albo zwykłym tekstem; dodajemy obie opcje.
            $payload['details'] = $response->json() ?? $response->body();
        }

        return $payload;
    }

    /**
     * GET /pet/findByStatus
     * Pobiera listę zwierząt wg statusu (available|pending|sold).
     */
    public function listByStatus(string $status = 'available'): array
    {
        if (!empty($this->errors)) {
            return $this->configError();
        }

        try {
            $res = $this->client()->get('/pet/findByStatus', ['status' => $status]);
        } catch (Throwable $e) {
            return ['error' => 'Błąd sieci podczas pobierania zwierząt wg statusu.', 'details' => $e->getMessage()];
        }

        if ($res->failed()) {
            return $this->failure('Nie udało się pobrać listy zwierząt.', $res);
        }

        return ['data' => $res->json()];
    }

    /**
     * GET /pet/{petId}
     * Pobiera pojedyncze zwierzę po ID.
     */
    public function getById(int $id): array
    {
        if (!empty($this->errors)) {
            return $this->configError();
        }

        try {
            $res = $this->client()->get("/pet/{$id}");
        } catch (Throwable $e) {
            return ['error' => 'Błąd sieci podczas pobierania zwierzęcia.', 'details' => $e->getMessage()];
        }

        if ($res->failed()) {
            return $this->failure('Nie udało się pobrać zwierzęcia.', $res);
        }

        return ['data' => $res->json()];
    }

    /**
     * POST /pet
     * Tworzy nowe zwierzę (payload musi pasować do schematu Petstore).
     */
    public function create(array $payload): array
    {
        if (!empty($this->errors)) {
            return $this->configError();
        }

        if (empty($payload['name'])) {
            return ['error' => 'Błąd walidacji: "name" jest wymagane.'];
        }

        try {
            $res = $this->client()->post('/pet', $payload);
        } catch (Throwable $e) {
            return ['error' => 'Błąd sieci podczas tworzenia zwierzęcia.', 'details' => $e->getMessage()];
        }

        if ($res->failed()) {
            return $this->failure('Nie udało się utworzyć zwierzęcia.', $res);
        }

        return ['data' => $res->json()];
    }

    /**
     * PUT /pet
     * Aktualizuje istniejące zwierzę (pełny obiekt).
     */
    public function update(array $payload): array
    {
        if (!empty($this->errors)) {
            return $this->configError();
        }

        if (empty($payload['id'])) {
            return ['error' => 'Błąd walidacji: "id" jest wymagane do aktualizacji.'];
        }

        try {
            $res = $this->client()->put('/pet', $payload);
        } catch (Throwable $e) {
            return ['error' => 'Błąd sieci podczas aktualizacji zwierzęcia.', 'details' => $e->getMessage()];
        }

        if ($res->failed()) {
            return $this->failure('Nie udało się zaktualizować zwierzęcia.', $res);
        }

        return ['data' => $res->json()];
    }

    /**
     * POST /pet/{petId}
     * Aktualizuje zwierzę przez dane formularza (tylko name/status).
     */
    public function updateWithForm(int $id, ?string $name = null, ?string $status = null): array
    {
        if (!empty($this->errors)) {
            return $this->configError();
        }

        $form = array_filter([
            'name'   => $name,
            'status' => $status,
        ], fn($v) => !is_null($v) && $v !== '');

        if (empty($form)) {
            return ['error' => 'Brak danych do aktualizacji. Podaj co najmniej "name" lub "status".'];
        }

        try {
            $res = $this->client()->asForm()->post("/pet/{$id}", $form);
        } catch (Throwable $e) {
            return ['error' => 'Błąd sieci podczas aktualizacji zwierzęcia formularzem.', 'details' => $e->getMessage()];
        }

        if ($res->failed()) {
            return $this->failure('Nie udało się zaktualizować zwierzęcia formularzem.', $res);
        }

        return ['data' => $res->json()];
    }

    /**
     * DELETE /pet/{petId}
     * Usuwa zwierzę po ID (wymaga nagłówka api_key).
     */
    public function delete(int $id): array
    {
        if (!empty($this->errors)) {
            $msg = implode(' ', $this->errors);
            return ['error' => $msg . ' Usunięcie zwierzęcia wymaga nagłówka api_key.'];
        }

        try {
            $res = $this->client(true)->delete("/pet/{$id}");
        } catch (Throwable $e) {
            return ['error' => 'Błąd sieci podczas usuwania zwierzęcia.', 'details' => $e->getMessage()];
        }

        if ($res->failed()) {
            return $this->failure('Nie udało się usunąć zwierzęcia.', $res);
        }

        return ['ok' => true];
    }

    /**
     * POST /pet/{petId}/uploadImage
     * Wysyła zdjęcie dla zwierzęcia (multipart/form-data).
     */
    public function uploadImage(int $id, string $filePath, ?string $additionalMetadata = null): array
    {
        if (!empty($this->errors)) {
            return $this->configError();
        }

        // Walidacja ścieżki
        if (!is_file($filePath) || !is_readable($filePath)) {
            return ['error' => __('Plik obrazu jest nieczytelny. Sprawdź ścieżkę i uprawnienia.'), 'details' => $filePath];
        }

        // Wczytaj zawartość (bez fopen i bez wycieków zasobów)
        $contents = @file_get_contents($filePath);
        if ($contents === false) {
            return ['error' => __('Nie udało się odczytać pliku obrazu.'), 'details' => $filePath];
        }

        try {
            $client = $this->client()->asMultipart();

            $res = $client
                ->attach('file', $contents, basename($filePath)) // ← zawartość binarna
                ->post("/pet/{$id}/uploadImage", array_filter([
                    'additionalMetadata' => $additionalMetadata,
                ]));
        } catch (Throwable $e) {
            return ['error' => __('Błąd sieci podczas wysyłania obrazu.'), 'details' => $e->getMessage()];
        }

        if ($res->failed()) {
            // Zwróć więcej kontekstu z odpowiedzi
            return $this->failure(__('Nie udało się wysłać obrazu zwierzęcia.'), $res);
        }

        // Opcjonalnie dołóż kod statusu
        return [
            'code' => $res->status(),
            'data' => $res->json(),
        ];
    }


    /**
     * Helper: transformuje dane formularza do payloadu zgodnego z Petstore.
     */
    public function fromForm(array $data, ?int $id = null): array
    {
        $photoUrls = array_values(array_filter(array_map('trim', explode(',', (string)($data['photoUrls'] ?? '')))));

        $tags = array_values(array_filter(array_map('trim', explode(',', (string)($data['tags'] ?? '')))));
        $tagObjects = array_map(
            fn($name, $i) => ['id' => $i + 1, 'name' => $name],
            $tags,
            array_keys($tags)
        );

        $payload = [
            'id'        => $id ?? ($data['id'] ?? null),
            'name'      => $data['name'] ?? '',
            'status'    => $data['status'] ?? 'available',
            'photoUrls' => $photoUrls ?: ['https://example.com/no-image.png'],
            'category'  => [
                'id'   => 1,
                'name' => $data['category_name'] ?? 'ogólna',
            ],
            'tags'      => $tagObjects,
        ];

        // Usuwamy null, żeby nie wysyłać pustych pól
        return array_filter($payload, fn($v) => !is_null($v));
    }
}
