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
    await new DashboardPotentialOutsideSystem().init();
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
