import Big from "big.js";
import GlobalConfig, { addThousandSeparators } from "../global-config.js";
import ApexCharts from "apexcharts";
import "gridjs/dist/gridjs.umd.js";
import GeneralTable from "../table-generator.js";
import InitDatePicker from "../utils/InitDatePicker.js";

var chart;

class DashboardPBG {
    async init() {
        try {
            new InitDatePicker(
                "#datepicker-dashboard-pbg",
                this.handleChangedDate.bind(this)
            ).init();

            // Load initial data
            this.updateData("latest");
        } catch (error) {
            console.error("Error initializing data:", error);
        }
    }

    handleChangedDate(filterDate) {
        if (!filterDate) return;
        this.updateData(filterDate);
    }

    async updateData(filterDate) {
        let resumeData = await this.getResume(filterDate);
        if (!resumeData) return;

        let targetPAD = resumeData.target_pad.sum;
        const targetPadElement = document.getElementById("target-pad");
        targetPadElement.textContent = formatCurrency(targetPAD);

        const totalPotensiBerkas = document.getElementById(
            "total-potensi-berkas"
        );
        totalPotensiBerkas.textContent = formatCurrency(
            resumeData.total_potensi.sum
        );

        const totalBerkasTerverifikasi = document.getElementById(
            "total-berkas-terverifikasi"
        );
        totalBerkasTerverifikasi.textContent = formatCurrency(
            resumeData.verified_document.sum
        );

        const totalKekuranganPotensi = document.getElementById(
            "total-kekurangan-potensi"
        );
        totalKekuranganPotensi.textContent = formatCurrency(
            resumeData.kekurangan_potensi.sum
        );

        const totalPotensiPBGTataRuang = document.getElementById(
            "total-potensi-pbd-tata-ruang"
        );
        totalPotensiPBGTataRuang.textContent = "Rp.-";

        const totalBerkasBelumTerverifikasi = document.getElementById(
            "total-berkas-belum-terverifikasi"
        );
        totalBerkasBelumTerverifikasi.textContent = formatCurrency(
            resumeData.non_verified_document.sum
        );

        const totalRealisasiTerbitPBG = document.getElementById(
            "realisasi-terbit-pbg"
        );
        totalRealisasiTerbitPBG.textContent = formatCurrency(
            resumeData.realisasi_terbit.sum
        );

        const totalProsesDinasTeknis = document.getElementById(
            "processing-technical-services"
        );
        totalProsesDinasTeknis.textContent = formatCurrency(
            resumeData.proses_dinas_teknis.sum
        );

        await this.initPieChart(resumeData);
    }

    async initPieChart(data) {
        // Total Berkas Usaha
        const totalBerkasUsahaTotalData = data.verified_document.sum;

        // Total Berkas Non Usaha
        const totalBerkasNonUsahaTotalData = data.non_verified_document.sum;

        // Pie Chart Section
        let persenUsaha = data.verified_document.percentage;

        let persenNonUsaha = data.non_verified_document.percentage;

        const dataSeriesPieChart = [
            Number(persenUsaha),
            Number(persenNonUsaha),
        ];
        const labelsPieChart = ["Berkas Usaha", "Berkas Non Usaha"];
        document.querySelector("td[data-category='non-usaha']").textContent =
            formatCurrency(totalBerkasNonUsahaTotalData).toLocaleString();
        document.querySelector(
            "td[data-category='non-usaha-percentage']"
        ).textContent = persenNonUsaha + "%";

        document.querySelector("td[data-category='usaha']").textContent =
            formatCurrency(totalBerkasUsahaTotalData).toLocaleString();
        document.querySelector(
            "td[data-category='usaha-percentage']"
        ).textContent = persenUsaha + "%";

        updatePieChart(dataSeriesPieChart, labelsPieChart);
    }

    async getResume(filterByDate) {
        try {
            const response = await fetch(
                `${GlobalConfig.apiHost}/api/bigdata-resume?filterByDate=${filterByDate}&type=leader`,
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
}
document.addEventListener("DOMContentLoaded", async function (e) {
    await new DashboardPBG().init();
    await initChart();

    async function updateDataByYear() {
        // Load all Tourism location
        const allLocation = await getAllLocation();
        console.log(allLocation);

        // Filter hanya data yang memiliki angka valid
        let validLocations = allLocation.dataLocation.filter((loc) => {
            return (
                !isNaN(parseFloat(loc.longitude)) &&
                !isNaN(parseFloat(loc.latitude))
            );
        });

        // Ubah string ke float
        validLocations = validLocations.map((loc) => ({
            name: loc.project_name,
            longitude: parseFloat(loc.longitude),
            latitude: parseFloat(loc.latitude),
        }));

        console.log(validLocations.name);

        // Inisialisasi peta
        var map = L.map("map").setView([-7.023, 107.5275], 10);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(map);

        // Tambahkan marker ke peta
        validLocations.forEach(function (loc) {
            L.marker([loc.latitude, loc.longitude])
                .addTo(map)
                .bindPopup(`<b>${loc.name}</b>`) // Popup saat diklik
                .bindTooltip(loc.name, { permanent: false, direction: "top" }); // Tooltip saat di-hover
        });

        // Load Tabel Baru di Update
        const tableLastUpdated = new GeneralTable(
            "pbg-filter-by-updated-at",
            `${GlobalConfig.apiHost}/api/api-pbg-task?isLastUpdated=true`,
            `${GlobalConfig.apiHost}`,
            pbgTaskColumns
        );

        tableLastUpdated.processData = function (data) {
            console.log("Response Data:", data);
            return data.data.map((item) => {
                return [
                    item.no,
                    item.name,
                    item.registration_number,
                    item.document_number,
                    item.address,
                ];
            });
        };

        tableLastUpdated.init();

        // Load Tabel Status SK Terbit
        const tableSKPBGTerbit = new GeneralTable(
            "pbg-filter-by-status",
            `${GlobalConfig.apiHost}/api/api-pbg-task?isLastUpdated=false`,
            `${GlobalConfig.apiHost}`,
            pbgTaskColumns
        );

        tableSKPBGTerbit.processData = function (data) {
            console.log("Response Data:", data);
            return data.data.map((item) => {
                return [
                    item.no,
                    item.name,
                    item.registration_number,
                    item.document_number,
                    item.address,
                ];
            });
        };

        tableSKPBGTerbit.init();

        document.querySelector(
            "#pbg-filter-by-updated-at .gridjs-search"
        ).hidden = true;
        document.querySelector(
            "#pbg-filter-by-updated-at .gridjs-footer"
        ).hidden = true;
        document.querySelector(
            "#pbg-filter-by-status .gridjs-search"
        ).hidden = true;
        document.querySelector(
            "#pbg-filter-by-status .gridjs-footer"
        ).hidden = true;
    }

    await updateDataByYear();
});

async function getAllLocation() {
    try {
        const response = await fetch(
            `${GlobalConfig.apiHost}/api/get-all-location`,
            {
                credentials: "include",
                headers: {
                    Authorization: `Bearer ${
                        document.querySelector("meta[name='api-token']").content
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
            dataLocation: data.data,
        };
    } catch (error) {
        console.error("Error fetching chart data:", error);
        return 0;
    }
}

async function initChart() {
    var options = {
        chart: {
            height: 180,
            type: "donut",
        },
        series: [0, 0], // Inisialisasi dengan nilai awal
        labels: ["Berkas Usaha", "Berkas Non Usaha"],
        legend: {
            show: false,
        },
        stroke: {
            width: 0,
        },
        plotOptions: {
            pie: {
                donut: {
                    size: "60%",
                },
            },
        },
        colors: ["#7e67fe", "#17c553"],
        dataLabels: {
            enabled: false,
        },
        responsive: [
            {
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200,
                    },
                },
            },
        ],
        fill: {
            type: "gradient",
        },
    };

    chart = new ApexCharts(document.querySelector("#conversions"), options);
    chart.render();
}

async function updatePieChart(dataSeries, labels) {
    if (!Array.isArray(dataSeries) || dataSeries.length === 0) {
        console.error("Data series tidak valid:", dataSeries);
        return;
    }

    // Perbarui data series chart
    chart.updateSeries(dataSeries);

    // Perbarui label jika diperlukan
    if (Array.isArray(labels) && labels.length === dataSeries.length) {
        chart.updateOptions({
            labels: labels,
        });
    }
}

// Fungsi untuk format angka ke Rupiah
function formatCurrency(number) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
}

const pbgTaskColumns = [
    "No",
    "Name",
    "Nomor Registrasi",
    "Nomor Dokumen",
    "Alamat",
];
