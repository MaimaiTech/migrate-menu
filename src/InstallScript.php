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

class InstallScript
{
    public function __invoke(): void
    {
        try {
            Db::beginTransaction();

            $menuRepository = \Hyperf\Support\make(MenuRepository::class);

            // 查找或创建系统管理菜单作为父级
            $systemMenu = $menuRepository->getQuery()
                ->where('name', 'system')
                ->where('parent_id', 0)
                ->first();

            if (! $systemMenu) {
                // 如果系统菜单不存在，创建一个
                $systemMenu = $menuRepository->create([
                    'parent_id' => 0,
                    'name' => 'system',
                    'path' => '/system',
                    'component' => 'LAYOUT',
                    'redirect' => '',
                    'status' => 1,
                    'sort' => 0,
                    'meta' => [
                        'title' => '系统管理',
                        'i18n' => 'menus.system',
                        'icon' => 'solar:settings-bold-duotone',
                        'type' => 'M',
                        'hidden' => false,
                        'breadcrumbEnable' => true,
                        'copyright' => false,
                        'cache' => true,
                    ],
                ]);
            }

            // 检查菜单迁移工具是否已存在
            $existingMenu = $menuRepository->getQuery()
                ->where('name', 'system:menu:migrate')
                ->first();

            if (! $existingMenu) {
                // 创建菜单迁移工具菜单项
                $menuMigrateMenu = $menuRepository->create([
                    'parent_id' => $systemMenu->id,
                    'name' => 'system:menu:migrate',
                    'path' => '/system/menu-migrate',
                    'component' => 'maimaitech/migrate-menu/views/index',
                    'redirect' => '',
                    'status' => 1,
                    'sort' => 100,
                    'meta' => [
                        'title' => '菜单迁移工具',
                        'i18n' => 'menu.menuMigrate',
                        'icon' => 'solar:import-bold-duotone',
                        'type' => 'M',
                        'hidden' => false,
                        'breadcrumbEnable' => true,
                        'copyright' => false,
                        'cache' => true,
                        'componentPath' => 'plugins/',
                        'componentSuffix' => '.vue',
                    ],
                ]);

                // 创建按钮权限
                $buttons = [
                    [
                        'code' => 'system:menu:migrate:list',
                        'title' => '查看菜单列表',
                        'i18n' => 'menu.menuMigrate.list',
                    ],
                    [
                        'code' => 'system:menu:migrate:export',
                        'title' => '导出菜单',
                        'i18n' => 'menu.menuMigrate.export',
                    ],
                    [
                        'code' => 'system:menu:migrate:import',
                        'title' => '导入菜单',
                        'i18n' => 'menu.menuMigrate.import',
                    ],
                ];

                foreach ($buttons as $button) {
                    $menuRepository->create([
                        'parent_id' => $menuMigrateMenu->id,
                        'name' => $button['code'],
                        'path' => '',
                        'component' => '',
                        'redirect' => '',
                        'status' => 1,
                        'sort' => 0,
                        'meta' => [
                            'title' => $button['title'],
                            'i18n' => $button['i18n'],
                            'type' => 'B',
                        ],
                    ]);
                }

                echo "Menu Migration Plugin installed successfully!\n";
                echo "✓ Created menu: 菜单迁移工具 (system:menu:migrate)\n";
                echo "✓ Created permissions:\n";
                foreach ($buttons as $button) {
                    echo "  - {$button['code']}: {$button['title']}\n";
                }
            } else {
                echo "Menu Migration Plugin already installed.\n";
            }

            Db::commit();

            echo "\nAPI Endpoints available:\n";
            echo "- GET /admin/menu-migrate/list - Get menu list\n";
            echo "- GET /admin/menu-migrate/export - Export menu configuration\n";
            echo "- POST /admin/menu-migrate/import - Import menu configuration\n";
        } catch (\Exception $e) {
            Db::rollBack();
            throw new \RuntimeException('Failed to install Menu Migration Plugin: ' . $e->getMessage());
        }
    }
}
