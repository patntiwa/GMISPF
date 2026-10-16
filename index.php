<?php
declare(strict_types=1);
http_response_code(503);
header('Content-Type: text/plain; charset=UTF-8');
echo 'GMISPF : configurez la racine du serveur sur le dossier public/. Consultez README.md.';
