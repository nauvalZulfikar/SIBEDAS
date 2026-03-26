import { Grid } from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";

class ReportPbgPTSP {
    constructor() {
        this.table = null;
        this.initTableReportPbgPTSP();
        this.handleExportToExcel();
        this.handleExportPDF();
    }
    initTableReportPbgPTSP() {
        let tableContainer = document.getElementById("table-report-pbg-ptsp");

        this.table = new Grid({
            columns: [
                { name: "Status" },
                {
                    name: "Total",
                    formatter: (cell) => addThousandSeparators(cell),
                },
            ],
            pagination: {
                limit: 10,
                server: {
                    url: (prev, page) =>
                        `${prev}${prev.includes("?") ? "&" : "?"}page=${
                            page + 1
                        }`,
                },
            },
            sort: true,
            server: {
                url: `${GlobalConfig.apiHost}/api/report-pbg-ptsp`,
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (response) => {
                    console.log("API Response:", response); // Debugging

                    // Pastikan response memiliki data
                    if (
                        !response ||
                        !response.data ||
                        !Array.isArray(response.data.data)
                    ) {
                        console.error("Error: Data is not an array", response);
                        return [];
                    }

                    return response.data.data.map((item) => [
                        item.status_name || "Unknown",
                        item.total,
                    ]);
                },
                total: (response) => response.data.total || 0, // Ambil total dari API pagination
            },
            width: "auto",
            fixedHeader: true,
        }).render(tableContainer);
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
                a.download = "laporan-ptsp.xlsx";
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
                a.download = "laporan-ptsp.pdf";
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
    new ReportPbgPTSP();
});
