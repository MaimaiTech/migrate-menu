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
import type { UploadFile } from 'element-plus'
import { Upload } from '@element-plus/icons-vue'
import { useMessage } from '@/hooks/useMessage.ts'
import { type MenuImportResult, importMenu } from '../api/menu.ts'
import { ResultCode } from '@/utils/ResultCode.ts'
import { useLocalTrans } from '@/hooks/useLocalTrans.ts'
import { useTrans } from '@/hooks/auto-imports/useTrans.ts'

defineOptions({ name: 'MenuImportDialog' })

const emit = defineEmits<{
  (event: 'success'): void
  (event: 'close'): void
}>()

const visible = defineModel<boolean>('visible', { default: false })

const t = useLocalTrans()
const msg = useMessage()

const loading = ref(false)
const importForm = reactive({
  file: null as File | null,
  overwrite: false,
})
const fileList = ref<UploadFile[]>([])
const importResult = ref<MenuImportResult | null>(null)
const showResult = ref(false)

// 文件上传前验证
function beforeUpload(file: File): boolean {
  const isJSON = file.type === 'application/json' || file.name.endsWith('.json')
  if (!isJSON) {
    msg.error(t('menuMigrate.fileFormatTip'))
    return false
  }

  const isLt10M = file.size / 1024 / 1024 < 10
  if (!isLt10M) {
    msg.error(t('menuMigrate.fileFormatTip'))
    return false
  }

  return true
}

// 文件选择
function handleFileChange(file: UploadFile): boolean {
  importForm.file = file.raw as File
  fileList.value = [file]
  return false // 阻止自动上传
}

// 移除文件
function handleRemove(): void {
  importForm.file = null
  fileList.value = []
  showResult.value = false
  importResult.value = null
}

// 执行导入
async function handleImport(): Promise<void> {
  if (!importForm.file) {
    msg.error(t('menuMigrate.selectFile'))
    return
  }

  loading.value = true
  showResult.value = false

  try {
    const response = await importMenu(importForm.file, importForm.overwrite)

    if (response.code === ResultCode.SUCCESS) {
      importResult.value = response.data
      showResult.value = true
      msg.success(t('menuMigrate.importSuccess'))
      emit('success')
    } else {
      msg.error(response.message || t('menuMigrate.importFailed'))
    }
  } catch (error: any) {
    console.error('Import error:', error)
    msg.error(error.message || t('menuMigrate.importFailed'))
  } finally {
    loading.value = false
  }
}

// 重置表单
function resetForm(): void {
  importForm.file = null
  importForm.overwrite = false
  fileList.value = []
  importResult.value = null
  showResult.value = false
}

// 关闭弹窗
function handleClose(): void {
  resetForm()
  emit('close')
}

// 监听弹窗关闭
watch(visible, (val) => {
  if (!val) {
    resetForm()
  }
})
</script>

<template>
  <el-dialog
    v-model="visible"
    :title="t('menuMigrate.importTitle')"
    width="600px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <div v-loading="loading" :element-loading-text="t('menuMigrate.importProgress')">
      <!-- 导入表单 -->
      <div v-if="!showResult">
        <p class="mb-4 text-gray-600">
          {{ t('menuMigrate.importDescription') }}
        </p>

        <!-- 文件上传 -->
        <div class="mb-6">
          <el-upload
            v-model:file-list="fileList"
            :auto-upload="false"
            :before-upload="beforeUpload"
            :on-change="handleFileChange"
            :on-remove="handleRemove"
            :limit="1"
            accept=".json"
            drag
          >
            <div class="p-6 text-center">
              <el-icon class="el-icon--upload mb-2" size="40">
                <Upload />
              </el-icon>
              <div class="el-upload__text">
                将JSON文件拖拽到此处，或
                <em>点击上传</em>
              </div>
              <div class="el-upload__tip mt-2 text-sm text-gray-500">
                {{ t('menuMigrate.fileFormatTip') }}
              </div>
            </div>
          </el-upload>
        </div>

        <!-- 导入选项 -->
        <div class="mb-6">
          <el-checkbox v-model="importForm.overwrite">
            {{ t('menuMigrate.overwriteOption') }}
          </el-checkbox>
          <div class="mt-1 text-sm text-gray-500">
            {{ t('menuMigrate.overwriteOptionTip') }}
          </div>
        </div>
      </div>

      <!-- 导入结果 -->
      <div v-if="showResult && importResult" class="space-y-4">
        <div class="text-center">
          <el-icon size="48" color="#67C23A" class="mb-2">
            <ma-svg-icon name="solar:check-circle-bold" />
          </el-icon>
          <h3 class="text-lg font-medium">{{ t('menuMigrate.importResult') }}</h3>
        </div>

        <!-- 统计信息 -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-blue-50 p-3 rounded">
            <div class="text-2xl font-bold text-blue-600">{{ importResult.total }}</div>
            <div class="text-sm text-blue-600">{{ t('menuMigrate.totalCount') }}</div>
          </div>
          <div class="bg-green-50 p-3 rounded">
            <div class="text-2xl font-bold text-green-600">{{ importResult.success }}</div>
            <div class="text-sm text-green-600">{{ t('menuMigrate.successCount') }}</div>
          </div>
          <div class="bg-red-50 p-3 rounded">
            <div class="text-2xl font-bold text-red-600">{{ importResult.failed }}</div>
            <div class="text-sm text-red-600">{{ t('menuMigrate.failedCount') }}</div>
          </div>
          <div class="bg-yellow-50 p-3 rounded">
            <div class="text-2xl font-bold text-yellow-600">{{ importResult.skipped }}</div>
            <div class="text-sm text-yellow-600">{{ t('menuMigrate.skippedCount') }}</div>
          </div>
        </div>

        <!-- 错误详情 -->
        <div v-if="importResult.errors && importResult.errors.length > 0" class="mt-4">
          <h4 class="mb-2 text-base font-medium">{{ t('menuMigrate.errorDetails') }}</h4>
          <div class="max-h-32 overflow-y-auto border rounded p-2 bg-red-50">
            <div
              v-for="error in importResult.errors"
              :key="error.menu"
              class="mb-1 text-sm text-red-600"
            >
              <strong>{{ error.menu }}:</strong> {{ error.error }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部按钮 -->
    <template #footer>
      <div class="flex justify-end space-x-2">
        <el-button @click="handleClose">
          {{ showResult ? useTrans().globalTrans('crud.ok') : useTrans().globalTrans('crud.cancel') }}
        </el-button>
        <el-button
          v-if="!showResult"
          type="primary"
          :loading="loading"
          :disabled="!importForm.file"
          @click="handleImport"
        >
          {{ t('menuMigrate.import') }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<style scoped lang="scss">
.el-upload-dragger {
  border: 2px dashed var(--el-border-color-hover);
}
</style>