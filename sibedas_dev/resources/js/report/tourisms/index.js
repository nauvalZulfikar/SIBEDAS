import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import { addThousandSeparators } from "../../global-config.js";

// Mengambil data dari input dengan id="business_type_counts"
const businessTypeCountsElement = document.getElementById("tourism_based_KBLI");
const businessTypeCounts = JSON.parse(businessTypeCountsElement.value); // Cek apakah data sudah terbawa dengan benar

// Membuat Grid.js instance
new gridjs.Grid({
    columns: ["Jenis Bisnis Pariwisata", "Jumlah Total"], // Nama kolom
    data: businessTypeCounts.map((item) => {
        return [item.kbli_title, addThousandSeparators(item.total_records)];
    }), // Mengubah data untuk Grid.js
    search: true, // Menambahkan fitur pencarian
    pagination: true, // Menambahkan fitur pagination
    sort: true, // Menambahkan fitur sorting
}).render(document.getElementById("tourisms-report-data-table"));

class TourismReport {
    init() {
        this.handleExportToExcel();
        this.handleExportPDF();
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
                a.download = "laporan-pariwisata.xlsx";
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
                a.download = "laporan-pariwisata.pdf";
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

document.addEventListener("DOMContentLoaded", function () {
    new TourismReport().init();
});
