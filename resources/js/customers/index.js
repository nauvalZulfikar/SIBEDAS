import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";
import Swal from "sweetalert2";

class Customers {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        // Initialize functions
        this.initTableCustomers();
        this.initEvents();
    }
    initEvents() {
        document.body.addEventListener("click", async (event) => {
            const deleteButton = event.target.closest(".btn-delete-customers");
            if (deleteButton) {
                event.preventDefault();
                await this.handleDelete(deleteButton);
            }
        });
    }

    initTableCustomers() {
        let tableContainer = document.getElementById("table-customers");
        // Create a new Grid.js instance only if it doesn't exist

        tableContainer.innerHTML = "";
        let canUpdate = tableContainer.getAttribute("data-updater") === "1";
        let canDelete = tableContainer.getAttribute("data-destroyer") === "1";
        let menuId = tableContainer.getAttribute("data-menuId");
        this.table = new Grid({
            columns: [
                "ID",
                "Nomor Pelanggan",
                "Nama",
                "Kota Pelayanan",
                "Alamat",
                "Latitude",
                "Longitude",
                {
                    name: "Action",
                    formatter: (cell) => {
                        let buttons = "";

                        if (canUpdate) {
                            buttons += `
                                <a href="/data/customers/${cell}/edit?menu_id=${menuId}" class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center">
                                    <i class='bx bx-edit'></i>
                                </a>
                            `;
                        }

                        if (canDelete) {
                            buttons += `
                                <button data-id="${cell}" class="btn btn-sm btn-red btn-delete-customers d-inline-flex align-items-center justify-content-center">
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
                url: `${GlobalConfig.apiHost}/api/customers`,
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) =>
                    data.data.map((item) => [
                        item.id,
                        item.nomor_pelanggan,
                        item.nama,
                        item.kota_pelayanan,
                        item.alamat,
                        item.latitude,
                        item.longitude,
                        item.id,
                    ]),
                total: (data) => data.meta.total,
            },
        }).render(tableContainer);
    }

    handleSearch() {}

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
                    `${GlobalConfig.apiHost}/api/customers/${id}`,
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
    new Customers();
});
