(() => {
    const shell = document.querySelector("[data-dashboard-shell]");
    const sidebar = document.querySelector("[data-dashboard-sidebar]");
    const sidebarToggle = document.querySelector("[data-sidebar-toggle]");
    const sidebarOverlay = document.querySelector("[data-sidebar-overlay]");

    const setSidebarOpen = (isOpen) => {
        if (!(shell instanceof HTMLElement) || !(sidebarToggle instanceof HTMLButtonElement)) {
            return;
        }

        shell.classList.toggle("sidebar-open", isOpen);
        sidebarToggle.setAttribute("aria-expanded", String(isOpen));
        sidebarToggle.setAttribute(
            "aria-label",
            isOpen ? "Fermer le menu" : "Ouvrir le menu",
        );
    };

    if (
        shell instanceof HTMLElement &&
        sidebar instanceof HTMLElement &&
        sidebarToggle instanceof HTMLButtonElement
    ) {
        sidebarToggle.addEventListener("click", () => {
            setSidebarOpen(!shell.classList.contains("sidebar-open"));
        });

        sidebarOverlay?.addEventListener("click", () => setSidebarOpen(false));

        sidebar.addEventListener("click", (event) => {
            if (event.target instanceof Element && event.target.closest("a")) {
                setSidebarOpen(false);
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                setSidebarOpen(false);
            }
        });

        window.addEventListener("resize", () => {
            if (window.innerWidth >= 992) {
                setSidebarOpen(false);
            }
        });
    }

    const toastRegion = document.querySelector("[data-toast-region]");
    const showToast = (message) => {
        if (!(toastRegion instanceof HTMLElement) || typeof message !== "string" || !message.trim()) {
            return;
        }

        const toast = document.createElement("div");
        toast.className = "toast show align-items-center border-0";
        toast.setAttribute("role", "status");
        toast.setAttribute("aria-live", "polite");
        toast.setAttribute("aria-atomic", "true");

        const row = document.createElement("div");
        row.className = "d-flex";

        const body = document.createElement("div");
        body.className = "toast-body";
        body.textContent = message;

        const close = document.createElement("button");
        close.type = "button";
        close.className = "btn-close me-2 m-auto";
        close.setAttribute("aria-label", "Fermer la notification");
        close.addEventListener("click", () => toast.remove());

        row.append(body, close);
        toast.append(row);
        toastRegion.append(toast);
        window.setTimeout(() => toast.remove(), 5000);
    };

    document.addEventListener("click", (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest("[data-toast-message]");
        if (trigger instanceof HTMLElement) {
            showToast(trigger.dataset.toastMessage ?? "");
        }
    });

    document.addEventListener("afyamtoto:toast", (event) => {
        if (event instanceof CustomEvent) {
            showToast(event.detail?.message ?? "");
        }
    });

    document.addEventListener("click", (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest("[data-confirm]");
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        const message = trigger.dataset.confirm;
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
})();
