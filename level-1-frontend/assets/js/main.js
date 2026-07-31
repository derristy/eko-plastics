document.addEventListener('DOMContentLoaded', function () {
    var burger = document.querySelector('.burger');
    var nav = document.querySelector('.nav');

    if (!burger || !nav) {
        return;
    }

    function toggle(open) {
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        nav.classList.toggle('is-open', open);
    }

    burger.addEventListener('click', function () {
        toggle(burger.getAttribute('aria-expanded') !== 'true');
    });

    nav.addEventListener('click', function (e) {
        if (e.target.closest('a')) {
            toggle(false);
        }
    });

    document.addEventListener('click', function (e) {
        if (!nav.contains(e.target) && !burger.contains(e.target)) {
            toggle(false);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            toggle(false);
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) {
            toggle(false);
        }
    });
});
