import Big from "big.js";
import GlobalConfig, { addThousandSeparators } from "../../global-config.js";

class DashboardPotentialInsideSystem {
    async init() {
        try {
            // Initialize native date picker
            this.initDatePicker();

            // Initialize default values
            this.bigTotalLackPotential = 0;

            // Fetch data with error handling
            this.totalPotensi = (await this.getDataTotalPotensi("latest")) || {
                total: 0,
            };
            this.totalTargetPAD =
                (await this.getDataSettings("TARGET_PAD")) || 0;
            this.allCountData = (await this.getValueDashboard()) || {};

            // Set counts with safe fallbacks
            this.pdamCount = this.allCountData.total_pdam ?? 0;
            this.tataRuangCount = this.allCountData.tata_ruang.count ?? 0;
            this.tataRuangSum = this.allCountData.tata_ruang.sum ?? 0;
            this.pajakReklameCount = this.allCountData.data_pajak_reklame ?? 0;
            this.surveyLapanganCount = this.allCountData.total_reklame ?? 0;
            this.reklameCount =
                this.pajakReklameCount + this.surveyLapanganCount;
            this.reklameSum =
                (this.pajakReklameCount + this.surveyLapanganCount) * 2500000 ??
                0;
            this.pajakRestoranCount =
                this.allCountData.data_pajak_restoran ?? 0;
            this.pajakHiburanCount = this.allCountData.data_pajak_hiburan ?? 0;
            this.pajakHotelCount = this.allCountData.data_pajak_hotel ?? 0;
            this.pajakParkirCount = this.allCountData.data_pajak_parkir ?? 0;
            this.tataRuangUsahaCount =
                this.allCountData.tata_ruang.business_count ?? 0;
            this.tataRuangNonUsahaCount =
                this.allCountData.tata_ruang.non_business_count ?? 0;
            this.tataRuangUsahaSum =
                this.allCountData.tata_ruang.business_sum ?? 0;
            this.tataRuangNonUsahaSum =
                this.allCountData.tata_ruang.non_business_sum ?? 0;

            // Handle tourism data safely
            let dataReportTourism = this.allCountData.data_report || [];

            this.totalVilla = dataReportTourism
                .filter(
                    (item) =>
                        item.kbli_title &&
                        item.kbli_title.toLowerCase() === "vila"
                )
                .reduce((sum, item) => sum + (item.total_records || 0), 0);
            this.totalRestoran = dataReportTourism
                .filter(
                    (item) =>
                        item.kbli_title &&
                        item.kbli_title.toLowerCase() === "restoran"
                )
                .reduce((sum, item) => sum + (item.total_records || 0), 0);
            this.totalPariwisata = dataReportTourism.reduce(
                (sum, item) => sum + (item.total_records || 0),
                0
            );

            // Calculate big numbers
            this.bigTargetPAD = new Big(this.totalTargetPAD ?? 0);
            this.bigTotalPotensi = new Big(this.totalPotensi.total ?? 0);
            this.bigTotalLackPotential = this.bigTargetPAD.minus(
                this.bigTotalPotensi
            );

            // Initialize charts and data
            this.initChartKekuranganPotensi();
            this.initDataValueDashboard();
            this.initChartTataRuang();
        } catch (error) {
            console.error("Error initializing dashboard:", error);
            // Set safe fallback values
            this.reklameCount = 0;
            this.pdamCount = 0;
            this.tataRuangCount = 0;
            this.tataRuangUsahaCount = 0;
            this.tataRuangNonUsahaCount = 0;
            this.totalVilla = 0;
            this.totalRestoran = 0;
            this.totalPariwisata = 0;
            this.bigTotalLackPotential = new Big(0);

            // Still try to initialize the dashboard with safe values
            this.initDataValueDashboard();
        }
    }
    initDatePicker() {
        const dateInput = document.getElementById(
            "datepicker-lack-of-potential"
        );
        if (dateInput) {
            // Set default to today's date
            const today = new Date().toISOString().split("T")[0];
            dateInput.value = today;

            // Add event listener for date changes
            dateInput.addEventListener("change", (e) => {
                this.handleChangedDate(e.target.value);
            });
        }
    }

    async handleChangedDate(filterDate) {
        try {
            // Convert date to the format expected by API (or use "latest" if empty)
            const apiDate = filterDate || "latest";
            const totalPotensi = await this.getDataTotalPotensi(apiDate);
            this.bigTotalPotensi = new Big(totalPotensi.total ?? 0);
            this.bigTotalLackPotential = this.bigTargetPAD.minus(
                this.bigTotalPotensi
            );

            this.initChartKekuranganPotensi();
        } catch (error) {
            console.error("Error handling date change:", error);
        }
    }
    async getDataTotalPotensi(filterDate) {
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
        // Helper function to safely update elements with class selector
        const safeUpdateElements = (selector, callback) => {
            try {
                const elements = document.querySelectorAll(selector);
                if (elements.length > 0) {
                    elements.forEach(callback);
                } else {
                    console.warn(
                        `No elements found with selector '${selector}'`
                    );
                }
            } catch (error) {
                console.error(
                    `Error updating elements with selector '${selector}':`,
                    error
                );
            }
        };

        safeUpdateElements(
            ".document-count.chart-lack-of-potential",
            (element) => {
                element.innerText = ``;
            }
        );

        safeUpdateElements(
            ".document-total.chart-lack-of-potential",
            (element) => {
                element.innerText = `Rp.${addThousandSeparators(
                    this.bigTotalLackPotential.toString()
                )}`;
            }
        );
    }

    initChartTataRuang() {
        // Helper function to safely update elements with class selector
        const safeUpdateElements = (selector, callback) => {
            try {
                const elements = document.querySelectorAll(selector);
                if (elements.length > 0) {
                    elements.forEach(callback);
                } else {
                    console.warn(
                        `No elements found with selector '${selector}'`
                    );
                }
            } catch (error) {
                console.error(
                    `Error updating elements with selector '${selector}':`,
                    error
                );
            }
        };

        safeUpdateElements(".document-count.chart-tata-ruang", (element) => {
            element.innerText = `${this.tataRuangCount}`;
        });

        safeUpdateElements(".document-total.chart-tata-ruang", (element) => {
            element.innerText = `Rp.${addThousandSeparators(
                this.tataRuangSum.toString()
            )}`;
        });
    }
    initDataValueDashboard() {
        // Helper function to safely set element text
        const safeSetText = (elementId, value) => {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerText = value;
            } else {
                console.warn(`Element with id '${elementId}' not found`);
            }
        };

        safeSetText("reklame-sum", this.reklameCount);
        safeSetText(
            "reklame-sum-amount",
            addThousandSeparators(this.reklameSum.toString())
        );
        safeSetText("survey-lapangan-count", this.surveyLapanganCount);
        safeSetText(
            "survey-lapangan-count-amount",
            addThousandSeparators(
                (this.surveyLapanganCount * 2500000).toString()
            )
        );
        safeSetText("pajak-reklame-count", this.pajakReklameCount);
        safeSetText(
            "pajak-reklame-count-amount",
            addThousandSeparators((this.pajakReklameCount * 2500000).toString())
        );
        safeSetText("restoran-count", this.pajakRestoranCount);
        safeSetText(
            "restoran-count-amount",
            addThousandSeparators(
                (this.pajakRestoranCount * 6200000).toString()
            )
        );
        safeSetText("hiburan-count", this.pajakHiburanCount);
        safeSetText(
            "hiburan-count-amount",
            addThousandSeparators((this.pajakHiburanCount * 6200000).toString())
        );
        safeSetText("hotel-count", this.pajakHotelCount);
        safeSetText(
            "hotel-count-amount",
            addThousandSeparators(this.pajakHotelCount * 6200000).toString()
        );
        safeSetText("parkir-count", this.pajakParkirCount);
        safeSetText(
            "parkir-count-amount",
            addThousandSeparators((this.pajakParkirCount * 6200000).toString())
        );
        safeSetText("pdam-count", this.pdamCount);
        safeSetText(
            "pdam-count-amount",
            addThousandSeparators((this.pdamCount * 285000).toString())
        );
        safeSetText("tata-ruang-count", this.tataRuangCount);
        safeSetText("tata-ruang-usaha-count", this.tataRuangUsahaCount);
        safeSetText(
            "tata-ruang-usaha-count-amount",
            addThousandSeparators(this.tataRuangUsahaSum.toString())
        );
        safeSetText("tata-ruang-non-usaha-count", this.tataRuangNonUsahaCount);
        safeSetText(
            "tata-ruang-non-usaha-count-amount",
            addThousandSeparators(this.tataRuangNonUsahaSum.toString())
        );
        safeSetText("pariwisata-count", this.totalPariwisata);
    }
}
document.addEventListener("DOMContentLoaded", async function (e) {
    await new DashboardPotentialInsideSystem().init();
});

function handleCircleClick(element) {
    const url = element.getAttribute("data-url") || "#";
    if (url !== "#") {
        window.location.href = url;
    }
}

function resizeDashboard() {
    let targetElement = document.getElementById("lack-of-potential-wrapper");
    let dashboardElement = document.getElementById(
        "lack-of-potential-fixed-container"
    );

    // Check if required elements exist
    if (!targetElement || !dashboardElement) {
        console.warn("Required elements for dashboard resize not found");
        return;
    }

    let targetWidth = targetElement.offsetWidth;
    let dashboardWidth = 1400;

    let scaleFactor = (targetWidth / dashboardWidth).toFixed(2);

    // Prevent scaling beyond 1 (100%) to avoid overflow
    scaleFactor = Math.min(scaleFactor, 1);

    dashboardElement.style.transformOrigin = "left top";
    dashboardElement.style.transition = "transform 0.2s ease-in-out";
    dashboardElement.style.transform = `scale(${scaleFactor})`;

    // Fix SVG scaling issue - reset SVG transform to prevent oversized icons
    const svgElements = dashboardElement.querySelectorAll("svg");
    svgElements.forEach((svg) => {
        svg.style.transform = `scale(${1 / scaleFactor})`;
        svg.style.transformOrigin = "center";
        svg.style.width = "17px";
        svg.style.height = "17px";
    });

    // Flatpickr removed - using native HTML date input

    // Ensure horizontal scrolling is allowed if necessary
    if (document.body) {
        document.body.style.overflowX = "auto";
    }
}

// Debounced function for better server performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Flatpickr functions removed - using native HTML date input

window.addEventListener("load", resizeDashboard);
window.addEventListener("resize", debounce(resizeDashboard, 100));

// Removed Flatpickr event listeners - no longer needed

// MutationObserver removed - no longer needed without Flatpickr
