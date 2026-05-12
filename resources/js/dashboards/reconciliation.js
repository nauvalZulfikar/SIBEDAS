import ApexCharts from "apexcharts";

const API = "/api/reconciliation";
const TOKEN = window.RECON_TOKEN || "";
const CLEARANCE = window.RECON_CLEARANCE || "level_1";
const RANK = { level_1: 1, level_2: 2, level_3: 3 };
const MY_RANK = RANK[CLEARANCE] || 1;
const H = {
    Accept: "application/json",
    "Content-Type": "application/json",
    Authorization: TOKEN ? `Bearer ${TOKEN}` : "",
};

const fmt = (n) => (n == null ? "-" : Number(n).toLocaleString("id-ID"));
const fmtPct = (p) => (p == null ? "-" : `${Number(p).toFixed(2)}%`);

let barChart = null;

async function loadOverview() {
    const [sumRes, kecRes] = await Promise.all([
        fetch(`${API}/summary`, { headers: H }).then((r) => r.json()),
        fetch(`${API}/per-kec`, { headers: H }).then((r) => r.json()),
    ]);
    renderKpi(sumRes.data);
    renderBar(kecRes.data);
    renderTable(kecRes.data);
}

function renderKpi(d) {
    document.getElementById("kpi-pbb-terbangun").textContent = fmt(d.pbb_terbangun);
    document.getElementById("kpi-pbb-total").textContent = fmt(d.pbb_total);
    document.getElementById("kpi-sat").textContent = fmt(d.sat_count);
    const gap = d.gap_sat_minus_terbangun;
    const gapEl = document.getElementById("kpi-gap");
    gapEl.textContent = (gap >= 0 ? "+" : "") + fmt(gap);
    gapEl.className = "mb-0 " + (gap >= 0 ? "text-danger" : "text-secondary");
    document.getElementById("kpi-gap-pct").textContent = fmtPct(d.gap_pct);
    document.getElementById("kpi-pbg").textContent = fmt(d.pbg_terbit_count);
    document.getElementById("last-computed").textContent =
        `Data dari snapshot reconciliation_summary — refresh manual via tombol Recompute (admin only). Auto-refresh tiap 02:00 WIB.`;
}

function renderBar(rows) {
    const sorted = [...rows].sort((a, b) => Math.abs(b.gap) - Math.abs(a.gap));
    const cats = sorted.map((r) => r.kecamatan);
    const data = sorted.map((r) => r.gap);

    const opts = {
        chart: { type: "bar", height: 360, toolbar: { show: false } },
        series: [{ name: "Gap (sat − terbangun)", data }],
        xaxis: { categories: cats, labels: { rotate: -45, style: { fontSize: "11px" } } },
        plotOptions: {
            bar: {
                colors: { ranges: [
                    { from: -1e9, to: -1, color: "#6c757d" },
                    { from: 0, to: 1e9, color: "#dc3545" },
                ]},
                dataLabels: { position: "top" },
            },
        },
        dataLabels: {
            enabled: true,
            formatter: (v) => (v >= 0 ? `+${fmt(v)}` : fmt(v)),
            style: { fontSize: "10px", colors: ["#333"] },
        },
        tooltip: {
            y: {
                formatter: (v, { dataPointIndex }) => {
                    const r = sorted[dataPointIndex];
                    return `${fmt(r.gap)} (${fmtPct(r.gap_pct)})\n` +
                           `PBB terbangun: ${fmt(r.pbb_terbangun)} | Sat: ${fmt(r.sat_count)}`;
                },
            },
        },
        legend: { show: false },
    };

    if (barChart) barChart.destroy();
    barChart = new ApexCharts(document.getElementById("recon-bar-chart"), opts);
    barChart.render();
}

function renderTable(rows) {
    const sorted = [...rows].sort((a, b) => Math.abs(b.gap) - Math.abs(a.gap));
    const tbody = document.getElementById("recon-tbody");
    tbody.innerHTML = sorted.map((r) => `
        <tr data-kec="${escapeAttr(r.kecamatan)}">
            <td><strong>${escapeHtml(r.kecamatan)}</strong></td>
            <td class="text-end">${fmt(r.pbb_total)}</td>
            <td class="text-end">${fmt(r.pbb_terbangun)}</td>
            <td class="text-end">${fmt(r.sat_count)}</td>
            <td class="text-end ${r.gap >= 0 ? "gap-pos" : "gap-neg"}">${r.gap >= 0 ? "+" : ""}${fmt(r.gap)}</td>
            <td class="text-end">${fmtPct(r.gap_pct)}</td>
            <td><iconify-icon icon="solar:alt-arrow-right-broken"></iconify-icon></td>
        </tr>
    `).join("");

    if (MY_RANK >= 2) {
        tbody.querySelectorAll("tr").forEach((tr) => {
            tr.addEventListener("click", () => openKelurahan(tr.dataset.kec));
        });
    } else {
        tbody.querySelectorAll("tr").forEach((tr) => {
            tr.style.cursor = "not-allowed";
            tr.title = "Drill-down kelurahan butuh clearance level_2 (admin).";
            const last = tr.querySelector("td:last-child");
            if (last) last.innerHTML = "";
        });
    }
}

async function openKelurahan(kecName) {
    const modal = new bootstrap.Modal(document.getElementById("kelurahanModal"));
    document.getElementById("kel-modal-title").textContent = `Detail Kelurahan — ${kecName}`;
    document.getElementById("kel-modal-tbody").innerHTML =
        `<tr><td colspan="6" class="text-center py-3 text-muted">Memuat…</td></tr>`;
    document.getElementById("kel-modal-coverage-note").textContent = "";
    modal.show();

    const res = await fetch(`${API}/kelurahan/${encodeURIComponent(kecName)}`, { headers: H }).then((r) => r.json());
    const rows = res.data || [];
    if (!rows.length) {
        document.getElementById("kel-modal-tbody").innerHTML =
            `<tr><td colspan="6" class="text-center py-3 text-muted">${escapeHtml(res.message || "Tidak ada data.")}</td></tr>`;
        return;
    }

    const pendingCount = rows.filter((r) => r.coverage_status === "pending_polygon").length;
    const note = pendingCount > 0
        ? `${pendingCount} dari ${rows.length} kelurahan belum punya polygon spasial — sat_count masih placeholder (Phase 7+).`
        : "";
    document.getElementById("kel-modal-coverage-note").textContent = note;

    document.getElementById("kel-modal-tbody").innerHTML = rows.map((r) => `
        <tr>
            <td><strong>${escapeHtml(r.kelurahan)}</strong></td>
            <td class="text-end">${fmt(r.pbb_terbangun)}</td>
            <td class="text-end">${fmt(r.sat_count)}</td>
            <td class="text-end ${r.gap == null ? "" : r.gap >= 0 ? "gap-pos" : "gap-neg"}">${
                r.gap == null ? "-" : (r.gap >= 0 ? "+" : "") + fmt(r.gap)
            }</td>
            <td class="text-end">${fmtPct(r.gap_pct)}</td>
            <td><span class="badge ${r.coverage_status === "covered" ? "badge-covered" : "badge-pending"}">${
                r.coverage_status === "covered" ? "Covered" : "Pending Polygon"
            }</span></td>
        </tr>
    `).join("");
}

async function loadAudit() {
    const [noSat, noNop] = await Promise.all([
        fetch(`${API}/no-satellite-nop?limit=100`, { headers: H }).then((r) => r.json()),
        fetch(`${API}/no-nop-satellite?limit=100`, { headers: H }).then((r) => r.json()),
    ]);

    const tbody1 = document.getElementById("audit-no-sat");
    const rows1 = noSat.data || [];
    tbody1.innerHTML = rows1.length
        ? rows1.map((r) => `
            <tr>
                <td><code>${escapeHtml(r.nop || "-")}</code></td>
                <td>${escapeHtml(r.nama_wp || "-")}</td>
                <td>${escapeHtml(r.kecamatan_name || "-")}</td>
                <td>${escapeHtml(r.kelurahan_name || "-")}</td>
                <td class="text-end">${fmt(r.luas_bangunan)}</td>
            </tr>
        `).join("")
        : `<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada data.</td></tr>`;
    document.getElementById("audit-no-sat-meta").textContent =
        `Showing ${noSat.meta?.returned ?? 0} of ${fmt(noSat.meta?.total_terbangun)} • ${noSat.note || ""}`;

    const tbody2 = document.getElementById("audit-no-nop");
    const rows2 = noNop.data || [];
    tbody2.innerHTML = rows2.length
        ? rows2.map((r) => `
            <tr>
                <td>${r.id}</td>
                <td>${Number(r.latitude).toFixed(5)}</td>
                <td>${Number(r.longitude).toFixed(5)}</td>
                <td>${escapeHtml(r.building_district_name || "-")}</td>
                <td class="text-end">${fmt(r.estimated_area_m2)}</td>
            </tr>
        `).join("")
        : `<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada data.</td></tr>`;
    document.getElementById("audit-no-nop-meta").textContent =
        `Showing ${noNop.meta?.returned ?? 0} of ${fmt(noNop.meta?.total_unmapped)} • ${noNop.note || ""}`;
}

document.querySelectorAll('[data-export]').forEach((a) => {
    a.addEventListener("click", (e) => {
        e.preventDefault();
        const kind = a.dataset.export;
        const scope = a.dataset.scope;
        let url;
        if (kind === "pdf") url = `${API}/export/pdf`;
        else if (kind === "excel") url = `${API}/export/excel`;
        else if (kind === "csv") url = `${API}/export/csv?scope=${scope}`;
        else return;
        triggerDownload(url, `reconciliation-${kind}${scope ? "-" + scope : ""}`);
    });
});

async function triggerDownload(url, hintName) {
    try {
        const r = await fetch(url, { headers: H });
        if (!r.ok) {
            const txt = await r.text();
            alert(`Export failed: HTTP ${r.status}\n${txt.slice(0, 200)}`);
            return;
        }
        const blob = await r.blob();
        const cd = r.headers.get("Content-Disposition") || "";
        const m = cd.match(/filename="?([^";]+)"?/);
        const name = m ? m[1] : `${hintName}.bin`;
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = name;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(a.href);
    } catch (err) {
        alert(`Network error: ${err.message}`);
    }
}

document.getElementById("btn-recompute")?.addEventListener("click", async (e) => {
    const btn = e.currentTarget;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Recomputing…`;
    try {
        const r = await fetch(`${API}/recompute`, { method: "POST", headers: H });
        const body = await r.json();
        if (r.status === 403) {
            alert(body.message || "Akses ditolak — admin only.");
        } else if (r.ok) {
            alert(`Recompute berhasil: ${body.data.rows_inserted} rows in ${body.data.elapsed_ms}ms`);
            await loadOverview();
        } else {
            alert(`Error: ${body.message || r.statusText}`);
        }
    } catch (err) {
        alert(`Network error: ${err.message}`);
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<iconify-icon icon="solar:refresh-broken" class="me-1"></iconify-icon>Recompute`;
    }
});

document.getElementById("tab-audit-btn")?.addEventListener("shown.bs.tab", () => {
    const btn = document.getElementById("tab-audit-btn");
    if (!btn.dataset.loaded) {
        loadAudit();
        btn.dataset.loaded = "1";
    }
});

function escapeHtml(s) {
    return String(s ?? "")
        .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
}
function escapeAttr(s) { return escapeHtml(s).replace(/`/g, "&#96;"); }

document.addEventListener("DOMContentLoaded", () => {
    loadOverview().catch((e) => {
        document.getElementById("recon-tbody").innerHTML =
            `<tr><td colspan="7" class="text-center py-3 text-danger">Error: ${escapeHtml(e.message)}</td></tr>`;
    });
});
