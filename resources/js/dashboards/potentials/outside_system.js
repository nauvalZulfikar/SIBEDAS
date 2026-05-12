import InitDatePicker from "../../utils/InitDatePicker.js";
import GlobalConfig, { addThousandSeparators } from "../../global-config.js";

class DashboardPotentialOutsideSystem {
    async init() {
        new InitDatePicker(
            "#datepicker-outside-system",
            this.handleChangedDate.bind(this)
        ).init();
        this.bigTotalLackPotential = 0;
        this.dataResume = await this.getBigDataResume("latest");
        console.log(this.dataResume);
        this.initChartNonBusiness();
        this.initChartBusiness();
    }
    async handleChangedDate(filterDate) {
        this.dataResume = await this.getBigDataResume(filterDate);
        this.initChartNonBusiness();
        this.initChartBusiness();
    }
    async getBigDataResume(filterDate) {
        try {
            const response = await fetch(
                `${GlobalConfig.apiHost}/api/bigdata-resume?filterByDate=${filterDate}&type=simbg`,
                {
                    credentials: "include",
                    headers: {
                        Authorization: `Bearer ${
                            document.querySelector("meta[name='api-token']")
                                .content
                        }`,
                        "Content-Type": "application/json",
                    },
                }
            );

            if (!response.ok) {
                console.error("Network response was not ok", response);
            }

            return await response.json();
        } catch (error) {
            console.error("Error fetching chart data:", error);
            return null;
        }
    }

    async loadSatelliteSync() {
        // Pulls aggregate from /api/detected-buildings/stats and renders the
        // "Sinkronisasi Monitoring Satelit — PBG Ter-Validasi" panel.
        // Decoupled from init() so a failure here doesn't blank the rest of
        // the dashboard, and a failure earlier in init() doesn't block this.
        const tokenEl = document.querySelector("meta[name='api-token']");
        const headers = { "Content-Type": "application/json" };
        if (tokenEl?.content) headers.Authorization = `Bearer ${tokenEl.content}`;

        const setErrorState = (msg) => {
            const tbody = document.getElementById("dalam-top-kecamatan");
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger small py-3">${msg}</td></tr>`;
            }
        };

        let res;
        try {
            res = await fetch(
                `${GlobalConfig.apiHost}/api/detected-buildings/stats?min_area=200`,
                { credentials: "include", headers }
            );
        } catch (err) {
            console.error("detected-buildings/stats network error", err);
            setErrorState("Gagal memuat data satelit (jaringan).");
            return;
        }
        if (!res.ok) {
            console.error("detected-buildings/stats failed", res.status);
            setErrorState(`Gagal memuat data satelit (HTTP ${res.status}).`);
            return;
        }
        const s = await res.json();

        const fmt = (v) => addThousandSeparators(v ?? 0, 0);
        const setText = (id, v) => {
            const el = document.getElementById(id);
            if (el) el.innerText = v;
        };

        const pbgByStatus = s.pbg_by_status_category || {};
        const pbgTerbit = pbgByStatus.terbit ?? 0;
        const pbgProses = pbgByStatus.proses ?? 0;
        const pbgDitolak = pbgByStatus.ditolak ?? 0;
        const pbgTotal = pbgTerbit + pbgProses + pbgDitolak;

        setText("dalam-pbg-total", fmt(pbgTotal));
        setText("dalam-permit-valid", fmt(pbgTerbit));
        setText("dalam-permit-process", fmt(pbgProses));
        setText("dalam-permit-rejected", fmt(pbgDitolak));

        const validRate =
            pbgTotal > 0 ? ((pbgTerbit / pbgTotal) * 100).toFixed(1) : "0.0";
        setText(
            "dalam-permit-valid-rate",
            `${validRate}% dari total PBG di citra`
        );

        // Top 5 kecamatan ranked by SK terbit (paling tervalidasi)
        const byDistrict = s.pbg_by_district || {};
        const top5 = Object.entries(byDistrict)
            .map(([kec, breakdown]) => ({
                kec,
                terbit: breakdown.terbit ?? 0,
                proses: breakdown.proses ?? 0,
                ditolak: breakdown.ditolak ?? 0,
            }))
            .sort((a, b) => b.terbit - a.terbit)
            .slice(0, 5);

        const tbody = document.getElementById("dalam-top-kecamatan");
        if (tbody) {
            if (top5.length === 0) {
                tbody.innerHTML =
                    '<tr><td colspan="5" class="text-center text-muted small py-3">Belum ada data deteksi.</td></tr>';
            } else {
                tbody.innerHTML = top5
                    .map(
                        (row, i) => `<tr>
                            <td>${i + 1}</td>
                            <td class="fw-medium">${row.kec}</td>
                            <td class="text-end text-success fw-medium">${fmt(row.terbit)}</td>
                            <td class="text-end text-warning">${fmt(row.proses)}</td>
                            <td class="text-end text-danger">${fmt(row.ditolak)}</td>
                        </tr>`
                    )
                    .join("");
            }
        }

        const note = document.getElementById("dalam-snapshot-note");
        if (note && s.snapshot_refreshed_at) {
            const d = new Date(s.snapshot_refreshed_at);
            note.innerText = `Snapshot terakhir di-refresh: ${d.toLocaleString("id-ID")}.`;
        }
    }

    initChartNonBusiness() {
        const nonBusinessDoc = this.dataResume?.non_business_document ?? {};

        document
            .querySelectorAll(".document-count.outside-system-non-business")
            .forEach((element) => {
                element.innerText = `${nonBusinessDoc.count ?? 0}`;
            });

        document
            .querySelectorAll(".document-total.outside-system-non-business")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    (nonBusinessDoc.sum ?? 0).toString()
                )}`;
            });

        document
            .querySelectorAll(".small-percentage.outside-system-non-business")
            .forEach((element) => {
                element.innerText = `${nonBusinessDoc.percentage ?? 0}%`;
            });
    }
    initChartBusiness() {
        const businessDoc = this.dataResume?.business_document ?? {};

        document
            .querySelectorAll(".document-count.outside-system-business")
            .forEach((element) => {
                element.innerText = `${businessDoc.count ?? 0}`;
            });

        document
            .querySelectorAll(".document-total.outside-system-business")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    (businessDoc.sum ?? 0).toString()
                )}`;
            });

        document
            .querySelectorAll(".small-percentage.outside-system-business")
            .forEach((element) => {
                element.innerText = `${businessDoc.percentage ?? 0}%`;
            });
    }
}
document.addEventListener("DOMContentLoaded", async function (e) {
    const dash = new DashboardPotentialOutsideSystem();
    // Run satellite sync independently — it must not be blocked by errors in
    // the rest of init().
    dash.loadSatelliteSync().catch((err) =>
        console.error("Satellite sync (dalam sistem) failed:", err)
    );
    await dash.init();
});
function resizeDashboard() {
    let targetElement = document.getElementById("outside-system-wrapper");
    let dashboardElement = document.getElementById(
        "outside-system-fixed-container"
    );

    let targetWidth = targetElement.offsetWidth;
    let dashboardWidth = 1400;

    let scaleFactor = (targetWidth / dashboardWidth).toFixed(2);

    // Prevent scaling beyond 1 (100%) to avoid overflow
    scaleFactor = Math.min(scaleFactor, 1);

    dashboardElement.style.transformOrigin = "left top";
    dashboardElement.style.transition = "transform 0.2s ease-in-out";
    dashboardElement.style.transform = `scale(${scaleFactor})`;

    // Ensure horizontal scrolling is allowed if necessary
    document.body.style.overflowX = "auto";
}

window.addEventListener("load", resizeDashboard);
window.addEventListener("resize", resizeDashboard);
