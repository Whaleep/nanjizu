<script setup>
import { ref } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

defineProps({ data: Object });
const isOpen = ref(false);

function closeModal() { isOpen.value = false; }
function openModal() { isOpen.value = true; }
</script>

<template>
    <div class="container mx-auto px-4 py-6 text-center">
        <button @click="openModal"
                class="px-6 py-2 border-2 border-blue-600 text-blue-600 rounded-full font-bold hover:bg-blue-600 hover:text-white transition">
            {{ data.btn_text }}
        </button>

        <TransitionRoot appear :show="isOpen" as="template">
            <Dialog as="div" @close="closeModal" class="relative z-50">
                <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-black/50" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-xl font-bold leading-6 text-gray-900 mb-4 border-b pb-2">
                                    {{ data.modal_title }}
                                </DialogTitle>
                                <div class="mt-2">
                                    <div class="prose max-w-none text-gray-600" v-html="data.modal_content"></div>
                                </div>

                                <div class="mt-6 text-center">
                                    <button type="button" class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none" @click="closeModal">
                                        關閉
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
