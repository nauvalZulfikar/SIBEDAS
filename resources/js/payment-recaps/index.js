import { Grid } from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";
import moment from "moment";
import InitDatePicker from "../utils/InitDatePicker.js";

class PaymentRecaps {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;
        this.startDate = undefined;
        this.endDate = undefined;
    }
    init() {
        this.initTablePaymentRecaps();
        this.initFilterDatepicker();
        this.handleFilterBtn();
        this.handleExportPDF();
        this.handleExportToExcel();
    }
    initFilterDatepicker() {
        new InitDatePicker(
            "#datepicker-payment-recap",
            this.handleChangeFilterDate.bind(this)
        ).init();
    }
    handleChangeFilterDate(filterDate) {
        this.startDate = moment(filterDate, "YYYY-MM-DD")
            .startOf("day")
            .format("YYYY-MM-DD");
        this.endDate = moment(filterDate, "YYYY-MM-DD")
            .endOf("day")
            .format("YYYY-MM-DD");
    }
    formatCategory(category) {
        const categoryMap = {
            potention_sum: "Potensi",
            non_verified_sum: "Belum Terverifikasi",
            verified_sum: "Terverifikasi",
            business_sum: "Usaha",
            non_business_sum: "Non Usaha",
            spatial_sum: "Tata Ruang",
            waiting_click_dpmptsp_sum: "Berproses di DPMPTSP",
            issuance_realization_pbg_sum: "Realisasi SK PBG Terbit",
            process_in_technical_office_sum: "Proses Di Dinas Teknis",
        };

        return categoryMap[category] || category; // Return mapped value or original category
    }
    initTablePaymentRecaps() {
        let tableContainer = document.getElementById("table-payment-recaps");

        // Fetch data from the server
        fetch(
            `${GlobalConfig.apiHost}/api/payment-recaps?start_date=${
                this.startDate || ""
            }&end_date=${this.endDate || ""}`,
            {
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
            }
        )
            .then((response) => response.json())
            .then((data) => {
                if (!data || !Array.isArray(data.data)) {
                    console.error("Error: Data is not an array", data);
                    return;
                }

                let formattedData = data.data.map((item) => [
                    this.formatCategory(item.category ?? "Unknown"),
                    addThousandSeparators(Number(item.nominal).toString() || 0),
                    moment(item.created_at).isValid()
                        ? moment(item.created_at).format("YYYY-MM-DD H:mm:ss")
                        : "-",
                ]);

                // 🔥 If the table already exists, update it instead of re-creating
                if (this.table) {
                    this.table
                        .updateConfig({
                            data: formattedData.length > 0 ? formattedData : [],
                        })
                        .forceRender();
                } else {
                    // 🔹 First-time initialization
                    this.table = new Grid({
                        columns: [
                            { name: "Kategori", data: (row) => row[0] },
                            { name: "Nominal", data: (row) => row[1] },
                            {
                                name: "Created",
                                data: (row) => row[2],
                                attributes: {
                                    style: "width: 200px; white-space: nowrap;",
                                },
                            },
                        ],
                        pagination: {
                            limit: 50,
                        },
                        sort: true,
                        data: formattedData.length > 0 ? formattedData : [],
                        width: "auto",
                        fixedHeader: true,
                    }).render(tableContainer);
                }
            })
            .catch((error) => console.error("Error fetching data:", error));
    }

    async handleFilterBtn() {
        const filterBtn = document.getElementById("btnFilterData");
        if (!filterBtn) {
            console.error("Button not found: #btnFilterData");
            return;
        }
        filterBtn.addEventListener("click", async () => {
            if (!this.startDate || !this.endDate) {
                console.log("No date filter applied, using default data");
            } else {
                console.log(
                    `Filtering with dates: ${this.startDate} - ${this.endDate}`
                );
            }

            // Reinitialize table with updated filters
            this.initTablePaymentRecaps();
        });
    }

    async handleExportToExcel() {
        const button = document.getElementById("btn-export-excel");
        if (!button) {
            console.error("Button not found: #btn-export-excel");
            return;
        }

        button.addEventListener("click", async () => {
            button.disabled = true;
            let exportUrl = new URL(button.getAttribute("data-url"));

            if (this.startDate) {
                exportUrl.searchParams.append("start_date", this.startDate);
            } else {
                console.warn("⚠️ start_date is missing");
            }

            if (this.endDate) {
                exportUrl.searchParams.append("end_date", this.endDate);
            } else {
                console.warn("⚠️ end_date is missing");
            }

            // Final check
            console.log("Final Export URL:", exportUrl.toString());
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
                a.download = "rekap-pembayaran.xlsx";
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

        button.addEventListener("click", async () => {
            button.disabled = true;
            let exportUrl = new URL(button.getAttribute("data-url"));

            if (this.startDate) {
                exportUrl.searchParams.append("start_date", this.startDate);
            } else {
                console.warn("⚠️ start_date is missing");
            }

            if (this.endDate) {
                exportUrl.searchParams.append("end_date", this.endDate);
            } else {
                console.warn("⚠️ end_date is missing");
            }
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
                a.download = "rekap-pembayaran.pdf";
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
}
document.addEventListener("DOMContentLoaded", function (e) {
    new PaymentRecaps().init();
});
