(function () {
    const ready = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    };

    const initPublicTables = () => {
        document.querySelectorAll('.fds-wrap').forEach((wrap) => {
            const table = wrap.querySelector('.fds-table');
            if (!table || table.matches('[data-fds-admin-table]')) return;

            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr'));
            const search = wrap.querySelector('.fds-search');
            const pager = wrap.querySelector('.fds-pagination');
            const perPage = parseInt(wrap.dataset.perPage || '10', 10);
            let filtered = [...rows];
            let currentPage = 1;

            const render = () => {
                rows.forEach((row) => {
                    row.style.display = 'none';
                });

                const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
                if (currentPage > totalPages) currentPage = totalPages;

                const start = (currentPage - 1) * perPage;
                const end = start + perPage;

                filtered.slice(start, end).forEach((row) => {
                    row.style.display = '';
                });

                if (!pager) return;

                pager.innerHTML = '';
                const ul = document.createElement('ul');

                const addItem = (text, page, disabled, active) => {
                    const li = document.createElement('li');
                    if (disabled) li.classList.add('disabled');
                    if (active) li.classList.add('active');

                    const a = document.createElement('a');
                    a.href = '#';
                    a.innerHTML = text;
                    a.addEventListener('click', (event) => {
                        event.preventDefault();
                        if (!disabled) {
                            currentPage = page;
                            render();
                        }
                    });
                    li.appendChild(a);
                    ul.appendChild(li);
                };

                const windowSize = 5;
                let startPage = Math.max(1, currentPage - Math.floor(windowSize / 2));
                let endPage = Math.min(totalPages, startPage + windowSize - 1);
                if (endPage - startPage < windowSize - 1) {
                    startPage = Math.max(1, endPage - windowSize + 1);
                }

                addItem('&laquo;', 1, currentPage === 1);
                addItem('&lsaquo;', currentPage - 1, currentPage === 1);
                for (let i = startPage; i <= endPage; i += 1) {
                    addItem(String(i), i, false, i === currentPage);
                }
                addItem('&rsaquo;', currentPage + 1, currentPage === totalPages);
                addItem('&raquo;', totalPages, currentPage === totalPages);

                pager.appendChild(ul);
            };

            if (search) {
                search.addEventListener('input', function onSearch() {
                    const query = this.value.toLowerCase().trim();
                    filtered = rows.filter((row) => row.innerText.toLowerCase().includes(query));
                    currentPage = 1;
                    render();
                });
            }

            table.querySelectorAll('th[data-sort]').forEach((th) => {
                th.addEventListener('click', () => {
                    const key = th.dataset.sort;
                    const dir = th.dataset.dir === 'asc' ? 'desc' : 'asc';
                    th.dataset.dir = dir;

                    filtered.sort((a, b) => {
                        const av = a.querySelector(`[data-${key}]`)?.dataset[key] || '';
                        const bv = b.querySelector(`[data-${key}]`)?.dataset[key] || '';
                        return dir === 'asc'
                            ? av.localeCompare(bv, 'fr', { numeric: true })
                            : bv.localeCompare(av, 'fr', { numeric: true });
                    });

                    currentPage = 1;
                    render();
                });
            });

            render();
        });
    };

    const initAdmin = () => {
        const admin = document.querySelector('.fds-admin');
        if (!admin || !window.FevdiFdsManager) return;

        const filters = admin.querySelector('[data-fds-filters]');
        const rows = admin.querySelector('[data-fds-rows]');
        const pager = admin.querySelector('[data-fds-admin-pagination]');
        const count = admin.querySelector('[data-fds-count]');
        const table = admin.querySelector('[data-fds-admin-table]');
        const upload = admin.querySelector('[data-fds-upload]');
        const uploadMessage = admin.querySelector('[data-fds-upload-message]');
        const exportLink = admin.querySelector('.fds-export-logs');
        let page = 1;
        let sort = 'date';
        let order = 'desc';
        let controller = null;

        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        };

        const setMessage = (node, message, type) => {
            if (!node) return;
            node.textContent = message || '';
            node.className = `fds-admin-message ${type || ''}`.trim();
        };

        const payload = () => {
            const formData = new FormData(filters);
            formData.set('action', 'fevdi_fds_admin_list');
            formData.set('nonce', window.FevdiFdsManager.nonce);
            formData.set('page', String(page));
            formData.set('sort', sort);
            formData.set('order', order);

            return formData;
        };

        const updateExportLink = () => {
            if (!exportLink) return;

            const url = new URL(exportLink.href);
            const formData = new FormData(filters);
            ['search', 'dir', 'date_from', 'date_to'].forEach((key) => {
                const value = formData.get(key);
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });

            exportLink.href = url.toString();
        };

        const renderPager = (current, totalPages) => {
            if (!pager) return;

            pager.innerHTML = '';
            const makeButton = (label, targetPage, disabled, active) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                button.disabled = disabled;
                if (active) button.classList.add('is-active');
                button.addEventListener('click', () => {
                    page = targetPage;
                    load();
                });
                pager.appendChild(button);
            };

            makeButton('«', 1, current === 1, false);
            makeButton('‹', current - 1, current === 1, false);

            const start = Math.max(1, current - 2);
            const end = Math.min(totalPages, start + 4);
            for (let i = start; i <= end; i += 1) {
                makeButton(String(i), i, false, i === current);
            }

            makeButton('›', current + 1, current === totalPages, false);
            makeButton('»', totalPages, current === totalPages, false);
        };

        const load = () => {
            if (controller) controller.abort();
            controller = new AbortController();

            if (rows) {
                rows.innerHTML = '<tr><td colspan="5">Chargement...</td></tr>';
            }

            updateExportLink();

            fetch(window.FevdiFdsManager.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: payload(),
                signal: controller.signal,
            })
                .then((response) => response.json())
                .then((response) => {
                    if (!response.success) {
                        throw new Error(response.data?.message || 'Chargement impossible.');
                    }

                    rows.innerHTML = response.data.rows;
                    if (count) count.textContent = response.data.summary;
                    page = response.data.page;
                    renderPager(response.data.page, response.data.totalPages);
                })
                .catch((error) => {
                    if (error.name === 'AbortError') return;
                    rows.innerHTML = `<tr><td colspan="5">${escapeHtml(error.message)}</td></tr>`;
                });
        };

        if (filters) {
            filters.addEventListener('input', () => {
                page = 1;
                load();
            });
            filters.addEventListener('change', () => {
                page = 1;
                load();
            });
        }

        if (table) {
            table.querySelectorAll('[data-sort]').forEach((button) => {
                button.addEventListener('click', () => {
                    const nextSort = button.dataset.sort || 'date';
                    order = sort === nextSort && order === 'asc' ? 'desc' : 'asc';
                    sort = nextSort;
                    page = 1;
                    table.querySelectorAll('[data-sort]').forEach((item) => item.removeAttribute('data-order'));
                    button.dataset.order = order;
                    load();
                });
            });

            table.addEventListener('click', (event) => {
                const button = event.target.closest('[data-fds-delete]');
                if (!button) return;

                const file = button.dataset.fdsDelete;
                const dir = button.dataset.dir || 'fr';

                if (!window.confirm(`Supprimer ${file} ?`)) return;

                const data = new FormData();
                data.set('action', 'fevdi_fds_admin_delete');
                data.set('nonce', window.FevdiFdsManager.nonce);
                data.set('file', file);
                data.set('dir', dir);
                button.disabled = true;

                fetch(window.FevdiFdsManager.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data,
                })
                    .then((response) => response.json())
                    .then((response) => {
                        if (!response.success) {
                            throw new Error(response.data?.message || 'Suppression impossible.');
                        }
                        load();
                    })
                    .catch((error) => {
                        window.alert(error.message);
                        button.disabled = false;
                    });
            });
        }

        if (upload) {
            upload.addEventListener('submit', (event) => {
                event.preventDefault();
                const data = new FormData(upload);
                data.set('action', 'fevdi_fds_admin_upload');
                data.set('nonce', window.FevdiFdsManager.nonce);
                setMessage(uploadMessage, 'Upload en cours...', 'is-loading');

                fetch(window.FevdiFdsManager.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data,
                })
                    .then((response) => response.json())
                    .then((response) => {
                        if (!response.success) {
                            throw new Error(response.data?.message || 'Upload impossible.');
                        }

                        upload.reset();
                        setMessage(uploadMessage, response.data.message, 'is-success');
                        page = 1;
                        load();
                    })
                    .catch((error) => {
                        setMessage(uploadMessage, error.message, 'is-error');
                    });
            });
        }

        load();
    };

    ready(() => {
        initPublicTables();
        initAdmin();
    });
}());
