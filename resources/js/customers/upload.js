import { Dropzone } from "dropzone";
Dropzone.autoDiscover = false;

class UploadCustomers {
    constructor() {
        this.spatialDropzone = null;
        this.formElement = document.getElementById("formUploadCustomers");
        this.uploadButton = document.getElementById("submit-upload");
        this.spinner = document.getElementById("spinner");
        if (!this.formElement) {
            console.error("Element formUploadCustomers tidak ditemukan!");
        }
    }

    init() {
        this.initDropzone();
        this.setupUploadButton();
    }

    initDropzone() {
        const toastNotification = document.getElementById("toastNotification");
        const toast = new bootstrap.Toast(toastNotification);
        let menuId = document.getElementById("menuId").value;
        var previewTemplate,
            dropzonePreviewNode = document.querySelector(
                "#dropzone-preview-list"
            );
        (dropzonePreviewNode.id = ""),
            dropzonePreviewNode &&
                ((previewTemplate = dropzonePreviewNode.parentNode.innerHTML),
                dropzonePreviewNode.parentNode.removeChild(dropzonePreviewNode),
                (this.spatialDropzone = new Dropzone(".dropzone", {
                    url: this.formElement.action,
                    method: "post",
                    acceptedFiles: ".xls,.xlsx",
                    previewTemplate: previewTemplate,
                    previewsContainer: "#dropzone-preview",
                    autoProcessQueue: false,
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
                                window.location.href = `/data/customers?menu_id=${menuId}`;
                            }, 2000);
                        });
                        this.on("error", function (file, errorMessage) {
                            document.getElementById("toast-message").innerText =
                                errorMessage.message;
                            toast.show();
                            this.uploadButton.disabled = false;
                            this.spinner.classList.add("d-none");
                        });
                    },
                })));
    }

    setupUploadButton() {
        this.uploadButton.addEventListener("click", (e) => {
            if (this.spatialDropzone.files.length > 0) {
                this.spatialDropzone.processQueue();
                this.uploadButton.disabled = true;
                this.spinner.classList.remove("d-none");
            } else {
                return;
            }
        });
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new UploadCustomers().init();
});
