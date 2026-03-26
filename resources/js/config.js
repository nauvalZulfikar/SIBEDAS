!(function () {
    var t = sessionStorage.getItem("__DARKONE_CONFIG__"),
        e = document.getElementsByTagName("html")[0],
        o = {
            theme: "light",
            topbar: { color: "light" },
            menu: { size: "default", color: "light" },
        };
        var html = document.getElementsByTagName("html")[0];
        var config = Object.assign(JSON.parse(JSON.stringify(o)), {});

    (html),
        ((config = Object.assign(JSON.parse(JSON.stringify(o)), {})).theme =
            e.getAttribute("data-bs-theme") || o.theme),
        (config.topbar.color =
            e.getAttribute("data-topbar-color") || o.topbar.color),
        (config.menu.color =
            e.getAttribute("data-sidebar-color") || o.menu.color),
        (config.menu.size = e.getAttribute("data-sidebar-size") || o.menu.size),
        (window.defaultConfig = JSON.parse(JSON.stringify(config))),
        null !== t && (config = JSON.parse(t)),
        (window.config = config) &&
            (e.setAttribute("data-bs-theme", config.theme),
            e.setAttribute("data-topbar-color", config.topbar.color),
            e.setAttribute("data-sidebar-color", config.menu.color),
            window.innerWidth <= 1140
                ? e.setAttribute("data-sidebar-size", "hidden")
                : e.setAttribute("data-sidebar-size", config.menu.size));
})();