import L from "leaflet";
import "leaflet/dist/leaflet.css";

document.addEventListener("DOMContentLoaded", function () {
    var map = L.map("map").setView([-6.9175, 107.6191], 10); // Bandung

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    // Dapatkan elemen loading
    const loadingDiv = document.getElementById("loading");
    loadingDiv.style.display = "flex"; // Tampilkan loading

    fetch("/storage/maps/rencana-polaruang.geojson")
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
                    let htmlString = feature.properties.description.toString();

                    let match = htmlString.match(
                        /<td>Kode Kawasan<\/td>\s*<td>(.*?)<\/td>/
                    );

                    console.log("Kode Kawasan ", match[1]);

                    let color_code = match[1];

                    return {
                        color: colorMapping[color_code], 
                        fillColor: colorMapping[color_code] || "#cccccc", 
                        fillOpacity: 0.6,
                        weight: 1.5,
                    };
                },
                onEachFeature: function (feature, layer) {
                    if (feature.properties && feature.properties.name) {
                        layer.bindPopup(feature.properties.name);
                    }
                },
            }).addTo(map);

            map.fitBounds(geoLayer.getBounds());

            // Sembunyikan loading setelah selesai render
            loadingDiv.style.display = "none";
        })
        .catch((error) => {
            console.error("Error loading GeoJSON:", error);
            loadingDiv.innerHTML =
                "<div class='loading-text' style='background: red;'>Failed to load data!</div>";
        });
});
