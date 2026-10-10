  </div><!-- /admin-container -->
</main>

<!-- Lucide sincrónico al final del body: el DOM ya está completo aquí -->
<script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.min.js"></script>
<script>
lucide.createIcons();

var toggle = document.getElementById('nav-toggle');
var nav    = document.getElementById('admin-nav');
if (toggle && nav) {
  toggle.addEventListener('click', function () {
    var open = nav.classList.toggle('admin-nav--open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
}
</script>
</body>
</html>
