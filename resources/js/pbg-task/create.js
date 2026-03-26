import GlobalConfig from "../global-config.js";

class MultiFormCreatePBG {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 4;
        this.formData = {}; // Menyimpan data dari semua langkah
    }

    init() {
        document
            .getElementById("nextStep")
            .addEventListener("click", () => this.nextStep());

        document
            .getElementById("prevStep")
            .addEventListener("click", () => this.prevStep());
    }

    nextStep() {
        if (!this.validateStep()) return;

        this.saveStepData();

        if (this.currentStep < this.totalSteps) {
            document
                .getElementById(`step${this.currentStep}`)
                .classList.add("d-none");

            this.currentStep++;
            document
                .getElementById(`step${this.currentStep}`)
                .classList.remove("d-none");

            document.getElementById(
                "stepTitle"
            ).innerText = `Step ${this.currentStep}`;
            document.getElementById("prevStep").disabled = false;
            document.getElementById("nextStep").innerText =
                this.currentStep === this.totalSteps ? "Submit" : "Next →";
        } else {
            this.submitForm(); // Submit ke API jika sudah step terakhir
        }
    }

    prevStep() {
        if (this.currentStep > 1) {
            document
                .getElementById(`step${this.currentStep}`)
                .classList.add("d-none");

            this.currentStep--;
            document
                .getElementById(`step${this.currentStep}`)
                .classList.remove("d-none");

            document.getElementById(
                "stepTitle"
            ).innerText = `Step ${this.currentStep}`;
            document.getElementById("prevStep").disabled =
                this.currentStep === 1;
            document.getElementById("nextStep").innerText = "Next →";
        }
    }

    saveStepData() {
        const stepForm = document.querySelector(`#step${this.currentStep}Form`);
        const formDataObj = new FormData(stepForm);

        if (!this.formData) {
            this.formData = {};
        }

        const stepKey = `step${this.currentStep}Form`;
        this.formData[stepKey] = {};

        for (const [key, value] of formDataObj.entries()) {
            this.formData[stepKey][key] = value;
        }

        console.log("form data", this.formData);
    }

    validateStep() {
        const stepForm = document.querySelector(`#step${this.currentStep}Form`);
        const inputs = stepForm.querySelectorAll(
            "input[required], select[required]"
        );
        let isValid = true;

        inputs.forEach((input) => {
            if (!input.value) {
                input.classList.add("is-invalid");
                isValid = false;
            } else {
                input.classList.remove("is-invalid");
            }
        });

        return isValid;
    }

    async submitForm() {
        try {
            const response = await fetch(
                `${GlobalConfig.apiHost}/api/api-pbg-task`,
                {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${
                            document.querySelector("meta[name='api-token']")
                                .content
                        }`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(this.formData),
                }
            );

            const result = await response.json();
            alert(result.message);
            window.location.href = "/pbg-task";
        } catch (error) {
            console.error("Error submitting form:", error);
            alert("Terjadi kesalahan saat mengirim data.");
        }
    }
}

document.addEventListener("DOMContentLoaded", function () {
    new MultiFormCreatePBG().init();
});
