// Dashboard Satelit ↔ PBG ↔ PBB
// Single API call: /api/satelit-pbg-pbb/summary?min_area=N
// Renders: 4 KPI, 1 stacked bar chart per-kec, 1 table.

const fmt = (n) => (n ?? 0).toLocaleString("id-ID");
let chart = null;

async function fetchSummary(minArea) {
    const url = `/api/satelit-pbg-pbb/summary?min_area=${minArea}`;
    const token = document.querySelector('meta[name="api-token"]')?.content;
    const headers = { Accept: "application/json" };
    if (token) headers.Authorization = `Bearer ${token}`;
    const r = await fetch(url, { headers, credentials: "same-origin" });
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
}

function renderKpi(t) {
    document.getElementById("kpi-sat").textContent = fmt(t.sat_count);
    document.getElementById("kpi-pbb").textContent = fmt(t.pbb_terbangun);
    document.getElementById("kpi-pbg").textContent = fmt(t.pbg_terbit);
    document.getElementById("kpi-rasio").textContent = (t.rasio_berizin ?? 0).toFixed(2);
    document.getElementById("kpi-tidak").textContent = fmt(t.tidak_berizin);
}

function renderTable(rows, totals) {
    const tb = document.getElementById("spp-tbody");
    if (!rows.length) {
        tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>';
    } else {
        tb.innerHTML = rows
            .map(
                (r) => `
                <tr>
                    <td>${r.kecamatan}</td>
                    <td class="text-end">${fmt(r.sat_count)}</td>
                    <td class="text-end">${fmt(r.pbb_terbangun)}</td>
                    <td class="text-end text-success">${fmt(r.pbg_terbit)}</td>
                    <td class="text-end text-warning">${fmt(r.pbg_proses)}</td>
                    <td class="text-end text-danger">${fmt(r.tidak_berizin)}</td>
                    <td class="text-end"><span class="badge bg-${r.rasio_berizin >= 5 ? "success" : "danger"}-subtle text-${r.rasio_berizin >= 5 ? "success" : "danger"}">${r.rasio_berizin.toFixed(2)}%</span></td>
                </tr>`
            )
            .join("");
    }
    document.getElementById("spp-foot").innerHTML = `
        <td>TOTAL (31 kec)</td>
        <td class="text-end">${fmt(totals.sat_count)}</td>
        <td class="text-end">${fmt(totals.pbb_terbangun)}</td>
        <td class="text-end text-success">${fmt(totals.pbg_terbit)}</td>
        <td class="text-end text-warning">${fmt(totals.pbg_proses)}</td>
        <td class="text-end text-danger">${fmt(totals.tidak_berizin)}</td>
        <td class="text-end">${(totals.rasio_berizin ?? 0).toFixed(2)}%</td>`;
}

function renderChart(rows) {
    const cats = rows.map((r) => r.kecamatan);
    const series = [
        { name: "PBG Terbit", data: rows.map((r) => r.pbg_terbit) },
        { name: "PBG Proses", data: rows.map((r) => r.pbg_proses) },
        { name: "Tidak Berizin", data: rows.map((r) => r.tidak_berizin) },
    ];
    const opts = {
        chart: { type: "bar", stacked: true, height: 380, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: "70%" } },
        xaxis: { categories: cats, labels: { rotate: -45, style: { fontSize: "10px" } } },
        yaxis: { labels: { formatter: (v) => fmt(v) } },
        colors: ["#22c55e", "#f59e0b", "#ef4444"],
        legend: { show: false },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: (v) => fmt(v) + " bangunan" } },
        series,
    };
    if (chart) chart.destroy();
    chart = new ApexCharts(document.getElementById("spp-bar"), opts);
    chart.render();
}

async function load(minArea) {
    document.getElementById("last-computed").textContent = "Memuat…";
    try {
        const data = await fetchSummary(minArea);
        renderKpi(data.totals);
        renderTable(data.per_kec, data.totals);
        renderChart(data.per_kec);
        document.getElementById("last-computed").textContent =
            `Computed: ${new Date(data.computed_at).toLocaleString("id-ID")} · scope: ${data.scope}`;
    } catch (e) {
        console.error(e);
        document.getElementById("last-computed").innerHTML =
            `<span class="text-danger">Error: ${e.message}</span>`;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const sel = document.getElementById("filter-min-area");
    sel.addEventListener("change", () => load(parseInt(sel.value, 10) || 0));
    load(0);
});
