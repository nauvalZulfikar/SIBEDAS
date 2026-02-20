import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";

class CommonTable {
    init() {
        // this.CommonTableInit();
        this.CommonTableInitWithFetchApi();
    }

    CommonTableInitWithFetchApi() {
        fetch(`${GlobalConfig.apiHost}/api/users`)
            .then((response) => response.json())
            .then((data) => {
                console.log("check log response");
                console.log(data.data);
                new Grid({
                    columns: [
                        {
                            name: "id",
                            formatter: function (cell) {
                                return gridjs.html(
                                    '<span class="fw-semibold">' +
                                        cell +
                                        "</span>"
                                );
                            },
                        },
                        "name",
                        {
                            name: "email",
                            formatter: function (cell) {
                                return gridjs.html(
                                    '<a href="">' + cell + "</a>"
                                );
                            },
                        },
                        "position",
                        "firstname",
                        "lastname",
                        {
                            name: "Actions",
                            width: "120px",
                            formatter: function (cell) {
                                return gridjs.html(`
                <div class="d-flex justify-items-end gap-10">
                  <a href="#" class="text-primary text-decoration-underline me-2">Details</a>
                  <a href="#" class="text-warning text-decoration-underline me-2">Update</a>
                  <a href="#" class="text-danger text-decoration-underline">Delete</a>
                </div>
              `);
                            },
                        },
                    ],
                    pagination: {
                        limit: 10,
                    },
                    sort: true,
                    search: true,
                    data: data,
                }).render(document.getElementById("common-table"));
            })
            .catch((error) => console.error("Error fetching data: " + error));
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new CommonTable().init();
});
