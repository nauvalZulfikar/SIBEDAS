<header class="app-topbar">
     <div class="container-fluid">
          <div class="navbar-header">
               <div class="d-flex align-items-center gap-2">
                    <!-- Menu Toggle Button -->
                    <div class="topbar-item">
                         <button type="button" class="button-toggle-menu topbar-button">
                              <iconify-icon icon="solar:hamburger-menu-outline"
                                   class="fs-24 align-middle"></iconify-icon>
                         </button>
                    </div>
               </div>

               <div class="d-flex align-items-center gap-2">

                    <div class="topbar-item">
                         <a href="{{ route('chatbot.index') }}" class="topbar-button">
                             <iconify-icon icon="solar:chat-square-outline" class="fs-22 align-middle"></iconify-icon>
                         </a>
                     </div>

                    <!-- User -->
                    <div class="dropdown topbar-item">
                         <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                              aria-haspopup="true" aria-expanded="false">
                              <span class="d-flex align-items-center">
                                   <img class="rounded-circle" width="32" src="/images/users/avatar-1.jpg"
                                        alt="avatar-3">
                              </span>
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <!-- item-->
                              <h6 class="dropdown-header">{{ Auth::user()->email }}</h6>

                              <div class="dropdown-divider my-1"></div>

                              <form id="logout-form" action="{{route('logout')}}" method="POST" style="display: none;">
                                   @csrf
                              </form>
                              <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); logoutUser();">
                                   <iconify-icon icon="solar:logout-3-outline"
                                        class="align-middle me-2 fs-18"></iconify-icon><span
                                        class="align-middle">Logout</span>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</header>
<style>
     /* Tampilkan hover submenu HANYA saat sidebar collapsed (berbagai kemungkinan class) */
     body.sidebar-collapsed .app-sidebar .navbar-nav > li.nav-item.has-children:hover > .collapse,
     .app-sidebar.collapsed .navbar-nav > li.nav-item.has-children:hover > .collapse,
     .app-sidebar.mini .navbar-nav > li.nav-item.has-children:hover > .collapse {
          display: block !important;
          position: absolute;
          top: 0;
          left: calc(100% + 8px);
          background: #ffffff;
          border-radius: 8px;
          box-shadow: 0 8px 20px rgba(0,0,0,0.08);
          padding: 6px 6px;
          min-width: 260px;
          width: clamp(260px, 40vw, 380px); /* responsive, bounded width */
          box-sizing: border-box;
          overflow: hidden; /* clip inner overflow to maintain box */
          z-index: 3;
     }

     .app-sidebar .sub-navbar-nav {
          width: 100%;
          max-width: 100%; /* pastikan nggak lebih besar dari box */
     }

     .app-sidebar .sub-navbar-nav .nav-link,
     .app-sidebar .sub-navbar-nav .nav-link .nav-text {
     display: block !important;
     width: 100% !important;
     max-width: 100% !important;
     white-space: normal !important;
     word-break: break-word !important;
     overflow-wrap: break-word !important;
     }

     .app-sidebar .sub-navbar-nav .nav-link:hover {
          background: #f1fbf5;
          color: #146c43;
     }

     .app-sidebar .sub-navbar-nav .nav-link .nav-text {
          display: inline !important;
          visibility: visible !important;
          opacity: 1 !important;
     }

     /* Do not force-hide parent nav-text in any state */
</style>
<script>
     function logoutUser() {
         // Hapus token dari localStorage
         localStorage.removeItem('token');

         // Submit form logout Laravel
         document.getElementById('logout-form').submit();
     }
</script>