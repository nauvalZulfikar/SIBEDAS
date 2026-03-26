import gridjs from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";
import GlobalConfig from "../../global-config.js";
import GeneralTable from "../../table-generator.js";

// Ambil hak akses dari data-attribute
const tableElement = document.getElementById("umkm-data-table");
const canUpdate = tableElement.getAttribute("data-updater") === "1";
const canDelete = tableElement.getAttribute("data-destroyer") === "1";
let menuId = document.getElementById("menuId").value;

const dataUMKMColumns = [
    "No",
    "Nama Usaha",
    "Alamat Usaha",
    "Deskripsi Usaha",
    "Kontak Usaha",
    "NIB",
    "Skala Usaha",
    "NIK",
    "Nama Pemilik",
    "Alamat Pemilik",
    "Kontak Pemilik",
    "Jenis Usaha",
    "Bentuk Usaha",
    "Revenue",
    "Desa",
    "Kecamatan",
    "Jumlah Karyawan",
    "Luas Tanah",
    "Ijin Status",
    {
        name: "Actions",
        widht: "120px",
        formatter: function (cell, row) {
            const id = row.cells[19].data;
            const model = "data/web-umkm";

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
        "umkm-data-table",
        `${GlobalConfig.apiHost}/api/umkm`,
        `${GlobalConfig.apiHost}`,
        dataUMKMColumns
    );

    table.processData = function (data) {
        return data.data.map((item) => {
            return [
                item.no,
                item.business_name,
                item.business_address,
                item.business_desc,
                item.business_contact,
                item.business_id_number,
                item.business_scale,
                item.owner_id,
                item.owner_name,
                item.owner_address,
                item.owner_contact,
                item.business_type,
                item.business_form,
                item.revenue,
                item.village_name,
                item.district_name,
                item.number_of_employee,
                item.land_area,
                item.permit_status,
                item.id,
            ];
        });
    };

    table.init();
});
