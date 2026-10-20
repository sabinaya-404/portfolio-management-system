(() => {
    "use strict";

    // ==========================================================================
    // Mobile Navigation with Focus Management
    // ==========================================================================
    const menuButton = document.querySelector("#menuBtn");
    const navigation = document.querySelector(".nav");

    if (menuButton && navigation) {
        const navLinks = navigation.querySelectorAll(".nav-links a, .nav-actions .btn");
        let lastFocusedElement = null;

        function openMenu() {
            lastFocusedElement = document.activeElement;
            navigation.classList.add("is-open");
            menuButton.setAttribute("aria-expanded", "true");
            document.body.classList.add("nav-open");
            // Focus first focusable element in menu
            const firstLink = navigation.querySelector(".nav-links a, .nav-actions .btn");
            if (firstLink) firstLink.focus();
        }

        function closeMenu() {
            navigation.classList.remove("is-open");
            menuButton.setAttribute("aria-expanded", "false");
            document.body.classList.remove("nav-open");
            // Return focus to menu button
            if (lastFocusedElement) lastFocusedElement.focus();
        }

        menuButton.addEventListener("click", () => {
            const isOpen = navigation.classList.toggle("is-open");
            menuButton.setAttribute("aria-expanded", String(isOpen));
            document.body.classList.toggle("nav-open", isOpen);

            if (isOpen) {
                lastFocusedElement = document.activeElement;
                const firstLink = navigation.querySelector(".nav-links a, .nav-actions .btn");
                if (firstLink) firstLink.focus();
            } else if (lastFocusedElement) {
                lastFocusedElement.focus();
            }
        });

        // Close on link click
        navLinks.forEach((link) => {
            link.addEventListener("click", () => {
                if (navigation.classList.contains("is-open")) {
                    closeMenu();
                }
            });
        });

        // Close on Escape key
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && navigation.classList.contains("is-open")) {
                closeMenu();
            }
        });

        // Trap focus in mobile menu
        navigation.addEventListener("keydown", (e) => {
            if (!navigation.classList.contains("is-open")) return;
            if (e.key !== "Tab") return;

            const focusableElements = navigation.querySelectorAll(
                '.nav-links a, .nav-actions .btn'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        });
    }

    // ==========================================================================
    // Scroll Spy for Active Navigation
    // ==========================================================================
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

    // ==========================================================================
    // Reveal Animations
    // ==========================================================================
    if ("IntersectionObserver" in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12 });

        // Elements to reveal - updated selectors for new structure
        document.querySelectorAll(
            ".section-header, .feature-card, .step, .about-content, .about-specs .spec-card, .cta-card"
        ).forEach((element) => {
            element.classList.add("reveal");
            revealObserver.observe(element);
        });
    }

    // ==========================================================================
    // Theme Toggle (if theme toggle button exists)
    // ==========================================================================
    const themeToggle = document.querySelector("#themeToggle");
    if (themeToggle) {
        const storedTheme = localStorage.getItem("theme");
        const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
        const initialTheme = storedTheme || (prefersDark ? "dark" : "light");

        document.documentElement.setAttribute("data-theme", initialTheme);
        themeToggle.setAttribute("aria-pressed", initialTheme === "dark");

        themeToggle.addEventListener("click", () => {
            const currentTheme = document.documentElement.getAttribute("data-theme");
            const newTheme = currentTheme === "dark" ? "light" : "dark";
            document.documentElement.setAttribute("data-theme", newTheme);
            localStorage.setItem("theme", newTheme);
            themeToggle.setAttribute("aria-pressed", newTheme === "dark");
        });
    }
})();