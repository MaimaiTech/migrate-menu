<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\MaimaiTech\MigrateMenu;

class InstallScript
{
    public function __invoke(): void
    {
        echo "Menu Migration Plugin installed successfully!\n";
        echo "API Endpoints available:\n";
        echo "- GET /admin/menu-migrate/list - Get menu list\n";
        echo "- GET /admin/menu-migrate/export - Export menu configuration\n";
        echo "- POST /admin/menu-migrate/import - Import menu configuration\n";
    }
}