import ApexCharts from "apexcharts";
import { addThousandSeparators } from "../../global-config.js";
class GrowthReport {
    init() {
        this.loadChart();
    }

    async loadChart() {
        try {
            const chartElement = document.getElementById("chart-growth-report");
            const apiUrl = chartElement.dataset.url;

            const token = document
                .querySelector('meta[name="api-token"]')
                .getAttribute("content");

            const response = await fetch(apiUrl, {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json",
                },
            });

            const data = await response.json();

            console.log("data", data);

            const categories = data.map((item) => item.date);

            const potentionSeries = {
                name: "Potensi Berkas",
                data: data.map((item) => item.potention_sum),
            };

            const verifiedSeries = {
                name: "Terverifikasi",
                data: data.map((item) => item.verified_sum),
            };

            const nonVerifiedSeries = {
                name: "Belum Terverifikasi",
                data: data.map((item) => item.non_verified_sum),
            };

            const options = {
                chart: {
                    type: "bar",
                    height: "auto",
                },
                title: {
                    text: "Grafik Pertumbuhan",
                },
                dataLabels: {
                    enabled: false,
                },
                legend: {
                    show: true,
                },
                xaxis: {
                    categories: categories,
                },
                yaxis: {
                    title: {
                        text: "Total SUM Per Date",
                    },
                    labels: {
                        formatter: function (value) {
                            return "Rp. " + addThousandSeparators(value);
                        },
                    },
                },
                noData: {
                    text: "Data tidak tersedia",
                    align: "center",
                    verticalAlign: "middle",
                    style: {
                        color: "#999",
                        fontSize: "16px",
                    },
                },
                series: [potentionSeries, verifiedSeries, nonVerifiedSeries],
            };

            const chart = new ApexCharts(chartElement, options);
            chart.render();
        } catch (error) {
            console.error("Failed to load growth report data:", error);
        }
    }
}

document.addEventListener("DOMContentLoaded", function () {
    new GrowthReport().init();
});
