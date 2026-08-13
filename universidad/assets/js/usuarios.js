(() => {
    const tableBody = document.querySelector('#usersTable tbody');
    if (!tableBody) return;

    const rows = [...tableBody.querySelectorAll('tr')];
    const search = document.getElementById('searchUser');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const pagination = document.getElementById('usersPagination');
    const resultsLabel = document.getElementById('resultsLabel');
    const modal = document.getElementById('usuarioModal');
    const form = document.getElementById('usuarioForm');
    const toast = bootstrap.Toast.getOrCreateInstance(document.getElementById('userToast'));
    const pageSize = 4;
    let currentPage = 1;

    function filteredRows() {
        const term = search.value.trim().toLowerCase();
        const role = roleFilter.value;
        const status = statusFilter.value;
        return rows.filter(row => {
            const matchesText = !term || row.dataset.search.includes(term);
            const matchesRole = !role || row.dataset.role === role;
            const matchesStatus = !status || row.dataset.status === status;
            return matchesText && matchesRole && matchesStatus;
        });
    }

    function render() {
        const filtered = filteredRows();
        const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
        currentPage = Math.min(currentPage, pages);
        rows.forEach(row => row.classList.add('d-none'));
        const start = (currentPage - 1) * pageSize;
        filtered.slice(start, start + pageSize).forEach(row => row.classList.remove('d-none'));
        const from = filtered.length ? start + 1 : 0;
        const to = Math.min(start + pageSize, filtered.length);
        resultsLabel.innerHTML = `Mostrando <strong>${from}</strong> a <strong>${to}</strong> de <strong>${filtered.length}</strong> resultados`;
        pagination.innerHTML = '';
        pagination.appendChild(pageItem('Anterior', currentPage === 1, () => { currentPage--; render(); }, 'bi-chevron-left'));
        for (let p = 1; p <= pages; p++) {
            const li = pageItem(String(p), false, () => { currentPage = p; render(); });
            if (p === currentPage) li.classList.add('active');
            pagination.appendChild(li);
        }
        pagination.appendChild(pageItem('Siguiente', currentPage === pages, () => { currentPage++; render(); }, 'bi-chevron-right', true));
    }

    function pageItem(label, disabled, click, icon, iconAfter = false) {
        const li = document.createElement('li');
        li.className = `page-item${disabled ? ' disabled' : ''}`;
        const button = document.createElement('button');
        button.className = 'page-link';
        button.type = 'button';
        button.innerHTML = icon ? (iconAfter ? `${label}<i class="bi ${icon} ms-2"></i>` : `<i class="bi ${icon} me-2"></i>${label}`) : label;
        if (!disabled) button.addEventListener('click', click);
        li.appendChild(button);
        return li;
    }

    [search, roleFilter, statusFilter].forEach(control => control.addEventListener('input', () => { currentPage = 1; render(); }));

    modal.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        const mode = trigger?.dataset.mode || 'edit';
        const title = document.getElementById('usuarioModalTitle');
        const subtitle = document.getElementById('usuarioModalSubtitle');
        const saveText = document.getElementById('saveButtonText');
        form.reset();

        if (mode === 'create') {
            title.textContent = 'Nuevo Usuario';
            subtitle.textContent = 'Registra un nuevo acceso para el sistema universitario.';
            saveText.textContent = 'Crear Usuario';
            document.getElementById('userId').value = '';
            return;
        }

        const row = trigger.closest('tr');
        const user = JSON.parse(row.dataset.user);
        title.textContent = 'Ajustar Perfil';
        subtitle.textContent = 'Modifica los datos personales y el nivel de acceso.';
        saveText.textContent = 'Guardar Cambios';
        document.getElementById('userId').value = user.id;
        document.getElementById('userName').value = user.nombre;
        document.getElementById('userLastName').value = user.apellidos;
        document.getElementById('userEmail').value = user.correo;
        document.getElementById('userRole').value = user.rol;
        document.querySelector(`input[name="estado"][value="${user.estado}"]`).checked = true;
    });

    form.addEventListener('submit', event => {
        event.preventDefault();
        showToast(document.getElementById('userId').value ? 'Los cambios del usuario fueron guardados.' : 'El nuevo usuario fue creado correctamente.');
        bootstrap.Modal.getInstance(modal).hide();
    });

    document.querySelectorAll('.action-unlock:not(:disabled)').forEach(button => {
        button.addEventListener('click', () => {
            const row = button.closest('tr');
            const user = JSON.parse(row.dataset.user);
            if (!confirm(`¿Desea desbloquear a ${user.nombre} ${user.apellidos}?`)) return;
            user.bloqueado = false;
            row.dataset.user = JSON.stringify(user);
            row.dataset.status = user.estado;
            row.querySelector('.status-badge').outerHTML = user.estado === 'activo'
                ? '<span class="status-badge status-active"><span></span>Activo</span>'
                : '<span class="status-badge status-inactive"><span></span>Inactivo</span>';
            row.querySelector('.blocked-caption')?.remove();
            button.disabled = true;
            button.classList.add('disabled');
            showToast('El usuario fue desbloqueado correctamente.');
            render();
        });
    });

    document.querySelectorAll('.action-delete').forEach(button => {
        button.addEventListener('click', () => {
            const row = button.closest('tr');
            const user = JSON.parse(row.dataset.user);
            if (confirm(`¿Desea eliminar a ${user.nombre} ${user.apellidos}?`)) {
                row.remove();
                const index = rows.indexOf(row);
                if (index >= 0) rows.splice(index, 1);
                showToast('El usuario fue eliminado de la lista de demostración.');
                render();
            }
        });
    });

    function showToast(message) {
        document.getElementById('toastText').textContent = message;
        toast.show();
    }

    render();
})();
