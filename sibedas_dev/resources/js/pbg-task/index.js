import { Grid, html } from "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";
import { Dropzone } from "dropzone";
import { addThousandSeparators } from "../global-config";

Dropzone.autoDiscover = false;

class PbgTasks {
    constructor() {
        this.table = null;
        this.selectedYear = new Date().getFullYear().toString();
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

        this.renderYearFilter();
        this.renderTable();
        this.handleSendNotification();
        this.handleExportExcel();
    }

    renderYearFilter() {
        const tableContainer = document.getElementById("table-pbg-tasks");
        const wrapper = document.createElement("div");
        wrapper.className = "d-flex align-items-center gap-2 mb-3";
        wrapper.innerHTML = `<label class="mb-0 fw-semibold">Tahun:</label>`;

        const select = document.createElement("select");
        select.className = "form-select form-select-sm w-auto";
        select.id = "year-filter-select";

        const allOpt = document.createElement("option");
        allOpt.value = "";
        allOpt.textContent = "Semua";
        select.appendChild(allOpt);

        const currentYear = new Date().getFullYear();
        for (let y = currentYear; y >= currentYear - 5; y--) {
            const opt = document.createElement("option");
            opt.value = y.toString();
            opt.textContent = y.toString();
            if (y.toString() === this.selectedYear) opt.selected = true;
            select.appendChild(opt);
        }

        select.addEventListener("change", () => {
            this.selectedYear = select.value;
            if (this.table) {
                this.table.destroy();
                this.table = null;
            }
            this.renderTable();
        });

        wrapper.appendChild(select);
        tableContainer.parentNode.insertBefore(wrapper, tableContainer);
    }

    renderTable() {
        const token = document.querySelector('meta[name="api-token"]').getAttribute("content");
        const tableContainer = document.getElementById("table-pbg-tasks");
        const canUpdate = tableContainer.getAttribute("data-updater") === "1";
        const year = this.selectedYear;

        const buildUrl = (page, limit) => {
            let url = `${GlobalConfig.apiHost}/api/request-assignments?page=${page}&per_page=${limit}`;
            if (year) url += `&year=${year}`;
            return url;
        };

        const config = {
            columns: [
                { name: "ID" },
                { name: "Nama Pemohon" },
                { name: "Nama Pemilik" },
                { name: "Kondisi" },
                { name: "Nomor Registrasi" },
                { name: "Nomor Dokumen" },
                { name: "Alamat" },
                { name: "Status" },
                { name: "Jenis Fungsi" },
                { name: "Nama Bangunan" },
                { name: "Jenis Konsultasi" },
                { name: "Tanggal Jatuh Tempo" },
                { name: "Retribusi" },
                { name: "Catatan Kekurangan Dokumen" },
                {
                    name: "Aksi",
                    formatter: (cell) => {
                        if (!canUpdate) return html(`<span class="text-muted">No Privilege</span>`);
                        return html(`
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <a href="/pbg-task/${cell.id}"
                            class="btn btn-yellow btn-sm d-inline-flex align-items-center justify-content-center"
                            style="white-space: nowrap; line-height: 1;">
                                <iconify-icon icon="mingcute:eye-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon>
                            </a>
                            ${cell.attachment_berita_acara
                                ? `<a href="/pbg-task-attachment/${cell.attachment_berita_acara.id}?type=berita-acara" class="btn btn-success btn-sm d-inline-flex align-items-center justify-content-center" style="white-space: nowrap; line-height: 1;" target="_blank"><iconify-icon icon="mingcute:eye-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon><span class="ms-1">Berita Acara</span></a>`
                                : `<button class="btn btn-sm btn-info d-inline-flex align-items-center justify-content-center upload-btn-berita-acara" data-id="${cell.id}" style="white-space: nowrap; line-height: 1;"><iconify-icon icon="mingcute:upload-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon><span class="ms-1" style="line-height: 1;">Berita Acara</span></button>`
                            }
                            ${cell.attachment_bukti_bayar
                                ? `<a href="/pbg-task-attachment/${cell.attachment_bukti_bayar.id}?type=bukti-bayar" class="btn btn-success btn-sm d-inline-flex align-items-center justify-content-center" style="white-space: nowrap; line-height: 1;" target="_blank"><iconify-icon icon="mingcute:eye-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon><span class="ms-1">Bukti Bayar</span></a>`
                                : `<button class="btn btn-sm btn-info d-inline-flex align-items-center justify-content-center upload-btn-bukti-bayar" data-id="${cell.id}" style="white-space: nowrap; line-height: 1;"><iconify-icon icon="mingcute:upload-2-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon><span class="ms-1" style="line-height: 1;">Bukti Bayar</span></button>`
                            }
                        </div>`);
                    },
                },
            ],
            server: {
                url: buildUrl(1, 15),
                then: (data) => data.data.map(item => [
                    item.id,
                    item.name,
                    item.owner_name,
                    item.condition,
                    item.registration_number,
                    item.document_number || "-",
                    item.address,
                    item.status_name,
                    item.function_type,
                    item.pbg_task_detail ? item.pbg_task_detail.name_building : "-",
                    item.consultation_type,
                    item.due_date,
                    item.pbg_task_retributions
                        ? addThousandSeparators(item.pbg_task_retributions.nilai_retribusi_bangunan)
                        : "-",
                    item.pbg_status ? item.pbg_status.note : "-",
                    item,
                ]),
                total: (data) => data.meta.total,
            },
            pagination: {
                limit: 15,
                server: {
                    url: (prev, page, limit) => buildUrl(page + 1, limit),
                },
            },
            search: {
                server: {
                    url: (prev, keyword) => {
                        let url = buildUrl(1, 15);
                        if (keyword) url += `&search=${encodeURIComponent(keyword)}`;
                        return url;
                    },
                },
            },
            sort: true,
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            },
            fetchOptions: {
                credentials: "include",
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json",
                },
            },
        };

        tableContainer.innerHTML = "";
        this.table = new Grid(config).render(tableContainer);
    }

    handleExportExcel() {
        const btn = document.getElementById("export-excel-btn");
        if (!btn) return;

        btn.addEventListener("click", () => {
            const token = document.querySelector('meta[name="api-token"]').getAttribute("content");
            const url = `${GlobalConfig.apiHost}/api/pbg-task/export-excel`;

            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status"></span> Exporting...`;

            fetch(url, {
                headers: { Authorization: `Bearer ${token}` },
                credentials: "include",
            })
                .then(res => res.blob())
                .then(blob => {
                    const a = document.createElement("a");
                    a.href = URL.createObjectURL(blob);
                    a.download = `pbg-data-${new Date().toISOString().slice(0,10)}.xlsx`;
                    a.click();
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = `<iconify-icon icon="mingcute:file-excel-fill" width="15" height="15" style="vertical-align: middle;"></iconify-icon> Export Excel`;
                });
        });
    }

    handleSendNotification() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);

        document.getElementById("sendNotificationBtn").addEventListener("click", () => {
            this.toastMessage.innerText = "Notifikasi berhasil dikirim!";
            this.toast.show();
            let modal = bootstrap.Modal.getInstance(document.getElementById("sendNotificationModal"));
            modal.hide();
        });
    }

    setupFileUploadModal({
        modalId, dropzoneId, uploadBtnClass, removeBtnId, submitBtnId,
        fileNameSpanId, fileInfoId, pbgType, bindFlag,
    }) {
        const modalEl = document.getElementById(modalId);
        const modalInstance = new bootstrap.Modal(modalEl);
        let taskId;

        modalEl.addEventListener("hide.bs.modal", () => {
            if (document.activeElement && modalEl.contains(document.activeElement)) {
                document.activeElement.blur();
                setTimeout(() => document.body.focus(), 10);
            }
        });

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

        if (!Dropzone.instances.some((dz) => dz.element.id === dropzoneId)) {
            const self = this;
            new Dropzone(`#${dropzoneId}`, {
                url: () => `/api/pbg-task-attachment/${taskId}`,
                maxFiles: 1,
                maxFilesize: 5,
                acceptedFiles: ".jpg,.png,.pdf",
                autoProcessQueue: false,
                paramName: "file",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    Authorization: `Bearer ${document.querySelector('meta[name="api-token"]').content}`,
                    Accept: "application/json",
                },
                params: { pbg_type: pbgType },
                dictDefaultMessage: "Drop your file here or click to upload.",
                init: function () {
                    const dz = this;
                    dz.on("addedfile", (file) => {
                        if (dz.files.length > 1) dz.removeFile(dz.files[0]);
                        setTimeout(() => {
                            document.getElementById(fileNameSpanId).textContent = file.name;
                            document.getElementById(fileInfoId).classList.remove("d-none");
                            document.querySelector(".dz-message").classList.add("d-none");
                        }, 10);
                    });
                    dz.on("removedfile", () => {
                        document.getElementById(fileInfoId).classList.add("d-none");
                        document.getElementById(fileNameSpanId).textContent = "";
                        document.querySelector(".dz-message").classList.remove("d-none");
                    });
                    document.getElementById(removeBtnId).addEventListener("click", () => dz.removeAllFiles());
                    document.getElementById(submitBtnId).addEventListener("click", () => {
                        if (dz.getQueuedFiles().length > 0) {
                            dz.processQueue();
                        } else {
                            self.toastMessage.innerText = "Please select a file to upload.";
                            self.toast.show();
                        }
                    });
                    dz.on("success", () => {
                        dz.removeAllFiles(true);
                        document.getElementById(fileInfoId).classList.add("d-none");
                        document.getElementById(fileNameSpanId).textContent = "";
                        document.querySelector(".dz-message").style.display = "block";
                        document.activeElement.blur();
                        setTimeout(() => { document.body.focus(); modalInstance.hide(); }, 50);
                        self.toastMessage.innerText = "File uploaded successfully!";
                        self.toast.show();
                        self.renderTable();
                    });
                    dz.on("error", (file, message) => {
                        self.toastMessage.innerText = message || "Upload failed!";
                        self.toast.show();
                    });
                },
            });
        }
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new PbgTasks().init();
});
