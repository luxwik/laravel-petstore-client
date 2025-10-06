<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Edytuj zwierzaka') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    {{-- Tagify (vanilla, bez jQuery) --}}
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <h1 class="h3 mb-4">✏️ {{ __('Edytuj zwierzaka') }}</h1>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Główna forma aktualizacji (bez zdjęć) --}}
        <form method="POST" action="{{ route('pets.update', $pet['id']) }}" class="mb-4">
            @csrf
            @method('PUT')

            {{-- Nazwa --}}
            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Nazwa') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name', $pet['name'] ?? '') }}"
                    class="form-control @error('name') is-invalid @enderror">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Status --}}
            <div class="mb-3">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $pet['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Kategoria (dowolna) --}}
            <div class="mb-3">
                <label for="category" class="form-label">{{ __('Kategoria (dowolna)') }}</label>
                <input type="text" name="category" id="category" value="{{ old('category', $category ?? '') }}"
                    class="form-control @error('category') is-invalid @enderror"
                    placeholder="{{ __('np. Pies, Kot, Papuga') }}">
                @error('category')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Tagi (Tagify) --}}
            <div class="mb-3">
                <label for="tags" class="form-label">{{ __('Tagi (dowolne)') }}</label>
                <input type="text" name="tags" id="tags" value="{{ old('tags', $tagsCsv ?? '') }}"
                    class="form-control @error('tags') is-invalid @enderror"
                    placeholder="{{ __('Dodaj tag i naciśnij Enter') }}">
                @error('tags')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">{{ __('Do serwera wysyłany jest ciąg rozdzielony przecinkami.') }}</div>
            </div>

            <button class="btn btn-warning">
                <i class="bi bi-save"></i> {{ __('Zaktualizuj') }}
            </button>
            <a href="{{ route('pets.index') }}" class="btn btn-secondary">{{ __('Anuluj') }}</a>
            <a href="{{ route('pets.show', ['id' => $pet['id']]) }}"
                class="btn btn-outline-secondary">{{ __('Podgląd') }}</a>
        </form>

        {{-- Sekcja zdjęć: podgląd + osobny upload --}}
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-images me-2"></i> {{ __('Zdjęcia') }}
            </div>
            <div class="card-body">
                @php $photoUrls = $photoUrls ?? []; @endphp

                @if (!empty($photoUrls))
                    <div class="row g-3 mb-3">
                        @foreach ($photoUrls as $url)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <a href="{{ $url }}" target="_blank" rel="noopener">
                                    <img src="{{ $url }}" alt="{{ __('Zdjęcie zwierzaka') }}"
                                        class="img-fluid rounded border">
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">{{ __('Brak zdjęć.') }}</p>
                @endif

                {{-- Osobny formularz do uploadu (oddzielny endpoint) --}}
                <form method="POST" action="{{ route('pets.photo.store', ['pet' => $pet['id']]) }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Plik zdjęcia') }}</label>
                            <input type="file" name="photo"
                                class="form-control @error('photo') is-invalid @enderror" accept="image/*" required>
                            @error('photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Dodatkowe metadane (opcjonalnie)') }}</label>
                            <input type="text" name="additionalMetadata"
                                class="form-control @error('additionalMetadata') is-invalid @enderror"
                                placeholder="{{ __('np. cover') }}">
                            @error('additionalMetadata')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 d-grid">
                            <button class="btn btn-secondary"><i class="bi bi-upload"></i>
                                {{ __('Prześlij') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script>
        (function() {
            const input = document.querySelector('#tags');
            if (!input) return;
            const tagify = new Tagify(input, {
                enforceWhitelist: false,
                dropdown: {
                    enabled: 0
                },
                delimiters: ",",
                originalInputValueFormat: valuesArr => valuesArr.map(v => v.value).join(',')
            });

            const csv = input.value?.trim();
            if (csv) {
                const arr = csv.split(',').map(s => s.trim()).filter(Boolean).map(v => ({
                    value: v
                }));
                tagify.removeAllTags();
                tagify.addTags(arr);
            }
        })();
    </script>

</body>

</html>
