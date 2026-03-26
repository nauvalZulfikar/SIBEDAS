<!DOCTYPE html>
<html lang="en" @yield('html-attribute')>

<style>
    /* .floating-icon {
        position: fixed;
        right: 40px;
        bottom: 100px;
        width: 70px;  
        height: 70px; 
        background: white;
        border-radius: 50%;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        z-index: 1000;
        background-image: url('/images/iconchatbot.jpeg');
        background-size: cover;  
        background-position: center;
    } */


    .floating-icon {
        position: fixed;
        right: 20px;
        bottom: 20px; 
        width: 60px;  
        height: 60px;
        background: white;
        border-radius: 50%;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
        cursor: pointer;
        z-index: 1000;
        background-image: url('/images/iconchatbot.jpeg'); 
        background-size: contain; 
        background-repeat: no-repeat;
        background-position: center;
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .floating-icon:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    }

    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    .floating-icon.animate {
        animation: bounce 1s infinite;
    }
     .menu-arrow {
          transition: transform 0.3s ease;
     }

     .nav-link[aria-expanded="true"] .menu-arrow i {
          transform: rotate(180deg);
     }
</style>

<head>
    @include('layouts.partials/title-meta')

    @include('layouts.partials/head-css')
</head>

<body>

    <div class="app-wrapper">

        @include('layouts.partials/sidebar')

        @include('layouts.partials/topbar')

        <div class="page-content">
            <div class="container-fluid">
                @yield('content')

                @if (!Request::is('chatbot') && !Request::is('main-chatbot'))
                    <a href="{{ route('chatbot.index') }}" class="floating-icon">
                        
                    </a>
                @endif
            </div>

            @include('layouts.partials/footer')
        </div>

    </div>

    @include('layouts.partials/vendor-scripts')

</body>

</html>