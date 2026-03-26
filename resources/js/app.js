import bootstrap from "bootstrap/dist/js/bootstrap";
window.bootstrap = bootstrap;
import "iconify-icon";
import "simplebar/dist/simplebar";

class Components {
    initBootstrapComponents() {
        [...document.querySelectorAll('[data-bs-toggle="popover"]')].map(
            (e) => new bootstrap.Popover(e)
        ),
            [...document.querySelectorAll('[data-bs-toggle="tooltip"]')].map(
                (e) => new bootstrap.Tooltip(e)
            ),
            [...document.querySelectorAll(".offcanvas")].map(
                (e) => new bootstrap.Offcanvas(e)
            );
        var e = document.getElementById("toastPlacement"),
            t =
                (e &&
                    document
                        .getElementById("selectToastPlacement")
                        .addEventListener("change", function () {
                            e.dataset.originalClass ||
                                (e.dataset.originalClass = e.className),
                                (e.className =
                                    e.dataset.originalClass + " " + this.value);
                        }),
                [].slice
                    .call(document.querySelectorAll(".toast"))
                    .map(function (e) {
                        return new bootstrap.Toast(e);
                    }),
                document.getElementById("liveAlertBtn"));
        t &&
            t.addEventListener("click", () => {
                alert("Nice, you triggered this alert message!", "success");
            });
    }
    initfullScreenListener() {
        var e = document.querySelector('[data-toggle="fullscreen"]');
        e &&
            e.addEventListener("click", function (e) {
                e.preventDefault(),
                    document.body.classList.toggle("fullscreen-enable"),
                    document.fullscreenElement ||
                    document.mozFullScreenElement ||
                    document.webkitFullscreenElement
                        ? document.cancelFullScreen
                            ? document.cancelFullScreen()
                            : document.mozCancelFullScreen
                            ? document.mozCancelFullScreen()
                            : document.webkitCancelFullScreen &&
                              document.webkitCancelFullScreen()
                        : document.documentElement.requestFullscreen
                        ? document.documentElement.requestFullscreen()
                        : document.documentElement.mozRequestFullScreen
                        ? document.documentElement.mozRequestFullScreen()
                        : document.documentElement.webkitRequestFullscreen &&
                          document.documentElement.webkitRequestFullscreen(
                              Element.ALLOW_KEYBOARD_INPUT
                          );
            });
    }
    initCounter() {
        var e = document.querySelectorAll(".counter-value");
        function a(e) {
            return e.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        e &&
            e.forEach(function (i) {
                !(function e() {
                    var t = +i.getAttribute("data-target"),
                        n = +i.innerText,
                        o = t / 250;
                    o < 1 && (o = 1),
                        n < t
                            ? ((i.innerText = (n + o).toFixed(0)),
                              setTimeout(e, 1))
                            : (i.innerText = a(t)),
                        a(i.innerText);
                })();
            });
    }
    init() {
        this.initBootstrapComponents(),
            this.initfullScreenListener(),
            this.initCounter();
    }
}
class FormValidation {
    initFormValidation() {
        document.querySelectorAll(".needs-validation").forEach((t) => {
            t.addEventListener(
                "submit",
                (e) => {
                    t.checkValidity() ||
                        (e.preventDefault(), e.stopPropagation()),
                        t.classList.add("was-validated");
                },
                !1
            );
        });
    }
    init() {
        this.initFormValidation();
    }
}
document.addEventListener("DOMContentLoaded", function (e) {
    new Components().init(), new FormValidation().init();
});
class ThemeLayout {
    constructor() {
        (this.html = document.getElementsByTagName("html")[0]),
            (this.config = {}),
            (this.defaultConfig = window.config);
    }
    initVerticalMenu() {
        var e = document.querySelectorAll(".navbar-nav li .collapse");
        document
            .querySelectorAll(".navbar-nav li [data-bs-toggle='collapse']")
            .forEach((e) => {
                e.addEventListener("click", function (e) {
                    e.preventDefault();
                });
            }),
            e.forEach((e) => {
                e.addEventListener("show.bs.collapse", function (t) {
                    let n = t.target.closest(".collapse.show");
                    document
                        .querySelectorAll(".navbar-nav .collapse.show")
                        .forEach((e) => {
                            e !== t.target &&
                                e !== n &&
                                new bootstrap.Collapse(e).hide();
                        });
                });
            }),
            document.querySelector(".navbar-nav") &&
                (document
                    .querySelectorAll(".navbar-nav a")
                    .forEach(function (t) {
                        var e = window.location.href.split(/[?#]/)[0];
                        if (t.href === e) {
                            t.classList.add("active"),
                                t.parentNode.classList.add("active");
                            let e = t.closest(".collapse");
                            for (; e; )
                                e.classList.add("show"),
                                    e.parentElement.children[0].classList.add(
                                        "active"
                                    ),
                                    e.parentElement.children[0].setAttribute(
                                        "aria-expanded",
                                        "true"
                                    ),
                                    (e = e.parentElement.closest(".collapse"));
                        }
                    }),
                setTimeout(function () {
                    var e,
                        n,
                        o,
                        i,
                        a,
                        t = document.querySelector(".nav-item li a.active");
                    null != t &&
                        ((e = document.querySelector(
                            ".app-sidebar .simplebar-content-wrapper"
                        )),
                        (t = t.offsetTop - 300),
                        e) &&
                        100 < t &&
                        ((o = (n = e).scrollTop),
                        (i = t - o),
                        (a = 0),
                        (function e() {
                            var t = (a += 20),
                                t =
                                    (t /= 300) < 1
                                        ? (i / 2) * t * t + o
                                        : (-i / 2) * (--t * (t - 2) - 1) + o;
                            (n.scrollTop = t), a < 600 && setTimeout(e, 20);
                        })());
                }, 200));
    }
    initConfig() {
        (this.defaultConfig = JSON.parse(JSON.stringify(window.defaultConfig))),
            (this.config = JSON.parse(JSON.stringify(window.config))),
            this.setSwitchFromConfig();
    }
    changeMenuColor(e) {
        (this.config.menu.color = e),
            this.html.setAttribute("data-sidebar-color", e),
            this.setSwitchFromConfig();
    }
    changeMenuSize(e, t = !0) {
        this.html.setAttribute("data-sidebar-size", e),
            t && ((this.config.menu.size = e), this.setSwitchFromConfig());
    }
    changeThemeMode(e) {
        (this.config.theme = e),
            this.html.setAttribute("data-bs-theme", e),
            this.setSwitchFromConfig();
    }
    changeTopbarColor(e) {
        (this.config.topbar.color = e),
            this.html.setAttribute("data-topbar-color", e),
            this.setSwitchFromConfig();
    }
    resetTheme() {
        (this.config = JSON.parse(JSON.stringify(window.defaultConfig))),
            this.changeMenuColor(this.config.menu.color),
            this.changeMenuSize(this.config.menu.size),
            this.changeThemeMode(this.config.theme),
            this.changeTopbarColor(this.config.topbar.color),
            this._adjustLayout();
    }
    initSwitchListener() {
        var n = this,
            e =
                (document
                    .querySelectorAll("input[name=data-sidebar-color]")
                    .forEach(function (t) {
                        t.addEventListener("change", function (e) {
                            n.changeMenuColor(t.value);
                        });
                    }),
                document
                    .querySelectorAll("input[name=data-sidebar-size]")
                    .forEach(function (t) {
                        t.addEventListener("change", function (e) {
                            n.changeMenuSize(t.value);
                        });
                    }),
                document
                    .querySelectorAll("input[name=data-bs-theme]")
                    .forEach(function (t) {
                        t.addEventListener("change", function (e) {
                            n.changeThemeMode(t.value);
                        });
                    }),
                document
                    .querySelectorAll("input[name=data-topbar-color]")
                    .forEach(function (t) {
                        t.addEventListener("change", function (e) {
                            n.changeTopbarColor(t.value);
                        });
                    }),
                document.getElementById("light-dark-mode"));
        e &&
            e.addEventListener("click", function (e) {
                "light" === n.config.theme
                    ? n.changeThemeMode("light")
                    : n.changeThemeMode("light");
            }),
            (e = document.querySelector("#reset-layout")) &&
                e.addEventListener("click", function (e) {
                    n.resetTheme();
                }),
            (e = document.querySelector(".button-toggle-menu")) &&
                e.addEventListener("click", function () {
                    var e = n.config.menu.size,
                        t = n.html.getAttribute("data-sidebar-size", e);
                    "hidden" !== t
                        ? "condensed" === t
                            ? n.changeMenuSize(
                                  "condensed" == e ? "default" : e,
                                  !1
                              )
                            : n.changeMenuSize("condensed", !1)
                        : n.showBackdrop(),
                        n.html.classList.toggle("sidebar-enable");
                });
    }
    showBackdrop() {
        let t = document.createElement("div"),
            n =
                ((t.classList = "offcanvas-backdrop fade show"),
                document.body.appendChild(t),
                (document.body.style.overflow = "hidden"),
                1040 < window.innerWidth &&
                    (document.body.style.paddingRight = "15px"),
                this);
        t.addEventListener("click", function (e) {
            n.html.classList.remove("sidebar-enable"),
                document.body.removeChild(t),
                (document.body.style.overflow = null),
                (document.body.style.paddingRight = null);
        });
    }
    initWindowSize() {
        var t = this;
        window.addEventListener("resize", function (e) {
            t._adjustLayout();
        });
    }
    _adjustLayout() {
        window.innerWidth <= 1140
            ? this.changeMenuSize("hidden", !1)
            : this.changeMenuSize(this.config.menu.size);
    }
    setSwitchFromConfig() {
        sessionStorage.setItem(
            "__DARKONE_CONFIG__",
            JSON.stringify(this.config)
        ),
            document
                .querySelectorAll(".settings-bar input[type=radio]")
                .forEach(function (e) {
                    e.checked = !1;
                });
        var e,
            t,
            n,
            o = this.config;
        o &&
            ((e = document.querySelector(
                "input[type=radio][name=data-bs-theme][value=" + o.theme + "]"
            )),
            (t = document.querySelector(
                "input[type=radio][name=data-topbar-color][value=" +
                    o.topbar.color +
                    "]"
            )),
            (n = document.querySelector(
                "input[type=radio][name=data-sidebar-size][value=" +
                    o.menu.size +
                    "]"
            )),
            (o = document.querySelector(
                "input[type=radio][name=data-sidebar-color][value=" +
                    o.menu.color +
                    "]"
            )),
            e && (e.checked = !0),
            t && (t.checked = !0),
            n && (n.checked = !0),
            o) &&
            (o.checked = !0);
    }
    init() {
        this.initVerticalMenu(),
            this.initConfig(),
            this.initSwitchListener(),
            this.initWindowSize(),
            this._adjustLayout(),
            this.setSwitchFromConfig();
    }
}
new ThemeLayout().init();
