import { Dropzone } from "dropzone";
import GlobalConfig from "../global-config";
Dropzone.autoDiscover = false;

var previewTemplate,
    dropzone,
    dropzonePreviewNode = document.querySelector("#dropzone-preview-list");

const uploadButton = document.getElementById("btnUploadBusinessIndustry");
const spinner = document.getElementById("spinner");

const toastNotification = document.getElementById("toastNotification");
const toast = new bootstrap.Toast(toastNotification);

let menuId = document.getElementById("menuId").value;

(dropzonePreviewNode.id = ""),
    dropzonePreviewNode &&
        ((previewTemplate = dropzonePreviewNode.parentNode.innerHTML),
        dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode),
        (dropzone = new Dropzone(".dropzone", {
            url: `${GlobalConfig.apiHost}/api/api-business-industries/upload`,
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
                this.on("success", function (file, response) {
                    document.getElementById("toast-message").innerText =
                        response.message;
                    toast.show();
                    setTimeout(() => {
                        window.location.href = `/data/business-industries?menu_id=${menuId}`;
                    }, 2000);
                });
                this.on("error", function (file, errorMessage) {
                    console.error("Error uploading file:", file);
                    console.error("Error message:", errorMessage);

                    document.getElementById("toast-message").innerText =
                        errorMessage.message;
                    toast.show();
                    uploadButton.disabled = false;
                    spinner.classList.add("d-none");
                });
            },
        })));

// Add event listener to control the submission manually
document
    .querySelector("#btnUploadBusinessIndustry")
    .addEventListener("click", function () {
        console.log("Ini adalah value dropzone", dropzone.files[0]);
        const formData = new FormData();

        if (dropzone.files.length > 0) {
            formData.append("file", dropzone.files[0]);
            dropzone.processQueue(); // Ini akan manual memicu upload
            uploadButton.disabled = true;
            spinner.classList.remove("d-none");
        } else {
            document.getElementById("toast-message").innerText =
                "Please add a file first.";
            toast.show();
            uploadButton.disabled = false;
            spinner.classList.add("d-none");
        }
    });

dropzone.on("addedfile", function (file) {});

dropzone.on("complete", function (file) {
    dropzone.removeFile(file);
});
