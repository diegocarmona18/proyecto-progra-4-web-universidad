const sidebar = document.getElementById('sidebar');
const mainWrapper = document.getElementById('mainWrapper');
const toggle = document.getElementById('sidebarToggle');

if (localStorage.getItem('sidebar-collapsed') === 'true') {
    sidebar.classList.add('collapsed');
    mainWrapper.classList.add('expanded');
}

toggle?.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainWrapper.classList.toggle('expanded');
    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
});

if (window.innerWidth < 992) {
    sidebar.classList.add('collapsed');
    mainWrapper.classList.add('expanded');
}
