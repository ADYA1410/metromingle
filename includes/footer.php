<?php if (current_user()): ?>
    </main>
  </div>
<?php else: ?>
  </main>
  <footer class="site-footer">
    <div class="container footer-inner">
      <strong>MetroMingle</strong>
      <span>&copy; <?php echo date('Y'); ?> MetroMingle. Event space discovery and booking management.</span>
    </div>
  </footer>
<?php endif; ?>
  <script src="<?php echo e(url('js/app.js')); ?>"></script>
</body>
</html>
