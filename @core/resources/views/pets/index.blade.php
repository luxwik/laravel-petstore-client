<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Lista zwierząt') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">

        <!-- Page header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">🐾 {{ __('Lista zwierząt') }}</h1>
            <a href="{{ route('pets.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> {{ __('Dodaj zwierzaka') }}
            </a>
        </div>

        <!-- Status filter -->
        <form method="GET" action="{{ route('pets.index') }}" class="mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label for="status" class="form-label">{{ __('Filtruj po statusie') }}</label>
                    <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <!-- Messages -->
        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        @if (!empty($error))
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $error }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Pets table -->
        <div class="card shadow-sm">
            <div class="card-header fw-bold">
                {{ __('Zwierzęta') }} ({{ $statusLabel }})
            </div>

            <div class="card-body p-0">
                @if (empty($pets))
                    <p class="p-3 mb-0 text-muted">{{ __('Brak zwierząt do wyświetlenia.') }}</p>
                @else
                    @php
                        $sort = $sort ?? null;
                        $dir = $dir ?? 'asc';
                        $nextDir = fn($col) => $sort === $col && $dir === 'asc' ? 'desc' : 'asc';
                        $arrow = function ($col) use ($sort, $dir) {
                            if ($sort !== $col) {
                                return '';
                            }
                            return $dir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill';
                        };
                    @endphp

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:10%; text-align:center;">
                                        <a href="{{ route('pets.index', ['status' => $status, 'sort' => 'id', 'dir' => $nextDir('id')]) }}"
                                            class="text-decoration-none text-dark">
                                            {{ __('ID') }} <i class="bi {{ $arrow('id') }}"></i>
                                        </a>
                                    </th>
                                    <th style="width:45%;">
                                        <a href="{{ route('pets.index', ['status' => $status, 'sort' => 'name', 'dir' => $nextDir('name')]) }}"
                                            class="text-decoration-none text-dark">
                                            {{ __('Nazwa') }} <i class="bi {{ $arrow('name') }}"></i>
                                        </a>
                                    </th>
                                    <th style="width:15%;">
                                        <a href="{{ route('pets.index', ['status' => $status, 'sort' => 'status', 'dir' => $nextDir('status')]) }}"
                                            class="text-decoration-none text-dark">
                                            {{ __('Status') }} <i class="bi {{ $arrow('status') }}"></i>
                                        </a>
                                    </th>
                                    <th style="width:30%;">{{ __('Akcje') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pets as $pet)
                                    <tr>
                                        <td>{{ $pet['id'] ?? '-' }}</td>
                                        <td class="fw-medium">
                                            {{ \Illuminate\Support\Str::limit($pet['name'] ?? '-', 50) }}
                                        </td>
                                        <td>
                                            @php $label = $pet['status_label'] ?? '-' @endphp
                                            <span
                                                class="badge
                                        @if ($label === 'Dostępny') bg-success
                                        @elseif($label === 'Oczekujący') bg-warning text-dark
                                        @elseif($label === 'Sprzedany') bg-secondary
                                        @else bg-light text-dark @endif">
                                                {{ __($label) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('pets.show', $pet['id']) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> {{ __('Podgląd') }}
                                            </a>
                                            <a href="{{ route('pets.edit', $pet['id']) }}"
                                                class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil-square"></i> {{ __('Edytuj') }}
                                            </a>
                                            <form action="{{ route('pets.destroy', $pet['id']) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="return confirm(__('Czy na pewno chcesz usunąć?'))">
                                                    <i class="bi bi-trash"></i> {{ __('Usuń') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</body>

</html>
