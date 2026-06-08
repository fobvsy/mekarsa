<?php
$files = glob('c:/xampp/htdocs/mekarsa/*.php');

$navbar_html = '            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="about.php">Tentang Kami</a></li>
                <li><a href="support-service.php">Shoe Clean</a></li>
                <li><a href="contact.php">Kontak</a></li>
            </ul>';

$footer_html = '                    <h4>Menu Cepat</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="menu.php">Menu Coffee</a></li>
                        <li><a href="about.php">Tentang Kami</a></li>
                        <li><a href="support-service.php">Layanan Shoe Clean</a></li>
                        <li><a href="contact.php">Kontak</a></li>
                    </ul>';

foreach ($files as $file) {
    if (basename($file) === 'cart_action.php') continue;
    
    $content = file_get_contents($file);
    
    // Replace navbar
    $content = preg_replace('/<ul class="nav-links">.*?<\/ul>/s', $navbar_html, $content);
    
    // Replace footer Menu Cepat
    $content = preg_replace('/<h4>Menu Cepat<\/h4>\s*<ul class="footer-links">.*?<\/ul>/s', $footer_html, $content);
    
    file_put_contents($file, $content);
    echo "Updated $file\n";
}
