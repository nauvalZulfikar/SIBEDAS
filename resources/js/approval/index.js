import { Grid } from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";

class Approval {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;
        this.initTableApproval();
    }
    initTableApproval() {
        let tableContainer = document.getElementById("table-approvals");
        this.table = new Grid({
            columns: [
                "ID",
                { name: "Name", width: "15%" },
                { name: "Condition", width: "7%" },
                "Registration Number",
                "Document Number",
                { name: "Address", width: "30%" },
                "Status",
                "Function Type",
                "Consultation Type",
                { name: "Due Date", width: "10%" },
                {
                    name: "Action",
                    formatter: (cell) => {
                        return gridjs.html(`
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                  <button class="btn btn-sm btn-success approve-btn" data-id="${cell}">
                                    Approve
                                  </button>
                                  <button class="btn btn-sm btn-danger reject-btn" data-id="${cell}">
                                    Reject
                                  </button>
                                </div>
                            `);
                    },
                },
            ],
            search: {
                server: {
                    url: (prev, keyword) => `${prev}?search=${keyword}`,
                },
                debounceTimeout: 1000,
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
                url: `${GlobalConfig.apiHost}/api/request-assignments`,
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
                        item.condition,
                        item.registration_number,
                        item.document_number,
                        item.address,
                        item.status_name,
                        item.function_type,
                        item.consultation_type,
                        item.due_date,
                        item.id,
                    ]),
                total: (data) => data.meta.total,
            },
        }).render(tableContainer);
    }
}
document.addEventListener("DOMContentLoaded", function (e) {
    new Approval();
});
