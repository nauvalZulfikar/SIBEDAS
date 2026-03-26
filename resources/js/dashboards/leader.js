import Big from "big.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";
import InitDatePicker from "../utils/InitDatePicker.js";

class BigData {
    async init() {
        try {
            new InitDatePicker(
                "#datepicker-dashboard-bigdata",
                this.handleChangeDate.bind(this)
            ).init();

            // Load initial data
            this.updateData("latest");
        } catch (error) {
            console.error("Error initializing data:", error);
        }
    }

    handleChangeDate(filterDate) {
        if (!filterDate) return;
        this.updateData(filterDate);
    }
    async updateData(filterDate) {
        try {
            this.resumeBigData = await this.getBigDataResume(filterDate);

            this.initChartTargetPAD(filterDate);
            this.initChartUsaha();
            this.initChartNonUsaha();
            this.initChartTotalPotensi();
            this.initChartVerificationDocuments();
            this.initChartNonVerificationDocuments();
            this.initChartKekuranganPotensi();
            this.initChartRealisasiTerbitPBG();
            this.initChartMenungguKlikDPMPTSP();
            this.initChartProsesDinasTeknis();
            this.initChartPotensiTataRuang();
        } catch (e) {
            console.error(e);
        }
    }

    async getBigDataResume(filterByDate) {
        try {
            const response = await fetch(
                `${GlobalConfig.apiHost}/api/bigdata-resume?filterByDate=${filterByDate}&&type=leader`,
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
            return null;
        }
    }

    initChartTargetPAD(filterDate) {
        const year =
            filterDate === "latest"
                ? new Date().getFullYear()
                : new Date(filterDate).getFullYear();
        document
            .querySelectorAll(".document-title.chart-target-pad")
            .forEach((element) => {
                element.innerText = `Target PAD ${year}`;
            });
        document
            .querySelectorAll(".document-count.chart-target-pad")
            .forEach((element) => {
                element.innerText = ``;
            });
        document
            .querySelectorAll(".document-total.chart-target-pad")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    this.resumeBigData.target_pad.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-target-pad")
            .forEach((element) => {
                element.innerText = `${this.resumeBigData.target_pad.percentage}%`;
            });
    }
    initChartTotalPotensi() {
        // const countAll = this.resultDataTotal.countData ?? 0;

        document
            .querySelectorAll(".document-count.chart-total-potensi")
            .forEach((element) => {
                // element.innerText = `${countAll}`;
                element.innerText = `${this.resumeBigData.total_potensi.count}`;
            });
        document
            .querySelectorAll(".document-total.chart-total-potensi")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.bigTotalPotensi.toString()
                    this.resumeBigData.total_potensi.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-total-potensi")
            .forEach((element) => {
                // element.innerText = `${this.resultPercentage}%`;
                element.innerText = `${this.resumeBigData.total_potensi.percentage}%`;
            });
    }
    initChartVerificationDocuments() {
        document
            .querySelectorAll(".document-count.chart-berkas-terverifikasi")
            .forEach((element) => {
                // element.innerText = `${this.dataVerification.count}`;
                element.innerText = `${this.resumeBigData.verified_document.count}`;
            });
        document
            .querySelectorAll(".document-total.chart-berkas-terverifikasi")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.bigTotalVerification.toString()
                    this.resumeBigData.verified_document.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-berkas-terverifikasi")
            .forEach((element) => {
                // element.innerText = `${this.percetageResultVerification}%`;
                element.innerText = `${this.resumeBigData.verified_document.percentage}%`;
            });
    }
    initChartNonVerificationDocuments() {
        document
            .querySelectorAll(
                ".document-count.chart-berkas-belum-terverifikasi"
            )
            .forEach((element) => {
                // element.innerText = `${this.dataNonVerification.count}`;
                element.innerText = `${this.resumeBigData.non_verified_document.count}`;
            });
        document
            .querySelectorAll(
                ".document-total.chart-berkas-belum-terverifikasi"
            )
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.bigTotalNonVerification.toString()
                    this.resumeBigData.non_verified_document.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(
                ".small-percentage.chart-berkas-belum-terverifikasi"
            )
            .forEach((element) => {
                // element.innerText = `${this.percentageResultNonVerification}%`;
                element.innerText = `${this.resumeBigData.non_verified_document.percentage}%`;
            });
    }
    initChartUsaha() {
        document
            .querySelectorAll(".document-count.chart-business")
            .forEach((element) => {
                // element.innerText = `${this.dataBusiness.count}`;
                element.innerText = `${this.resumeBigData.business_document.count}`;
            });
        document
            .querySelectorAll(".document-total.chart-business")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.bigTotalBusiness.toString()
                    this.resumeBigData.business_document.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-business")
            .forEach((element) => {
                // element.innerText = `${this.percentageResultBusiness}%`;
                element.innerText = `${this.resumeBigData.business_document.percentage}%`;
            });
    }
    initChartNonUsaha() {
        document
            .querySelectorAll(".document-count.chart-non-business")
            .forEach((element) => {
                // element.innerText = `${this.dataNonBusiness.count}`;
                element.innerText = `${this.resumeBigData.non_business_document.count}`;
            });
        document
            .querySelectorAll(".document-total.chart-non-business")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.bigTotalNonBusiness.toString()
                    this.resumeBigData.non_business_document.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-non-business")
            .forEach((element) => {
                // element.innerText = `${this.percentageResultNonBusiness}%`;
                element.innerText = `${this.resumeBigData.non_business_document.percentage}%`;
            });
    }
    initChartKekuranganPotensi() {
        document
            .querySelectorAll(".document-count.chart-kekurangan-potensi")
            .forEach((element) => {
                element.innerText = ``;
            });
        document
            .querySelectorAll(".document-total.chart-kekurangan-potensi")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.totalKekuranganPotensi.toString()
                    this.resumeBigData.kekurangan_potensi.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-kekurangan-potensi")
            .forEach((element) => {
                // element.innerText = `${this.percentageKekuranganPotensi}%`;
                element.innerText = `${this.resumeBigData.kekurangan_potensi.percentage}%`;
            });
    }
    initChartRealisasiTerbitPBG() {
        document
            .querySelectorAll(".document-count.chart-realisasi-tebit-pbg")
            .forEach((element) => {
                // element.innerText = `${this.dataCountRealisasiTerbit}`;
                element.innerText = `${this.resumeBigData.realisasi_terbit.count}`;
            });
        document
            .querySelectorAll(".document-total.chart-realisasi-tebit-pbg")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.dataSumRealisasiTerbit
                    this.resumeBigData.realisasi_terbit.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-realisasi-tebit-pbg")
            .forEach((element) => {
                element.innerText = `${this.resumeBigData.realisasi_terbit.percentage}%`;
            });
    }
    initChartMenungguKlikDPMPTSP() {
        document
            .querySelectorAll(".document-count.chart-menunggu-klik-dpmptsp")
            .forEach((element) => {
                // element.innerText = `${this.dataCountMenungguKlikDPMPTSP}`;
                element.innerText = `${this.resumeBigData.menunggu_klik_dpmptsp.count}`;
            });
        document
            .querySelectorAll(".document-total.chart-menunggu-klik-dpmptsp")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.dataSumMenungguKlikDPMPTSP
                    this.resumeBigData.menunggu_klik_dpmptsp.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-menunggu-klik-dpmptsp")
            .forEach((element) => {
                element.innerText = `${this.resumeBigData.menunggu_klik_dpmptsp.percentage}%`;
            });
    }
    initChartProsesDinasTeknis() {
        document
            .querySelectorAll(".document-count.chart-proses-dinas-teknis")
            .forEach((element) => {
                // element.innerText = `${this.dataCountProsesDinasTeknis}`;
                element.innerText = `${this.resumeBigData.proses_dinas_teknis.count}`;
            });
        document
            .querySelectorAll(".document-total.chart-proses-dinas-teknis")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    // this.dataSumProsesDinasTeknis
                    this.resumeBigData.proses_dinas_teknis.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-proses-dinas-teknis")
            .forEach((element) => {
                element.innerText = `${this.resumeBigData.proses_dinas_teknis.percentage}%`;
            });
    }
    initChartPotensiTataRuang() {
        document
            .querySelectorAll(".document-count.chart-potensi-tata-ruang")
            .forEach((element) => {
                element.innerText = `${this.resumeBigData.tata_ruang.count}`;
            });
        document
            .querySelectorAll(".document-total.chart-potensi-tata-ruang")
            .forEach((element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    this.resumeBigData.tata_ruang.sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-potensi-tata-ruang")
            .forEach((element) => {
                element.innerText = `${this.resumeBigData.tata_ruang.percentage}%`;
            });
    }
}

document.addEventListener("DOMContentLoaded", async function (e) {
    await new BigData().init();
});

// function resizeDashboard() {
//     //Target Width
//     let targetElement = document.getElementById("dashboard-fixed-wrapper");
//     let targetWidth = targetElement.offsetWidth;
//     //console.log("TARGET ",targetWidth);

//     //Real Object Width
//     let dashboardElement = document.getElementById("dashboard-fixed-container");
//     let dashboardWidth = 1110; //dashboardElement.offsetWidth;
//     //console.log("CURRENT ",dashboardWidth);

//     if (targetWidth > dashboardWidth) {
//         targetWidth = dashboardWidth;
//     }

//     dashboardElement.style.transformOrigin = "left top";
//     dashboardElement.style.transition = "transform 0.2s ease-in-out";
//     dashboardElement.style.transform =
//         "scale(" + (targetWidth / dashboardWidth).toFixed(2) + ")";
//     //console.log("SCALE ", (targetWidth/dashboardWidth).toFixed(2));
// }

// window.addEventListener("load", function () {
//     resizeDashboard();
// });

// window.addEventListener("resize", function () {
//     resizeDashboard();
// });

function resizeDashboard() {
    let targetElement = document.getElementById("dashboard-fixed-wrapper");
    let dashboardElement = document.getElementById("dashboard-fixed-container");

    let targetWidth = targetElement.offsetWidth;
    let dashboardWidth = 1110;

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
