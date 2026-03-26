import { Grid, html } from "gridjs";
import { addThousandSeparators } from "../global-config";

class QuickSearchResult {
    constructor() {
        this.table = null;
        const baseInput = document.getElementById("base_url_datatable");
        this.baseUrl = baseInput ? baseInput.value.split("?")[0] : "";
        this.keywordInput = document.getElementById("search_input");
        this.searchButton = document.getElementById("search_button");

        this.datatableUrl = this.buildUrl(this.keywordInput.value);
    }

    init() {
        this.bindSearchButton();
        this.initDatatable();
    }

    bindSearchButton() {
        const handleSearch = () => {
            const newKeyword = this.keywordInput.value.trim();
            if (newKeyword !== "") {
                // 1. Update datatable URL and reload
                this.datatableUrl = this.buildUrl(newKeyword);
                this.initDatatable();

                // 2. Update URL query string (without reloading the page)
                const newUrl = `${
                    window.location.pathname
                }?keyword=${encodeURIComponent(newKeyword)}`;
                window.history.pushState({ path: newUrl }, "", newUrl);

                // 3. Update visible keyword text in <em>{{ $keyword }}</em>>
                const keywordDisplay = document.querySelector(".qs-header em");
                if (keywordDisplay) {
                    keywordDisplay.textContent = newKeyword;
                }
            }
        };

        this.searchButton.addEventListener("click", handleSearch);

        this.keywordInput.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                handleSearch();
            }
        });
    }

    buildUrl(keyword) {
        const url = new URL(this.baseUrl, window.location.origin);
        url.searchParams.set("search", keyword);
        return url.toString();
    }

    initDatatable() {
        const tableContainer = document.getElementById(
            "datatable-quick-search-result"
        );

        const config = {
            columns: [
                "ID",
                { name: "Nama Pemohon" },
                { name: "Nama Pemilik" },
                { name: "Kondisi" },
                "Nomor Registrasi",
                "Nomor Dokumen",
                { name: "Alamat" },
                "Status",
                "Jenis Fungsi",
                { name: "Nama Bangunan" },
                "Jenis Konsultasi",
                { name: "Tanggal Jatuh Tempo" },
                { name: "Retribusi" },
                { name: "Catatan Kekurangan Dokumen" },
                {
                    name: "Action",
                    formatter: (cell) => {
                        return html(`
                          <a href="/quick-search/${cell.id}" 
                            class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center"
                            style="white-space: nowrap; line-height: 1;">
                                <iconify-icon icon="mingcute:eye-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon>
                          </a>
                      `);
                    },
                },
            ],
            search: false,
            pagination: {
                limit: 15,
                server: {
                    url: (prev, page) =>
                        `${prev}${prev.includes("?") ? "&" : "?"}page=${
                            page + 1
                        }`,
                },
            },
            sort: true,
            server: {
                url: this.datatableUrl,
                then: (data) =>
                    data.data.map((item) => [
                        item.id,
                        item.name,
                        item.owner_name,
                        item.condition,
                        item.registration_number,
                        item.document_number,
                        item.address,
                        item.status_name,
                        item.function_type,
                        item.name_building,
                        item.consultation_type,
                        item.due_date,
                        addThousandSeparators(item.nilai_retribusi_bangunan),
                        item.note || "-",
                        item,
                    ]),
                total: (data) => data.total,
            },
        };

        if (this.table) {
            this.table
                .updateConfig({
                    ...config,
                    server: { ...config.server, url: this.datatableUrl },
                })
                .forceRender();
        } else {
            tableContainer.innerHTML = "";
            this.table = new Grid(config).render(tableContainer);
        }
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const app = new QuickSearchResult();
    app.init();
});
