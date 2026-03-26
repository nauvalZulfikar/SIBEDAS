import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig, { addThousandSeparators } from "../../global-config.js";
import GeneralTable from "../../table-generator.js";
import moment from "moment";

// Ambil hak akses dari data-attribute
const tableElement = document.getElementById("spatial-planning-data-table");
const canUpdate = tableElement.getAttribute("data-updater") === "1";
const canDelete = tableElement.getAttribute("data-destroyer") === "1";
let menuId = document.getElementById("menuId").value;

const dataSpatialPlanningColumns = [
    "No",
    "Nama",
    "KBLI",
    "Kegiatan",
    "Luas Lahan",
    "BCR",
    "Jenis Usaha",
    "Status Terbit",
    "Retribusi",
    "Lokasi",
    "Nomor",
    "Tanggal",
    {
        name: "Actions",
        widht: "120px",
        formatter: function (cell, row) {
            const id = row.cells[12].data;
            const model = "data/web-spatial-plannings";

            let actionButtons = '<div class="d-flex justify-items-end gap-10">';
            let hasPrivilege = false;

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
        "spatial-planning-data-table",
        `${GlobalConfig.apiHost}/api/spatial-plannings`,
        `${GlobalConfig.apiHost}`,
        dataSpatialPlanningColumns
    );

    table.processData = function (data) {
        return data.data.map((item) => {
            // Format retribution amount
            const retributionAmount = item.calculated_retribution
                ? addThousandSeparators(item.calculated_retribution)
                : "0";

            // Format business type
            const businessType = item.is_business_type ? "USAHA" : "NON USAHA";

            return [
                item.no,
                item.name,
                item.kbli,
                item.activities,
                addThousandSeparators(item.land_area || item.area),
                addThousandSeparators(item.site_bcr),
                `${businessType}`,
                item.is_terbit ? "Sudah Terbit" : "Belum Terbit",
                `${retributionAmount}`,
                item.location,
                item.number,
                moment(item.date).format("YYYY-MM-DD"),
                item.id,
            ];
        });
    };

    table.init();
});
