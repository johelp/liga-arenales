/**
 * widget-resize.js
 * Incluir en WordPress para que los iframes del widget se auto-ajusten en altura.
 *
 * Uso en WordPress (agregar en footer o plugin "Insert Headers and Footers"):
 *   <script src="https://tudominio.com/liga/widget-resize.js"></script>
 */
(function () {
    window.addEventListener('message', function (e) {
        if (!e.data || e.data.type !== 'ligaWidgetResize') return;
        var iframes = document.querySelectorAll('iframe');
        iframes.forEach(function (iframe) {
            try {
                if (iframe.contentWindow === e.source) {
                    iframe.style.height = (e.data.height + 8) + 'px';
                    iframe.style.overflow = 'hidden';
                }
            } catch (_) {}
        });
    });
})();
