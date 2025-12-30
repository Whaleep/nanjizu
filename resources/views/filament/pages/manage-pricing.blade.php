<x-filament-panels::page>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- 左側區塊：下載與範本 -->
        <x-filament::section>
            <x-slot name="heading">
                1. 取得報價單 Excel
            </x-slot>

            <div class="space-y-6">
                <!-- 方法 A -->
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <h3 class="font-bold text-gray-700 dark:text-gray-200 mb-2">A. 產生新報價單</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">系統自動產生包含所有資料的標準格式。</p>
                    {{ $this->downloadAction }}
                </div>

                <!-- 方法 B -->
                <div class="p-4 rounded-lg border border-orange-200 dark:border-orange-900 bg-orange-50 dark:bg-orange-900/20">
                    <h3 class="font-bold text-gray-700 dark:text-gray-200 mb-2">B. 同步我的範本 (推薦)</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        上傳您慣用的 Excel (含多個分頁)，系統會把最新價格填進去後還給您。<br>
                    </p>
                    {{ $this->syncTemplateAction }}
                </div>
            </div>
        </x-filament::section>

        <!-- 右側區塊：上傳更新 -->
        <x-filament::section>
            <x-slot name="heading">
                2. 上傳更新系統
            </x-slot>
            <x-slot name="description">
                修改完價格或排序後，請在此上傳。
            </x-slot>

            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit">
                        開始匯入更新
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

    </div>

</x-filament-panels::page>
