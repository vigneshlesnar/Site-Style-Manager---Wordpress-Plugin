<?php
// Badge HTML is injected by frontend-badge.js — this file is intentionally empty.
// The JS builds the full DOM at runtime for better performance and flexibility.
// However, we output a no-script fallback link here.
?>
<noscript>
  <a href="<?php echo esc_url( admin_url( 'admin.php?page=site-style-manager' ) ); ?>"
     style="position:fixed;bottom:20px;right:20px;z-index:999999;background:#6366f1;color:#fff;padding:10px 18px;border-radius:8px;font-family:sans-serif;font-size:13px;text-decoration:none;">
    🎨 Style Manager
  </a>
</noscript>
