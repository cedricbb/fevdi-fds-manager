document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fds-wrap').forEach((wrap) => {
        const table = wrap.querySelector('.fds-table');
        if (!table) return;

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

            if (pager) {
                pager.innerHTML = '';
                const ul = document.createElement('ul');

                const addItem = (text, page, disabled, active) => {
                    const li = document.createElement('li');
                    if (disabled) li.classList.add('disabled');
                    if (active)   li.classList.add('active');
                    const a = document.createElement('a');
                    a.href = '#';
                    a.innerHTML = text;
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (!disabled) { currentPage = page; render(); }
                    });
                    li.appendChild(a);
                    ul.appendChild(li);
                };

                const windowSize = 5;
                let startPage = Math.max(1, currentPage - Math.floor(windowSize / 2));
                let endPage   = Math.min(totalPages, startPage + windowSize - 1);
                if (endPage - startPage < windowSize - 1) {
                    startPage = Math.max(1, endPage - windowSize + 1);
                }

                addItem('&laquo;', 1,               currentPage === 1);
                addItem('&lsaquo;', currentPage - 1, currentPage === 1);
                for (let i = startPage; i <= endPage; i += 1) {
                    addItem(String(i), i, false, i === currentPage);
                }
                addItem('&rsaquo;', currentPage + 1, currentPage === totalPages);
                addItem('&raquo;', totalPages,       currentPage === totalPages);

                pager.appendChild(ul);
            }
        };

        if (search) {
            search.addEventListener('input', function onSearch() {
                const q = this.value.toLowerCase().trim();
                filtered = rows.filter((row) => row.innerText.toLowerCase().includes(q));
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
});