import { Grid } from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";

class TpaTpt {
    constructor() {
        this.toastMessage = document.getElementById("toast-message");
        this.toastElement = document.getElementById("toastNotification");
        this.toast = new bootstrap.Toast(this.toastElement);
        this.table = null;

        // Initialize functions
        this.initTableTpaTpt();
        this.initEvents();
    }
    initEvents() {}
    initTableTpaTpt() {
        let tableContainer = document.getElementById("table-tpa-tpt");

        tableContainer.innerHTML = "";
        let canUpdate = tableContainer.getAttribute("data-updater") === "1";
        let canDelete = tableContainer.getAttribute("data-destroyer") === "1";

        // Kecamatan (districts) in Kabupaten Bandung
        const kecamatanList = [
            "Bojongsoang",
            "Cangkuang",
            "Cicalengka",
            "Cikancung",
            "Cilengkrang",
            "Cileunyi",
            "Cimaung",
            "Cimenyan",
            "Ciparay",
            "Cisaat",
            "Dayeuhkolot",
            "Ibun",
            "Katapang",
            "Kertasari",
            "Kutawaringin",
            "Majalaya",
            "Margaasih",
            "Margahayu",
            "Nagreg",
            "Pacet",
            "Pameungpeuk",
            "Pangalengan",
            "Paseh",
            "Rancaekek",
            "Solokanjeruk",
            "Soreang",
            "Banjaran",
            "Baleendah",
        ];

        // Generate random PT (companies)
        function generateCompanyName() {
            const prefixes = ["PT", "CV", "UD"];
            const names = [
                "Mitra Sejahtera",
                "Berkah Jaya",
                "Makmur Abadi",
                "Sukses Mandiri",
                "Indah Lestari",
                "Tirta Alam",
            ];
            const suffixes = [
                "Indonesia",
                "Sentosa",
                "Persada",
                "Global",
                "Tbk",
                "Jaya",
            ];

            return `${prefixes[Math.floor(Math.random() * prefixes.length)]} ${
                names[Math.floor(Math.random() * names.length)]
            } ${suffixes[Math.floor(Math.random() * suffixes.length)]}`;
        }

        // Function to generate random dummy data
        function generateDummyData(count) {
            let data = [];

            for (let i = 1; i <= count; i++) {
                let name = `TPA ${String.fromCharCode(64 + (i % 26))}${i}`; // Example: TPA A1, TPA B2, etc.
                let location =
                    kecamatanList[
                        Math.floor(Math.random() * kecamatanList.length)
                    ];
                let lat = (-6.9 + Math.random() * 0.3).toFixed(6); // Approximate latitude for Bandung area
                let lng = (107.5 + Math.random() * 0.5).toFixed(6); // Approximate longitude for Bandung area
                let owner = generateCompanyName();

                data.push([name, location, lat, lng, owner]);
            }
            return data;
        }

        this.table = new Grid({
            columns: ["Nama", "Kecamatan", "Lat", "Lng", "Pemilik (PT)"],
            data: generateDummyData(100), // Generate 100 rows of dummy data
            pagination: {
                limit: 10,
            },
            search: true,
            sort: true,
        }).render(tableContainer);
    }
}
document.addEventListener("DOMContentLoaded", function (e) {
    new TpaTpt();
});
