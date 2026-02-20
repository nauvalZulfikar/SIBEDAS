<div class="app-sidebar">
     <!-- Sidebar Logo -->
     <div class="logo-box">
          <a href="{{ route('dashboard.home') }}" class="logo-dark">
               <img src="/images/dputr-kab-bandung.png" class="logo-sm" alt="logo sm">
               <img src="/images/dputr-kab-bandung.png" class="logo-lg" alt="logo dark">
          </a>

          <a href="{{ route('dashboard.home') }}" class="logo-light">
               <img src="/images/dputr-kab-bandung.png" class="logo-sm" alt="logo sm">
               <img src="/images/dputr-kab-bandung.png" class="logo-lg" alt="logo light">
          </a>
     </div>

     <div class="scrollbar" data-simplebar>
          <ul class="navbar-nav" id="navbar-nav">
          <li class="menu-title">Menu</li>

          @php
          // Menentukan apakah sebuah menu (atau anaknya) aktif berdasarkan request('menu_id')
          function isActiveMenu($menu, $currentId) {
               if (!$currentId) return false;
               if ((string)$menu->id === (string)$currentId) return true;
               foreach ($menu->children as $child) {
                    if (isActiveMenu($child, $currentId)) return true;
               }
               return false;
          }

          function renderMenu($menus) {
               $currentMenuId = request('menu_id');
               foreach ($menus as $menu) {
                    $collapseId = "sidebar-" . $menu->id;
                    $hasChildren = $menu->children->count() > 0;
                    $isActive = isActiveMenu($menu, $currentMenuId);

                    // Pastikan route tersedia dan boleh ditampilkan
                    $menuUrl = '#';
                    if ($menu->url) {
                         if (Route::has($menu->url)) {
                              $menuUrl = route($menu->url, ['menu_id' => $menu->id]);
                         } else {
                              $menuUrl = $menu->url . (strpos($menu->url, '?') !== false ? '&' : '?') . 'menu_id=' . $menu->id;
                         }
                    }

                    echo '<li class="nav-item ' . ($hasChildren ? 'has-children' : '') . ' ' . ($isActive ? 'active' : '') . '">';
                    echo '<a class="nav-link ' . ($hasChildren ? 'menu-arrow' : '') . ' ' . ($isActive ? 'active' : '') . '" 
                              href="' . ($hasChildren ? "#$collapseId" : $menuUrl) . '" 
                              ' . ($hasChildren ? 'data-bs-toggle="collapse" role="button" aria-expanded="' . ($isActive ? 'true' : 'false') . '" aria-controls="' . $collapseId . '"' : '') . '>';
                    
                    // Tampilkan ikon hanya jika tersedia
                    if (!empty($menu->icon)) {
                         echo '<span class="nav-icon">
                                   <iconify-icon icon="' . $menu->icon . '"></iconify-icon>
                              </span>';
                    }

                    echo '<span class="nav-text">' . $menu->name . '</span>';
                    echo '</a>';

                    if ($hasChildren) {
                         echo '<div class="collapse ' . ($isActive ? 'show' : '') . '" id="' . $collapseId . '">
                                   <ul class="nav sub-navbar-nav">';
                         renderMenu($menu->children);
                         echo '</ul></div>';
                    }

                    echo '</li>';
               }
          }
          @endphp

          @php renderMenu($menus); @endphp
          </ul>
     </div>
</div>

<!-- Efek Bintang -->
<div class="animated-stars">
     @for ($i = 0; $i < 20; $i++)
          <div class="shooting-star"></div>
     @endfor
     @for ($i = 0; $i < 20; $i++)
          <div class="shooting-star"></div>
     @endfor
</div>

<style>
     /* Sidebar hover/active contrast improvements */
     .app-sidebar .nav-link {
          transition: background-color .2s ease, color .2s ease;
          border-radius: 6px;
     }

     /* Hover state (dark green theme) */
     .app-sidebar .nav-link:hover {
          background-color: #eaf7f0; /* light green */
          color: #146c43; /* lighter dark green */
     }

     /* Active state for parents and leaf items (dark green) */
     .app-sidebar .nav-item.active > .nav-link,
     .app-sidebar .nav-link.active {
          background-color: #198754; /* bootstrap success */
          color: #ffffff;
          font-weight: 600;
     }

     /* Optional: subtle left border indicator on active */
     .app-sidebar .nav-item.active > .nav-link,
     .app-sidebar .sub-navbar-nav .nav-link.active {
          box-shadow: inset 4px 0 0 0 #146c43;
     }

     /* Submenu links */
     .app-sidebar .sub-navbar-nav .nav-link:hover {
          background-color: #f1fbf5;
          color: #146c43;
     }

     .app-sidebar .sub-navbar-nav .nav-link.active {
          background-color: #198754;
          color: #ffffff;
          font-weight: 600;
     }

     /* Keep icon color in sync */
     .app-sidebar .nav-link:hover .nav-icon iconify-icon,
     .app-sidebar .nav-item.active > .nav-link .nav-icon iconify-icon,
     .app-sidebar .nav-link.active .nav-icon iconify-icon,
     .app-sidebar .sub-navbar-nav .nav-link.active .nav-icon iconify-icon {
          color: currentColor;
     }
</style>