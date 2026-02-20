import { Grid } from "gridjs/dist/gridjs.umd.js";
import "gridjs/dist/gridjs.umd.js";

// Fungsi untuk menghasilkan data dummy
function generateDummyInvitations(count = 10) {
    const statuses = ["Terkirim", "Gagal", "Menunggu"];
    const dummyData = [];

    for (let i = 1; i <= count; i++) {
        const email = `user${i}@example.com`;
        const status = statuses[Math.floor(Math.random() * statuses.length)];
        const createdAt = new Date(
            Date.now() - Math.floor(Math.random() * 100000000)
        )
            .toISOString()
            .replace("T", " ")
            .substring(0, 19);

        dummyData.push([i, email, status, createdAt]);
    }

    return dummyData;
}

class Invitations {
    constructor() {
        this.table = null;
        this.initEvents();
    }

    initEvents() {
        this.initTableInvitations();
    }

    initTableInvitations() {
        let tableContainer = document.getElementById("table-invitations");
        this.table = new Grid({
            columns: [
                { name: "ID" },
                { name: "Email" },
                { name: "Status" },
                { name: "Created" },
            ],
            data: generateDummyInvitations(50), // Bisa ganti jumlah data dummy
            pagination: {
                limit: 10,
            },
            sort: true,
            search: true,
        }).render(tableContainer);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    new Invitations();
});
