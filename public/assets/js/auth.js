(() => {
    document.addEventListener("click", (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const toggle = event.target.closest("[data-password-toggle]");
        if (!(toggle instanceof HTMLButtonElement)) {
            return;
        }

        const input = toggle.closest(".auth-password-field")?.querySelector("input");
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const revealPassword = input.type === "password";
        input.type = revealPassword ? "text" : "password";
        toggle.textContent = revealPassword ? "Masquer" : "Afficher";
        toggle.setAttribute("aria-label", revealPassword ? "Masquer le mot de passe" : "Afficher le mot de passe");
    });

    document.addEventListener("submit", (event) => {
        if (!(event.target instanceof HTMLFormElement) || !event.target.matches(".auth-form")) {
            return;
        }

        const submit = event.target.querySelector('button[type="submit"]');
        if (!(submit instanceof HTMLButtonElement) || !event.target.reportValidity()) {
            return;
        }

        submit.disabled = true;
        submit.setAttribute("aria-busy", "true");
        submit.dataset.originalLabel = submit.textContent ?? "";
        submit.textContent = submit.dataset.loadingLabel ?? "Envoi…";
    });
})();
