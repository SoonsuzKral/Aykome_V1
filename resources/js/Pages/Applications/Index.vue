<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    applications: Object,
});
</script>

<template>
    <Head title="Başvurular" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Kazı Başvuruları
                </h2>
                <Link :href="route('admin.applications.create')">
                    <PrimaryButton>Yeni Başvuru</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">No</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">
                                        Kurum
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">
                                        Başvuran
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">
                                        Durum
                                    </th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-600">
                                        İşlem
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="row in applications.data"
                                    :key="row.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 font-mono text-xs">
                                        {{ row.application_no }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ row.institution?.name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ row.applicant_first_name }}
                                        {{ row.applicant_last_name }}
                                    </td>
                                    <td class="px-4 py-3 capitalize">
                                        {{ row.status }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            :href="route('admin.applications.show', row.id)"
                                            class="text-indigo-600 hover:underline"
                                        >
                                            Detay
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!applications.data.length">
                                    <td
                                        colspan="5"
                                        class="px-4 py-8 text-center text-gray-500"
                                    >
                                        Henüz başvuru yok.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        v-if="applications.prev_page_url || applications.next_page_url"
                        class="flex justify-between border-t border-gray-100 px-4 py-3 text-sm"
                    >
                        <Link
                            v-if="applications.prev_page_url"
                            :href="applications.prev_page_url"
                            class="text-indigo-600 hover:underline"
                        >
                            Önceki
                        </Link>
                        <span v-else class="text-gray-400">Önceki</span>
                        <Link
                            v-if="applications.next_page_url"
                            :href="applications.next_page_url"
                            class="text-indigo-600 hover:underline"
                        >
                            Sonraki
                        </Link>
                        <span v-else class="text-gray-400">Sonraki</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
