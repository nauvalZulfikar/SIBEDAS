document.addEventListener("DOMContentLoaded", function (e) {
    let form = document.getElementById("formUpdateDataSettings");
    let submitButton = document.getElementById("btnUpdateDataSettings");
    let spinner = document.getElementById("spinner");
    let toastMessage = document.getElementById("toast-message");
    let toast = new bootstrap.Toast(
        document.getElementById("toastNotification")
    );
    let menuId = document.getElementById("menuId").value;
    submitButton.addEventListener("click", async function () {
        let submitButton = this;

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
                toastMessage.innerText = result.data.message;
                toast.show();
                setTimeout(() => {
                    window.location.href = `/data-settings?menu_id=${menuId}`;
                }, 2000);
            } else {
                let error = await response.json();
                toastMessage.innerText = error.message;
                toast.show();
                console.error("Error:", error);
                submitButton.disabled = false;
                spinner.classList.add("d-none");
            }
        } catch (error) {
            console.error("Request failed:", error);
            toastMessage.innerText = error.message;
            toast.show();
        }
    });
});
