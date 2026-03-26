import { Grid } from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import Swal from "sweetalert2";

class GeneralTable {
    constructor(tableId, apiUrl, baseUrl, columns, options = {}) {
        this.tableId = tableId;
        this.apiUrl = apiUrl;
        this.baseUrl = baseUrl; // Tambahkan base URL
        this.columns = columns;
        this.options = options;
    }

    init() {
        const tableContainer = document.getElementById(this.tableId);

        // Kosongkan container sebelum render ulang
        tableContainer.innerHTML = "";

        const table = new Grid({
            columns: this.columns,
            search: this.options.search || {
                server: {
                    url: (prev, keyword) => `${prev}?search=${keyword}`,
                },
                debounceTimeout: 1000,
            },
            pagination: this.options.pagination || {
                limit: 15,
                server: {
                    url: (prev, page) =>
                        `${prev}${prev.includes("?") ? "&" : "?"}page=${
                            page + 1
                        }`,
                },
            },
            sort: this.options.sort || true,
            server: {
                url: this.apiUrl,
                headers: this.options.headers || {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
                then: (data) => this.processData(data),
                total: (data) => data.meta.total,
            },
        });

        table.render(tableContainer);
        this.handleActions();
    }

    // Memproses data dari API
    processData(data) {
        return data.data.map((item) => {
            return this.columns.map((column) => {
                return item[column] || "";
            });
        });
    }

    handleActions() {
        document.addEventListener("click", (event) => {
            if (event.target && event.target.classList.contains("btn-edit")) {
                this.handleEdit(event);
            } else if (
                event.target &&
                event.target.classList.contains("btn-delete")
            ) {
                this.handleDelete(event);
            } else if (
                event.target &&
                event.target.classList.contains("btn-create")
            ) {
                this.handleCreate(event);
            } else if (
                event.target &&
                event.target.classList.contains("btn-bulk-create")
            ) {
                this.handleBulkCreate(event);
            }
        });
    }

    // Fungsi untuk menangani create
    handleCreate(event) {
        // Menggunakan model dan ID untuk membangun URL dinamis
        const model = event.target.getAttribute("data-model"); // Mengambil model dari data-model
        let menuId = event.target.getAttribute("data-menu");
        window.location.href = `${this.baseUrl}/${model}/create?menu_id=${menuId}`;
    }

    handleBulkCreate(event) {
        // Menggunakan model dan ID untuk membangun URL dinamis
        const model = event.target.getAttribute("data-model");
        let menuId = event.target.getAttribute("data-menu");
        window.location.href = `${this.baseUrl}/${model}/bulk-create?menu_id=${menuId}`;
    }

    // Fungsi untuk menangani edit
    handleEdit(event) {
        const id = event.target.getAttribute("data-id");
        const model = event.target.getAttribute("data-model"); // Mengambil model dari data-model
        console.log("Editing record with ID:", id);
        // Menggunakan model dan ID untuk membangun URL dinamis
        let menuId = event.target.getAttribute("data-menu");
        window.location.href = `${this.baseUrl}/${model}/${id}/edit?menu_id=${menuId}`;
    }

    // Fungsi untuk menangani delete
    handleDelete(event) {
        const id = event.target.getAttribute("data-id");
        console.log(id);
        // if (confirm("Are you sure you want to delete this item?")) {
        //     this.deleteRecord(id);
        // }
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {
            if (result.isConfirmed) {
                this.deleteRecord(id);
                Swal.fire({
                    title: "Deleted!",
                    text: "Your record has been deleted.",
                    icon: "success",
                    showConfirmButton: false, // Menghilangkan tombol OK
                    timer: 2000, // Menutup otomatis dalam 2 detik (opsional)
                });
            }
        });
    }

    async deleteRecord(id) {
        try {
            console.log(id);
            const response = await fetch(`${this.apiUrl}/${id}`, {
                // Menambahkan model dalam URL
                method: "DELETE",
                headers: this.options.headers || {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                },
            });
            if (response.status === 204) {
                location.reload();
            } else {
                const data = await response.json();
                showErrorAlert(
                    `Failed to delete record: ${
                        data.message || "Unknown error"
                    }`
                );
            }
        } catch (error) {
            console.error("Error deleting data:", error);
            showErrorAlert("Error deleting data. Please try again.");
        }
    }
}

// Fungsi untuk menampilkan alert
function showErrorAlert(message) {
    const alertContainer = document.getElementById("alert-container");

    alertContainer.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}

export default GeneralTable;
