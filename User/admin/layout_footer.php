    </main><!-- /content -->
  </div><!-- /main wrap -->
</div><!-- /flex wrapper -->

<script src="js/app.v1.js"></script>
<script src="js/app.plugin.js"></script>
<script>
function toggleSidebar() {
    var s = document.getElementById('dashSidebar');
    var o = document.getElementById('dashOverlay');
    s.classList.toggle('-translate-x-full');
    o.classList.toggle('hidden');
}
function closeSidebar() {
    document.getElementById('dashSidebar').classList.add('-translate-x-full');
    document.getElementById('dashOverlay').classList.add('hidden');
}
</script>
</body>
</html>

