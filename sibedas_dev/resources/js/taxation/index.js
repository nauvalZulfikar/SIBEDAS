import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";
import { addThousandSeparators } from "../global-config";
import Swal from "sweetalert2";

class Taxation {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        this.initTableTaxation();
        this.initEvents();
        this.handleExportToExcel();
    }

    handleExportToExcel() {
        const button = document.getElementById("btn-export-excel");
        if (!button) {
            console.error("Button not found: #btn-export-excel");
            return;
        }

        const exportUrl = button.getAttribute("data-url");

        button.addEventListener("click", function () {
            button.disabled = true;

            fetch(exportUrl, {
                method: "GET",
                credentials: "include",
                headers: {
                    Authorization:
                        "Bearer " +
                        document
                            .querySelector('meta[name="api-token"]')
                            .getAttribute("content"),
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error(
                            "Error fetching data: " + response.statusText
                        );
                    }
                    return response.blob();
                })
                .then(function (blob) {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = "laporan-rekap-data-pembayaran.xlsx";
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                })
                .catch(function (error) {
                    console.error("Error fetching data:", error);
                })
                .finally(function () {
                    button.disabled = false;
                });
        });
    }

    initEvents() {
        document.body.addEventListener("click", (event) => {
            const deleteButton = event.target.closest(".btn-delete-taxation");
            if (deleteButton) {
                event.preventDefault();
                this.handleDelete(deleteButton);
            }
        });
    }

    handleDelete(deleteButton) {
        const id = deleteButton.getAttribute("data-id");

        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${GlobalConfig.apiHost}/api/taxs/${id}`, {
                    method: "DELETE",
                    credentials: "include",
                    headers: {
                        Authorization: `Bearer ${document
                            .querySelector('meta[name="api-token"]')
                            .getAttribute("content")}`,
                        "Content-Type": "application/json",
                    },
                })
                    .then((response) => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            return response.json().then((error) => {
                                throw new Error(
                                    error.message || "Delete failed!"
                                );
                            });
                        }
                    })
                    .then((result) => {
                        this.toastMessage.innerText =
                            result.message || "Data deleted successfully!";
                        this.toast.show();

                        if (typeof this.table !== "undefined") {
                            this.table.updateConfig({}).forceRender();
                        }
                    })
                    .catch((error) => {
                        console.error("Error deleting item:", error);
                        this.toastMessage.innerText =
                            error.message || "An error occurred!";
                        this.toast.show();
                    });
            }
        });
    }

    initTableTaxation() {
        let tableContainer = document.getElementById("table-taxation");

        if (!tableContainer) {
            console.error("Table container not found!");
            return;
        }

        // Clear previous table content
        tableContainer.innerHTML = "";

        let canUpdate = tableContainer.getAttribute("data-updater") === "1";
        let canDelete = tableContainer.getAttribute("data-destroyer") === "1";
        let menuId = tableContainer.getAttribute("data-menuId");

        this.table = new Grid({
            columns: [
                "ID",
                { name: "Tax No" },
                { name: "Tax Code" },
                { name: "WP Name" },
                { name: "Business Name" },
                { name: "Address" },
                { name: "Start Validity" },
                { name: "End Validity" },
                { name: "Tax Value" },
                { name: "Subdistrict" },
                { name: "Village" },
                {
                    name: "Action",
                    formatter: (cell) => {
                        let buttons = "";
                        if (canUpdate) {
                            buttons += `
                                <a href="/tax/${cell}/edit?menu_id=${menuId}" class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center">
                                    <i class='bx bx-edit'></i>
                                </a>
                            `;
                        }
                        if (canDelete) {
                            buttons += `
                                <button data-id="${cell}" class="btn btn-sm btn-red btn-delete-taxation d-inline-flex align-items-center justify-content-center">
                                    <i class='bx bxs-trash'></i>
                                </button>
                            `;
                        }
                        if (!canUpdate && !canDelete) {
                            buttons = `<span class="text-muted">No Privilege</span>`;
                        }

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
                url: `${GlobalConfig.apiHost}/api/taxs`,
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
                        return [
                            item.id,
                            item.tax_no,
                            item.tax_code,
                            item.wp_name,
                            item.business_name,
                            item.address,
                            item.start_validity,
                            item.end_validity,
                            addThousandSeparators(item.tax_value),
                            item.subdistrict,
                            item.village,
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
}

document.addEventListener("DOMContentLoaded", function (e) {
    new Taxation();
});
