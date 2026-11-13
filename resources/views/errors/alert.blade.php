<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} | Mercado Preço Bom</title>
    @include('partials.alerts', ['alertPayload' => [$code >= 500 ? 'error' : 'warning' => $description]])
    <style>
        body {margin:0;background:#edf1ee;color:#253c30;font:16px Arial,sans-serif;}
        main {max-width:540px;margin:12vh auto;padding:32px;background:white;border:1px solid #ccd6ce;border-radius:12px;}
        h1 {font-size:25px;} p {line-height:1.7;}
        a {display:inline-block;margin:12px 16px 0 0;color:#145b3d;font-weight:bold;}
        @media(max-width:600px){main {margin:24px;padding:24px;}}
    </style>
</head>
<body>
    <main>
        <span>Mercado Preço Bom · {{ $code }}</span>
        <h1>{{ $heading }}</h1>
        <p>{{ $description }}</p>
        <a href="{{ route('inicio') }}">Voltar ao início</a>
        <a href="{{ route('login') }}">Acessar conta</a>
    </main>
</body>
</html>