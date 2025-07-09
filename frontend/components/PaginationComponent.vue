<template>
  <nav
    class="flex items-center justify-between px-4 py-3 bg-white border border-gray-200 rounded-lg shadow-sm"
  >
    <!-- モバイル表示（簡易版） -->
    <div class="flex justify-between flex-1 sm:hidden">
      <button
        @click="goToPrevious"
        :disabled="currentPage === 1"
        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        前へ
      </button>
      <span
        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700"
      >
        {{ currentPage }} / {{ totalPages }}
      </span>
      <button
        @click="goToNext"
        :disabled="currentPage === totalPages"
        class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        次へ
      </button>
    </div>

    <!-- デスクトップ表示（詳細版） -->
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700">
          <span class="font-medium">{{ totalItems }}</span
          >件中 <span class="font-medium">{{ fromItem }}</span
          >〜<span class="font-medium">{{ toItem }}</span
          >件を表示
        </p>
      </div>
      <div>
        <nav
          class="isolate inline-flex -space-x-px rounded-md shadow-sm"
          aria-label="Pagination"
        >
          <!-- 前のページ -->
          <button
            @click="goToPrevious"
            :disabled="currentPage === 1"
            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="sr-only">前のページ</span>
            <svg
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <!-- ページ番号 -->
          <template v-for="page in visiblePages" :key="page">
            <span
              v-if="page === '...'"
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0"
            >
              ...
            </span>
            <button
              v-else
              @click="goToPage(page as number)"
              :class="[
                'relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                currentPage === page
                  ? 'z-10 bg-blue-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                  : 'text-gray-900',
              ]"
            >
              {{ page }}
            </button>
          </template>

          <!-- 次のページ -->
          <button
            @click="goToNext"
            :disabled="currentPage === totalPages"
            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="sr-only">次のページ</span>
            <svg
              class="h-5 w-5"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </nav>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
interface Props {
  currentPage: number
  totalPages: number
  totalItems: number
  perPage: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  pageChange: [page: number]
}>()

// 表示範囲の計算
const fromItem = computed(() => (props.currentPage - 1) * props.perPage + 1)
const toItem = computed(() =>
  Math.min(props.currentPage * props.perPage, props.totalItems)
)

// 表示するページ番号の計算
const visiblePages = computed(() => {
  const pages: (number | string)[] = []
  const maxVisible = 7 // 表示する最大ページ数

  if (props.totalPages <= maxVisible) {
    // 全ページを表示
    for (let i = 1; i <= props.totalPages; i++) {
      pages.push(i)
    }
  } else {
    // 一部のページを表示
    if (props.currentPage <= 4) {
      // 最初の方のページ
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(props.totalPages)
    } else if (props.currentPage >= props.totalPages - 3) {
      // 最後の方のページ
      pages.push(1)
      pages.push('...')
      for (let i = props.totalPages - 4; i <= props.totalPages; i++) {
        pages.push(i)
      }
    } else {
      // 中間のページ
      pages.push(1)
      pages.push('...')
      for (let i = props.currentPage - 1; i <= props.currentPage + 1; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(props.totalPages)
    }
  }

  return pages
})

// ページ移動
const goToPage = (page: number) => {
  if (page !== props.currentPage && page >= 1 && page <= props.totalPages) {
    emit('pageChange', page)
  }
}

const goToPrevious = () => {
  if (props.currentPage > 1) {
    goToPage(props.currentPage - 1)
  }
}

const goToNext = () => {
  if (props.currentPage < props.totalPages) {
    goToPage(props.currentPage + 1)
  }
}
</script>
