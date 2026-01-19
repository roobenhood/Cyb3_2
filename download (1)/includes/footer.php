    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content" style="flex-direction: column; align-items: center; text-align: center;">
                <div class="footer-brand" style="text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 10px;">
                        <span class="logo-icon">🛡️</span>
                        <span class="logo-text">AlwaniCTF</span>
                    </div>
                    <p><?php echo __('site_description_footer'); ?></p>
                </div>
                <div class="footer-links" style="justify-content: center;">
                    <a href="challenges.php"><?php echo __('challenges'); ?></a>
                    <a href="scoreboard.php"><?php echo __('scoreboard'); ?></a>
                    <a href="teams.php"><?php echo __('teams'); ?></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> AlwaniCTF. <?php echo __('copyright'); ?></p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/enhanced.js?v=<?php echo time(); ?>"></script>
</body>
</html>
