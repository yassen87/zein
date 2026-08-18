(function () {
    var KEY = 'medal-theme';
    var root = document.documentElement;
    var btn = document.getElementById('admin-theme-toggle');

    function setAria() {
        if (!btn) {
            return;
        }
        var toLight = btn.getAttribute('data-to-daylight') || '';
        var toDark = btn.getAttribute('data-to-dark') || '';
        // If it HAS .dark, we want to go back to daylight (toLight)
        btn.setAttribute('aria-label', root.classList.contains('dark') ? toLight : toDark);
    }

    function setDarkMode(on) {
        if (on) {
            root.classList.add('dark');
            try {
                localStorage.setItem(KEY, 'dark');
            } catch (e) {}
        } else {
            root.classList.remove('dark');
            try {
                localStorage.setItem(KEY, 'daylight');
            } catch (e) {}
        }
        setAria();
    }

    if (btn) {
        setAria();
        btn.addEventListener('click', function () {
            setDarkMode(!root.classList.contains('dark'));
        });
    }
})();
