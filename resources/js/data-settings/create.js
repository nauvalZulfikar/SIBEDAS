document.addEventListener("DOMContentLoaded", function (e) {
    const toastNotification = document.getElementById("toastNotification");
    const toast = new bootstrap.Toast(toastNotification);
    let menuId = document.getElementById("menuId").value;
    document
        .getElementById("btnCreateDataSettings")
        .addEventListener("click", async function () {
            let submitButton = this;
            let spinner = document.getElementById("spinner");
            let form = document.getElementById("formDataSettings");

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
                    credentials: "include",
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
                        result.data.message;
                    toast.show();
                    setTimeout(() => {
                        window.location.href = `/data-settings?menu_id=${menuId}`;
                    }, 2000);
                } else {
                    let error = await response.json();
                    document.getElementById("toast-message").innerText =
                        error.message;
                    toast.show();
                    console.error("Error:", error);
                    submitButton.disabled = false;
                    spinner.classList.add("d-none");
                }
            } catch (error) {
                console.error("Request failed:", error);
                document.getElementById("toast-message").innerText =
                    error.message;
                toast.show();
            }
        });
});
