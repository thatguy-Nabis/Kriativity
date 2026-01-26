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
                    <li><a href="collections.php" class="footer-link">Collections</a></li>
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

<style>
/* ============================================
   CLEAN FOOTER – INLINE STYLES
   ============================================ */

.footer-container {
    margin-top: 4rem;
    background: linear-gradient(180deg, rgba(21,5,29,.7), #15051d);
    border-top: 1px solid rgba(206,161,245,.15);
    padding: 3rem 2rem 2rem;
    color: #e0e0e0;
}

.footer-content {
    max-width: 1400px;
    margin: auto;
}

.footer-top {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(206,161,245,.15);
}

.footer-logo {
    font-size: 1.6rem;
    font-weight: 800;
    background: linear-gradient(135deg,#CEA1F5,#b88de0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.footer-description {
    margin-top: .5rem;
    font-size: .95rem;
    color: #a0a0a0;
    line-height: 1.6;
    max-width: 340px;
}

.footer-heading {
    color: #CEA1F5;
    font-weight: 700;
    margin-bottom: .75rem;
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-link {
    display: inline-block;
    margin-bottom: .5rem;
    color: #a0a0a0;
    font-size: .95rem;
    transition: .2s;
}

.footer-link:hover {
    color: #CEA1F5;
    transform: translateX(4px);
}

.social-links {
    display: flex;
    gap: .75rem;
    margin-top: 1rem;
}

.social-link {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(206,161,245,.12);
    color: #CEA1F5;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: .25s;
}

.social-link:hover {
    background: rgba(206,161,245,.25);
    transform: translateY(-3px);
}

.footer-bottom {
    margin-top: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-copy {
    font-size: .9rem;
    color: #a0a0a0;
}

.back-to-top {
    font-size: .9rem;
    color: #CEA1F5;
}

.back-to-top:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
    .footer-top {
        grid-template-columns: 1fr;
    }

    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
}
</style>
