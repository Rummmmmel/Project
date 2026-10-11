    </div></main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  var root = document.documentElement;
  document.getElementById('burger').addEventListener('click', function(){
    root.classList.toggle('collapsed');
    localStorage.setItem('side', root.classList.contains('collapsed') ? '1' : '0');
  });
  document.getElementById('darkBtn').addEventListener('click', function(){
    var t = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = t; localStorage.setItem('theme', t);
  });
})();
</script>
</body>
</html>
