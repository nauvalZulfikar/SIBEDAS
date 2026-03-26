import GlobalConfig from "../global-config";
import { Dropzone } from "dropzone";
import { addThousandSeparators } from "../global-config";

Dropzone.autoDiscover = false;

class PbgTasks {
    constructor() {
        const params = new URLSearchParams(window.location.search);
        this.selectedYear = params.get('year') || new Date().getFullYear().toString();
        this.selectedFilter = params.get('filter') || '';
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

        if (this.selectedFilter) {
            const filterLabels = {
                'potention': 'Total Potensi Berkas',
                'verified': 'Berkas Lengkap',
                'non-verified': 'Berkas Belum Lengkap',
                'business': 'Usaha',
                'non-business': 'Non Usaha',
                'issuance-realization-pbg': 'Realisasi PAD PBG',
                'waiting-click-dpmptsp': 'Menunggu Klik DPMPTSP',
                'process-in-technical-office': 'Berproses Di Dinas Teknis',
                'non-business-rab': 'Non Usaha - RAB',
                'non-business-krk': 'Non Usaha - KRK',
                'business-rab': 'Usaha - RAB',
                'business-krk': 'Usaha - KRK',
                'business-dlh': 'Usaha - DLH',
            };
            const label = filterLabels[this.selectedFilter] || this.selectedFilter;
            const badge = document.createElement("span");
            badge.className = "badge bg-info text-dark";
            badge.style.fontSize = "12px";
            badge.innerHTML = `Filter: ${label} &nbsp;<a href="?menu_id=${new URLSearchParams(window.location.search).get('menu_id') || ''}" class="text-dark" style="text-decoration:none;">✕</a>`;
            wrapper.appendChild(badge);
        }

        tableContainer.parentNode.insertBefore(wrapper, tableContainer);
    }

    async fetchPage(page, search) {
        const token = document.querySelector('meta[name="api-token"]').getAttribute("content");
        let url = `${GlobalConfig.apiHost}/api/request-assignments?page=${page}&per_page=15`;
        if (this.selectedYear) url += `&year=${this.selectedYear}`;
        if (this.selectedFilter) url += `&filter=${encodeURIComponent(this.selectedFilter)}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        const resp = await fetch(url, {
            headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
            credentials: "include",
        });
        return resp.json();
    }

    statusBadge(s, name) {
        name = name || "-";
        if (s === 9 || s === 3)               return `<span class="badge bg-danger" style="white-space:normal">${name}</span>`;
        if (s === 20 || s === 28)             return `<span class="badge bg-success" style="white-space:normal">${name}</span>`;
        if (s === 19 || s === 18)             return `<span class="badge bg-primary" style="white-space:normal">${name}</span>`;
        if (s === 14 || s === 15 || s === 25) return `<span class="badge bg-warning text-dark" style="white-space:normal">${name}</span>`;
        if (s === 1  || s === 2  || s === 8)  return `<span class="badge bg-secondary" style="white-space:normal">${name}</span>`;
        return `<span class="badge bg-info text-dark" style="white-space:normal">${name}</span>`;
    }

    renderTable() {
        const tableContainer = document.getElementById("table-pbg-tasks");
        const canUpdate = tableContainer.getAttribute("data-updater") === "1";
        const self = this;
        let currentPage = 1;
        let currentSearch = "";
        let totalPages = 1;
        let currentData = [];
        let sortCol = null;
        let sortDir = "asc";
        let colFilters = {};

        const allStatuses = [
            "Verifikasi Kelengkapan Dokumen",
            "Perbaikan Dokumen",
            "Permohonan Dibatalkan",
            "Menunggu Penugasan TPT/TPA",
            "Menunggu Jadwal Konsultasi",
            "Pelaksanaan Konsultasi",
            "Perbaikan Dokumen Konsultasi",
            "Permohonan Ditolak",
            "Perhitungan Retribusi",
            "Menunggu Pembayaran Retribusi",
            "Verifikasi Pembayaran Retribusi",
            "Retribusi Tidak Sesuai",
            "Verifikasi SK PBG",
            "Pengambilan SK PBG",
            "SK PBG Terbit",
            "Sertifikat PBG Dibekukan",
            "Penerbitan SPPST",
            "Proses Penerbitan SKRD",
        ];

        const columns = [
            { label: "ID",                  key: "id",                  width: "60px",  nowrap: true },
            { label: "Nama Pemohon",        key: "name",                width: "160px" },
            { label: "Nama Pemilik",        key: "owner_name",          width: "140px" },
            { label: "Kondisi",             key: "condition",           width: "100px", nowrap: true, filterable: true },
            { label: "Nomor Registrasi",    key: "registration_number", width: "145px" },
            { label: "Nomor Dokumen",       key: "document_number",     width: "135px", nowrap: true },
            { label: "Alamat",              key: "address",             width: "180px" },
            { label: "Status",              key: "status_name",         width: "155px", filterable: true },
            { label: "Jenis Fungsi",        key: "function_type",       width: "145px", filterable: true },
            { label: "Nama Bangunan",       key: "_name_building",      width: "145px" },
            { label: "Jenis Konsultasi",    key: "consultation_type",   width: "160px", filterable: true, nowrap: false },
            { label: "Tanggal Dibuat",      key: "task_created_at",     width: "105px", nowrap: true },
            { label: "Tanggal Mulai",       key: "start_date",          width: "105px", nowrap: true },
            { label: "Tanggal Jatuh Tempo", key: "due_date",            width: "105px", nowrap: true },
            { label: "Luas (m²)",           key: "total_area",          width: "90px",  nowrap: true },
            { label: "Unit",                key: "unit",                width: "70px",  nowrap: true, filterable: true },
            { label: "Retribusi",           key: "_retribusi",          width: "125px", nowrap: true },
            { label: "Catatan",             key: "_catatan",            width: "180px" },
            { label: "Aksi",                key: "_aksi",               width: "135px", nowrap: true, nosort: true },
        ];

        const getVal = (item, key) => {
            if (key === "_name_building") return item.pbg_task_detail ? item.pbg_task_detail.name_building : "";
            if (key === "_retribusi") return item.pbg_task_retributions ? item.pbg_task_retributions.nilai_retribusi_bangunan : "";
            if (key === "_catatan") return item.pbg_status ? item.pbg_status.note : "";
            if (key === "_aksi") return "";
            return item[key] || "";
        };

        const renderRows = (items) => {
            let filtered = items.filter(item =>
                Object.entries(colFilters).every(([key, val]) => {
                    if (!val) return true;
                    return (getVal(item, key) || "").toString().toLowerCase().includes(val.toLowerCase());
                })
            );
            if (sortCol) {
                filtered = [...filtered].sort((a, b) => {
                    const av = (getVal(a, sortCol) || "").toString().toLowerCase();
                    const bv = (getVal(b, sortCol) || "").toString().toLowerCase();
                    return sortDir === "asc" ? av.localeCompare(bv) : bv.localeCompare(av);
                });
            }
            return filtered;
        };

        const buildTable = (items) => {
            const filtered = renderRows(items);

            const headerHtml = columns.map((col, i) => {
                const w = col.width ? `width:${col.width};min-width:${col.width};` : "";
                const base = `${w}white-space:nowrap;font-size:13px;padding:8px 12px;background:#f8f9fa;border-bottom:2px solid #dee2e6;`;
                if (col.nosort) return `<th style="${base}">${col.label}</th>`;
                const isActive = sortCol === col.key;
                const arrow = isActive ? (sortDir === "asc" ? " ↑" : " ↓") : " ↕";
                const activeStyle = isActive ? "color:#0d6efd;" : "color:#6c757d;";
                return `<th data-colidx="${i}" style="${base}cursor:pointer;user-select:none;">${col.label}<span style="${activeStyle}font-size:10px">${arrow}</span></th>`;
            }).join("");

            const filterHtml = columns.map((col, i) => {
                const w = col.width ? `width:${col.width};min-width:${col.width};` : "";
                if (col.nosort) return `<th style="${w}padding:4px 6px;background:#f8f9fa;"></th>`;
                if (col.filterable) {
                    const unique = col.key === "status_name"
                        ? allStatuses
                        : [...new Set(items.map(item => getVal(item, col.key)).filter(Boolean))].sort();
                    const opts = unique.map(v => `<option value="${v}" ${colFilters[col.key] === v ? "selected" : ""}>${v}</option>`).join("");
                    return `<th style="${w}padding:4px 6px;background:#f8f9fa;"><select class="form-select form-select-sm col-filter" data-key="${col.key}" style="font-size:11px"><option value="">-- Semua --</option>${opts}</select></th>`;
                }
                return `<th style="${w}padding:4px 6px;background:#f8f9fa;"><input type="text" class="form-control form-control-sm col-filter" data-key="${col.key}" value="${colFilters[col.key] || ""}" placeholder="..." style="font-size:11px"></th>`;
            }).join("");

            const tbodyHtml = filtered.map((item, idx) => {
                const ret = item.pbg_task_retributions ? addThousandSeparators(item.pbg_task_retributions.nilai_retribusi_bangunan) : "-";
                const aksi = canUpdate ? `
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="/pbg-task/${item.id}" class="btn btn-yellow btn-sm"><iconify-icon icon="mingcute:eye-2-fill" width="13"></iconify-icon></a>
                        ${item.attachment_berita_acara
                            ? `<a href="/pbg-task-attachment/${item.attachment_berita_acara.id}?type=berita-acara" class="btn btn-success btn-sm" target="_blank" style="font-size:11px">BA</a>`
                            : `<button class="btn btn-sm btn-info upload-btn-berita-acara" data-id="${item.id}" style="font-size:11px">BA</button>`}
                        ${item.attachment_bukti_bayar
                            ? `<a href="/pbg-task-attachment/${item.attachment_bukti_bayar.id}?type=bukti-bayar" class="btn btn-success btn-sm" target="_blank" style="font-size:11px">BB</a>`
                            : `<button class="btn btn-sm btn-info upload-btn-bukti-bayar" data-id="${item.id}" style="font-size:11px">BB</button>`}
                    </div>` : `<span class="text-muted" style="font-size:11px">No Privilege</span>`;
                const bg = idx % 2 === 0 ? "" : "background:#f9f9f9;";
                const td = `${bg}padding:8px 12px;font-size:13px;vertical-align:middle;border-color:#dee2e6;`;
                const tdnw = `${td}white-space:nowrap;overflow:hidden;`;
                return `<tr style="border-color:#dee2e6;">
                    <td style="${tdnw}">${item.id}</td>
                    <td style="${td}">${item.name || "-"}</td>
                    <td style="${td}">${item.owner_name || "-"}</td>
                    <td style="${tdnw}">${item.condition || "-"}</td>
                    <td style="${td}">${item.registration_number || "-"}</td>
                    <td style="${tdnw}">${item.document_number || "-"}</td>
                    <td style="${td}">${item.address || "-"}</td>
                    <td style="${td}">${self.statusBadge(item.status, item.status_name)}</td>
                    <td style="${td}">${item.function_type || "-"}</td>
                    <td style="${td}">${item.pbg_task_detail ? item.pbg_task_detail.name_building : "-"}</td>
                    <td style="${td}">${item.consultation_type || "-"}</td>
                    <td style="${tdnw}">${item.task_created_at ? item.task_created_at.substring(0,10) : "-"}</td>
                    <td style="${tdnw}">${item.start_date ? item.start_date.substring(0,10) : "-"}</td>
                    <td style="${tdnw}">${item.due_date ? item.due_date.substring(0,10) : "-"}</td>
                    <td style="${tdnw}">${item.total_area ? addThousandSeparators(item.total_area) : "-"}</td>
                    <td style="${tdnw}">${item.unit || "-"}</td>
                    <td style="${tdnw}">${ret}</td>
                    <td style="${td}">${item.pbg_status ? item.pbg_status.note : "-"}</td>
                    <td style="${tdnw}">${aksi}</td>
                </tr>`;
            }).join("");

            tableContainer.innerHTML = `
                <div class="table-responsive" style="border:1px solid #dee2e6;border-radius:4px;">
                    <table style="width:100%;border-collapse:collapse;min-width:1800px;">
                        <thead><tr>${headerHtml}</tr><tr>${filterHtml}</tr></thead>
                        <tbody>${tbodyHtml || `<tr><td colspan="${columns.length}" style="text-align:center;padding:20px;color:#6c757d;">Tidak ada data</td></tr>`}</tbody>
                    </table>
                </div>`;

            tableContainer.querySelectorAll("th[data-colidx]").forEach(th => {
                th.addEventListener("click", () => {
                    const col = columns[parseInt(th.getAttribute("data-colidx"))];
                    if (sortCol === col.key) sortDir = sortDir === "asc" ? "desc" : "asc";
                    else { sortCol = col.key; sortDir = "asc"; }
                    buildTable(currentData);
                });
            });

            tableContainer.querySelectorAll(".col-filter").forEach(el => {
                el.addEventListener(el.tagName === "SELECT" ? "change" : "input", () => {
                    const key = el.getAttribute("data-key");
                    const cursorPos = el.selectionStart;
                    colFilters[key] = el.value;
                    buildTable(currentData);
                    const restored = tableContainer.querySelector(`.col-filter[data-key="${key}"]`);
                    if (restored && restored.tagName !== "SELECT") {
                        restored.focus();
                        restored.setSelectionRange(cursorPos, cursorPos);
                    }
                });
            });
        };

        const loadPage = async (page, search) => {
            tableContainer.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat data...</p></div>`;
            const data = await self.fetchPage(page, search);
            totalPages = data.meta.last_page;
            currentPage = page;
            currentData = data.data;
            colFilters = {};
            buildTable(currentData);
            renderPagination(page, totalPages, data.meta.total);
        };

        const renderPagination = (page, total, totalRecords) => {
            let pag = document.getElementById("custom-pagination");
            if (!pag) {
                pag = document.createElement("div");
                pag.id = "custom-pagination";
                pag.className = "d-flex align-items-center justify-content-between mt-2 px-1";
                tableContainer.parentNode.insertBefore(pag, tableContainer.nextSibling);
            }
            pag.innerHTML = `
                <small class="text-muted">Menampilkan ${((page-1)*15)+1}–${Math.min(page*15, totalRecords)} dari ${totalRecords} data</small>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="pag-first" ${page <= 1 ? "disabled" : ""}>&laquo;</button>
                    <button class="btn btn-sm btn-outline-secondary" id="pag-prev" ${page <= 1 ? "disabled" : ""}>&lsaquo; Prev</button>
                    <span style="font-size:13px;color:#6c757d">Halaman ${page} / ${total}</span>
                    <button class="btn btn-sm btn-outline-secondary" id="pag-next" ${page >= total ? "disabled" : ""}>Next &rsaquo;</button>
                    <button class="btn btn-sm btn-outline-secondary" id="pag-last" ${page >= total ? "disabled" : ""}>&raquo;</button>
                </div>`;
            document.getElementById("pag-first").addEventListener("click", () => loadPage(1, currentSearch));
            document.getElementById("pag-prev").addEventListener("click", () => loadPage(currentPage - 1, currentSearch));
            document.getElementById("pag-next").addEventListener("click", () => loadPage(currentPage + 1, currentSearch));
            document.getElementById("pag-last").addEventListener("click", () => loadPage(totalPages, currentSearch));
        };

        loadPage(1, "");
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
