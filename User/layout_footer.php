    </main><!-- /content -->
  </div><!-- /main wrap -->
</div><!-- /flex wrapper -->

<script>
function toggleSidebar() {
    var s = document.getElementById('dashSidebar');
    var o = document.getElementById('dashOverlay');
    if (s && o) {
        s.classList.toggle('-translate-x-full');
        o.classList.toggle('hidden');
    }
}
function closeSidebar() {
    var s = document.getElementById('dashSidebar');
    var o = document.getElementById('dashOverlay');
    if (s && o) {
        s.classList.add('-translate-x-full');
        o.classList.add('hidden');
    }
}

/**
 * Universal Debounced Form Submitter for Search Inputs
 */
function initTableSearchDebounce(inputId, formId, delay = 450) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var form = formId ? document.getElementById(formId) : input.closest('form');
    if (!form) return;
    
    var timer;
    input.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(function() {
            var pageInput = form.querySelector('input[name="page"]');
            if (pageInput) pageInput.value = '1';
            form.submit();
        }, delay);
    });
}

/**
 * Universal Column Sorting
 */
function applyTableSort(col, defaultOrder = 'asc') {
    var url = new URL(window.location.href);
    var curSort = url.searchParams.get('sort');
    var curOrder = url.searchParams.get('order') || 'asc';
    
    if (curSort === col) {
        url.searchParams.set('order', curOrder === 'asc' ? 'desc' : 'asc');
    } else {
        url.searchParams.set('sort', col);
        url.searchParams.set('order', defaultOrder);
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

/**
 * Universal Client-side Live Table Filter with Debounce
 */
function initClientFilter(config) {
    var searchInput = document.getElementById(config.searchInputId);
    var filterSelect = config.filterSelectId ? document.getElementById(config.filterSelectId) : null;
    var items = document.querySelectorAll(config.rowSelector);
    var emptyNotice = config.emptyNoticeId ? document.getElementById(config.emptyNoticeId) : null;
    var countNotice = config.countNoticeId ? document.getElementById(config.countNoticeId) : null;

    if (!items.length) return;

    var timer;
    function filterItems() {
        var term = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var filterVal = filterSelect ? filterSelect.value.toLowerCase().trim() : 'all';
        var visibleCount = 0;

        items.forEach(function(item) {
            var text = (item.getAttribute('data-search-text') || item.innerText).toLowerCase();
            var status = (item.getAttribute('data-status') || '').toLowerCase();

            var matchesSearch = !term || text.indexOf(term) !== -1;
            var matchesFilter = (filterVal === 'all' || !filterVal) || (status === filterVal);

            if (matchesSearch && matchesFilter) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        if (emptyNotice) {
            emptyNotice.style.display = visibleCount === 0 ? '' : 'none';
        }
        if (countNotice) {
            countNotice.textContent = visibleCount + ' record' + (visibleCount !== 1 ? 's' : '');
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(filterItems, 250);
        });
    }
    if (filterSelect) {
        filterSelect.addEventListener('change', filterItems);
    }
}
</script>
</body>
</html>

