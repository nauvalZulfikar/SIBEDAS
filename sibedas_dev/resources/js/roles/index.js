import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";
import Swal from "sweetalert2";

class Roles {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        // Initialize functions
        this.initTableRoles();
        this.initEvents();
    }
    initEvents() {
        document.body.addEventListener("click", async (event) => {
            const deleteButton = event.target.closest(".btn-delete-role");
            if (deleteButton) {
                event.preventDefault();
                await this.handleDelete(deleteButton);
            }
        });
    }

    initTableRoles() {
        let tableContainer = document.getElementById("table-roles");

        tableContainer.innerHTML = "";
        let canUpdate = tableContainer.getAttribute("data-updater") === "1";
        let canDelete = tableContainer.getAttribute("data-destroyer") === "1";
        let menuId = tableContainer.getAttribute("data-menuId");
        // Create a new Grid.js instance only if it doesn't exist
        this.table = new gridjs.Grid({
            columns: [
                "ID",
                "Name",
                "Description",
                {
                    name: "Action",
                    formatter: (cell) => {
                        let buttons = `<div class="d-flex justify-content-center gap-2">`;

                        if (canUpdate) {
                            buttons += `
                            <a href="/roles/${cell}/edit?menu_id=${menuId}" class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center">
                                <i class='bx bx-edit'></i>
                            </a>
                            <a href="/roles/role-menu/${cell}?menu_id=${menuId}" class="btn btn-primary btn-sm d-inline-flex align-items-center justify-content-center">
                                Role Menu
                            </a>
                        `;
                        }

                        if (canDelete) {
                            buttons += `
                            <button data-id="${cell}" class="btn btn-sm btn-red btn-delete-role d-none d-inline-flex align-items-center justify-content-center">
                                <i class='bx bxs-trash'></i>
                            </button>
                        `;
                        }

                        if (!canUpdate && !canDelete) {
                            buttons += `<span class="text-muted">No Privilege</span>`;
                        }

                        buttons += `</div>`;

                        return gridjs.html(buttons);
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
                url: `${GlobalConfig.apiHost}/api/roles`,
                credentials: "include",
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) =>
                    data.data.map((item) => [
                        item.id,
                        item.name,
                        item.description,
                        item.id,
                    ]),
                total: (data) => data.total,
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
                    `${GlobalConfig.apiHost}/api/roles/${id}`,
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
    new Roles();
});
