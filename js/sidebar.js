document.addEventListener('DOMContentLoaded', function () {
    // Toggle sidebar for mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarToggleIcon = document.getElementById('sidebar-toggle-icon');
    const overlay = document.getElementById('overlay');
    const body = document.body;

    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        body.classList.add('sidebar-open');
        if (sidebarToggleIcon) {
            sidebarToggleIcon.classList.remove('fa-bars');
            sidebarToggleIcon.classList.add('fa-times');
        }
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        body.classList.remove('sidebar-open');
        if (sidebarToggleIcon) {
            sidebarToggleIcon.classList.remove('fa-times');
            sidebarToggleIcon.classList.add('fa-bars');
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarToggleIcon) {
        sidebarToggleIcon.addEventListener('click', function () {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar when clicking on a menu item on mobile
    const menuItems = document.querySelectorAll('.sidebar-menu a');
    if (window.innerWidth <= 768) {
        menuItems.forEach(item => {
            item.addEventListener('click', closeSidebar);
        });
    }

    // Handle window resize
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            body.classList.remove('sidebar-open');
        }
    });
});