import Big from "big.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";
import InitDatePicker from "../utils/InitDatePicker.js";

class BigData {
    // Helper function to safely access nested object properties with default values
    safeGet(obj, path, defaultValue = 0) {
        try {
            return path.split(".").reduce((current, key) => {
                return current &&
                    current[key] !== null &&
                    current[key] !== undefined
                    ? current[key]
                    : defaultValue;
            }, obj);
        } catch (error) {
            return defaultValue;
        }
    }

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

            // Ensure resumeBigData is not null/undefined
            if (!this.resumeBigData) {
                this.resumeBigData = {};
                console.warn(
                    "No data received, using empty object with default values"
                );
            }

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
            this.initChartBusinessRAB();
            this.initChartBusinessKRK();
            this.initChartNonBusinessRAB();
            this.initChartNonBusinessKRK();
            this.initChartNonBusinessDLH();
            this.initChartPbgPayment();
        } catch (e) {
            console.error(e);
            // Initialize with empty data if there's an error
            this.resumeBigData = {};
        }
    }

    async getBigDataResume(filterByDate) {
        try {
            const response = await fetch(
                `${GlobalConfig.apiHost}/api/bigdata-resume?filterByDate=${filterByDate}&type=simbg`,
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
                const sum = this.safeGet(
                    this.resumeBigData,
                    "target_pad.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-target-pad")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "target_pad.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartTotalPotensi() {
        // const countAll = this.resultDataTotal.countData ?? 0;

        document
            .querySelectorAll(".document-count.chart-total-potensi")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "total_potensi.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-total-potensi")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "total_potensi.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-total-potensi")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "total_potensi.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartVerificationDocuments() {
        document
            .querySelectorAll(".document-count.chart-berkas-terverifikasi")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "verified_document.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-berkas-terverifikasi")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "verified_document.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-berkas-terverifikasi")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "verified_document.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartNonVerificationDocuments() {
        document
            .querySelectorAll(
                ".document-count.chart-berkas-belum-terverifikasi"
            )
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "non_verified_document.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(
                ".document-total.chart-berkas-belum-terverifikasi"
            )
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "non_verified_document.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(
                ".small-percentage.chart-berkas-belum-terverifikasi"
            )
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "non_verified_document.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartUsaha() {
        document
            .querySelectorAll(".document-count.chart-business")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "business_document.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-business")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "business_document.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-business")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "business_document.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartNonUsaha() {
        document
            .querySelectorAll(".document-count.chart-non-business")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "non_business_document.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-non-business")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "non_business_document.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-non-business")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "non_business_document.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
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
                const sum = this.safeGet(
                    this.resumeBigData,
                    "kekurangan_potensi.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-kekurangan-potensi")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "kekurangan_potensi.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartRealisasiTerbitPBG() {
        document
            .querySelectorAll(".document-count.chart-realisasi-tebit-pbg")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "realisasi_terbit.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-realisasi-tebit-pbg")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "realisasi_terbit.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-realisasi-tebit-pbg")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "realisasi_terbit.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartMenungguKlikDPMPTSP() {
        document
            .querySelectorAll(".document-count.chart-menunggu-klik-dpmptsp")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "menunggu_klik_dpmptsp.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-menunggu-klik-dpmptsp")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "menunggu_klik_dpmptsp.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-menunggu-klik-dpmptsp")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "menunggu_klik_dpmptsp.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartProsesDinasTeknis() {
        document
            .querySelectorAll(".document-count.chart-proses-dinas-teknis")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "proses_dinas_teknis.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-proses-dinas-teknis")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "proses_dinas_teknis.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-proses-dinas-teknis")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "proses_dinas_teknis.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartPotensiTataRuang() {
        document
            .querySelectorAll(".document-count.chart-potensi-tata-ruang")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "tata_ruang.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-potensi-tata-ruang")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "tata_ruang.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-potensi-tata-ruang")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "tata_ruang.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
            });
    }
    initChartBusinessRAB() {
        document.querySelectorAll("#business-rab-count").forEach((element) => {
            const count = this.safeGet(
                this.resumeBigData,
                "business_rab_count",
                0
            );
            element.innerText = `${count}`;
        });
    }
    initChartBusinessKRK() {
        document.querySelectorAll("#business-krk-count").forEach((element) => {
            const count = this.safeGet(
                this.resumeBigData,
                "business_krk_count",
                0
            );
            element.innerText = `${count}`;
        });
    }
    initChartBusinessDLH() {
        document.querySelectorAll("#business-dlh-count").forEach((element) => {
            const count = this.safeGet(
                this.resumeBigData,
                "business_dlh_count",
                0
            );
            element.innerText = `${count}`;
        });
    }
    initChartNonBusinessRAB() {
        document
            .querySelectorAll("#non-business-rab-count")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "non_business_rab_count",
                    0
                );
                element.innerText = `${count}`;
            });
    }
    initChartNonBusinessKRK() {
        document
            .querySelectorAll("#non-business-krk-count")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "non_business_krk_count",
                    0
                );
                element.innerText = `${count}`;
            });
    }
    initChartNonBusinessDLH() {
        document.querySelectorAll("#business-dlh-count").forEach((element) => {
            const count = this.safeGet(
                this.resumeBigData,
                "business_dlh_count",
                0
            );
            element.innerText = `${count}`;
        });
    }

    initChartPbgPayment() {
        document
            .querySelectorAll(".document-count.chart-payment-pbg-task")
            .forEach((element) => {
                const count = this.safeGet(
                    this.resumeBigData,
                    "pbg_task_payments.count",
                    0
                );
                element.innerText = `${count}`;
            });
        document
            .querySelectorAll(".document-total.chart-payment-pbg-task")
            .forEach((element) => {
                const sum = this.safeGet(
                    this.resumeBigData,
                    "pbg_task_payments.sum",
                    0
                );
                element.innerText = `Rp.${addThousandSeparators(
                    sum.toString()
                )}`;
            });
        document
            .querySelectorAll(".small-percentage.chart-payment-pbg-task")
            .forEach((element) => {
                const percentage = this.safeGet(
                    this.resumeBigData,
                    "pbg_task_payments.percentage",
                    0
                );
                element.innerText = `${percentage}%`;
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
