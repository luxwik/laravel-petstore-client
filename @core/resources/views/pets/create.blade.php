<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Dodaj zwierzaka') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Tagify (vanilla) --}}
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="h3 mb-4">➕ {{ __('Dodaj zwierzaka') }}</h1>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('pets.store') }}">
            @csrf

            {{-- Nazwa --}}
            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Nazwa') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="{{ __('np. Azor, Filemon') }}">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Status --}}
            <div class="mb-3">
                <label for="status" class="form-label">{{ __('Status') }}</label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Kategoria (dowolna) --}}
            <div class="mb-3">
                <label for="category" class="form-label">{{ __('Kategoria (dowolna)') }}</label>
                <input type="text" name="category" id="category" value="{{ old('category') }}"
                    class="form-control @error('category') is-invalid @enderror"
                    placeholder="{{ __('np. Pies, Kot, Papuga') }}">
                @error('category')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Tagi (Tagify) --}}
            <div class="mb-3">
                <label for="tags" class="form-label">{{ __('Tagi (dowolne)') }}</label>
                <input type="text" name="tags" id="tags" value="{{ old('tags') }}"
                    class="form-control @error('tags') is-invalid @enderror"
                    placeholder="{{ __('Dodaj tag i naciśnij Enter') }}">
                @error('tags')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">
                    {{ __('Wpisuj tagi i zatwierdzaj Enterem (do backendu wyśle się lista rozdzielona przecinkami).') }}
                </div>
            </div>

            <button class="btn btn-success">
                <i class="bi bi-check-circle"></i> {{ __('Zapisz') }}
            </button>
            <a href="{{ route('pets.index') }}" class="btn btn-secondary">{{ __('Anuluj') }}</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Tagify --}}
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script>
        // Initialize Tagify on the #tags input
        const tagsInput = document.querySelector('#tags');

        const tagify = new Tagify(tagsInput, {
            enforceWhitelist: false,
            dropdown: {
                enabled: 0
            },
            delimiters: ",",
            originalInputValueFormat: valuesArr => valuesArr.map(v => v.value).join(',')
        });

        // If old('tags') has CSV, convert to Tagify items on load
        (function preloadCSV() {
            const csv = tagsInput.value?.trim();
            if (!csv) return;
            const arr = csv.split(',').map(s => s.trim()).filter(Boolean).map(v => ({
                value: v
            }));
            tagify.removeAllTags();
            tagify.addTags(arr);
        })();
    </script>

</body>

</html>
