(() => {
    "use strict";

    const menuButton = document.querySelector("#menuBtn");
    const navigation = document.querySelector("#siteNav");

    if (menuButton && navigation) {
        menuButton.addEventListener("click", () => {
            const isOpen = navigation.classList.toggle("mobile-open");
            menuButton.setAttribute("aria-expanded", String(isOpen));
        });

        navigation.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => {
                navigation.classList.remove("mobile-open");
                menuButton.setAttribute("aria-expanded", "false");
            });
        });
    }

    const sections = [...document.querySelectorAll("section[id]")];
    const navLinks = [...document.querySelectorAll(".nav-links a")];

    if ("IntersectionObserver" in window && sections.length && navLinks.length) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                navLinks.forEach((link) => {
                    link.classList.toggle(
                        "active",
                        link.getAttribute("href") === `#${entry.target.id}`
                    );
                });
            });
        }, { rootMargin: "-35% 0px -55% 0px" });

        sections.forEach((section) => sectionObserver.observe(section));
    }

    if ("IntersectionObserver" in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12 });

        document.querySelectorAll(
            ".section-heading, .feature-card, .central-text, .big-dashboard, .step-card, .organized-text, .benefit, .cta-content"
        ).forEach((element) => {
            element.classList.add("reveal");
            revealObserver.observe(element);
        });
    }
})();
