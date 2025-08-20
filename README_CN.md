[CN](./README.md)
# 菜单迁移插件

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/maimaitech)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![MineAdmin](https://img.shields.io/badge/MineAdmin-Compatible-orange.svg)](https://mineadmin.com)

为 MineAdmin 提供全面菜单配置迁移功能的工具，支持菜单结构的无缝导入导出。

## ✨ 功能特性

- **📋 菜单列表显示** - 带状态指示器的当前系统菜单交互式树形视图
- **📤 菜单导出** - 将完整菜单配置导出为带元数据的 JSON 格式
- **📥 菜单导入** - 从 JSON 文件导入菜单配置并进行验证
- **🔄 智能合并** - 现有菜单的可配置覆盖/合并选项
- **📊 实时统计** - 显示菜单数量、类型和状态指标的仪表板
- **🌍 多语言支持** - 支持中文（简体/繁体）和英文翻译
- **🔒 权限控制** - 通过 `system:menu:migrate` 权限安全访问
- **⚡ 事务安全** - 所有操作都包装在数据库事务中

## 🚀 安装方式

### 方法一：MineAdmin 插件市场（推荐）

1. 访问您的 MineAdmin 管理面板
2. 导航到**插件市场**
3. 搜索 "菜单迁移" 或 "maimaitech/migrate-menu"
4. 点击**安装**并按提示操作

### 方法二：手动安装

1. 将此插件下载或克隆到您的 MineAdmin 插件目录：
   ```bash
   cd /path/to/your/mineadmin/api/plugin/maimaitech/
   git clone https://github.com/maimaitech/migrate-menu.git
   cd ../../../
   php bin/hyperf.php mine-extension:install maimaitech/migrate-menu --yes
   ```

2. 通过 MineAdmin 管理面板或配置启用插件

## 📖 使用指南

### 访问插件

1. 登录您的 MineAdmin 管理面板
2. 导航到侧边栏的**菜单迁移**
3. 插件仪表板将显示当前菜单统计和选项

### 导出菜单配置

#### 前端导出（推荐）
1. 点击**导出配置**按钮
2. 浏览器将自动下载包含菜单结构的 JSON 文件
3. 文件格式：`menu_config_YYYYMMDD_HHMMSS.json`

#### 后端导出（API）
```bash
curl -X GET "https://your-domain.com/admin/menu-migrate/export" \
     -H "Authorization: Bearer YOUR_TOKEN"
```

### 导入菜单配置

1. 点击**导入配置**按钮
2. 拖放或选择 JSON 配置文件
3. 选择导入选项：
   - **覆盖现有菜单**：替换同名/同路径的菜单
   - **仅合并**：跳过现有菜单，只添加新菜单
4. 查看显示成功/失败统计的导入摘要

### 导出文件格式

```json
{
  "version": "1.0.0",
  "export_time": "2024-01-20 12:00:00",
  "export_by": "MineAdmin Menu Migration Plugin",
  "total_count": 25,
  "menus": [
    {
      "name": "dashboard",
      "path": "/dashboard",
      "component": "Dashboard/index.vue",
      "redirect": "",
      "status": 1,
      "sort": 0,
      "remark": "仪表板菜单",
      "meta": {
        "title": "仪表板",
        "icon": "dashboard",
        "type": "M"
      },
      "children": [...]
    }
  ]
}
```

## 🛠️ API 文档

### 接口端点

| 方法 | 端点 | 描述 | 权限 |
|------|------|------|------|
| GET | `/admin/menu-migrate/list` | 获取用于显示的菜单列表 | `system:menu:migrate` |
| GET | `/admin/menu-migrate/export` | 导出菜单配置 | `system:menu:migrate` |
| POST | `/admin/menu-migrate/import` | 导入菜单配置 | `system:menu:migrate` |

### 导入请求格式

```bash
curl -X POST "https://your-domain.com/admin/menu-migrate/import" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: multipart/form-data" \
     -F "file=@menu_config.json" \
     -F "overwrite=true"
```

### 响应格式

#### 成功响应
```json
{
  "code": 200,
  "message": "菜单导入完成",
  "data": {
    "total": 25,
    "success": 23,
    "failed": 1,
    "skipped": 1,
    "errors": [
      {
        "menu": "invalid-menu",
        "error": "缺少必需字段：name"
      }
    ]
  }
}
```

#### 错误响应
```json
{
  "code": 500,
  "message": "仅支持JSON格式文件",
  "data": null
}
```

## 🏗️ 架构设计

### 后端结构
```
api/plugin/maimaitech/migrate-menu/
├── src/
│   ├── Controller/
│   │   └── MenuMigrateController.php    # API 端点
│   ├── Service/
│   │   └── MenuMigrateService.php       # 业务逻辑
│   ├── Request/
│   │   └── MenuImportRequest.php        # 输入验证
│   ├── ConfigProvider.php               # 服务注册
│   ├── InstallScript.php                # 安装处理器
│   └── UninstallScript.php              # 清理处理器
├── Database/
│   ├── Migrations/                      # 数据库迁移
│   └── Seeder/                          # 测试数据种子
├── mine.json                            # 插件配置
└── README.md                            # 说明文档
```

### 前端结构
```
admin-ui/src/plugins/maimaitech/menu-migrate/
├── views/
│   └── index.vue                        # 主插件界面
├── components/
│   ├── menu-import.vue                  # 导入对话框组件
│   └── menu-tree-node.vue               # 树节点组件
├── api/
│   └── menu.ts                          # API 服务函数
├── locales/                             # 翻译文件
│   ├── zh_CN[简体中文].yaml
│   ├── en[English].yaml
│   └── zh_TW[繁體中文].yaml
└── index.ts                             # 插件注册
```

## 🔧 配置说明

### 插件配置（`mine.json`）
```json
{
  "name": "maimaitech/migrate-menu",
  "version": "1.0.0",
  "type": "mixed",
  "description": "菜单配置迁移工具",
  "author": [{
    "name": "MaimaiTech",
    "email": "support@maimaitech.com"
  }]
}
```

### 权限要求
- `system:menu:migrate` - 所有插件功能所需权限

### 环境变量
无需额外环境变量。插件使用现有的 MineAdmin 数据库和身份验证系统。

### 代码规范
- PHP 遵循 PSR-12 编码标准
- TypeScript/Vue 组件使用 ESLint 和 Prettier
- 为所有函数和变量添加类型注解

### 贡献指南
1. Fork 仓库
2. 创建功能分支
3. 进行更改并添加适当测试
4. 提交拉取请求

## 🐛 故障排除

### 常见问题

#### 导入失败，提示"JSON格式无效"
- 确保上传的文件是有效的 JSON
- 检查文件编码是否为 UTF-8
- 验证文件大小是否小于 10MB

#### 导入后菜单不显示
- 检查菜单状态是否设置为 1（启用）
- 验证父子关系是否正确
- 确保用户有查看菜单的权限

#### 插件无法加载
- 验证插件在 MineAdmin 设置中已启用
- 检查 PHP 错误日志中的命名空间问题
- 确保所有依赖已安装

### 调试模式
在 MineAdmin 配置中启用调试日志：
```php
// config/autoload/logger.php
'default' => [
    'level' => \Monolog\Level::Debug,
]
```

## 📄 许可证

本项目采用 MIT 许可证。

## 🙋 支持

- **文档**：[https://doc.mineadmin.com](https://doc.mineadmin.com)
- **问题反馈**：GitHub Issues
- **邮箱**：2771717608@qq.com

## 🏆 更新日志

### v1.0.0 (2024-01-20)
- ✨ 初始发布
- 📋 带树形视图的菜单列表显示
- 📤 JSON 导出功能
- 📥 JSON 导入和验证
- 🌍 多语言支持（中文/英文/繁体中文）
- 🔒 基于权限的访问控制
- 📊 实时统计仪表板

---

由 [MaimaiTech](https://github.com/maimaitech) 为 [MineAdmin](https://mineadmin.com) 社区倾情打造 ❤️