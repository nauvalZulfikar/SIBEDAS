import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";
import Swal from "sweetalert2";

class DataSettings {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        // Initialize immediately
        this.init();
    }

    /**
     * Initialize the DataSettings class
     */
    init() {
        this.initTableDataSettings();
        this.initEvents();
    }

    /**
     * Get API token from meta tag
     * @returns {string|null}
     */
    getApiToken() {
        const tokenMeta = document.querySelector('meta[name="api-token"]');
        return tokenMeta ? tokenMeta.getAttribute("content") : null;
    }

    /**
     * Get authentication headers for API requests
     * @returns {object}
     */
    getAuthHeaders() {
        const token = this.getApiToken();
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        const headers = {
            "Content-Type": "application/json",
            Accept: "application/json",
        };

        if (token) {
            headers["Authorization"] = `Bearer ${token}`;
        }

        if (csrfToken) {
            headers["X-CSRF-TOKEN"] = csrfToken;
        }

        return headers;
    }

    /**
     * Make API request with authentication
     * @param {string} url
     * @param {object} options
     * @returns {Promise}
     */
    async makeApiRequest(url, options = {}) {
        const defaultOptions = {
            headers: this.getAuthHeaders(),
            ...options,
        };

        try {
            const response = await fetch(url, defaultOptions);

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`
                );
            }

            return response;
        } catch (error) {
            console.error("API Request failed:", error);
            throw error;
        }
    }

    initEvents() {
        document.body.addEventListener("click", async (event) => {
            const deleteButton = event.target.closest(
                ".btn-delete-data-settings"
            );
            if (deleteButton) {
                event.preventDefault();
                await this.handleDelete(deleteButton);
            }
        });
    }

    initTableDataSettings() {
        let tableContainer = document.getElementById("table-data-settings");

        tableContainer.innerHTML = "";
        let canUpdate = tableContainer.getAttribute("data-updater") === "1";
        let canDelete = tableContainer.getAttribute("data-destroyer") === "1";
        let menuId = tableContainer.getAttribute("data-menuId");

        // Create a new Grid.js instance
        this.table = new Grid({
            columns: [
                "ID",
                "Key",
                "Value",
                "Created",
                {
                    name: "Actions",
                    width: "120px",
                    formatter: function (cell) {
                        let buttons = "";

                        if (canUpdate) {
                            buttons += `
                                <a href="/data-settings/${cell}/edit?menu_id=${menuId}" class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center">
                                    <i class='bx bx-edit'></i>
                                </a>
                            `;
                        }

                        if (canDelete) {
                            buttons += `
                                <button class="btn btn-sm btn-red d-inline-flex align-items-center justify-content-center btn-delete-data-settings" data-id="${cell}">
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
                limit: 15,
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
                url: `${GlobalConfig.apiHost}/api/data-settings`,
                headers: this.getAuthHeaders(),
                then: (data) =>
                    data.data.map((item) => [
                        item.id,
                        item.key,
                        item.type === "decimal"
                            ? addThousandSeparators(item.value)
                            : item.value,
                        item.created_at,
                        item.id,
                    ]),
                total: (data) => data.meta.total,
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
                const response = await this.makeApiRequest(
                    `${GlobalConfig.apiHost}/api/data-settings/${id}`,
                    { method: "DELETE" }
                );

                const result = await response.json();
                this.toastMessage.innerText =
                    result.message || "Deleted successfully!";
                this.toast.show();

                // Refresh Grid.js table
                if (this.table) {
                    this.table.updateConfig({}).forceRender();
                }
            } catch (error) {
                console.error("Error deleting item:", error);
                this.toastMessage.innerText =
                    error.message || "An error occurred!";
                this.toast.show();
            }
        }
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    new DataSettings();
});
