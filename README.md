# Laravel Petstore Client

Lekki klient **Swagger Petstore API** dla Laravel — obsługuje listowanie, tworzenie, edycję, usuwanie oraz wysyłanie zdjęć zwierząt (`multipart/form-data`).  
Formularze obsługują **dowolne tagi** (Tagify) i **dowolne kategorie tekstowe**.

---

## Konfiguracja

W pliku `.env` ustaw:

````env
APP_URL=http://localhost/laravel-petstore-client
ASSET_URL=http://localhost/laravel-petstore-client/@core/public
PETSTORE_BASE_URL=https://petstore.swagger.io/v2
PETSTORE_API_KEY=demo-key

Następnie uruchom:
php artisan storage:link
php artisan config:clear
php artisan cache:clear



## Serwis: `App\Services\PetService`

Serwis odpowiada za pełną komunikację z **Swagger Petstore API**.
Umożliwia wykonywanie wszystkich operacji CRUD oraz wysyłanie zdjęć zwierząt.

---

### `listByStatus(string $status = 'available'): array`
Pobiera listę zwierząt według statusu (`available`, `pending`, `sold`).

---

### `getById(int $id): array`
Zwraca szczegóły pojedynczego zwierzaka.

---

### `create(array $payload): array`
Tworzy nowe zwierzę w API.
Payload powinien być zgodny ze schematem Petstore.

---

### `update(array $payload): array`
Aktualizuje pełne dane istniejącego zwierzęcia (`PUT /pet`).

---

### `updateWithForm(int $id, ?string $name = null, ?string $status = null): array`
Aktualizuje tylko pola `name` i `status` przez formularz (`POST /pet/{id}`).

---

### `delete(int $id): array`
Usuwa zwierzę po ID.
Wymaga nagłówka `api_key` w konfiguracji (`PETSTORE_API_KEY`).

---

### `uploadImage(int $id, string $filePath, ?string $additionalMetadata = null): array`
Wysyła zdjęcie zwierzęcia (`multipart/form-data`):

- **`filePath`** – lokalna ścieżka do pliku (np. `storage/app/public/pets/example.jpg`)
- **`additionalMetadata`** – opcjonalne metadane tekstowe

Zwraca:
```json
{
  "code": 200,
  "data": {
    "message": "File uploaded to ./filename.jpg"
  }
}
````
