import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig, { addThousandSeparators } from "../../global-config.js";
import GeneralTable from "../../table-generator.js";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

// Ambil hak akses dari data-attribute
const tableElement = document.getElementById("tourisms-data-table");
const canView = "1";
const canUpdate = tableElement.getAttribute("data-updater") === "1";
const canDelete = tableElement.getAttribute("data-destroyer") === "1";
let menuId = document.getElementById("menuId").value;

const dataTourismsColumns = [
    "No",
    "Nama Perusahaan",
    "Nama Proyek",
    "Alamat Usaha",
    "Kecamatan",
    "Desa",
    "Luas Tanah (m2)",
    "Jumlah Investasi",
    "TKI",
    "Longitude",
    "Latitude",
    {
        name: "Actions",
        widht: "120px",
        formatter: function (cell, row) {
            const id = row.cells[11].data;
            const district = row.cells[4].data;
            const long = row.cells[9].data;
            const lat = row.cells[10].data;
            const model = "data/web-tourisms";

            let actionButtons = '<div class="d-flex justify-items-end gap-10">';
            let hasPrivilege = false;

            // Tampilkan tombol View jika user punya akses view
            if (canView) {
                hasPrivilege = true;
                actionButtons += `
                <button class="btn btn-info me-2 btn-view" 
                    data-long="${long}" data-lat="${lat}" data-district="${district}">
                    <i class='bx bx-map'></i>
                </button>`;
            }

            // Tampilkan tombol Edit jika user punya akses update
            if (canUpdate) {
                hasPrivilege = true;
                actionButtons += `
                    <button class="btn btn-warning me-2 btn-edit"
                        data-id="${id}"
                        data-model="${model}"
                        data-menu="${menuId}">
                        <i class='bx bx-edit'></i>
                    </button>`;
            }

            // Tampilkan tombol Delete jika user punya akses delete
            if (canDelete) {
                hasPrivilege = true;
                actionButtons += `
                    <button class="btn btn-red btn-delete" 
                        data-id="${id}">
                        <i class='bx bxs-trash'></i>
                    </button>`;
            }

            actionButtons += "</div>";
            // Jika tidak memiliki akses, tampilkan teks "No Privilege"
            return gridjs.html(
                hasPrivilege
                    ? actionButtons
                    : '<span class="text-muted">No Privilege</span>'
            );
        },
    },
];

document.addEventListener("DOMContentLoaded", () => {
    const table = new GeneralTable(
        "tourisms-data-table",
        `${GlobalConfig.apiHost}/api/tourisms`,
        `${GlobalConfig.apiHost}`,
        dataTourismsColumns
    );
    const customIcon = new L.Icon({
        iconUrl: "/leaflet/marker-icon.png",
        iconRetinaUrl: "leaflet/marker-icon-2x.png",
        shadowUrl: "/leaflet/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
    });

    table.processData = function (data) {
        return data.data.map((item) => {
            return [
                item.no,
                item.business_name,
                item.project_name,
                item.business_address,
                item.district_name,
                item.village_name,
                item.land_area_in_m2,
                addThousandSeparators(item.investment_amount),
                item.tki,
                item.longitude,
                item.latitude,
                item.id,
            ];
        });
    };

    table.init();

    // Event listener untuk tombol "View" yang memunculkan modal
    // document.addEventListener("click", function (e) {
    //     if (e.target && e.target.classList.contains("btn-view")) {
    //         const long = e.target.getAttribute("data-long");
    //         const lat = e.target.getAttribute("data-lat");

    //         // Menyiapkan URL iframe dengan koordinat yang didapatkan
    //         const iframeSrc = `https://www.google.com/maps?q=${lat},${long}&hl=es;z=14&output=embed`;

    //         // Menemukan modal dan iframe di dalam modal
    //         const modal = document.querySelector(".modalGMaps");
    //         const iframe = modal.querySelector("iframe");

    //         // Set src iframe untuk menampilkan peta dengan koordinat yang relevan
    //         iframe.src = iframeSrc;

    //         // Menampilkan modal
    //         var modalInstance = new bootstrap.Modal(modal);
    //         modalInstance.show();
    //     }
    // });

    let map;
    let geoLayer;
    // Event listener untuk tombol "View" yang memunculkan modal dengan Leaflet
    document.addEventListener("click", function (e) {
        if (e.target && e.target.classList.contains("btn-view")) {
            const long = parseFloat(e.target.getAttribute("data-long"));
            const lat = parseFloat(e.target.getAttribute("data-lat"));
            const district = e.target.getAttribute("data-district");

            const modal = document.querySelector(".modalGMaps");
            var modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            const loading = document.getElementById("loading");
            loading.style.display = "block";

            setTimeout(() => {
                if (!map) {
                    map = L.map("map").setView([lat, long], 14);

                    // Tambahkan tile layer (peta dasar)
                    L.tileLayer(
                        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
                        {
                            attribution: "&copy; OpenStreetMap contributors",
                        }
                    ).addTo(map);
                } else {
                    map.setView([lat, long], 14);
                }

                if (geoLayer) {
                    map.removeLayer(geoLayer);
                }

                // Tambahkan marker untuk lokasi
                L.marker([lat, long], { icon: customIcon })
                    .addTo(map)
                    .bindPopup(
                        `<b>${district}</b><br>Lat: ${lat}, Long: ${long}`
                    )
                    .openPopup();

                // Tambahkan GeoJSON ke dalam peta
                fetch(`/storage/maps/tourisms/${district.toUpperCase()}.json`)
                    .then((res) => res.json())
                    .then((geojson) => {
                        let colorMapping = {
                            BJ: "rgb(235, 30, 30)",
                            BA: "rgb(151, 219, 242)",
                            CA: "rgb(70, 70, 165)",
                            "P-2": "rgb(230, 255, 75)",
                            HL: "rgb(50, 95, 40)",
                            HPT: "rgb(75, 155, 55)",
                            HP: "rgb(125, 180, 55)",
                            W: "rgb(255, 165, 255)",
                            PTL: "rgb(0, 255, 205)",
                            "IK-2": "rgb(130, 185, 210)",
                            "P-3": "rgb(175, 175, 55)",
                            PS: "rgb(5, 215, 215)",
                            PD: "rgb(235, 155, 60)",
                            PK: "rgb(245, 155, 30)",
                            HK: "rgb(155, 0, 255)",
                            KPI: "rgb(105, 0, 0)",
                            MBT: "rgb(95, 115, 145)",
                            "P-4": "rgb(185, 235, 185)",
                            TB: "rgb(70, 150, 255)",
                            "P-1": "rgb(200, 245, 70)",
                            TR: "rgb(215, 55, 0)",
                            THR: "rgb(185, 165, 255)",
                            TWA: "rgb(210, 190, 255)",
                        };
                        var geoLayer = L.geoJSON(geojson, {
                            style: function (feature) {
                                let htmlString =
                                    feature.properties.description.toString();
                                let match = htmlString.match(
                                    /<td>Kode Zona<\/td>\s*<td>(.*?)<\/td>/
                                );

                                let color_code = match[1];
                                return {
                                    color: colorMapping[color_code],
                                    fillColor:
                                        colorMapping[color_code] || "#cccccc",
                                    fillOpacity: 0.6,
                                    weight: 1.5,
                                };
                            },
                            onEachFeature: function (feature, layer) {
                                if (
                                    feature.properties &&
                                    feature.properties.name
                                ) {
                                    layer.bindPopup(feature.properties.name);
                                }
                            },
                        }).addTo(map);
                        map.fitBounds(geoLayer.getBounds());
                        loading.style.display = "none";
                    })
                    .catch((error) => {
                        console.error("Error loading GeoJSON:", error);
                        loading.style.display = "none";
                    });
            }, 500);
        }
    });
});
