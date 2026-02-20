import { Grid } from "gridjs";
class QuickSearchDetail {
    init() {
        this.initTablePbgTaskAssignments();
    }

    initTablePbgTaskAssignments() {
        let tableContainer = document.getElementById(
            "table-pbg-task-assignments"
        );

        let url_task_assignments = document.getElementById(
            "url_task_assignments"
        ).value;

        new Grid({
            columns: [
                "ID",
                "Nama",
                "Email",
                "Nomor Telepon",
                "Keahlian",
                "Status",
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
                url: `${url_task_assignments}`,
                then: (data) => {
                    return data.data.map((item) => {
                        const expertiseArray =
                            typeof item.expertise === "string"
                                ? JSON.parse(item.expertise)
                                : item.expertise;

                        return [
                            item.id,
                            item.name,
                            item.email,
                            item.phone_number,
                            Array.isArray(expertiseArray)
                                ? expertiseArray.map((e) => e.name).join(", ")
                                : "-",
                            item.status_name,
                        ];
                    });
                },
                total: (data) => data.meta.total,
            },
        }).render(tableContainer);
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new QuickSearchDetail().init();
});
