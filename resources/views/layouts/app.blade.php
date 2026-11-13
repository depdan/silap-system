<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'SILAP – Sistem Laporan PPSU')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
  <link rel="icon" type="image/png" href="{{ asset('foto/silap.png') }}">
  @yield('styles')
</head>
<body class="@yield('body-class', 'admin-page-body')">
  @yield('content')

  <script src="{{ asset('js/shared.js') }}?v={{ time() }}"></script>
  @yield('scripts')
</body>
</html>

