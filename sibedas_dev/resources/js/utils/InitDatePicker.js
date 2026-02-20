import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

class InitDatePicker {
    constructor(selector = ".datepicker", onChangeCallback = null) {
        this.selector = selector;
        this.onChangeCallback = onChangeCallback;
    }

    init() {
        const today = new Date();

        document.querySelectorAll(this.selector).forEach((element) => {
            flatpickr(element, {
                enableTime: false,
                dateFormat: "Y-m-d",
                maxDate: today,
                onChange: (selectedDates, dateStr) => {
                    if (this.onChangeCallback) {
                        this.onChangeCallback(dateStr); // Call callback with selected date
                    }
                },
                onReady: (selectedDates, dateStr, instance) => {
                    // Call the callback with the default date when initialized
                    if (this.onChangeCallback && dateStr) {
                        this.onChangeCallback(dateStr);
                    }
                },
            });
        });
    }
}

export default InitDatePicker;
