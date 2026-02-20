/**
 * Multi-User Session Handler
 * Menangani kasus ketika multiple user login dan session conflict
 */
class MultiUserSessionHandler {
    constructor() {
        this.checkInterval = null;
        this.lastCheckTime = 0;
        this.isChecking = false;
        this.init();
    }

    init() {
        console.log("Multi-User Session Handler initialized");

        // Check session setiap 10 detik
        this.startPeriodicCheck();

        // Check session ketika tab menjadi aktif
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                this.checkSession();
            }
        });

        // Check session ketika window focus
        window.addEventListener("focus", () => {
            this.checkSession();
        });

        // Check session ketika user melakukan interaksi
        document.addEventListener("click", () => {
            this.checkSession();
        });

        // Check session ketika ada AJAX request
        this.interceptAjaxRequests();
    }

    startPeriodicCheck() {
        this.checkInterval = setInterval(() => {
            this.checkSession();
        }, 10000); // 10 detik
    }

    stopPeriodicCheck() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }

    async checkSession() {
        // Prevent multiple simultaneous checks
        if (this.isChecking) {
            return;
        }

        // Prevent checking too frequently
        const now = Date.now();
        if (now - this.lastCheckTime < 5000) {
            // 5 detik minimum interval
            return;
        }

        this.isChecking = true;
        this.lastCheckTime = now;

        try {
            const response = await fetch("/api/check-session", {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "Cache-Control": "no-cache",
                },
                credentials: "include",
            });

            if (response.status === 401) {
                console.log("Session invalid detected, logging out...");
                this.handleSessionInvalid();
            } else if (response.status === 200) {
                const data = await response.json();
                if (!data.valid) {
                    console.log("Session validation failed, logging out...");
                    this.handleSessionInvalid();
                }
            }
        } catch (error) {
            console.error("Session check failed:", error);
        } finally {
            this.isChecking = false;
        }
    }

    interceptAjaxRequests() {
        // Intercept fetch requests
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            try {
                const response = await originalFetch(...args);

                // Check if response is 401
                if (response.status === 401) {
                    console.log(
                        "401 detected in fetch request, checking session..."
                    );
                    this.checkSession();
                }

                return response;
            } catch (error) {
                console.error("Fetch request failed:", error);
                throw error;
            }
        };

        // Intercept XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (...args) {
            this._url = args[1];
            return originalXHROpen.apply(this, args);
        };

        XMLHttpRequest.prototype.send = function (...args) {
            const xhr = this;
            const originalOnReadyStateChange = xhr.onreadystatechange;

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 401) {
                    console.log(
                        "401 detected in XHR request, checking session..."
                    );
                    window.multiUserSessionHandler.checkSession();
                }

                if (originalOnReadyStateChange) {
                    originalOnReadyStateChange.apply(xhr, arguments);
                }
            };

            return originalXHRSend.apply(this, args);
        };
    }

    handleSessionInvalid() {
        this.stopPeriodicCheck();

        // Show notification
        this.showNotification(
            "Session Anda telah berakhir karena user lain login. Silakan login ulang.",
            "warning"
        );

        // Clear any stored data
        this.clearStoredData();

        // Redirect to login after 2 seconds
        setTimeout(() => {
            window.location.href = "/login";
        }, 2000);
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
            });
        } else if (typeof alert !== "undefined") {
            alert(message);
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
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    clearStoredData() {
        // Clear localStorage
        try {
            localStorage.clear();
        } catch (e) {
            console.warn("Could not clear localStorage:", e);
        }

        // Clear sessionStorage
        try {
            sessionStorage.clear();
        } catch (e) {
            console.warn("Could not clear sessionStorage:", e);
        }
    }

    // Method untuk manual logout
    logout() {
        this.stopPeriodicCheck();

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
                this.clearStoredData();
                window.location.href = "/login";
            })
            .catch(() => {
                this.clearStoredData();
                window.location.href = "/login";
            });
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    window.multiUserSessionHandler = new MultiUserSessionHandler();
});

// Export for module usage
if (typeof module !== "undefined" && module.exports) {
    module.exports = MultiUserSessionHandler;
}
