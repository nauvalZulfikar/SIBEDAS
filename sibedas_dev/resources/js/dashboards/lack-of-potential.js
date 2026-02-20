import Big from "big.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";
import InitDatePicker from "../utils/InitDatePicker.js";

class LackOfPotential {
    async init() {
        new InitDatePicker(
            "#datepicker-lack-of-potential",
            this.handleChangedDate.bind(this)
        ).init();
        this.bigTotalLackPotential = 0;
        this.totalPotensi = await this.getDataTotalPotensi("latest");
        this.totalTargetPAD = await this.getDataSettings("TARGET_PAD");
        this.allCountData = await this.getValueDashboard();
        this.reklameCount = this.allCountData.total_reklame ?? 0;
        this.pdamCount = this.allCountData.total_pdam ?? 0;
        this.tataRuangCount = this.allCountData.total_tata_ruang ?? 0;

        let dataReportTourism = this.allCountData.data_report;

        this.totalVilla = dataReportTourism
            .filter((item) => item.kbli_title.toLowerCase() === "vila")
            .reduce((sum, item) => sum + item.total_records, 0);
        this.totalRestoran = dataReportTourism
            .filter((item) => item.kbli_title.toLowerCase() === "restoran")
            .reduce((sum, item) => sum + item.total_records, 0);
        this.totalPariwisata = dataReportTourism.reduce(
            (sum, item) => sum + item.total_records,
            0
        );

        this.bigTargetPAD = new Big(this.totalTargetPAD ?? 0);
        this.bigTotalPotensi = new Big(this.totalPotensi.total ?? 0);
        this.bigTotalLackPotential = this.bigTargetPAD.minus(
            this.bigTotalPotensi
        );
        this.initChartKekuranganPotensi();
        this.initDataValueDashboard();
    }
    async handleChangedDate(filterDate) {
        const totalPotensi = await this.getDataTotalPotensi(filterDate);
        this.bigTotalPotensi = new Big(totalPotensi.total ?? 0);
        this.bigTotalLackPotential = this.bigTargetPAD.minus(
            this.bigTotalPotensi
        );

        this.initChartKekuranganPotensi();
    }
    async getDataTotalPotensi(filterDate) {
        try {
            const response = await fetch(
                `${GlobalConfig.apiHost}/api/bigdata-resume?filterByDate=${filterDate}&type=leader`,
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

            const data = await response.json();
            return {
                total: data.total_potensi.sum,
            };
        } catch (error) {
            console.error("Error fetching chart data:", error);
            return null;
        }
    }
    async getDataSettings(string_key) {
        try {
            const response = await fetch(
                `${GlobalConfig.apiHost}/api/data-settings?search=${string_key}`,
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

            const data = await response.json();
            return data.data[0].value;
        } catch (error) {
            console.error("Error fetching chart data:", error);
            return 0;
        }
    }
    async getValueDashboard() {
        try {
            const response = await fetch(
                `${GlobalConfig.apiHost}/api/dashboard-potential-count`,
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

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Error fetching chart data:", error);
            return 0;
        }
    }
    initChartKekuranganPotensi() {
        document
            .querySelectorAll(".document-count.chart-lack-of-potential")
            .forEach((element) => {
                element.innerText = ``;
            });
        document
            .querySelectorAll(".document-total.chart-lack-of-potential")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    this.bigTotalLackPotential.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-lack-of-potential")
            .forEach((element) => {
                element.innerText = ``;
            });
    }
    initDataValueDashboard() {
        document.getElementById("reklame-count").innerText = this.reklameCount;
        document.getElementById("pdam-count").innerText = this.pdamCount;
        document.getElementById("pbb-bangunan-count").innerText =
            this.tataRuangCount;
        document.getElementById("tata-ruang-count").innerText =
            this.tataRuangCount;
        document.getElementById("tata-ruang-usaha-count").innerText =
            this.tataRuangCount;
        document.getElementById("restoran-count").innerText =
            this.totalRestoran;
        document.getElementById("villa-count").innerText = this.totalVilla;
        document.getElementById("pariwisata-count").innerText =
            this.totalPariwisata;
    }
}
document.addEventListener("DOMContentLoaded", async function (e) {
    await new LackOfPotential().init();
});

function resizeDashboard() {
    let targetElement = document.getElementById("lack-of-potential-wrapper");
    let dashboardElement = document.getElementById(
        "lack-of-potential-fixed-container"
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
