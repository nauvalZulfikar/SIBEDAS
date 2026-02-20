<!-- Google Font Family link -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Play:wght@400;700&display=swap" rel="stylesheet">

@yield('css')

@if (app()->environment('production'))
@vite([ 'resources/scss/icons.scss', 'resources/scss/style.scss'])
@vite([ 'resources/js/config.js'])
@else
    @vite([ 'resources/scss/icons.scss', 'resources/scss/style.scss'])
    @vite([ 'resources/js/config.js'])
@endif