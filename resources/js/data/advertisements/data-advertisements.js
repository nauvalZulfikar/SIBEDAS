import { Grid } from "gridjs/dist/gridjs.umd.js";
import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../../global-config.js";
import GeneralTable from "../../table-generator.js";

// Ambil hak akses dari data-attribute
const tableElement = document.getElementById("reklame-data-table");
const canUpdate = tableElement.getAttribute("data-updater") === "1";
const canDelete = tableElement.getAttribute("data-destroyer") === "1";
let menuId = document.getElementById("menuId").value;

const dataAdvertisementsColumns = [
    "No",
    "Nama Wajib Pajak",
    "NPWPD",
    "Jenis Reklame",
    "Isi Reklame",
    "Alamat Wajib Pajak",
    "Lokasi Reklame",
    "Desa",
    "Kecamatan",
    "Kontak",
    {
        name: "Actions",
        width: "120px",
        formatter: function (cell, row) {
            const id = row.cells[10].data;
            const model = `data/web-advertisements`;

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
        "reklame-data-table",
        `${GlobalConfig.apiHost}/api/advertisements`,
        `${GlobalConfig.apiHost}`,
        dataAdvertisementsColumns
    );

    table.processData = function (data) {
        return data.data.map((item) => {
            return [
                item.no,
                item.business_name,
                item.npwpd,
                item.advertisement_type,
                item.advertisement_content,
                item.business_address,
                item.advertisement_location,
                item.village_name,
                item.district_name,
                item.contact,
                item.id,
            ];
        });
    };

    table.init();
});
