(function () {
    function closeMenus() {
        document.querySelectorAll('.dropdown1-content.is-open').forEach(function (menu) {
            menu.classList.remove('is-open');
            menu.style.left = '';
            menu.style.top = '';
        });
    }

    function placeMenu(button, menu) {
        menu.classList.add('is-open');

        var rect = button.getBoundingClientRect();
        var width = menu.offsetWidth;
        var height = menu.offsetHeight;
        var left = rect.right - width;
        left = Math.max(8, Math.min(left, window.innerWidth - width - 8));

        var top = rect.bottom + 6;
        if (top + height > window.innerHeight - 8 && rect.top - height - 6 > 8) {
            top = rect.top - height - 6;
        }

        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.dropdown1-button');

        if (button) {
            event.preventDefault();
            var menu = button.parentElement.querySelector('.dropdown1-content');
            if (!menu) {
                return;
            }

            var alreadyOpen = menu.classList.contains('is-open');
            closeMenus();
            if (!alreadyOpen) {
                placeMenu(button, menu);
            }
            return;
        }

        if (!event.target.closest('.dropdown1-content')) {
            closeMenus();
        }
    });

    window.addEventListener('resize', closeMenus);
    document.addEventListener('scroll', closeMenus, true);
})();
