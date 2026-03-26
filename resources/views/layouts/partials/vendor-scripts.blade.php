@yield('script-bottom')

@if (app()->environment('production'))
    @vite(['resources/js/app.js'])
@else
    @vite('resources/js/app.js')
@endif

<!-- Simple Session Validator -->
@auth
    <script src="{{ asset('js/utils/simple-session-validator.js') }}"></script>
@endauth

@yield('scripts')

<script>
  window.GlobalConfig = {
    apiHost: "{{config('app.api_url')}}"
  };
</script>