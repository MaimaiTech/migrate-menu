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

use App\Repository\Permission\MenuRepository;
use Hyperf\DbConnection\Db;

class UninstallScript
{
    public function __invoke(): void
    {
        try {
            Db::beginTransaction();

            $menuRepository = \Hyperf\Support\make(MenuRepository::class);

            // 查找菜单迁移工具菜单
            $menuMigrateMenu = $menuRepository->getQuery()
                ->where('name', 'system:menu:migrate')
                ->first();

            if ($menuMigrateMenu) {
                // 删除所有子权限（按钮权限）
                $buttonPermissions = $menuRepository->getQuery()
                    ->where('parent_id', $menuMigrateMenu->id)
                    ->get();

                $deletedButtons = [];
                foreach ($buttonPermissions as $permission) {
                    $deletedButtons[] = $permission->name;
                    $permission->delete();
                }

                // 删除主菜单
                $menuMigrateMenu->delete();

                echo "Menu Migration Plugin uninstalled successfully!\n";
                echo "✓ Removed menu: 菜单迁移工具 (system:menu:migrate)\n";
                if (! empty($deletedButtons)) {
                    echo "✓ Removed permissions:\n";
                    foreach ($deletedButtons as $button) {
                        echo "  - {$button}\n";
                    }
                }
            } else {
                echo "Menu Migration Plugin menu not found, skipping menu cleanup.\n";
            }

            Db::commit();

            echo "\nAll plugin routes and services have been removed.\n";
        } catch (\Exception $e) {
            Db::rollBack();
            throw new \RuntimeException('Failed to uninstall Menu Migration Plugin: ' . $e->getMessage());
        }
    }
}
