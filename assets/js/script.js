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
            themeColorMeta.setAttribute("content", theme === "dark" ? "#0b1120" : "#f4f6f9");
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
});