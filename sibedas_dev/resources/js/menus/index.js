import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";
import Swal from "sweetalert2";

class Menus {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        // Initialize functions
        this.initTableMenus();
        this.initEvents();
    }
    initEvents() {
        document.body.addEventListener("click", async (event) => {
            const deleteButton = event.target.closest(".btn-delete-menu");
            if (deleteButton) {
                event.preventDefault();
                await this.handleDelete(deleteButton);
            }
        });
    }

    initTableMenus() {
        let tableContainer = document.getElementById("table-menus");

        tableContainer.innerHTML = "";
        let canUpdate = tableContainer.getAttribute("data-updater") === "1";
        let canDelete = tableContainer.getAttribute("data-destroyer") === "1";
        let menuId = tableContainer.getAttribute("data-menuId");

        this.table = new Grid({
            columns: [
                "ID",
                "Name",
                "Url",
                "Icon",
                "Parent Name",
                "Sort Order",
                {
                    name: "Action",
                    formatter: (cell) => {
                        let buttons = `<div class="d-flex justify-content-center align-items-center gap-2">`;

                        if (canUpdate) {
                            buttons += `
                                <a href="/menus/${cell}/edit?menu_id=${menuId}" class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center">
                                    <i class='bx bx-edit'></i>
                                </a>
                            `;
                        }

                        if (canDelete) {
                            buttons += `
                                <button data-id="${cell}" class="btn btn-red btn-sm btn-delete-menu d-inline-flex align-items-center justify-content-center">
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
                url: `${GlobalConfig.apiHost}/api/menus`,
                credentials: "include",
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) => {
                    console.log("Full API Response:", data); // Log the full response

                    return data.data.map((item, index) => {
                        console.log(`Item ${index + 1}:`, item); // Log each item
                        return [
                            item.id,
                            item.name,
                            item.url,
                            item.icon,
                            item.parent?.name,
                            item.sort_order,
                            item.id,
                        ];
                    });
                },
                total: (data) => data.total,
            },
        }).render(tableContainer);
    }

    async handleDelete(button) {
        const id = button.getAttribute("data-id");

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
                    `${GlobalConfig.apiHost}/api/menus/${id}`,
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
    new Menus();
});
