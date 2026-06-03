import { Grid, html } from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";

class RequestAssignment {
    init() {
        this.initTableRequestAssignment();
    }

    initTableRequestAssignment() {
        new Grid({
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
                { name: "Due Date", width: "7%" },
                {
                    name: "Coordinate",
                    width: "10%",
                    sort: false,
                    formatter: (cell) =>
                        cell && cell.lat != null && cell.lng != null
                            ? html(
                                  `<a href="https://www.google.com/maps?q=${cell.lat},${cell.lng}" target="_blank" rel="noopener">${Number(cell.lat).toFixed(5)}, ${Number(cell.lng).toFixed(5)}</a>`,
                              )
                            : "-",
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
                        item.pbg_task_detail
                            ? {
                                  lat: item.pbg_task_detail.latitude,
                                  lng: item.pbg_task_detail.longitude,
                              }
                            : null,
                    ]),
                total: (data) => data.meta.total,
            },
        }).render(document.getElementById("table-request-assignment"));
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new RequestAssignment().init();
});
