import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";

export default defineConfig(({ mode }) => {
    // Load environment variables
    const env = loadEnv(mode, process.cwd(), "");

    // Determine the host based on VITE_APP_HOST environment variable
    const appHost = env.VITE_APP_HOST || "localhost";
    const isLocalhost = appHost === "localhost";

    return {
        server: {
            host: isLocalhost ? "0.0.0.0" : appHost,
            hmr: {
                host: appHost,
            },
            watch: {
                usePolling: true,
            },
        },
        build: {
            outDir: "public/build",
            assetsDir: "assets",
            manifest: "manifest.json",
            rollupOptions: {
                output: {
                    manualChunks: undefined,
                    entryFileNames: "assets/[name]-[hash].js",
                    chunkFileNames: "assets/[name].js",
                    assetFileNames: "assets/[name].[ext]",
                },
            },
        },
        resolve: {
            alias: {
                "@": path.resolve(__dirname, "resources/js"),
            },
        },
        plugins: [
            laravel({
                input: [
                    //css
                    "resources/scss/icons.scss",
                    "resources/scss/style.scss",
                    "resources/scss/components/_circle.scss",
                    "resources/scss/dashboards/_bigdata.scss",
                    "resources/scss/dashboards/_lack-of-potential.scss",
                    "resources/scss/components/_custom_circle.scss",
                    "resources/scss/dashboards/potentials/_inside_system.scss",
                    "resources/scss/dashboards/potentials/_outside_system.scss",
                    "resources/scss/pages/quick-search/detail.scss",
                    "resources/scss/pages/quick-search/index.scss",
                    "resources/scss/pages/quick-search/result.scss",
                    "resources/scss/pages/public-search/index.scss",
                    "resources/scss/pages/pbg-task/show.scss",

                    "node_modules/quill/dist/quill.snow.css",
                    "node_modules/quill/dist/quill.bubble.css",
                    "node_modules/flatpickr/dist/flatpickr.min.css",
                    "node_modules/flatpickr/dist/themes/dark.css",
                    "node_modules/gridjs/dist/theme/mermaid.css",
                    "node_modules/flatpickr/dist/themes/dark.css",
                    "node_modules/gridjs/dist/theme/mermaid.min.css",

                    //js
                    "resources/js/app.js",
                    "resources/js/config.js",
                    "resources/js/pages/dashboard.js",
                    "resources/js/pages/chart.js",
                    "resources/js/pages/form-quilljs.js",
                    "resources/js/pages/form-fileupload.js",
                    "resources/js/pages/form-flatepicker.js",
                    "resources/js/pages/table-gridjs.js",
                    "resources/js/pages/maps-google.js",
                    "resources/js/pages/maps-vector.js",
                    "resources/js/pages/maps-spain.js",
                    "resources/js/pages/maps-russia.js",
                    "resources/js/pages/maps-iraq.js",
                    "resources/js/pages/maps-canada.js",
                    "resources/js/data/advertisements/data-advertisements.js",
                    "resources/js/data/advertisements/form-create-update.js",
                    "resources/js/data/advertisements/form-upload.js",

                    //js-additional
                    "resources/js/settings/syncronize/syncronize.js",
                    "resources/js/settings/general/general-settings.js",
                    "resources/js/tables/common-table.js",

                    // dashboards
                    "resources/js/dashboards/bigdata.js",
                    "resources/js/dashboards/potentials/inside_system.js",
                    "resources/js/dashboards/potentials/outside_system.js",
                    "resources/js/dashboards/leader.js",
                    // roles
                    "resources/js/roles/index.js",
                    "resources/js/roles/create.js",
                    "resources/js/roles/update.js",
                    "resources/js/roles/role_menu.js",
                    // users
                    "resources/js/master/users/users.js",
                    "resources/js/master/users/create.js",
                    "resources/js/master/users/update.js",
                    // menus
                    "resources/js/menus/index.js",
                    "resources/js/menus/create.js",
                    "resources/js/menus/update.js",
                    //data-settings
                    "resources/js/data-settings/index.js",
                    "resources/js/data-settings/create.js",
                    "resources/js/data-settings/update.js",
                    // business-industries
                    "resources/js/business-industries/create.js",
                    "resources/js/business-industries/update.js",
                    "resources/js/business-industries/index.js",
                    // umkm
                    "resources/js/data/umkm/data-umkm.js",
                    "resources/js/data/umkm/form-upload.js",
                    "resources/js/data/umkm/form-create-update.js",
                    // tourisms
                    "resources/js/data/tourisms/data-tourisms.js",
                    "resources/js/data/tourisms/form-create-update.js",
                    "resources/js/data/tourisms/form-upload.js",
                    "resources/js/report/tourisms/index.js",
                    // spatial-plannings
                    "resources/js/data/spatialPlannings/data-spatialPlannings.js",
                    "resources/js/data/spatialPlannings/form-create-update.js",
                    "resources/js/data/spatialPlannings/form-upload.js",
                    // customers
                    "resources/js/customers/upload.js",
                    "resources/js/customers/index.js",
                    "resources/js/customers/create.js",
                    "resources/js/customers/edit.js",
                    "resources/js/dashboards/pbg.js",
                    // maps
                    "resources/js/maps/maps-kml.js",
                    // laporan pimpinan
                    "resources/js/bigdata-resumes/index.js",
                    "resources/js/chatbot/index.js",
                    "resources/js/chatbot-pimpinan/index.js",
                    //pbg-task
                    "resources/js/pbg-task/index.js",
                    "resources/js/pbg-task/show.js",
                    "resources/js/pbg-task/create.js",
                    // google-sheets
                    "resources/js/data/google-sheet/index.js",
                    // quick-search
                    "resources/js/quick-search/index.js",
                    "resources/js/quick-search/result.js",
                    "resources/js/quick-search/detail.js",
                    // public-search
                    "resources/js/public-search/index.js",
                    // growth-report
                    "resources/js/report/growth-report/index.js",
                    // dummy
                    "resources/js/approval/index.js",
                    "resources/js/invitations/index.js",
                    "resources/js/payment-recaps/index.js",
                    "resources/js/report-pbg-ptsp/index.js",
                    "resources/js/tpa-tpt/index.js",
                    "resources/js/report-payment-recaps/index.js",
                    // taxation
                    "resources/js/taxation/index.js",
                    "resources/js/taxation/upload.js",
                    "resources/js/taxation/edit.js",
                ],
                refresh: true,
            }),
        ],
    };
});
