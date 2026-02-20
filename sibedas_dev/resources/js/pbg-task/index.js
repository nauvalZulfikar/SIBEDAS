import { Grid, html } from "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";
import { Dropzone } from "dropzone";
import { addThousandSeparators } from "../global-config";

Dropzone.autoDiscover = false;

class PbgTasks {
    constructor() {
        this.table = null;
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
    }

    init() {
        this.setupFileUploadModal({
            modalId: "modalBuktiBayar",
            dropzoneId: "dropzoneBuktiBayar",
            uploadBtnClass: "upload-btn-bukti-bayar",
            removeBtnId: "removeFileBtnBuktiBayar",
            submitBtnId: "submitBuktiBayar",
            fileNameSpanId: "uploadedFileNameBuktiBayar",
            fileInfoId: "fileInfoBuktiBayar",
            pbgType: "bukti_bayar",
            bindFlag: "uploadHandlerBoundBuktiBayar",
        });

        this.setupFileUploadModal({
            modalId: "modalBeritaAcara",
            dropzoneId: "dropzoneBeritaAcara",
            uploadBtnClass: "upload-btn-berita-acara",
            removeBtnId: "removeFileBtnBeritaAcara",
            submitBtnId: "submitBeritaAcara",
            fileNameSpanId: "uploadedFileNameBeritaAcara",
            fileInfoId: "fileInfoBeritaAcara",
            pbgType: "berita_acara",
            bindFlag: "uploadHandlerBoundBeritaAcara",
        });
        this.handleFilterDatatable();
        this.handleSendNotification();
    }

    handleFilterDatatable() {
        const form = document.getElementById("filter-form");
        const filterSelect = document.getElementById("filter-select");
        const yearSelect = document.getElementById("year-select");
        const resetBtn = document.getElementById("reset-filter");

        const urlParams = new URLSearchParams(window.location.search);
        const initialFilter = urlParams.get("filter") || "";
        const initialYear = urlParams.get("year") || "2025"; // Default to 2025

        // Set initial year if not in URL
        if (!urlParams.get("year")) {
            yearSelect.value = "2025";
        }

        this.initTableRequestAssignment(initialFilter, initialYear); // Initial load with query params

        form.addEventListener("submit", (e) => {
            e.preventDefault();

            const selectedFilter = filterSelect.value;
            const selectedYear = yearSelect.value;

            const params = new URLSearchParams(window.location.search);
            params.set("filter", selectedFilter);
            params.set("year", selectedYear);

            // Update the URL without reloading
            window.history.replaceState(
                {},
                "",
                `${location.pathname}?${params}`
            );

            // Call the method again with the selected filter and year
            this.initTableRequestAssignment(selectedFilter, selectedYear);
        });

        // Handle reset button
        resetBtn.addEventListener("click", (e) => {
            e.preventDefault();

            // Reset form values
            filterSelect.value = "";
            yearSelect.value = "2025";

            // Clear URL parameters
            window.history.replaceState({}, "", location.pathname);

            // Reload table with default values
            this.initTableRequestAssignment("", "2025");
        });
    }

    initTableRequestAssignment(filterValue = "", yearValue = "2025") {
        const urlBase = `${GlobalConfig.apiHost}/api/request-assignments`;

        // Ambil token
        const token = document
            .querySelector('meta[name="api-token"]')
            .getAttribute("content");

        const config = {
            columns: [
                "ID",
                { name: "Nama Pemohon" },
                { name: "Nama Pemilik" },
                { name: "Kondisi" },
                "Nomor Registrasi",
                "Nomor Dokumen",
                { name: "Alamat" },
                "Status",
                "Jenis Fungsi",
                { name: "Nama Bangunan" },
                "Jenis Konsultasi",
                { name: "Tanggal Jatuh Tempo" },
                {
                    name: "Retribusi",
                },
                {
                    name: "Catatan Kekurangan Dokumen",
                },
                {
                    name: "Aksi",
                    formatter: (cell) => {
                        let canUpdate =
                            tableContainer.getAttribute("data-updater") === "1";

                        if (!canUpdate) {
                            return html(
                                `<span class="text-muted">No Privilege</span>`
                            );
                        }

                        return html(`
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <a href="/pbg-task/${cell.id}" 
                            class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center"
                            style="white-space: nowrap; line-height: 1;">
                                <iconify-icon icon="mingcute:eye-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon>
                            </a>

                            ${
                                cell.attachment_berita_acara
                                    ? `
                                        <a href="/pbg-task-attachment/${cell.attachment_berita_acara.id}?type=berita-acara"
                                            class="btn btn-success btn-sm d-inline-flex align-items-center justify-content-center"
                                            style="white-space: nowrap; line-height: 1;"
                                            target="_blank">
                                            <iconify-icon icon="mingcute:eye-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon>
                                            <span class="ms-1">Berita Acara</span>
                                        </a>
                                    `
                                    : `
                                        <button class="btn btn-sm btn-info d-inline-flex align-items-center justify-content-center upload-btn-berita-acara"
                                                data-id="${cell.id}"
                                                style="white-space: nowrap; line-height: 1;">
                                            <iconify-icon icon="mingcute:upload-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon>
                                            <span class="ms-1" style="line-height: 1;">Berita Acara</span>
                                        </button>
                                    `
                            }

                            ${
                                cell.attachment_bukti_bayar
                                    ? `
                                        <a href="/pbg-task-attachment/${cell.attachment_bukti_bayar.id}?type=bukti-bayar"
                                            class="btn btn-success btn-sm d-inline-flex align-items-center justify-content-center"
                                            style="white-space: nowrap; line-height: 1;"
                                            target="_blank">
                                            <iconify-icon icon="mingcute:eye-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon>
                                            <span class="ms-1">Bukti Bayar</span>
                                        </a>
                                    `
                                    : `
                                        <button class="btn btn-sm btn-info d-inline-flex align-items-center justify-content-center upload-btn-bukti-bayar"
                                                data-id="${cell.id}"
                                                style="white-space: nowrap; line-height: 1;">
                                            <iconify-icon icon="mingcute:upload-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon>
                                            <span class="ms-1" style="line-height: 1;">Bukti Bayar</span>
                                        </button>
                                    `
                            }
                        </div>
                    `);
                    },
                },
            ],
            search: {
                server: {
                    url: (prev, keyword) =>
                        `${prev}${
                            prev.includes("?") ? "&" : "?"
                        }search=${keyword}`,
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
                url: `${urlBase}?filter=${filterValue}&year=${yearValue}`,
                credentials: "include",
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json",
                },
                then: (data) =>
                    data.data.map((item) => {
                        return [
                            item.id,
                            item.name,
                            item.owner_name,
                            item.condition,
                            item.registration_number,
                            item.document_number || "-",
                            item.address,
                            item.status_name,
                            item.function_type,
                            item.pbg_task_detail
                                ? item.pbg_task_detail.name_building
                                : "-",
                            item.consultation_type,
                            item.due_date,
                            item.pbg_task_retributions
                                ? addThousandSeparators(
                                      item.pbg_task_retributions
                                          .nilai_retribusi_bangunan
                                  )
                                : "-",
                            item.pbg_status ? item.pbg_status.note : "-",
                            item,
                        ];
                    }),
                total: (data) => data.meta.total,
            },
        };

        const tableContainer = document.getElementById("table-pbg-tasks");

        if (this.table) {
            this.table.updateConfig(config).forceRender();
        } else {
            tableContainer.innerHTML = "";
            this.table = new Grid(config).render(tableContainer);
        }
    }

    handleSendNotification() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);

        document
            .getElementById("sendNotificationBtn")
            .addEventListener("click", () => {
                let notificationStatus =
                    document.getElementById("notificationStatus").value;

                // Show success toast
                this.toastMessage.innerText = "Notifikasi berhasil dikirim!";
                this.toast.show();

                // Close modal after sending
                let modal = bootstrap.Modal.getInstance(
                    document.getElementById("sendNotificationModal")
                );
                modal.hide();
            });
    }

    setupFileUploadModal({
        modalId,
        dropzoneId,
        uploadBtnClass,
        removeBtnId,
        submitBtnId,
        fileNameSpanId,
        fileInfoId,
        pbgType,
        bindFlag,
    }) {
        const modalEl = document.getElementById(modalId);
        const modalInstance = new bootstrap.Modal(modalEl);
        let taskId;

        // Blur-fix for modal
        modalEl.addEventListener("hide.bs.modal", () => {
            if (
                document.activeElement &&
                modalEl.contains(document.activeElement)
            ) {
                document.activeElement.blur();
                setTimeout(() => document.body.focus(), 10);
            }
        });

        // Bind click listener only once
        if (!window[bindFlag]) {
            document.addEventListener("click", (e) => {
                const btn = e.target.closest(`.${uploadBtnClass}`);
                if (btn) {
                    taskId = btn.getAttribute("data-id");
                    modalInstance.show();
                }
            });
            window[bindFlag] = true;
        }

        // Avoid reinitializing Dropzone
        if (!Dropzone.instances.some((dz) => dz.element.id === dropzoneId)) {
            const self = this;

            new Dropzone(`#${dropzoneId}`, {
                url: () => `/api/pbg-task-attachment/${taskId}`,
                maxFiles: 1,
                maxFilesize: 5, // MB
                acceptedFiles: ".jpg,.png,.pdf",
                autoProcessQueue: false,
                paramName: "file",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Authorization: `Bearer ${
                        document.querySelector('meta[name="api-token"]').content
                    }`,
                    Accept: "application/json",
                },
                params: { pbg_type: pbgType },
                dictDefaultMessage: "Drop your file here or click to upload.",
                init: function () {
                    const dz = this;

                    dz.on("addedfile", (file) => {
                        if (dz.files.length > 1) dz.removeFile(dz.files[0]);
                        setTimeout(() => {
                            document.getElementById(
                                fileNameSpanId
                            ).textContent = file.name;
                            document
                                .getElementById(fileInfoId)
                                .classList.remove("d-none");
                            document
                                .querySelector(".dz-message")
                                .classList.add("d-none");
                        }, 10);
                    });

                    dz.on("removedfile", () => {
                        document
                            .getElementById(fileInfoId)
                            .classList.add("d-none");
                        document.getElementById(fileNameSpanId).textContent =
                            "";
                        document
                            .querySelector(".dz-message")
                            .classList.remove("d-none");
                    });

                    document
                        .getElementById(removeBtnId)
                        .addEventListener("click", () => dz.removeAllFiles());

                    document
                        .getElementById(submitBtnId)
                        .addEventListener("click", () => {
                            if (dz.getQueuedFiles().length > 0) {
                                dz.processQueue();
                            } else {
                                self.toastMessage.innerText =
                                    "Please select a file to upload.";
                                self.toast.show();
                            }
                        });

                    dz.on("success", () => {
                        dz.removeAllFiles(true);
                        document
                            .getElementById(fileInfoId)
                            .classList.add("d-none");
                        document.getElementById(fileNameSpanId).textContent =
                            "";
                        document.querySelector(".dz-message").style.display =
                            "block";
                        document.activeElement.blur();
                        setTimeout(() => {
                            document.body.focus();
                            modalInstance.hide();
                        }, 50);
                        self.toastMessage.innerText =
                            "File uploaded successfully!";
                        self.toast.show();

                        // Get current filter and year values to refresh table
                        const currentFilter =
                            document.getElementById("filter-select").value ||
                            "";
                        const currentYear =
                            document.getElementById("year-select").value ||
                            "2025";
                        self.initTableRequestAssignment(
                            currentFilter,
                            currentYear
                        );
                    });

                    dz.on("error", (file, message) => {
                        self.toastMessage.innerText =
                            message || "Upload failed!";
                        self.toast.show();
                    });
                },
            });
        }
    }

    handleDownloadButtons(buttonClass) {
        const buttons = document.querySelectorAll(`.${buttonClass}`);

        buttons.forEach((button) => {
            button.addEventListener("click", () => {
                const attachmentId = button.getAttribute("data-id");
                const originalContent = button.innerHTML;

                // Disable button & show loading
                button.disabled = true;
                button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Loading...
            `;

                fetch(`/api/pbg-task-attachment/${attachmentId}/download`, {
                    method: "GET",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                        Authorization: `Bearer ${document
                            .querySelector('meta[name="api-token"]')
                            .getAttribute("content")}`,
                        Accept: "application/json",
                    },
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error("File not found or server error.");
                        }
                        return response
                            .blob()
                            .then((blob) => ({ blob, response }));
                    })
                    .then(({ blob, response }) => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.href = url;

                        const contentDisposition = response.headers.get(
                            "Content-Disposition"
                        );
                        let fileName = "downloaded-file";

                        if (contentDisposition?.includes("filename=")) {
                            fileName = contentDisposition
                                .split("filename=")[1]
                                .replace(/"/g, "")
                                .trim();
                        }

                        a.download = fileName;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                    })
                    .catch((error) => {
                        console.error("Download failed:", error);
                        alert("Failed to download file.");
                    })
                    .finally(() => {
                        button.disabled = false;
                        button.innerHTML = originalContent;
                    });
            });
        });
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new PbgTasks().init();
});
