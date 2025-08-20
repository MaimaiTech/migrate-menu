<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 - Thank you very much for using MineAdmin.
 -
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<i18n lang="yaml" src="../locales/zh_CN[简体中文].yaml"></i18n>
<i18n lang="yaml" src="../locales/en[English].yaml"></i18n>

<script setup lang="ts">
import { useMessage } from '@/hooks/useMessage.ts'
import { useLocalTrans } from '@/hooks/useLocalTrans.ts'
import { getMenuList, exportMenusToJson, type MenuVo } from '../api/menu.ts'
import MenuImportDialog from '../components/menu-import.vue'
import MenuTreeNode from '../components/menu-tree-node.vue'
import getOnlyWorkAreaHeight from '@/utils/getOnlyWorkAreaHeight.ts'

defineOptions({ name: 'MenuMigrateRoute' })

const t = useLocalTrans()
const msg = useMessage()

const menuList = ref<MenuVo[]>([])
const loading = ref<boolean>(false)
const exportLoading = ref<boolean>(false)
const showImportDialog = ref<boolean>(false)
const expandedKeys = ref<number[]>([])

// 获取菜单列表
async function loadMenuList() {
  loading.value = true
  try {
    const { data } = await getMenuList()
    menuList.value = data
    // 默认展开所有一级菜单
    expandedKeys.value = data.filter(item => !item.parent_id).map(item => item.id as number)
  } catch (error: any) {
    console.error('Load menu list error:', error)
    msg.error(error.message || t('menuMigrate.loading'))
  } finally {
    loading.value = false
  }
}

// 导出菜单配置
async function handleExport() {
  if (menuList.value.length === 0) {
    msg.warning(t('menuMigrate.noMenusFound'))
    return
  }

  exportLoading.value = true
  try {
    exportMenusToJson(menuList.value)
    msg.success(t('menuMigrate.exportSuccess'))
  } catch (error: any) {
    console.error('Export error:', error)
    msg.error(error.message || t('menuMigrate.exportFailed'))
  } finally {
    exportLoading.value = false
  }
}

// 显示导入对话框
function showImport() {
  showImportDialog.value = true
}

// 导入成功回调
async function handleImportSuccess() {
  await loadMenuList()
}


// 计算菜单统计信息
const menuStats = computed(() => {
  const flatten = (menus: MenuVo[]): MenuVo[] => {
    let result: MenuVo[] = []
    menus.forEach(menu => {
      result.push(menu)
      if (menu.children && menu.children.length > 0) {
        result = result.concat(flatten(menu.children))
      }
    })
    return result
  }

  const allMenus = flatten(menuList.value)
  const enabledMenus = allMenus.filter(menu => menu.status === 1)
  const menuTypes = allMenus.reduce((acc, menu) => {
    const type = menu.meta?.type || 'unknown'
    acc[type] = (acc[type] || 0) + 1
    return acc
  }, {} as Record<string, number>)

  return {
    total: allMenus.length,
    enabled: enabledMenus.length,
    disabled: allMenus.length - enabledMenus.length,
    types: menuTypes
  }
})

onMounted(() => {
  loadMenuList()
})
</script>

<template>
  <div
    v-loading="loading"
    class="mine-card h-full flex flex-col"
    :style="{ height: `${getOnlyWorkAreaHeight() + 12}px` }"
  >
    <!-- 页面头部 -->
    <div class="flex-shrink-0 border-b border-gray-200 pb-4 mb-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ t('menuMigrate.title') }}</h1>
          <p class="mt-1 text-gray-600">{{ t('menuMigrate.description') }}</p>
        </div>
        <div class="flex items-center space-x-3">
          <el-button
            :loading="loading"
            @click="loadMenuList"
          >
            <template #icon>
              <ma-svg-icon name="solar:refresh-bold" />
            </template>
            {{ t('menuMigrate.refreshData') }}
          </el-button>
          <el-button
            type="success"
            :loading="exportLoading"
            :disabled="menuList.length === 0"
            @click="handleExport"
          >
            <template #icon>
              <ma-svg-icon name="solar:download-square-bold" />
            </template>
            {{ t('menuMigrate.export') }}
          </el-button>
          <el-button
            type="primary"
            @click="showImport"
          >
            <template #icon>
              <ma-svg-icon name="solar:upload-square-broken" />
            </template>
            {{ t('menuMigrate.import') }}
          </el-button>
        </div>
      </div>
    </div>

    <!-- 统计信息卡片 -->
    <div class="flex-shrink-0 grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-blue-600 text-sm font-medium">{{ t('menuMigrate.totalMenus') }}</p>
            <p class="text-blue-900 text-2xl font-bold">{{ menuStats.total }}</p>
          </div>
          <ma-svg-icon name="solar:menu-dots-bold-duotone" class="text-blue-500 text-2xl" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-green-600 text-sm font-medium">{{ t('menuMigrate.enabledMenus') }}</p>
            <p class="text-green-900 text-2xl font-bold">{{ menuStats.enabled }}</p>
          </div>
          <ma-svg-icon name="solar:check-circle-bold-duotone" class="text-green-500 text-2xl" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-red-50 to-red-100 p-4 rounded-lg border border-red-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-red-600 text-sm font-medium">{{ t('menuMigrate.disabledMenus') }}</p>
            <p class="text-red-900 text-2xl font-bold">{{ menuStats.disabled }}</p>
          </div>
          <ma-svg-icon name="solar:close-circle-bold-duotone" class="text-red-500 text-2xl" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-purple-600 text-sm font-medium">{{ t('menuMigrate.menuTypes') }}</p>
            <p class="text-purple-900 text-xl font-bold">
              <span v-for="(count, type) in menuStats.types" :key="type" class="mr-1">
                {{ type }}({{ count }})
              </span>
            </p>
          </div>
          <ma-svg-icon name="solar:widget-5-bold-duotone" class="text-purple-500 text-2xl" />
        </div>
      </div>
    </div>

    <!-- 菜单树 -->
    <div class="flex-1 bg-white rounded-lg border border-gray-200 p-4 overflow-hidden">
      <div class="h-full">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800">{{ t('menuMigrate.currentMenus') }}</h3>
          <div class="flex items-center space-x-2 text-sm text-gray-500">
            <span>共 {{ menuStats.total }} 个菜单项</span>
          </div>
        </div>

        <div class="h-[calc(100%-3rem)] overflow-y-auto">
          <el-tree
            v-if="menuList.length > 0"
            :data="menuList"
            :props="{
              children: 'children',
              label: 'name',
            }"
            :default-expanded-keys="expandedKeys"
            node-key="id"
            class="menu-tree"
          >
            <template #default="{ data }">
              <MenuTreeNode :menu="data" :t="t" />
            </template>
          </el-tree>

          <el-empty
            v-else
            :description="t('menuMigrate.noMenusFound')"
            class="mt-10"
          />
        </div>
      </div>
    </div>

    <!-- 菜单导入弹窗 -->
    <MenuImportDialog
      v-model:visible="showImportDialog"
      @success="handleImportSuccess"
      @close="showImportDialog = false"
    />
  </div>
</template>

<style scoped lang="scss">
.menu-tree {
  .el-tree-node__content {
    height: auto;
    padding: 8px 0;

    &:hover {
      background-color: #f5f7fa;
    }
  }

  .el-tree-node__label {
    flex: 1;
  }
}
</style>
