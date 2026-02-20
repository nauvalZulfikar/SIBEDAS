import { Grid } from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

class PbgTaskAssignments {
    init() {
        this.initTablePbgTaskAssignments();
        this.handleUpdateData();
        this.initDatePicker();
        this.initIsValidToggle();
    }

    initDatePicker() {
        let element = document.getElementById("datepicker_due_date");
        flatpickr(element, {
            dateFormat: "Y-m-d",
            minDate: new Date(),
        });
    }

    initIsValidToggle() {
        const checkbox = document.getElementById("is_valid");
        const statusText = document.querySelector(".status-text");
        const statusDescription = statusText?.nextElementSibling;

        if (checkbox && statusText) {
            checkbox.addEventListener("change", function () {
                if (this.checked) {
                    statusText.textContent = "Data Valid";
                    if (statusDescription) {
                        statusDescription.textContent =
                            "Data telah diverifikasi dan sesuai";
                    }
                } else {
                    statusText.textContent = "Data Tidak Valid";
                    if (statusDescription) {
                        statusDescription.textContent =
                            "Data perlu diverifikasi atau diperbaiki";
                    }
                }
            });
        }
    }

    initTablePbgTaskAssignments() {
        let tableContainer = document.getElementById(
            "table-pbg-task-assignments"
        );

        let uuid = document.getElementById("uuid").value;

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
                url: `${GlobalConfig.apiHost}/api/task-assignments/${uuid}`,
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
                        item.email,
                        item.phone_number,
                        item.expertise,
                        item.status_name,
                    ]),
                total: (data) => data.meta.total,
            },
        }).render(tableContainer);
    }

    handleUpdateData() {
        const button = document.getElementById("btnUpdatePbgTask");
        const form = document.getElementById("formUpdatePbgTask");
        const toastNotification = document.getElementById("toastNotification");
        const toast = new bootstrap.Toast(toastNotification);
        button.addEventListener("click", function (event) {
            event.preventDefault();
            let submitButton = this;
            let spinner = document.getElementById("spinner");
            submitButton.disabled = true;
            spinner.classList.remove("d-none");

            const formData = new FormData(form);
            const formObject = {};
            formData.forEach((value, key) => {
                formObject[key] = value;
            });

            // Handle checkbox properly - ensure boolean value is sent
            const isValidCheckbox = document.getElementById("is_valid");
            if (isValidCheckbox) {
                formObject["is_valid"] = isValidCheckbox.checked ? 1 : 0;
            }
            fetch(form.action, {
                method: "PUT", // Ensure your Laravel route is set to accept PUT requests
                body: JSON.stringify(formObject), // Convert form data to JSON
                credentials: "include",
                headers: {
                    Authorization: `Bearer ${document
                        .querySelector('meta[name="api-token"]')
                        .getAttribute("content")}`,
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content, // For Laravel security
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        return response.json().then((err) => {
                            throw new Error(
                                err.message || "Something went wrong"
                            );
                        });
                    }
                    return response.json();
                })
                .then((data) => {
                    document.getElementById("toast-message").innerText =
                        data.message;
                    toast.show();
                    submitButton.disabled = false;
                    spinner.classList.add("d-none");
                })
                .catch((error) => {
                    console.error("Error updating task:", error);
                    document.getElementById("toast-message").innerText =
                        error.message;
                    toast.show();
                    submitButton.disabled = false;
                    spinner.classList.add("d-none");
                });
        });
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new PbgTaskAssignments().init();
});
