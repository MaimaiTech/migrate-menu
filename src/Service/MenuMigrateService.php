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

namespace Plugin\MaimaiTech\MigrateMenu\Service;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Model\Permission\Meta;
use App\Repository\Permission\MenuRepository;
use Hyperf\Codec\Json;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Db;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Response;
use Psr\Http\Message\ResponseInterface;

final class MenuMigrateService
{
    public function __construct(
        private readonly MenuRepository $repository
    ) {}

    /**
     * 获取菜单列表 (用于前端展示).
     */
    public function getMenuList(): Collection
    {
        return $this->repository->allTree();
    }

    /**
     * 导出菜单为JSON格式.
     */
    public function exportMenu(array $params = []): ResponseInterface
    {
        // 获取菜单树结构
        $menus = $this->repository->allTree();

        // 转换菜单数据为导出格式
        $exportData = [
            'version' => '1.0.0',
            'export_time' => date('Y-m-d H:i:s'),
            'export_by' => 'MineAdmin Menu Migration Plugin',
            'total_count' => $this->countMenusRecursive($menus->toArray()),
            'menus' => $this->formatMenusForExport($menus->toArray()),
        ];

        // 生成文件名
        $filename = 'menus_' . date('Ymd_His') . '.json';

        // 创建响应
        $response = new Response();
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withBody(new SwooleStream(Json::encode($exportData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE)));
    }

    /**
     * 从JSON文件导入菜单.
     */
    public function importMenu(UploadedFile $file, bool $overwrite = false, int $userId = 0): array
    {
        // 验证文件类型
        if ($file->getExtension() !== 'json') {
            throw new BusinessException(ResultCode::FAIL, '仅支持JSON格式文件');
        }

        // 读取文件内容
        $content = $file->getStream()->getContents();
        if (empty($content)) {
            throw new BusinessException(ResultCode::FAIL, '文件内容为空');
        }

        // 解析JSON
        try {
            $data = Json::decode($content, true);
        } catch (\Throwable $e) {
            throw new BusinessException(ResultCode::FAIL, 'JSON格式无效: ' . $e->getMessage());
        }

        // 验证数据格式
        $this->validateImportData($data);

        $result = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // 开始事务
        return Db::transaction(function () use ($data, $overwrite, $userId, &$result) {
            $this->importMenusRecursive($data['menus'], 0, $overwrite, $userId, $result);
            return $result;
        });
    }

    /**
     * 统计菜单总数（递归）.
     */
    private function countMenusRecursive(array $menus): int
    {
        $count = \count($menus);
        foreach ($menus as $menu) {
            if (! empty($menu['children'])) {
                $count += $this->countMenusRecursive($menu['children']);
            }
        }
        return $count;
    }

    /**
     * 格式化菜单数据用于导出.
     */
    private function formatMenusForExport(array $menus): array
    {
        $result = [];

        foreach ($menus as $menu) {
            $formattedMenu = [
                'name' => $menu['name'],
                'path' => $menu['path'],
                'component' => $menu['component'],
                'redirect' => $menu['redirect'],
                'status' => $menu['status'],
                'sort' => $menu['sort'],
                'remark' => $menu['remark'],
                'meta' => \is_array($menu['meta']) ? $menu['meta'] : [],
            ];

            // 如果有子菜单，递归处理
            if (! empty($menu['children'])) {
                $formattedMenu['children'] = $this->formatMenusForExport($menu['children']);
            }

            $result[] = $formattedMenu;
        }

        return $result;
    }

    /**
     * 递归导入菜单.
     */
    private function importMenusRecursive(array $menus, int $parentId, bool $overwrite, int $userId, array &$result): void
    {
        foreach ($menus as $menuData) {
            ++$result['total'];

            try {
                // 检查菜单是否已存在 (根据name和parent_id)
                $existingMenu = $this->repository->getQuery()
                    ->where('name', $menuData['name'])
                    ->where('parent_id', $parentId)
                    ->first();

                if ($existingMenu) {
                    if (! $overwrite) {
                        ++$result['skipped'];
                        // 即使跳过当前菜单，也要处理子菜单
                        if (! empty($menuData['children'])) {
                            $this->importMenusRecursive($menuData['children'], $existingMenu->id, $overwrite, $userId, $result);
                        }
                        continue;
                    }

                    // 更新现有菜单
                    $updateData = $this->prepareMenuData($menuData, $parentId, $userId, false);
                    $this->repository->updateById($existingMenu->id, $updateData);
                    $menuId = $existingMenu->id;
                    ++$result['success'];
                } else {
                    // 创建新菜单
                    $createData = $this->prepareMenuData($menuData, $parentId, $userId, true);
                    $menu = $this->repository->create($createData);
                    $menuId = $menu->id;
                    ++$result['success'];
                }

                // 处理子菜单
                if (! empty($menuData['children'])) {
                    $this->importMenusRecursive($menuData['children'], $menuId, $overwrite, $userId, $result);
                }
            } catch (\Throwable $e) {
                ++$result['failed'];
                $result['errors'][] = [
                    'menu' => $menuData['name'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * 准备菜单数据.
     */
    private function prepareMenuData(array $data, int $parentId, int $userId, bool $isCreate): array
    {
        $menuData = [
            'parent_id' => $parentId,
            'name' => $data['name'],
            'path' => $data['path'] ?? '',
            'component' => $data['component'] ?? '',
            'redirect' => $data['redirect'] ?? '',
            'status' => $data['status'] ?? 1,
            'sort' => $data['sort'] ?? 0,
            'remark' => $data['remark'] ?? '',
            'meta' => new Meta($data['meta'] ?? []),
        ];

        if ($isCreate) {
            $menuData['created_by'] = $userId;
        } else {
            $menuData['updated_by'] = $userId;
        }

        return $menuData;
    }

    /**
     * 验证导入数据格式.
     */
    private function validateImportData(array $data): void
    {
        if (! isset($data['menus']) || ! \is_array($data['menus'])) {
            throw new BusinessException(ResultCode::FAIL, '数据格式错误：缺少menus字段或格式不正确');
        }

        $this->validateMenuArrayStructure($data['menus']);
    }

    /**
     * 递归验证菜单数组结构.
     */
    private function validateMenuArrayStructure(array $menus, string $path = ''): void
    {
        foreach ($menus as $index => $menu) {
            $currentPath = $path ? "{$path}[{$index}]" : "menus[{$index}]";

            if (empty($menu['name'])) {
                throw new BusinessException(ResultCode::FAIL, "{$currentPath}: 菜单缺少name字段");
            }

            if (isset($menu['meta']) && ! \is_array($menu['meta'])) {
                throw new BusinessException(ResultCode::FAIL, "{$currentPath}: meta字段格式不正确");
            }

            // 递归验证子菜单
            if (isset($menu['children']) && \is_array($menu['children'])) {
                $this->validateMenuArrayStructure($menu['children'], "{$currentPath}.children");
            }
        }
    }
}
