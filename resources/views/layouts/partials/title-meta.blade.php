<!-- Title Meta -->
<meta charset="utf-8" />
<title>{{ $subtitle}} | DPUTR</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="DPUTR Dashboard App" />
<meta name="author" content="DPUTR" />
<meta name="keywords" content="DPUTR, PUTR, dinas pekerjaan umum dan tata ruang, kabupaten bandung" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="robots" content="index, follow" />
<meta name="theme-color" content="#ffffff">

<!-- Authentication Tokens -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@auth
    <!-- API Token from session (primary method) -->
    <meta name="api-token" content="{{ session('api_token') }}">
    
    <!-- Alternative: User ID for token generation -->
    <meta name="user-id" content="{{ auth()->id() }}">
    
    <!-- Token expiration timestamp (if available) -->
    @if(session('api_token_expires'))
        <meta name="api-token-expires" content="{{ session('api_token_expires') }}">
    @endif
@endauth

<!-- App favicon -->
<link rel="shortcut icon" href="/images/dputr.ico">