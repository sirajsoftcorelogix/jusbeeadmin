(() => {
    const showModal = (modal) => {
        if (typeof modal.showModal === "function") {
            if (!modal.open) {
                modal.showModal();
            }
            return;
        }

        modal.setAttribute("open", "");
    };

    const closeModal = (modal) => {
        if (typeof modal.close === "function" && modal.open) {
            modal.close();
            return;
        }

        modal.removeAttribute("open");
    };

    document.querySelectorAll(".modal-dialog").forEach((modal) => {
        if (modal.hasAttribute("data-modal-initial-open")) {
            showModal(modal);
        }
    });

    document.addEventListener("click", (event) => {
        const openButton = event.target.closest("[data-modal-open]");
        if (openButton) {
            const modal = document.querySelector(openButton.dataset.modalOpen);
            if (modal) {
                showModal(modal);
            }
            return;
        }

        const closeButton = event.target.closest("[data-modal-close]");
        if (closeButton) {
            const modal = closeButton.closest(".modal-dialog");
            if (modal) {
                closeModal(modal);
            }
            return;
        }

        const backdropModal = event.target.closest(".modal-dialog");
        if (backdropModal && event.target === backdropModal) {
            closeModal(backdropModal);
        }
    });

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
