(() => {
    const toggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    const backdrop = document.getElementById("sidebarBackdrop");

    if (!toggle || !sidebar || !backdrop) {
        return;
    }

    const closeSidebar = () => {
        sidebar.classList.remove("is-open");
        backdrop.classList.remove("is-visible");
    };

    toggle.addEventListener("click", () => {
        sidebar.classList.toggle("is-open");
        backdrop.classList.toggle("is-visible");
    });

    backdrop.addEventListener("click", closeSidebar);

    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
})();
