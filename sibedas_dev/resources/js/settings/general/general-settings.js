import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../../global-config.js";

class SyncronizeTask {
    init() {
        this.initTableGeneralSettings();
    }
    initTableGeneralSettings() {
        const table = new Grid({
            columns: [
                "ID",
                "Key",
                "Value",
                "Description",
                "Created",
                {
                    name: "Actions",
                    width: "120px",
                    formatter: function (cell) {
                        return gridjs.html(`
					  <div class="d-flex justify-items-end gap-10">
						<a href="/settings/general/${cell}/edit" class="btn btn-yellow me-2">Update</a>
						<button class="btn btn-red btn-delete btn-delete-global-settings" data-id="${cell}">Delete</button>
					  </div>
					`);
                    },
                },
            ],
            search: {
                server: {
                    url: (prev, keyword) => `${prev}?search=${keyword}`,
                },
            },
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
            server: {
                url: `${GlobalConfig.apiHost}/api/global-settings`,
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) =>
                    data.data.map((item) => [
                        item.id,
                        item.key,
                        item.value,
                        item.description,
                        item.created_at,
                        item.id,
                    ]),
                total: (data) => data.meta.total,
            },
        });
        table.render(document.getElementById("general-setting-table"));

        document.addEventListener("click", this.handleDelete);
    }
    handleDelete(event) {
        if (event.target.classList.contains("btn-delete-global-settings")) {
            event.preventDefault();
            const id = event.target.getAttribute("data-id");

            if (confirm("Are you sure you want to delete this item?")) {
                fetch(`/settings/general/${id}`, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                        "Content-Type": "application/json",
                    },
                })
                    .then((response) => {
                        if (response.ok) {
                            alert("Item deleted successfully!");
                            window.location.reload();
                        } else {
                            return response.json().then((error) => {
                                throw new Error(
                                    error.message || "Failed to delete item."
                                );
                            });
                        }
                    })
                    .catch((error) => {
                        console.error("Error deleting item:", error);
                        alert("Something went wrong. Please try again.");
                    });
            }
        }
    }
}
document.addEventListener("DOMContentLoaded", function (e) {
    new SyncronizeTask().init();
});
