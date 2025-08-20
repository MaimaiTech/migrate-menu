<!--
 - MineAdmin is committed to providing solutions for quickly building web applications
 - Please view the LICENSE file that was distributed with this source code,
 - For the full copyright and license information.
 - Thank you very much for using MineAdmin.
 -
 - @Author X.Mo<root@imoi.cn>
 - @Link   https://github.com/mineadmin
-->
<script setup lang="ts">
import type { MenuVo } from '../api/menu.ts'

interface Props {
  menu: MenuVo
  t: (key: string) => string
}

const props = defineProps<Props>()

const nodeData = computed(() => {
  const menu = props.menu
  const icon = menu.meta?.icon || 'solar:folder-outline'
  const title = menu.meta?.i18n ? props.t(menu.meta.i18n) : menu.meta?.title || menu.name
  const statusText = menu.status === 1 ? '启用' : '禁用'
  const statusType = menu.status === 1 ? 'success' : 'danger'
  
  return {
    icon,
    title,
    statusText,
    statusType,
    path: menu.path,
    type: menu.meta?.type
  }
})
</script>

<template>
  <div class="flex items-center justify-between w-full">
    <div class="flex items-center space-x-2">
      <ma-svg-icon :name="nodeData.icon" class="text-16px" />
      <span class="font-medium">{{ nodeData.title }}</span>
      <el-tag size="small" :type="nodeData.statusType as any">
        {{ nodeData.statusText }}
      </el-tag>
    </div>
    <div class="flex items-center space-x-1 text-sm text-gray-500">
      <span>{{ nodeData.path }}</span>
      <el-tag v-if="nodeData.type" size="small" effect="plain">
        {{ nodeData.type }}
      </el-tag>
    </div>
  </div>
</template>