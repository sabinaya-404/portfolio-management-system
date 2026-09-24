// =========================================================
// GLOBAL THEME TOGGLE CONTROLLER (VANILLA JS)
// =========================================================

document.addEventListener("DOMContentLoaded", function () {
    const themeBtn = document.getElementById("themeToggleBtn");
    const sunIcon = document.getElementById("themeIconSun");
    const moonIcon = document.getElementById("themeIconMoon");
    const themeText = document.getElementById("themeToggleText");

    function updateThemeUI(theme) {
        if (!themeBtn) return;
        const themeColorMeta = document.querySelector('meta[name="theme-color"]');
        if (themeColorMeta) {
            themeColorMeta.setAttribute("content", theme === "dark" ? "#101918" : "#f6f7f5");
        }
        if (theme === "dark") {
            sunIcon.style.display = "inline-block";
            moonIcon.style.display = "none";
            themeText.textContent = "Light";
        } else {
            sunIcon.style.display = "none";
            moonIcon.style.display = "inline-block";
            themeText.textContent = "Dark";
        }
    }

    // Set initial button state based on active theme
    const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
    updateThemeUI(currentTheme);

    // Toggle event listener
    if (themeBtn) {
        themeBtn.addEventListener("click", function () {
            const current = document.documentElement.getAttribute("data-theme") || "light";
            const nextTheme = current === "dark" ? "light" : "dark";

            document.documentElement.setAttribute("data-theme", nextTheme);
            try {
                localStorage.setItem("portfolio_theme", nextTheme);
            } catch (error) {
                // Keep the current theme for this page when storage is unavailable.
            }
            updateThemeUI(nextTheme);
        });
    }

    const navToggle = document.getElementById("mobileNavToggle");
    const navClose = document.getElementById("mobileNavClose");
    const navigation = document.getElementById("primary-navigation");
    if (navToggle && navigation) {
        navToggle.addEventListener("click", function () {
            const isOpen = document.body.classList.toggle("nav-open");
            navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        navigation.addEventListener("click", function (event) {
            if (event.target.closest("a")) {
                document.body.classList.remove("nav-open");
                navToggle.setAttribute("aria-expanded", "false");
            }
        });
        if (navClose) {
            navClose.addEventListener("click", function () {
                document.body.classList.remove("nav-open");
                navToggle.setAttribute("aria-expanded", "false");
                navToggle.focus();
            });
        }
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && document.body.classList.contains("nav-open")) {
                document.body.classList.remove("nav-open");
                navToggle.setAttribute("aria-expanded", "false");
                navToggle.focus();
            }
        });
    }
});