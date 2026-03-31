import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";
import moment from "moment";

class BigdataResume {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        // Initialize functions
        this.initEvents();
    }
    async initEvents() {
        await this.initBigdataResumeTable();
        // this.handleSearch();
        await this.handleExportPDF();
        await this.handleExportToExcel();
    }

    async initBigdataResumeTable() {
        let tableContainer = document.getElementById("table-bigdata-resumes");

        this.table = new Grid({
            columns: [
                { name: "ID" },
                { name: "Year" },
                { name: "Jumlah Potensi" },
                { name: "Total Potensi" },
                { name: "Jumlah Berkas Belum Terverifikasi" },
                { name: "Total Berkas Belum Terverifikasi" },
                { name: "Jumlah Berkas Terverifikasi" },
                { name: "Total Berkas Terverifikasi" },
                { name: "Jumlah Usaha" },
                { name: "Total Usaha" },
                { name: "Jumlah Non Usaha" },
                { name: "Total Non Usaha" },
                { name: "Jumlah Tata Ruang" },
                { name: "Total Tata Ruang" },
                { name: "Jumlah Berproses di DPMPTSP" },
                { name: "Total Berproses di DPMPTSP" },
                { name: "Jumlah Realisasi SK PBG Terbit" },
                { name: "Total Realisasi SK PBG Terbit" },
                { name: "Jumlah Proses Dinas Teknis" },
                { name: "Total Proses Dinas Teknis" },
                {
                    name: "Created",
                    attributes: {
                        style: "width: 200px; white-space: nowrap;",
                    },
                },
            ],
            pagination: {
                limit: 50,
                server: {
                    url: (prev, page) =>
                        `${prev}${prev.includes("?") ? "&" : "?"}page=${
                            page + 1
                        }`,
                },
            },
            sort: true,
            search: {
                server: {
                    url: (prev, keyword) => `${prev}?search=${keyword}`,
                },
                debounceTimeout: 1000,
            },
            server: {
                url: `${GlobalConfig.apiHost}/api/bigdata-report`,
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) => {
                    return data.data.map((item) => [
                        item.id,
                        item.year,
                        addThousandSeparators(item.potention_count),
                        addThousandSeparators(item.potention_sum),
                        addThousandSeparators(item.non_verified_count),
                        addThousandSeparators(item.non_verified_sum),
                        addThousandSeparators(item.verified_count),
                        addThousandSeparators(item.verified_sum),
                        addThousandSeparators(item.business_count),
                        addThousandSeparators(item.business_sum),
                        addThousandSeparators(item.non_business_count),
                        addThousandSeparators(item.non_business_sum),
                        addThousandSeparators(item.spatial_count),
                        addThousandSeparators(item.spatial_sum),
                        addThousandSeparators(item.waiting_click_dpmptsp_count),
                        addThousandSeparators(item.waiting_click_dpmptsp_sum),
                        addThousandSeparators(
                            item.issuance_realization_pbg_count
                        ),
                        addThousandSeparators(
                            item.issuance_realization_pbg_sum
                        ),
                        addThousandSeparators(
                            item.process_in_technical_office_count
                        ),
                        addThousandSeparators(
                            item.process_in_technical_office_sum
                        ),
                        moment(item.created_at).format("YYYY-MM-DD H:mm:ss"),
                    ]);
                },
                total: (data) => data.total,
            },
            width: "auto",
            fixedHeader: true,
        });

        return new Promise((resolve) => {
            this.table.render(tableContainer);
            this.table.on("ready", resolve); // Tunggu event "ready"
        });
    }

    async handleExportToExcel() {
        const button = document.getElementById("btn-export-excel");
        if (!button) {
            console.error("Button not found: #btn-export-excel");
            return;
        }

        let exportUrl = button.getAttribute("data-url");

        button.addEventListener("click", async () => {
            button.disabled = true;
            try {
                const response = await fetch(`${exportUrl}`, {
                    method: "GET",
                    credentials: "include",
                    headers: {
                        Authorization: `Bearer ${document
                            .querySelector('meta[name="api-token"]')
                            .getAttribute("content")}`,
                    },
                });
                if (!response.ok) {
                    console.error("Error fetching data:", response.statusText);
                    button.disabled = false;
                    return;
                }

                // Convert response to Blob and trigger download
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "laporan-pimpinan.xlsx";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Error fetching data:", error);
                button.disabled = false;
                return;
            } finally {
                button.disabled = false;
            }
        });
    }

    async handleExportPDF() {
        const button = document.getElementById("btn-export-pdf");
        if (!button) {
            console.error("Button not found: #btn-export-pdf");
            return;
        }

        let exportUrl = button.getAttribute("data-url");

        button.addEventListener("click", async () => {
            button.disabled = true;
            try {
                const response = await fetch(`${exportUrl}`, {
                    method: "GET",
                    credentials: "include",
                    headers: {
                        Authorization: `Bearer ${document
                            .querySelector('meta[name="api-token"]')
                            .getAttribute("content")}`,
                    },
                });
                if (!response.ok) {
                    console.error("Error fetching data:", response.statusText);
                    button.disabled = false;
                    return;
                }

                // Convert response to Blob and trigger download
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "laporan-pimpinan.pdf";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error("Error fetching data:", error);
                button.disabled = false;
                return;
            } finally {
                button.disabled = false;
            }
        });
    }

    handleSearch() {
        document.getElementById("search-btn").addEventListener("click", () => {
            let searchValue = document.getElementById("search-box").value;

            if (!this.table) {
                // Ensure table is initialized
                console.error("Table element not found!");
                return;
            }

            this.table
                .updateConfig({
                    server: {
                        url: `${GlobalConfig.apiHost}/api/bigdata-report?search=${searchValue}`,
                        headers: {
                            Authorization: `Bearer ${document
                                .querySelector('meta[name="api-token"]')
                                .getAttribute("content")}`,
                            "Content-Type": "application/json",
                        },
                        then: (data) => {
                            return data.data.map((item) => [
                                item.id,
                                item.potention_count,
                                addThousandSeparators(item.potention_sum),
                                item.non_verified_count,
                                addThousandSeparators(item.non_verified_sum),
                                item.verified_count,
                                addThousandSeparators(item.verified_sum),
                                item.business_count,
                                addThousandSeparators(item.business_sum),
                                item.non_business_count,
                                addThousandSeparators(item.non_business_sum),
                                item.spatial_count,
                                addThousandSeparators(item.spatial_sum),
                                item.waiting_click_dpmptsp_count,
                                addThousandSeparators(
                                    item.waiting_click_dpmptsp_sum
                                ),
                                item.issuance_realization_pbg_count,
                                addThousandSeparators(
                                    item.issuance_realization_pbg_sum
                                ),
                                item.process_in_technical_office_count,
                                addThousandSeparators(
                                    item.process_in_technical_office_sum
                                ),
                                moment(item.created_at).format(
                                    "YYYY-MM-DD H:mm:ss"
                                ),
                            ]);
                        },
                        total: (data) => data.total,
                    },
                })
                .forceRender();
        });
    }
}
document.addEventListener("DOMContentLoaded", function (e) {
    new BigdataResume();
});
