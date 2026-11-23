<?php

declare(strict_types=1);

/* Выход из админки (только кнопкой — через форму с защитным кодом). */

admin_logout();
redirect('/admin/login?bye=1');
