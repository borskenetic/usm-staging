function openDeleteModal(courseId, courseCode) {
    const form = document.getElementById('deleteForm');
    form.action = `/prospectus/course/${courseId}`;
    document.getElementById('deleteMessage').innerText =
        `Are you sure you want to delete course "${courseCode}"?`;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

function openEditModal(courseId, code, name) {
    const form = document.getElementById('editForm');
    form.action = `/prospectus/course/${courseId}`;
    document.getElementById('editCourseCode').value = code;
    document.getElementById('editCourseName').value = name;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function courseCountLabel(count) {
    return `${count} ${count === 1 ? 'course' : 'courses'}`;
}

function bumpStat(name, delta) {
    const el = document.querySelector(`[data-stat="${name}"]`);
    if (!el) return;
    const next = Math.max(0, (parseInt(el.textContent.replace(/,/g, ''), 10) || 0) + delta);
    el.textContent = next.toLocaleString();
}

function refreshYearCount(yearId) {
    const list = document.getElementById(`year-${yearId}-list`);
    const badge = document.querySelector(`[data-year-course-count="${yearId}"]`);
    if (!list || !badge) return;
    const count = list.querySelectorAll('.prospectus-course-item').length;
    badge.textContent = courseCountLabel(count);
}

function refreshProgramCount(programId) {
    const card = document.getElementById(`program-card-${programId}`);
    const counter = document.querySelector(`[data-program-course-count="${programId}"]`);
    const label = document.querySelector(`[data-program-course-label="${programId}"]`);
    if (!card || !counter) return;
    const count = card.querySelectorAll('.prospectus-course-item').length;
    counter.textContent = String(count);
    if (label) label.textContent = count === 1 ? 'course' : 'courses';
}

function ensureEmptyState(list) {
    if (!list) return;
    if (list.querySelectorAll('.prospectus-course-item').length === 0 && !list.querySelector('.prospectus-course-empty')) {
        list.insertAdjacentHTML('beforeend', '<li class="prospectus-course-empty">No courses yet.</li>');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#prospectus-page [data-prospectus-panel]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const sel = btn.getAttribute('data-prospectus-panel');
            const panel = sel ? document.querySelector(sel) : null;
            if (!panel) return;

            const card = panel.closest('.prospectus-program');
            const willCollapse = !panel.classList.contains('hidden') && !(card && card.classList.contains('is-collapsed'));

            if (card) {
                card.classList.toggle('is-collapsed', willCollapse);
            }
            panel.classList.toggle('hidden', willCollapse);
            btn.setAttribute('aria-expanded', willCollapse ? 'false' : 'true');
            btn.textContent = willCollapse
                ? (btn.getAttribute('data-expand-label') || 'Expand')
                : (btn.getAttribute('data-collapse-label') || 'Collapse');
        });
    });

    const editForm = document.getElementById('editForm');
    const deleteForm = document.getElementById('deleteForm');

    function toggleLoading(button, loading) {
        if (!button) return;
        const spinner = button.querySelector('.spinner');
        const text = button.querySelector('.btn-text');
        if (loading) {
            if (spinner) spinner.classList.remove('hidden');
            if (text) text.classList.add('hidden');
            button.disabled = true;
        } else {
            if (spinner) spinner.classList.add('hidden');
            if (text) text.classList.remove('hidden');
            button.disabled = false;
        }
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `prospectus-toast prospectus-toast--${type === 'success' ? 'success' : 'error'} animate-slide-in`;
        toast.innerHTML = `
            <span>${message}</span>
            <button type="button" aria-label="Dismiss">×</button>
        `;

        toast.querySelector('button').addEventListener('click', () => toast.remove());
        setTimeout(() => {
            toast.classList.remove('animate-slide-in');
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 450);
        }, 2000);

        container.appendChild(toast);
    }

    if (editForm) {
        editForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('editBtn');
            toggleLoading(btn, true);

            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            toggleLoading(btn, false);

            if (response.ok) {
                const updatedItem = await response.text();
                const courseId = this.action.split('/').pop();
                const li = document.getElementById('course-' + courseId);
                if (li) li.outerHTML = updatedItem;
                closeEditModal();
                showToast('Course updated');
            } else {
                showToast('Error updating course', 'error');
            }
        });
    }

    if (deleteForm) {
        deleteForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('deleteBtn');
            toggleLoading(btn, true);

            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            toggleLoading(btn, false);

            if (response.ok) {
                const courseId = this.action.split('/').pop();
                const li = document.getElementById('course-' + courseId);
                const yearId = li?.dataset?.yearId;
                const programId = li?.closest('[data-program-id]')?.getAttribute('data-program-id');
                const list = li?.parentElement;
                if (li) li.remove();
                ensureEmptyState(list);
                if (yearId) refreshYearCount(yearId);
                if (programId) refreshProgramCount(programId);
                bumpStat('courses', -1);
                closeDeleteModal();
                showToast('Course deleted');
            } else {
                showToast('Error deleting course', 'error');
            }
        });
    }

    document.querySelectorAll('.add-course-form').forEach((form) => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            toggleLoading(btn, true);

            const yearId = this.dataset.year;
            const programId = this.dataset.program;
            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            toggleLoading(btn, false);

            if (response.ok) {
                const newItem = await response.text();
                const ul = document.getElementById(`year-${yearId}-list`);
                if (ul) {
                    const emptyMsg = ul.querySelector('.prospectus-course-empty');
                    if (emptyMsg) emptyMsg.remove();
                    ul.insertAdjacentHTML('beforeend', newItem);
                }
                this.reset();
                refreshYearCount(yearId);
                if (programId) refreshProgramCount(programId);
                bumpStat('courses', 1);
                showToast('Course added');
            } else {
                showToast('Error adding course', 'error');
            }
        });
    });

    window.openProgramEditModal = function (programId, programCode, programName) {
        const modal = document.getElementById('editProgramModal');
        const form = document.getElementById('editProgramForm');
        form.action = `/prospectus/program/${programId}`;
        document.getElementById('editProgramCode').value = programCode;
        document.getElementById('editProgramName').value = programName;
        modal.classList.remove('hidden');
    };

    window.closeProgramEditModal = function () {
        document.getElementById('editProgramModal').classList.add('hidden');
    };

    const editProgramForm = document.getElementById('editProgramForm');
    if (editProgramForm) {
        editProgramForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('editProgramBtn');
            toggleLoading(btn, true);

            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            toggleLoading(btn, false);

            if (response.ok) {
                const data = await response.json();
                const nameEl = document.getElementById('program-name-' + data.id);
                if (nameEl) nameEl.textContent = data.program_name;
                const card = document.getElementById('program-card-' + data.id);
                const badge = card?.querySelector('.prospectus-badge');
                if (badge) badge.textContent = data.program_code;
                closeProgramEditModal();
                showToast('Program updated');
            } else {
                showToast('Error updating program', 'error');
            }
        });
    }

    window.openProgramDeleteModal = function (programId, programCode) {
        const modal = document.getElementById('deleteProgramModal');
        const form = document.getElementById('deleteProgramForm');
        form.action = `/prospectus/program/${programId}`;
        document.getElementById('deleteProgramCode').textContent = programCode;
        modal.classList.remove('hidden');
    };

    window.closeProgramDeleteModal = function () {
        document.getElementById('deleteProgramModal').classList.add('hidden');
    };

    const deleteProgramForm = document.getElementById('deleteProgramForm');
    if (deleteProgramForm) {
        deleteProgramForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('deleteProgramBtn');
            toggleLoading(btn, true);

            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            toggleLoading(btn, false);

            if (response.ok) {
                const data = await response.json();
                const card = document.getElementById('program-card-' + data.id);
                const courseDelta = card ? -card.querySelectorAll('.prospectus-course-item').length : 0;
                if (card) card.remove();
                bumpStat('programs', -1);
                bumpStat('showing', -1);
                if (courseDelta) bumpStat('courses', courseDelta);
                closeProgramDeleteModal();
                showToast('Program deleted');
            } else {
                showToast('Error deleting program', 'error');
            }
        });
    }
});
