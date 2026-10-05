{{--
    Applies the colour theme before the page paints (no flash of the wrong colours).
    - Signed-in pages: <html> already carries data-palette from the user's saved theme;
      we remember it in localStorage so guest pages (login, register) match.
    - Guest pages: no attribute yet, so we read the remembered value.
    Dark mode is separate (the "dark" class, handled by its own script).
--}}
<script>
    (function () {
        var root = document.documentElement;
        var palette = root.getAttribute('data-palette');
        try {
            if (palette) {
                localStorage.setItem('automail-palette', palette);
            } else {
                palette = localStorage.getItem('automail-palette');
            }
        } catch (e) {}
        if (palette && /^[a-z0-9-]+$/.test(palette)) {
            root.setAttribute('data-palette', palette);
        }
    })();
</script>
