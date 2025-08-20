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

class UninstallScript
{
    public function __invoke(): void
    {
        echo "Menu Migration Plugin uninstalled successfully!\n";
        echo "All plugin routes and services have been removed.\n";
    }
}