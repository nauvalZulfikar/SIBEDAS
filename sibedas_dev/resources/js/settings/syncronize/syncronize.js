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
        this.pollInterval = null;
    }

    getApiToken() {
        return document
            .querySelector('meta[name="api-token"]')
            .getAttribute("content");
    }

    getHeaders() {
        return {
            Authorization: `Bearer ${this.getApiToken()}`,
            "Content-Type": "application/json",
        };
    }

    init() {
        this.initTableImportDatasources();
        this.handleSubmitSync();
        this.startPolling();
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
                    width: "220px",
                    formatter: (cell) => {
                        let buttons = "";

                        if (cell.status === "processing") {
                            buttons = `
                                <div class="d-flex gap-1">
                                    <button data-id="${cell.id}" data-action="pause" class="btn btn-sm btn-warning d-flex align-items-center gap-1 btn-action">
                                        <iconify-icon icon="mingcute:pause-line" width="15" height="15"></iconify-icon>
                                        <span>Pause</span>
                                    </button>
                                    <button data-id="${cell.id}" data-action="cancel" class="btn btn-sm btn-danger d-flex align-items-center gap-1 btn-action">
                                        <iconify-icon icon="mingcute:close-line" width="15" height="15"></iconify-icon>
                                        <span>Cancel</span>
                                    </button>
                                </div>
                            `;
                        } else if (cell.status === "paused") {
                            buttons = `
                                <div class="d-flex gap-1">
                                    <button data-id="${cell.id}" data-action="resume" class="btn btn-sm btn-info d-flex align-items-center gap-1 btn-action">
                                        <iconify-icon icon="mingcute:play-line" width="15" height="15"></iconify-icon>
                                        <span>Resume</span>
                                    </button>
                                    <button data-id="${cell.id}" data-action="cancel" class="btn btn-sm btn-danger d-flex align-items-center gap-1 btn-action">
                                        <iconify-icon icon="mingcute:close-line" width="15" height="15"></iconify-icon>
                                        <span>Cancel</span>
                                    </button>
                                </div>
                            `;
                        } else if (
                            cell.status === "failed" &&
                            cell.failed_uuid !== null
                        ) {
                            buttons = `
                                <button data-id="${cell.id}" class="btn btn-sm btn-warning d-flex align-items-center gap-1 btn-retry">
                                    <iconify-icon icon="mingcute:refresh-3-line" width="15" height="15"></iconify-icon>
                                    <span>Retry</span>
                                </button>
                            `;
                        }

                        return gridjs.html(buttons);
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
                    Authorization: `Bearer ${this.getApiToken()}`,
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
            let btn = event.target.closest(".btn-action");
            if (btn) {
                const id = btn.getAttribute("data-id");
                const action = btn.getAttribute("data-action");
                btn.disabled = true;

                if (action === "pause") {
                    this.handleAction(id, "pause", btn);
                } else if (action === "resume") {
                    this.handleAction(id, "resume", btn);
                } else if (action === "cancel") {
                    if (confirm("Are you sure you want to cancel this scraping job?")) {
                        this.handleAction(id, "cancel", btn);
                    } else {
                        btn.disabled = false;
                    }
                }
                return;
            }

            let retryBtn = event.target.closest(".btn-retry");
            if (retryBtn) {
                const id = retryBtn.getAttribute("data-id");
                retryBtn.disabled = true;
                this.handleRetrySync(id, retryBtn);
            }
        });
    }

    handleAction(id, action, btn) {
        fetch(`${GlobalConfig.apiHost}/api/scraping/${id}/${action}`, {
            method: "POST",
            headers: this.getHeaders(),
        })
            .then((r) => r.json())
            .then((data) => {
                const message =
                    data?.data?.message || data?.message || `Scraping ${action}d`;
                this.toastMessage.innerText = message;
                this.toast.show();
                this.refreshTable();
            })
            .catch((err) => {
                console.error(`${action} error:`, err);
                this.toastMessage.innerText = `Failed to ${action}`;
                this.toast.show();
                btn.disabled = false;
            });
    }

    handleSubmitSync() {
        const button = document.getElementById("btn-sync-submit");
        const spinner = document.getElementById("spinner");

        // Check if can execute
        fetch(
            `${GlobalConfig.apiHost}/api/import-datasource/check-datasource`,
            { method: "GET", headers: this.getHeaders() }
        )
            .then((r) => r.json())
            .then((data) => {
                button.disabled = !data.can_execute;
                if (!data.can_execute) {
                    spinner.classList.remove("d-none");
                } else {
                    spinner.classList.add("d-none");
                    button.removeEventListener("click", this.handleSyncClick);
                    button.addEventListener(
                        "click",
                        this.handleSyncClick.bind(this)
                    );
                }
            })
            .catch((err) => {
                console.error("Fetch error:", err);
            });
    }

    handleSyncClick() {
        const button = document.getElementById("btn-sync-submit");
        const spinner = document.getElementById("spinner");

        button.disabled = true;
        spinner.classList.remove("d-none");

        fetch(`${GlobalConfig.apiHost}/api/scraping`, {
            method: "GET",
            headers: this.getHeaders(),
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                this.toastMessage.innerText =
                    data?.data?.message ||
                    data?.message ||
                    "Synchronize started!";
                this.toast.show();
                spinner.classList.add("d-none");

                // Refresh table to show the new row with Pause/Cancel buttons
                this.refreshTable();
            })
            .catch((err) => {
                console.error("Fetch error:", err);
                this.toastMessage.innerText =
                    err.message || "Failed to start synchronization!";
                this.toast.show();
                button.disabled = false;
                spinner.classList.add("d-none");
            });
    }

    handleRetrySync(id, btn) {
        fetch(`${GlobalConfig.apiHost}/api/retry-scraping/${id}`, {
            method: "GET",
            headers: this.getHeaders(),
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                const message =
                    data?.data?.message ||
                    data?.message ||
                    "Synchronization retrying!";
                this.toastMessage.innerText = message;
                this.toast.show();
                this.refreshTable();
            })
            .catch((err) => {
                console.error("Fetch error:", err);
                this.toastMessage.innerText =
                    err.message ||
                    "Failed to synchronize, something went wrong!";
                this.toast.show();
                btn.disabled = false;
            });
    }

    refreshTable() {
        if (this.table) {
            this.table.updateConfig({}).forceRender();
        }
        // Also re-check if Sync button should be enabled/disabled
        this.handleSubmitSync();
    }

    startPolling() {
        this.pollInterval = setInterval(() => {
            this.refreshTable();
        }, 10000);
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new SyncronizeTask().init();
});
