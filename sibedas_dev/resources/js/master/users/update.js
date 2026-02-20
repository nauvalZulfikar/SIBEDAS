document.addEventListener("DOMContentLoaded", function (e) {
    let form = document.getElementById("formUpdateUsers");
    let submitButton = document.getElementById("btnUpdateUsers");
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
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: formData,
            });

            if (response.ok) {
                let result = await response.json();
                toastMessage.innerText = result.message;
                toast.show();
                setTimeout(() => {
                    window.location.href = `/master/users?menu_id=${menuId}`;
                }, 2000);
            } else {
                let error = await response.json();
                toastMessage.innerText = error.message;
                toast.show();
                console.error("Error:", error);
            }
        } catch (error) {
            console.error("Request failed:", error);
            toastMessage.innerText = error.message;
            toast.show();
        }
    });
});
