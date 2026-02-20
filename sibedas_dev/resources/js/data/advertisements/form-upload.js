import { Dropzone } from "dropzone";
import GlobalConfig from "../../global-config.js";

Dropzone.autoDiscover = false;

var previewTemplate,
    dropzone,
    dropzonePreviewNode = document.querySelector("#dropzone-preview-list");
console.log(previewTemplate);
console.log(dropzone);
console.log(dropzonePreviewNode);

(dropzonePreviewNode.id = ""),
    dropzonePreviewNode &&
        ((previewTemplate = dropzonePreviewNode.parentNode.innerHTML),
        dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode),
        (dropzone = new Dropzone(".dropzone", {
            url: `${GlobalConfig.apiHost}/api/advertisements/import`,
            // url: "https://httpbin.org/post",
            method: "post",
            acceptedFiles: ".xls,.xlsx", // Use acceptedFiles for better validation
            previewTemplate: previewTemplate,
            previewsContainer: "#dropzone-preview",
            autoProcessQueue: false, // Disable auto post
            headers: {
                Authorization: `Bearer ${document
                    .querySelector('meta[name="api-token"]')
                    .getAttribute("content")}`,
            },
            init: function () {
                let menuId = document.getElementById("menuId").value;
                // Listen for the success event
                this.on("success", function (file, response) {
                    console.log("File successfully uploaded:", file);
                    console.log("API Response:", response);

                    // Show success toast
                    showToast("bxs-check-square", "green", response.message);
                    document.getElementById("submit-upload").innerHTML =
                        "Upload Files";
                    // Tunggu sebentar lalu reload halaman
                    setTimeout(() => {
                        window.location.href = `/data/web-advertisements?menu_id=${menuId}`;
                    }, 2000);
                });
                // Listen for the error event
                this.on("error", function (file, errorMessage) {
                    console.error("Error uploading file:", file);
                    console.error("Error message:", errorMessage);
                    // Handle the error response

                    // Show error toast
                    showToast("bxs-error-alt", "red", errorMessage.message);
                    document.getElementById("submit-upload").innerHTML =
                        "Upload Files";
                });
            },
        })));

// Add event listener to control the submission manually
document.querySelector("#submit-upload").addEventListener("click", function () {
    console.log("Ini adalah value dropzone", dropzone.files[0]);
    const formData = new FormData();
    console.log("Dropzonefiles", dropzone.files);

    this.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...';

    // Pastikan ada file dalam queue sebelum memprosesnya
    if (dropzone.files.length > 0) {
        formData.append("file", dropzone.files[0]);
        console.log("ini adalah form data on submit", ...formData);
        dropzone.processQueue(); // Ini akan manual memicu upload
    } else {
        // Show error toast when no file is selected
        showToast("bxs-error-alt", "red", "Please add a file first.");
        document.getElementById("submit-upload").innerHTML = "Upload Files";
    }
});

// Optional: Listen for the 'addedfile' event to log or control file add behavior
dropzone.on("addedfile", function (file) {
    console.log("File ditambahkan:", file);
    console.log("Nama File:", file.name);
    console.log("Tipe File:", file.type);
    console.log("Ukuran File:", (file.size / 1024).toFixed(2) + " KB");
});

dropzone.on("complete", function (file) {
    dropzone.removeFile(file);
});

// Add event listener to donwload file template
document
    .getElementById("downloadtempadvertisement")
    .addEventListener("click", function () {
        var url = `${GlobalConfig.apiHost}/api/download-template-advertisement`;
        fetch(url, {
            method: "GET",
            headers: {
                Authorization: `Bearer ${document
                    .querySelector('meta[name="api-token"]')
                    .getAttribute("content")}`,
            },
        })
            .then((response) => {
                if (response.ok) {
                    return response.blob();
                } else {
                    return response.json();
                }
            })
            .then((blob) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.style.display = "none";
                a.href = url;
                a.download = "template_reklame.xlsx";
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch((error) => {
                console.error("Gagal mendownload file:", error);
                showToast(
                    "bxs-error-alt",
                    "red",
                    "Template file is not already exist."
                );
            });
    });

// Function to show toast
function showToast(iconClass, iconColor, message) {
    const toastElement = document.getElementById("toastUploadAdvertisement");
    const toastBody = toastElement.querySelector(".toast-body");
    const toastHeader = toastElement.querySelector(".toast-header");

    // Remove existing icon (if any) before adding the new one
    const existingIcon = toastHeader.querySelector(".bx");
    if (existingIcon) {
        toastHeader.querySelector(".auth-logo").removeChild(existingIcon); // Remove the existing icon
    }

    // Add the new icon to the toast header
    const icon = document.createElement("i");
    icon.classList.add("bx", iconClass);
    icon.style.fontSize = "25px";
    icon.style.color = iconColor;
    toastHeader.querySelector(".auth-logo").appendChild(icon);

    // Set the toast message
    toastBody.textContent = message;

    // Show the toast
    const toast = new bootstrap.Toast(toastElement); // Inisialisasi Bootstrap Toast
    toast.show();
}
