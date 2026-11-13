@php
    $notice = $alertPayload ?? [
        'success' => session('status') ?? session('success'),
        'warning' => session('warning'),
        'info' => session('info'),
        'error' => session('error'),
        'errors' => isset($errors) ? $errors->all() : [],
    ];
@endphp
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/alertas.css') }}">
<script id="mpb-alert-data" type="application/json">@json($notice)</script>
<script defer src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
<script defer src="{{ asset('js/alertas.js') }}"></script>