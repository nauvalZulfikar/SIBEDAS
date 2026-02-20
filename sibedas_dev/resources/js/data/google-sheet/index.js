import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../../global-config.js";

class GoogleSheets {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        // Initialize functions
        this.initTableGoogleSheets();
        this.initEvents();
    }

    initEvents() {
        document.body.addEventListener("click", async (event) => {
            const deleteButton = event.target.closest(
                ".btn-delete-google-sheet"
            );
            if (deleteButton) {
                event.preventDefault();
                await this.handleDelete(deleteButton);
            }
        });
    }

    initTableGoogleSheets() {
        let tableContainer = document.getElementById(
            "table-data-google-sheets"
        );

        if (!tableContainer) {
            console.error("Table container not found!");
            return;
        }

        // Clear previous table content
        tableContainer.innerHTML = "";

        // Get user permissions from data attributes
        // let canUpdate = tableContainer.getAttribute("data-updater") === "1";
        // let canDelete = tableContainer.getAttribute("data-destroyer") === "1";

        this.table = new Grid({
            columns: [
                "ID",
                "No Registratsi",
                "No KRK",
                "Format STS",
                "Fungsi BG",
                "Selesai Terbit",
                "Selesai Verifikasi",
                "Tanggal Permohonan",
                {
                    name: "Action",
                    formatter: (cell) => {
                        let buttons = "";

                        buttons += `
                          <a href="/data/google-sheets/${cell}" class="btn btn-primary btn-sm d-inline-flex align-items-center justify-content-center">
                            <i class='bx bx-show'></i>
                          </a>
                        `;

                        // if (canUpdate) {
                        //     buttons += `
                        //     <a href="#" class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center">
                        //         <i class='bx bx-edit'></i>
                        //     </a>
                        // `;
                        // }

                        // if (canDelete) {
                        //     buttons += `
                        //     <button data-id="${cell}" class="btn btn-sm btn-red btn-delete-google-sheet d-inline-flex align-items-center justify-content-center">
                        //         <i class='bx bxs-trash'></i>
                        //     </button>
                        // `;
                        // }

                        // if (!canUpdate && !canDelete) {
                        //     buttons = `<span class="text-muted">No Privilege</span>`;
                        // }

                        return gridjs.html(
                            `<div class="d-flex justify-content-center gap-2">${buttons}</div>`
                        );
                    },
                },
            ],
            pagination: {
                limit: 50,
                server: {
                    url: (prev, page) => {
                        let separator = prev.includes("?") ? "&" : "?";
                        return `${prev}${separator}page=${page + 1}`;
                    },
                },
            },
            sort: true,
            search: {
                server: {
                    url: (prev, keyword) => {
                        let separator = prev.includes("?") ? "&" : "?";
                        return `${prev}${separator}search=${encodeURIComponent(
                            keyword
                        )}`;
                    },
                },
                debounceTimeout: 1000,
            },
            server: {
                url: `${GlobalConfig.apiHost}/api/pbg-task-google-sheet`,
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) => {
                    if (!data || !data.data) {
                        console.warn("⚠️ No data received from API");
                        return [];
                    }

                    return data.data.map((item) => {
                        console.log("🔹 Processing Item:", item);
                        return [
                            item.id,
                            item.no_registrasi,
                            item.no_krk,
                            item.format_sts,
                            item.fungsi_bg,
                            item.selesai_terbit,
                            item.selesai_verifikasi,
                            item.tgl_permohonan,
                            item.id,
                        ];
                    });
                },
                total: (data) => {
                    let totalRecords = data?.meta?.total || 0;
                    return totalRecords;
                },
                catch: (error) => {
                    console.error("❌ Error fetching data:", error);
                },
            },
        }).render(tableContainer);
    }

    async handleDelete(deleteButton) {
        const id = deleteButton.getAttribute("data-id");

        const result = await Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!",
        });

        if (result.isConfirmed) {
            try {
                let response = await fetch(
                    `${GlobalConfig.apiHost}/api/pbg-task-google-sheet/${id}`,
                    {
                        method: "DELETE",
                        credentials: "include",
                        headers: {
                            Authorization: `Bearer ${document
                                .querySelector('meta[name="api-token"]')
                                .getAttribute("content")}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                if (response.ok) {
                    let result = await response.json();
                    this.toastMessage.innerText =
                        result.message || "Deleted successfully!";
                    this.toast.show();

                    // Refresh Grid.js table
                    if (typeof this.table !== "undefined") {
                        this.table.updateConfig({}).forceRender();
                    }
                } else {
                    let error = await response.json();
                    console.error("Delete failed:", error);
                    this.toastMessage.innerText =
                        error.message || "Delete failed!";
                    this.toast.show();
                }
            } catch (error) {
                console.error("Error deleting item:", error);
                this.toastMessage.innerText = "An error occurred!";
                this.toast.show();
            }
        }
    }
}
document.addEventListener("DOMContentLoaded", function (e) {
    new GoogleSheets();
});
