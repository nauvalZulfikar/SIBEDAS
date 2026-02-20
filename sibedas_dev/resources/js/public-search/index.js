import { Grid, html } from "gridjs";
import { addThousandSeparators } from "../global-config";

class PublicSearch {
    constructor() {
        this.table = null;
        const baseInput = document.getElementById("base_url_datatable");
        this.baseUrl = baseInput ? baseInput.value.split("?")[0] : "";
        this.keywordInput = document.getElementById("search_input");
        this.searchButton = document.getElementById("search_button");
        this.searchHeader = document.getElementById("search-header");
        this.tableWrapper = document.getElementById("table-wrapper");
        this.emptyState = document.getElementById("empty-state");

        // Tidak inisialisasi datatable sampai ada pencarian
        this.datatableUrl = null;
    }

    init() {
        this.bindSearchButton();

        // Check if there's a keyword in URL
        const urlParams = new URLSearchParams(window.location.search);
        const keyword = urlParams.get("keyword");

        if (keyword && keyword.trim() !== "") {
            this.keywordInput.value = keyword.trim();
            this.handleSearchFromUrl(keyword.trim());
        }
    }

    handleSearchFromUrl(keyword) {
        // Validasi input kosong atau hanya spasi
        if (!keyword || keyword.trim().length === 0) {
            this.showEmptyState("Mulai Pencarian");
            return;
        }

        // Validasi minimal 3 karakter
        if (keyword.trim().length < 3) {
            this.showEmptyState("Minimal 3 karakter untuk pencarian");
            return;
        }

        this.datatableUrl = this.buildUrl(keyword.trim());
        this.showSearchResults();
        this.initDatatable();
    }

    bindSearchButton() {
        const handleSearch = () => {
            const newKeyword = this.keywordInput.value.trim();

            // Validasi input kosong atau hanya spasi
            if (!newKeyword || newKeyword.length === 0) {
                this.showEmptyState("Mulai Pencarian");
                return;
            }

            // Validasi minimal 3 karakter (setelah trim)
            if (newKeyword.length < 3) {
                this.showEmptyState("Minimal 3 karakter untuk pencarian");
                return;
            }

            // 1. Update datatable URL and reload
            this.datatableUrl = this.buildUrl(newKeyword);
            this.showSearchResults();
            this.initDatatable();

            // 2. Update URL query string (tanpa reload page)
            const newUrl = `${window.location.pathname}${
                newKeyword ? `?keyword=${encodeURIComponent(newKeyword)}` : ""
            }`;
            window.history.pushState({ path: newUrl }, "", newUrl);

            // 3. Update visible keyword text di <em>{{ $keyword }}</em>>
            const keywordDisplay = document.querySelector(".qs-header em");
            if (keywordDisplay) {
                keywordDisplay.textContent = newKeyword || "-";
            }
        };

        this.searchButton.addEventListener("click", handleSearch);

        this.keywordInput.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                handleSearch();
            }
        });

        // Handle input change untuk real-time validation
        this.keywordInput.addEventListener("input", (event) => {
            const value = event.target.value.trim();

            // Remove existing classes
            this.keywordInput.classList.remove("valid", "warning", "invalid");

            // Jika input kosong atau hanya spasi, show empty state
            if (!value || value.length === 0) {
                this.showEmptyState("Mulai Pencarian");
                return;
            }

            // Jika kurang dari 3 karakter, show warning
            if (value.length < 3) {
                this.showEmptyState("Minimal 3 karakter untuk pencarian");
                this.keywordInput.classList.add("warning");
                return;
            }

            // Jika valid, add valid class
            this.keywordInput.classList.add("valid");
        });

        // Handle input focus untuk clear warning state
        this.keywordInput.addEventListener("focus", () => {
            const value = this.keywordInput.value.trim();
            if (value.length >= 3) {
                // Jika sudah valid, hide empty state
                this.emptyState.style.display = "none";
            }
        });

        // Handle input blur untuk final validation
        this.keywordInput.addEventListener("blur", () => {
            const value = this.keywordInput.value.trim();
            if (!value || value.length === 0) {
                this.showEmptyState("Mulai Pencarian");
            } else if (value.length < 3) {
                this.showEmptyState("Minimal 3 karakter untuk pencarian");
            }
        });

        // Handle Escape key untuk clear search
        this.keywordInput.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                this.clearSearch();
            }
        });
    }

    buildUrl(keyword) {
        const url = new URL(this.baseUrl, window.location.origin);

        // Validasi keyword tidak kosong dan tidak hanya spasi
        if (keyword && keyword.trim() !== "" && keyword.trim().length >= 3) {
            url.searchParams.set("search", keyword.trim());
        } else {
            url.searchParams.delete("search"); // pastikan tidak ada search param
        }

        return url.toString();
    }

    showSearchResults() {
        this.searchHeader.style.display = "block";
        this.tableWrapper.style.display = "block";
        this.emptyState.style.display = "none";
    }

    showEmptyState(message = "Tidak ada data yang ditemukan") {
        this.searchHeader.style.display = "none";
        this.tableWrapper.style.display = "none";
        this.emptyState.style.display = "block";

        // Update empty state message and icon
        const emptyStateTitle = this.emptyState.querySelector("h4");
        const emptyStateDesc = this.emptyState.querySelector("p");
        const emptyIcon = this.emptyState.querySelector(".empty-icon i");

        if (emptyStateTitle) {
            emptyStateTitle.textContent = message;
        }

        if (emptyStateDesc) {
            if (message === "Mulai Pencarian") {
                emptyStateDesc.textContent =
                    "Masukkan kata kunci minimal 3 karakter untuk mencari data PBG";
            } else if (message === "Minimal 3 karakter untuk pencarian") {
                emptyStateDesc.textContent =
                    "Masukkan kata kunci minimal 3 karakter untuk mencari data PBG";
            } else {
                emptyStateDesc.textContent =
                    "Coba gunakan kata kunci yang berbeda atau lebih spesifik";
            }
        }

        // Update icon based on message
        if (emptyIcon) {
            if (message === "Mulai Pencarian") {
                emptyIcon.className = "fas fa-search fa-3x text-muted";
            } else if (message === "Minimal 3 karakter untuk pencarian") {
                emptyIcon.className =
                    "fas fa-exclamation-triangle fa-3x text-warning";
            } else {
                emptyIcon.className = "fas fa-search fa-3x text-muted";
            }
        }

        // Clear existing table if any
        if (this.table) {
            this.table.destroy();
            this.table = null;
        }
    }

    clearSearch() {
        this.keywordInput.value = "";
        this.showEmptyState("Mulai Pencarian");

        // Reset CSS classes
        this.keywordInput.classList.remove("valid", "warning", "invalid");

        // Clear URL parameter
        const newUrl = window.location.pathname;
        window.history.pushState({ path: newUrl }, "", newUrl);

        // Reset datatable URL
        this.datatableUrl = null;
    }

    initDatatable() {
        const tableContainer = document.getElementById(
            "datatable-public-search"
        );

        const config = {
            columns: [
                { name: "ID", width: "80px" },
                { name: "Nama Pemohon", width: "150px" },
                { name: "Nama Pemilik", width: "150px" },
                { name: "Kondisi", width: "120px" },
                { name: "Nomor Registrasi", width: "180px" },
                { name: "Status", width: "120px" },
                { name: "Jenis Fungsi", width: "150px" },
                { name: "Nama Bangunan", width: "200px" },
                { name: "Jenis Konsultasi", width: "150px" },
                { name: "Tanggal Jatuh Tempo", width: "140px" },
                { name: "Retribusi", width: "120px" },
                { name: "Catatan Kekurangan Dokumen", width: "120px" },
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
                then: (data) => {
                    // Check if data is empty
                    if (!data.data || data.data.length === 0) {
                        this.showEmptyState(
                            data.message || "Tidak ada data yang ditemukan"
                        );
                        return [];
                    }

                    return data.data.map((item) => [
                        item.id || "-",
                        item.name || "-",
                        item.owner_name || "-",
                        item.condition || "-",
                        item.registration_number || "-",
                        item.status_name || "-",
                        item.function_type || "-",
                        item.name_building || "-",
                        item.consultation_type || "-",
                        item.due_date || "-",
                        item.nilai_retribusi_bangunan
                            ? addThousandSeparators(
                                  item.nilai_retribusi_bangunan
                              )
                            : "-",
                        item.note || "-",
                    ]);
                },
                total: (data) => data.total || 0,
                error: (error) => {
                    console.error("Datatable error:", error);
                    this.showEmptyState(
                        "Terjadi kesalahan saat mengambil data"
                    );
                },
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
    const app = new PublicSearch();
    app.init();
});
