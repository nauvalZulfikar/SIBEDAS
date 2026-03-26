class UpdateTax {
    constructor() {
        this.initUpdateCustomer();
    }

    initUpdateCustomer() {
        const toastNotification = document.getElementById("toastNotification");
        const toast = new bootstrap.Toast(toastNotification);
        let menuId = document.getElementById("menuId").value;
        document
            .getElementById("btnUpdateTax")
            .addEventListener("click", async function () {
                let submitButton = this;
                let spinner = document.getElementById("spinner");
                let form = document.getElementById("formUpdateTax");

                if (!form) {
                    console.error("Form element not found!");
                    return;
                }
                // Get form data
                let formData = new FormData(form);

                // Disable button and show spinner
                submitButton.disabled = true;
                spinner.classList.remove("d-none");

                try {
                    let response = await fetch(form.action, {
                        method: "POST",
                        headers: {
                            Authorization: `Bearer ${document
                                .querySelector('meta[name="api-token"]')
                                .getAttribute("content")}`,
                        },
                        body: formData,
                    });

                    if (response.ok) {
                        let result = await response.json();
                        document.getElementById("toast-message").innerText =
                            result.message;
                        toast.show();
                        setTimeout(() => {
                            window.location.href = `/tax?menu_id=${menuId}`;
                        }, 2000);
                    } else {
                        let error = await response.json();
                        document.getElementById("toast-message").innerText =
                            error.message;
                        toast.show();
                        console.error("Error:", error);
                    }
                } catch (error) {
                    console.error("Request failed:", error);
                    document.getElementById("toast-message").innerText =
                        error.message;
                    toast.show();
                }
            });
    }
}

document.addEventListener("DOMContentLoaded", function (e) {
    new UpdateTax();
});
