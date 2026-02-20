/**
 * Session Manager untuk menangani multi-user session
 */
class SessionManager {
    constructor() {
        this.checkInterval = null;
        this.init();
    }

    init() {
        // Check session setiap 30 detik
        this.startSessionCheck();

        // Listen untuk visibility change (tab focus/blur)
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                this.checkSession();
            }
        });

        // Listen untuk storage events (multi-tab)
        window.addEventListener("storage", (e) => {
            if (e.key === "session_invalid") {
                this.handleSessionInvalid();
            }
        });
    }

    startSessionCheck() {
        this.checkInterval = setInterval(() => {
            this.checkSession();
        }, 30000); // 30 detik
    }

    stopSessionCheck() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }

    async checkSession() {
        try {
            const response = await fetch("/api/check-session", {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "include",
            });

            if (response.status === 401) {
                this.handleSessionInvalid();
            }
        } catch (error) {
            console.error("Session check failed:", error);
        }
    }

    handleSessionInvalid() {
        this.stopSessionCheck();

        // Show notification
        this.showNotification(
            "Session Anda telah berakhir. Silakan login ulang.",
            "warning"
        );

        // Redirect to login after 3 seconds
        setTimeout(() => {
            window.location.href = "/login";
        }, 3000);
    }

    showNotification(message, type = "info") {
        // Check if notification library exists (like Toastr, SweetAlert, etc.)
        if (typeof toastr !== "undefined") {
            toastr[type](message);
        } else if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Peringatan",
                text: message,
                icon: type,
                confirmButtonText: "OK",
            });
        } else {
            // Fallback to alert
            alert(message);
        }
    }

    // Method untuk logout manual
    logout() {
        this.stopSessionCheck();

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
                window.location.href = "/login";
            });
    }
}

// Initialize session manager when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    window.sessionManager = new SessionManager();
});

// Export for module usage
if (typeof module !== "undefined" && module.exports) {
    module.exports = SessionManager;
}
