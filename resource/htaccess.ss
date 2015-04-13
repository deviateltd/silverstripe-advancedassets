RewriteEngine On
RewriteBase $base
RewriteCond %{REQUEST_URI} ^(.*)$
RewriteRule .* $frameworkDir/main.php?url=%1 [QSA]