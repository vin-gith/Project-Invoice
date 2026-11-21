(function () {
    'use strict';

    var html = document.documentElement;
    var btn = document.getElementById('themeSwitch');
    if (!btn) { return; }

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var busy = false;
    var FADE_IN = 210;    // ms, samakan dengan .theme-veil.on di glass.css
    var FADE_OUT = 470;   // ms, samakan dengan .theme-veil di glass.css

    function apply(next) {
        html.setAttribute('data-theme', next);
        try { localStorage.setItem('theme', next); } catch (e) {}
        btn.setAttribute('aria-checked', next === 'dark' ? 'true' : 'false');
    }

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.closest || !t.closest('#themeSwitch')) { return; }

        e.stopImmediatePropagation();
        e.preventDefault();
        if (busy) { return; }

        var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        if (reduce) { apply(next); return; }

        busy = true;
        var veil = document.createElement('div');
        veil.className = 'theme-veil';
        veil.setAttribute('data-theme', next);
        veil.setAttribute('aria-hidden', 'true');
        document.body.appendChild(veil);
        void veil.offsetWidth;
        veil.classList.add('on');

        setTimeout(function () {
            html.classList.add('theme-switching');
            apply(next);
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    html.classList.remove('theme-switching');
                    veil.classList.remove('on');
                    setTimeout(function () {
                        if (veil.parentNode) { veil.parentNode.removeChild(veil); }
                        busy = false;
                    }, FADE_OUT + 40);
                });
            });
        }, FADE_IN);
    }, true);
})();
