/**
 * Simple Session Validator
 * Menangani validasi session tanpa periodic checking
 * Hanya respond pada 401 errors dari API requests
 */
class SimpleSessionValidator {
    constructor() {
        this.isLoggingOut = false;
        this.consecutiveErrors = 0;
        this.maxConsecutiveErrors = 2;
        this.init();
    }

    init() {
        console.log("Simple Session Validator initialized");

        // Intercept all AJAX requests untuk detect 401
        this.interceptAjaxRequests();

        // Listen untuk page visibility changes
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden && this.consecutiveErrors > 0) {
                // Reset errors ketika user kembali ke tab
                this.consecutiveErrors = 0;
                console.log("Page visible, reset error counter");
            }
        });
    }

    interceptAjaxRequests() {
        const validator = this;

        // Intercept fetch requests
        const originalFetch = window.fetch;
        window.fetch = async function (...args) {
            try {
                const response = await originalFetch(...args);

                // Check if response is 401 dan URL mengandung /api/
                if (response.status === 401) {
                    const url = args[0];
                    if (typeof url === "string" && url.includes("/api/")) {
                        console.log("401 detected in API fetch request:", url);
                        validator.handleApiError401(url);
                    }
                }

                return response;
            } catch (error) {
                console.error("Fetch request failed:", error);
                throw error;
            }
        };

        // Intercept XMLHttpRequest
        const originalXHRSend = XMLHttpRequest.prototype.send;
        const originalXHROpen = XMLHttpRequest.prototype.open;

        XMLHttpRequest.prototype.open = function (...args) {
            this._url = args[1];
            return originalXHROpen.apply(this, args);
        };

        XMLHttpRequest.prototype.send = function (...args) {
            const xhr = this;
            const originalOnReadyStateChange = xhr.onreadystatechange;

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 401) {
                    if (xhr._url && xhr._url.includes("/api/")) {
                        console.log(
                            "401 detected in API XHR request:",
                            xhr._url
                        );
                        validator.handleApiError401(xhr._url);
                    }
                }

                if (originalOnReadyStateChange) {
                    originalOnReadyStateChange.apply(xhr, arguments);
                }
            };

            return originalXHRSend.apply(this, args);
        };

        // Intercept jQuery AJAX jika tersedia
        if (typeof $ !== "undefined" && $.ajaxSetup) {
            $(document).ajaxError(function (event, xhr, settings, thrownError) {
                if (
                    xhr.status === 401 &&
                    settings.url &&
                    settings.url.includes("/api/")
                ) {
                    console.log(
                        "401 detected in jQuery AJAX request:",
                        settings.url
                    );
                    validator.handleApiError401(settings.url);
                }
            });
        }
    }

    handleApiError401(url) {
        if (this.isLoggingOut) {
            return;
        }

        console.log(`API 401 Error detected on ${url}`);

        // Increment consecutive errors
        this.consecutiveErrors++;

        // Jika sudah 2x error berturut-turut, logout
        if (this.consecutiveErrors >= this.maxConsecutiveErrors) {
            this.handleSessionInvalid(
                "Token API tidak valid. User lain telah login."
            );
        }
    }

    handleSessionInvalid(message) {
        if (this.isLoggingOut) {
            return;
        }

        this.isLoggingOut = true;
        console.log("Handling session invalid:", message);

        // Show notification
        this.showNotification(message, "warning");

        // Redirect to login after 3 seconds
        setTimeout(() => {
            this.forceLogout();
        }, 3000);
    }

    showNotification(message, type = "info") {
        // Try different notification libraries
        if (typeof toastr !== "undefined") {
            toastr[type](message);
        } else if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Peringatan Session",
                text: message,
                icon: type,
                confirmButtonText: "OK",
                allowOutsideClick: false,
                timer: 5000,
                timerProgressBar: true,
            });
        } else {
            // Create custom notification
            this.createCustomNotification(message, type);
        }
    }

    createCustomNotification(message, type) {
        const notification = document.createElement("div");
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === "warning" ? "#ffc107" : "#007bff"};
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 9999;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            font-family: Arial, sans-serif;
            font-size: 14px;
        `;
        notification.innerHTML = `
            <strong>Peringatan!</strong><br>
            ${message}<br>
            <small>Anda akan diarahkan ke halaman login...</small>
        `;

        document.body.appendChild(notification);

        // Remove after 8 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 8000);
    }

    forceLogout() {
        console.log("Forcing logout...");

        // Try to logout via API first
        fetch("/logout", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "include",
        })
            .then(() => {
                window.location.href = "/login";
            })
            .catch(() => {
                // Force redirect even if logout fails
                window.location.href = "/login";
            });
    }

    // Method untuk manual reset
    reset() {
        this.consecutiveErrors = 0;
        this.isLoggingOut = false;
        console.log("Session validator reset");
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    window.simpleSessionValidator = new SimpleSessionValidator();
});

// Export for module usage
if (typeof module !== "undefined" && module.exports) {
    module.exports = SimpleSessionValidator;
}
