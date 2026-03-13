import { Grid, html } from "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../global-config";
import { Dropzone } from "dropzone";
import { addThousandSeparators } from "../global-config";

Dropzone.autoDiscover = false;

class PbgTasks {
    constructor() {
        this.table = null;
        this.allData = [];
        this.filteredData = [];
        this.columnFilters = {};
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

        this.loadAllData();
        this.handleSendNotification();
        this.handleExportExcel();
    }

    async loadAllData() {
        const token = document.querySelector('meta[name="api-token"]').getAttribute("content");
        const urlBase = `${GlobalConfig.apiHost}/api/request-assignments`;
        const tableContainer = document.getElementById("table-pbg-tasks");

        tableContainer.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat data...</p></div>`;

        let allItems = [];
        let page = 1;
        let totalPages = 1;

        try {
            do {
                const resp = await fetch(`${urlBase}?page=${page}&per_page=100`, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        "Content-Type": "application/json",
                    },
                    credentials: "include",
                });
                const data = await resp.json();
                allItems = allItems.concat(data.data);
                totalPages = data.meta.last_page;
                page++;
            } while (page <= totalPages);

            this.allData = allItems;
            this.filteredData = allItems;
            this.renderTable();
        } catch (e) {
            tableContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat data: ${e.message}</div>`;
        }
    }

    getUniqueValues(key) {
        const values = new Set();
        this.allData.forEach(item => {
            const val = item[key];
            if (val) values.add(val);
        });
        return [...values].sort();
    }

    applyColumnFilters() {
        this.filteredData = this.allData.filter(item => {
            return Object.entries(this.columnFilters).every(([key, val]) => {
                if (!val) return true;
                const itemVal = (item[key] || "").toString().toLowerCase();
                return itemVal.includes(val.toLowerCase());
            });
        });
        this.renderTable();
    }

    makeDropdown(key, placeholder) {
        const values = this.getUniqueValues(key);
        const select = document.createElement("select");
        select.className = "form-select form-select-sm mt-1";
        select.style.fontSize = "11px";

        const defaultOpt = document.createElement("option");
        defaultOpt.value = "";
        defaultOpt.textContent = placeholder;
        select.appendChild(defaultOpt);

        values.forEach(v => {
            const opt = document.createElement("option");
            opt.value = v;
            opt.textContent = v;
            select.appendChild(opt);
        });

        select.addEventListener("change", () => {
            this.columnFilters[key] = select.value;
            this.applyColumnFilters();
        });

        return select;
    }

    renderTable() {
        const tableContainer = document.getElementById("table-pbg-tasks");
        const canUpdate = tableContainer.getAttribute("data-updater") === "1";

        const rows = this.filteredData.map(item => [
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
        ]);

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
            data: rows,
            sort: true,
            search: true,
        };

        tableContainer.innerHTML = "";
        this.table = new Grid(config).render(tableContainer);

        // Add column filter dropdowns after table renders
        this.table.on("ready", () => this.addColumnDropdowns());
        setTimeout(() => this.addColumnDropdowns(), 500);
    }

    addColumnDropdowns() {
        const thead = document.querySelector("#table-pbg-tasks thead tr");
        if (!thead) return;

        const filterRow = document.createElement("tr");
        const dropdownCols = [
            null,           // ID - no filter
            null,           // Nama Pemohon
            null,           // Nama Pemilik
            { key: "condition", placeholder: "Kondisi" },
            null,           // Nomor Registrasi
            null,           // Nomor Dokumen
            null,           // Alamat
            { key: "status_name", placeholder: "Status" },
            { key: "function_type", placeholder: "Fungsi" },
            null,           // Nama Bangunan
            { key: "consultation_type", placeholder: "Konsultasi" },
            null,           // Tanggal
            null,           // Retribusi
            null,           // Catatan
            null,           // Aksi
        ];

        dropdownCols.forEach(col => {
            const td = document.createElement("th");
            td.style.padding = "2px 4px";
            if (col) {
                td.appendChild(this.makeDropdown(col.key, `-- ${col.placeholder} --`));
            }
            filterRow.appendChild(td);
        });

        thead.parentNode.insertBefore(filterRow, thead.nextSibling);
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
                        self.loadAllData();
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
