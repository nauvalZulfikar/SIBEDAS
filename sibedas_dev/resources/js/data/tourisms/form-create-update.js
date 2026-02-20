import GlobalConfig from "../../global-config";

document.addEventListener("DOMContentLoaded", function () {
    const saveButton = document.querySelector(".modal-footer .btn-primary");
    const modalButton = document.querySelector(".btn-modal");
    const form = document.querySelector("form#create-update-form");
    var authLogo = document.querySelector(".auth-logo");
    let menuId = document.getElementById("menuId").value;

    if (!saveButton || !form) return;

    saveButton.addEventListener("click", function () {
        // Disable tombol dan tampilkan spinner
        modalButton.disabled = true;
        modalButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            Loading...
        `;
        const isEdit = saveButton.classList.contains("btn-edit");
        const formData = new FormData(form);
        const toast = document.getElementById("toastEditUpdate");
        const toastBody = toast.querySelector(".toast-body");
        const toastHeader = toast.querySelector(".toast-header small");

        const data = {};

        // Mengonversi FormData ke dalam JSON
        formData.forEach((value, key) => {
            data[key] = value;
        });

        const url = form.getAttribute("action");

        const method = isEdit ? "PUT" : "POST";

        fetch(url, {
            method: method,
            body: JSON.stringify(data),
            headers: {
                Authorization: `Bearer ${document
                    .querySelector('meta[name="api-token"]')
                    .getAttribute("content")}`,
                "Content-Type": "application/json",
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.errors) {
                    // Remove existing icon (if any) before adding the new one
                    if (authLogo) {
                        // Hapus ikon yang sudah ada jika ada
                        const existingIcon = authLogo.querySelector(".bx");
                        if (existingIcon) {
                            authLogo.removeChild(existingIcon);
                        }

                        // Buat ikon baru
                        const icon = document.createElement("i");
                        icon.classList.add("bx", "bxs-check-square");
                        icon.style.fontSize = "25px";
                        icon.style.color = "green"; // Pastikan 'green' dalam bentuk string

                        // Tambahkan ikon ke dalam auth-logo
                        authLogo.appendChild(icon);
                    }

                    // Set success message for the toast
                    toastBody.textContent = isEdit
                        ? "Data updated successfully!"
                        : "Data created successfully!";
                    toast.classList.add("show"); // Show the toast
                    setTimeout(() => {
                        toast.classList.remove("show"); // Hide the toast after 3 seconds
                    }, 3000);

                    setTimeout(() => {
                        window.location.href = `/data/web-tourisms?menu_id=${menuId}`;
                    }, 3000);
                } else {
                    if (authLogo) {
                        // Hapus ikon yang sudah ada jika ada
                        const existingIcon = authLogo.querySelector(".bx");
                        if (existingIcon) {
                            authLogo.removeChild(existingIcon);
                        }

                        // Buat ikon baru
                        const icon = document.createElement("i");
                        icon.classList.add("bx", "bxs-error-alt");
                        icon.style.fontSize = "25px";
                        icon.style.color = "red"; // Pastikan 'green' dalam bentuk string

                        // Tambahkan ikon ke dalam auth-logo
                        authLogo.appendChild(icon);
                    }
                    // Set error message for the toast
                    toastBody.textContent =
                        "Error: " + (data.message || "Something went wrong");
                    toast.classList.add("show"); // Show the toast

                    // Enable button and reset its text on error
                    modalButton.disabled = false;
                    modalButton.innerHTML = isEdit ? "Update" : "Create";

                    setTimeout(() => {
                        toast.classList.remove("show"); // Hide the toast after 3 seconds
                    }, 3000);
                }
            })
            .catch((error) => {
                if (authLogo) {
                    // Hapus ikon yang sudah ada jika ada
                    const existingIcon = authLogo.querySelector(".bx");
                    if (existingIcon) {
                        authLogo.removeChild(existingIcon);
                    }

                    // Buat ikon baru
                    const icon = document.createElement("i");
                    icon.classList.add("bx", "bxs-error-alt");
                    icon.style.fontSize = "25px";
                    icon.style.color = "red"; // Pastikan 'green' dalam bentuk string

                    // Tambahkan ikon ke dalam auth-logo
                    authLogo.appendChild(icon);
                }
                // Set error message for the toast
                toastBody.textContent =
                    "An error occurred while processing your request.";
                toast.classList.add("show"); // Show the toast

                // Enable button and reset its text on error
                modalButton.disabled = false;
                modalButton.innerHTML = isEdit ? "Update" : "Create";

                setTimeout(() => {
                    toast.classList.remove("show"); // Hide the toast after 3 seconds
                }, 3000);
            });
    });

    // Fungsi fetchOptions untuk autocomplete server-side
    window.fetchOptions = function (field) {
        let inputValue = document.getElementById(field).value;
        if (inputValue.length < 2) return;
        let districtValue = document.getElementById("district_name").value; // Ambil kecamatan terpilih

        let url = `${
            GlobalConfig.apiHost
        }/api/combobox/search-options?query=${encodeURIComponent(
            inputValue
        )}&field=${field}`;

        // Jika field desa, tambahkan kecamatan sebagai filter
        if (field === "village_name") {
            url += `&district=${encodeURIComponent(districtValue)}`;
        }

        fetch(url, {
            method: "GET",
            headers: {
                Authorization: `Bearer ${document
                    .querySelector('meta[name="api-token"]')
                    .getAttribute("content")}`,
                "Content-Type": "application/json",
            },
        })
            .then((response) => response.json())
            .then((data) => {
                let dataList = document.getElementById(field + "Options");
                dataList.innerHTML = "";

                data.forEach((item) => {
                    let option = document.createElement("option");
                    option.value = item.name;
                    option.dataset.code = item.code;
                    dataList.appendChild(option);
                });
            })
            .catch((error) => console.error("Error fetching options:", error));
    };

    document.querySelector(".btn-back").addEventListener("click", function () {
        window.history.back();
    });
});
