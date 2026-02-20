class UpdateBusinessIndustries {
    init() {
        this.handleUpdateData();
    }

    handleUpdateData() {
        const form = document.getElementById("formUpdateBusinessIndustries");
        const submitButton = document.getElementById(
            "btnUpdateBusinessIndustries"
        );
        const toastNotification = document.getElementById("toastNotification");
        const toastBody = document.getElementById("toastBody"); // Add an element inside toast to display messages
        const spinner = document.getElementById("spinner");
        const toast = new bootstrap.Toast(toastNotification);

        let menuId = document.getElementById("menuId").value;

        if (!submitButton) {
            console.error("Error: Submit button not found!");
            return;
        }

        submitButton.addEventListener("click", async function (e) {
            e.preventDefault();

            // Disable button and show spinner
            submitButton.disabled = true;
            spinner.classList.remove("d-none");

            // Create FormData object
            const formData = new FormData(form);
            const formObject = {};
            formData.forEach((value, key) => {
                formObject[key] = value;
            });
            formData.append("_method", "PUT");

            try {
                let response = await fetch(form.action, {
                    method: "POST", // Laravel's update route uses PUT, so adjust accordingly
                    headers: {
                        Authorization: `Bearer ${document
                            .querySelector('meta[name="api-token"]')
                            .getAttribute("content")}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(formObject),
                });

                let data = await response.json();

                if (response.ok) {
                    // Show success toast
                    document.getElementById("toast-message").innerText =
                        data.message;
                    toast.show();
                    setTimeout(() => {
                        window.location.href = `/data/business-industries?menu_id=${menuId}`;
                    }, 2000);
                } else {
                    // Show error toast with message from API
                    document.getElementById("toast-message").innerText =
                        data.message;
                    toast.show();
                    submitButton.disabled = false;
                    spinner.classList.add("d-none");
                }
            } catch (error) {
                // Show error toast for network errors
                document.getElementById("toast-message").innerText =
                    data.message;
                toast.show();
                submitButton.disabled = false;
                spinner.classList.add("d-none");
            }
        });
    }
}
document.addEventListener("DOMContentLoaded", function (e) {
    new UpdateBusinessIndustries().init();
});
