<?php
// ============================================
// CLEAN REUSABLE FOOTER – KRIATIVITY
// ============================================
?>
<footer class="footer-container">
    <div class="footer-content">

        <!-- TOP -->
        <div class="footer-top">

            <!-- BRAND -->
            <div class="footer-column footer-brand">
                <div class="footer-logo">✨ Kriativity</div>
                <p class="footer-description">
                    Discover, curate, and share creative content.
                    Built for creators, powered by inspiration.
                </p>

                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Twitter">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>
                        </svg>
                    </a>

                    <a href="#" class="social-link" aria-label="GitHub">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57
                            0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695
                            -.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99
                            .105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925
                            0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18
                            0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405
                            c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18
                            .765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925
                            .435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3
                            0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                        </svg>
                    </a>

                    <a href="#" class="social-link" aria-label="Instagram">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="2" y="2" width="20" height="20" rx="5"/>
                            <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" fill="#15051d"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- QUICK LINKS -->
            <div class="footer-column">
                <h3 class="footer-heading">Explore</h3>
                <ul class="footer-links">
                    <li><a href="homepage.php" class="footer-link">Home</a></li>
                    <li><a href="homepage.php?tab=trending" class="footer-link">Trending</a></li>
                    <li><a href="profile.php" class="footer-link">Profile</a></li>
                </ul>
            </div>

        </div>

        <!-- BOTTOM -->
        <div class="footer-bottom">
            <p class="footer-copy">
                © <?= date('Y') ?> Kriativity. All rights reserved.
            </p>

            <a href="#" class="back-to-top" id="backToTop">↑ Back to top</a>
        </div>

    </div>
</footer>

<script>
document.getElementById('backToTop').addEventListener('click', e => {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>

<link rel="stylesheet" href="styles/footer.css">
