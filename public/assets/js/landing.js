(() => {
    const aiModal = document.querySelector("#aiDetailsModal");
    if (aiModal) {
        document.body.append(aiModal);
    }

    const revealItems = document.querySelectorAll(".landing-reveal");

    if (!("IntersectionObserver" in window) || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }

    document.documentElement.classList.add("landing-motion-ready");

    const observer = new IntersectionObserver(
        (entries, currentObserver) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) {
                    continue;
                }

                entry.target.classList.add("is-visible");
                currentObserver.unobserve(entry.target);
            }
        },
        { rootMargin: "0px 0px -40px 0px", threshold: 0.1 },
    );

    revealItems.forEach((item) => observer.observe(item));
})();
