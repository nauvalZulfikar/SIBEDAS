import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";
import Swal from "sweetalert2";

class BusinessIndustries {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        // Initialize functions
        this.initTableBusinessIndustries();
        this.initEvents();
    }
    initEvents() {
        document.body.addEventListener("click", async (event) => {
            const deleteButton = event.target.closest(
                ".btn-delete-business-industry"
            );
            if (deleteButton) {
                event.preventDefault();
                await this.handleDelete(deleteButton);
            }
        });
    }

    initTableBusinessIndustries() {
        let tableContainer = document.getElementById(
            "table-business-industries"
        );

        tableContainer.innerHTML = "";
        let canUpdate = tableContainer.getAttribute("data-updater") === "1";
        let canDelete = tableContainer.getAttribute("data-destroyer") === "1";
        let menuId = tableContainer.getAttribute("data-menuId");

        // Create a new Grid.js instance only if it doesn't exist
        this.table = new Grid({
            columns: [
                { name: "ID", width: "80px", hidden: false },
                { name: "Nama Kecamatan", width: "200px" },
                { name: "Nama Kelurahan", width: "200px" },
                { name: "NOP", width: "150px" },
                { name: "Nama Wajib Pajak", width: "250px" },
                { name: "Alamat Wajib Pajak", width: "300px" },
                { name: "Alamat Objek Pajak", width: "300px" },
                { name: "Luas Bumi", width: "150px" },
                { name: "Luas Bangunan", width: "150px" },
                { name: "NJOP Bumi", width: "150px" },
                { name: "NJOP Bangunan", width: "150px" },
                { name: "Ketetapan", width: "150px" },
                { name: "Tahun Pajak", width: "120px" },
                { name: "Created", width: "180px" },
                {
                    name: "Action",
                    formatter: (cell) => {
                        let buttons = `<div class="d-flex justify-content-center gap-2">`;

                        if (canUpdate) {
                            buttons += `
                                <a href="/data/business-industries/${cell}/edit?menu_id=${menuId}" class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center">
                                    <i class='bx bx-edit'></i>
                                </a>
                            `;
                        }

                        if (canDelete) {
                            buttons += `
                                <button data-id="${cell}" class="btn btn-sm btn-red btn-delete-business-industry d-inline-flex align-items-center justify-content-center">
                                    <i class='bx bxs-trash'></i>
                                </button>
                            `;
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
                url: `${GlobalConfig.apiHost}/api/api-business-industries`,
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) =>
                    data.data.map((item) => {
                        return [
                            item.id,
                            item.nama_kecamatan,
                            item.nama_kelurahan,
                            item.nop,
                            item.nama_wajib_pajak,
                            item.alamat_wajib_pajak,
                            item.alamat_objek_pajak,
                            addThousandSeparators(item.luas_bumi),
                            addThousandSeparators(item.luas_bangunan),
                            addThousandSeparators(item.njop_bumi),
                            addThousandSeparators(item.njop_bangunan),
                            addThousandSeparators(item.ketetapan),
                            item.tahun_pajak,
                            item.created_at,
                            item.id, // ID for Actions column
                        ];
                    }),
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
                    `${GlobalConfig.apiHost}/api/api-business-industries/${id}`,
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
    new BusinessIndustries();
});
