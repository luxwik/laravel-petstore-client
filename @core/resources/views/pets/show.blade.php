<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Szczegóły zwierzaka') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">📄 {{ __('Szczegóły zwierzaka') }}</h1>
            <a href="{{ route('pets.index') }}" class="btn btn-secondary">
                ← {{ __('Wróć do listy') }}
            </a>
        </div>

        @if ($pet)
            <div class="card shadow-sm">
                <div class="card-body">
                    <p><strong>{{ __('ID:') }}</strong> {{ $pet['id'] ?? '-' }}</p>
                    <p><strong>{{ __('Nazwa:') }}</strong> {{ $pet['name'] ?? '-' }}</p>

                    <p class="mb-1"><strong>{{ __('Status:') }}</strong>
                        <span
                            class="badge
                        @if (($pet['status_label'] ?? '') === 'Dostępny') bg-success
                        @elseif(($pet['status_label'] ?? '') === 'Oczekujący') bg-warning text-dark
                        @elseif(($pet['status_label'] ?? '') === 'Sprzedany') bg-secondary
                        @else bg-light text-dark @endif">
                            {{ __($pet['status_label'] ?? ($pet['status'] ?? '-')) }}
                        </span>
                    </p>

                    @if (!empty($category))
                        <p class="mt-2"><strong>{{ __('Kategoria:') }}</strong> {{ $category }}</p>
                    @endif

                    @if (!empty($tagNames))
                        <div class="mt-2">
                            <strong>{{ __('Tagi:') }}</strong>
                            <div class="mt-1">
                                @foreach ($tagNames as $t)
                                    <span class="badge bg-primary me-1">{{ $t }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (!empty($photoUrls))
                        <div class="mt-4">
                            <strong>{{ __('Zdjęcia:') }}</strong>
                            <div class="row g-3 mt-1">
                                @foreach ($photoUrls as $url)
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                        <a href="{{ $url }}" target="_blank" rel="noopener">
                                            <img src="{{ $url }}" alt="{{ __('Zdjęcie zwierzaka') }}"
                                                class="img-fluid rounded border">
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-3">
                <a href="{{ route('pets.edit', $pet['id']) }}" class="btn btn-warning me-1">
                    <i class="bi bi-pencil-square"></i> {{ __('Edytuj') }}
                </a>
                <form action="{{ route('pets.destroy', $pet['id']) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" onclick="return confirm(__('Czy na pewno chcesz usunąć?'))">
                        <i class="bi bi-trash"></i> {{ __('Usuń') }}
                    </button>
                </form>
            </div>

            <hr class="my-4">
            <h5>{{ __('Dodaj zdjęcie') }}</h5>
            <form method="POST" action="{{ route('pets.photo.store', $pet['id']) }}" enctype="multipart/form-data"
                class="mt-2">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="file" name="photo" class="form-control" accept="image/*" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="additionalMetadata" class="form-control"
                            placeholder="{{ __('opcjonalne metadane') }}">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-secondary"><i class="bi bi-upload"></i> {{ __('Prześlij') }}</button>
                    </div>
                </div>
            </form>
        @else
            <div class="alert alert-info">{{ __('Nie znaleziono zwierzaka.') }}</div>
        @endif
    </div>

</body>

</html>
