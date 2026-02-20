import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../../global-config.js";

class SyncronizeTask {
    constructor() {
        this.toastElement = document.getElementById("toastNotification");
        this.toastMessage = document.getElementById("toast-message");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;
    }
    init() {
        this.initTableImportDatasources();
        this.handleSubmitSync();
    }
    initTableImportDatasources() {
        let tableContainer = document.getElementById(
            "table-import-datasources"
        );
        this.table = new gridjs.Grid({
            columns: [
                "ID",
                "Message",
                "Status",
                "Started",
                "Duration",
                "Finished",
                "Created",
                {
                    name: "Action",
                    formatter: (cell) => {
                        if (
                            cell.status === "failed" &&
                            cell.failed_uuid !== null
                        ) {
                            return gridjs.html(`
                                <button data-id="${cell.id}" class="btn btn-sm btn-warning d-flex align-items-center gap-1 btn-retry">
                                    <iconify-icon icon="mingcute:refresh-3-line" width="15" height="15"></iconify-icon>
                                    <span>Retry</span>
                                </button>
                            `);
                        }
                    },
                },
            ],
            search: {
                server: {
                    url: (prev, keyword) => `${prev}?search=${keyword}`,
                },
            },
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
            server: {
                url: `${GlobalConfig.apiHost}/api/import-datasource`,
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) =>
                    data.data.map((item) => [
                        item.id,
                        item.message,
                        item.status,
                        item.start_time,
                        item.duration,
                        item.finish_time,
                        item.created_at,
                        item,
                    ]),
                total: (data) => data.meta.total,
            },
        }).render(tableContainer);

        tableContainer.addEventListener("click", (event) => {
            let btn = event.target.closest(".btn-retry");
            if (btn) {
                const id = btn.getAttribute("data-id");
                btn.disabled = true;
                this.handleRetrySync(id, btn);
            }
        });
    }
    handleSubmitSync() {
        const button = document.getElementById("btn-sync-submit");
        const spinner = document.getElementById("spinner");
        const apiToken = document
            .querySelector('meta[name="api-token"]')
            .getAttribute("content");

        // Show the spinner while checking
        spinner.classList.remove("d-none");

        fetch(
            `${GlobalConfig.apiHost}/api/import-datasource/check-datasource`,
            {
                method: "GET",
                headers: {
                    Authorization: `Bearer ${apiToken}`,
                    "Content-Type": "application/json",
                },
            }
        )
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.json();
            })
            .then((data) => {
                button.disabled = !data.can_execute;

                if (!data.can_execute) {
                    // Keep spinner visible if cannot execute
                    spinner.classList.remove("d-none");
                } else {
                    // Hide spinner when execution is allowed
                    spinner.classList.add("d-none");

                    // Remove previous event listener before adding a new one
                    button.removeEventListener("click", this.handleSyncClick);
                    button.addEventListener(
                        "click",
                        this.handleSyncClick.bind(this)
                    );
                }
            })
            .catch((err) => {
                console.error("Fetch error:", err);
                alert("An error occurred while checking the datasource");
            });
    }

    handleRetrySync(id, btn) {
        const apiToken = document
            .querySelector('meta[name="api-token"]')
            .getAttribute("content");

        fetch(`${GlobalConfig.apiHost}/api/retry-scraping/${id}`, {
            method: "GET",
            headers: {
                Authorization: `Bearer ${apiToken}`,
                "Content-Type": "application/json",
            },
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("API Response:", data); // Debugging

                // Show success message
                const message =
                    data?.data?.message ||
                    data?.message ||
                    "Synchronization successful!";
                this.toastMessage.innerText = message;
                this.toast.show();
            })
            .catch((err) => {
                console.error("Fetch error:", err);

                // Show error message
                this.toastMessage.innerText =
                    err.message ||
                    "Failed to synchronize, something went wrong!";
                this.toast.show();

                // Re-enable button on failure
                btn.disabled = false;
            });
    }
    handleSyncClick() {
        const button = document.getElementById("btn-sync-submit");
        const spinner = document.getElementById("spinner");
        const apiToken = document
            .querySelector('meta[name="api-token"]')
            .getAttribute("content");

        button.disabled = true; // Prevent multiple clicks
        spinner.classList.remove("d-none"); // Show spinner during sync

        fetch(`${GlobalConfig.apiHost}/api/scraping`, {
            method: "GET",
            headers: {
                Authorization: `Bearer ${apiToken}`,
                "Content-Type": "application/json",
            },
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                let data;
                try {
                    data = await response.json();
                } catch (jsonError) {
                    throw new Error("Failed to parse JSON response");
                }

                return data;
            })
            .then((data) => {
                this.toastMessage.innerText =
                    data?.data?.message ||
                    data?.message ||
                    "Synchronize successfully!";
                this.toast.show();

                // Update the table if it exists
                if (this.table) {
                    this.table.updateConfig({}).forceRender();
                }
            })
            .catch((err) => {
                console.error("Fetch error:", err);
                this.toastMessage.innerText =
                    err.message || "Failed to syncronize something wrong!";
                this.toast.show();
                button.disabled = false;
                spinner.classList.add("d-none");
            });
    }
}
document.addEventListener("DOMContentLoaded", function (e) {
    new SyncronizeTask().init();
});
