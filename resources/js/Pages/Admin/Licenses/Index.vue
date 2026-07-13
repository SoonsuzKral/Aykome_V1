<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    licenses: Object,
});

const createForm = useForm({
    license_key: '',
    owner_name: '',
    valid_from: '',
    valid_until: '',
    is_active: true,
    user_limit: null,
});

function storeLicense() {
    createForm.post(route('admin.licenses.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}
</script>

<template>
    <Head title="Lisans Yönetimi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">
                    Lisanslar
                </h2>
                <Link
                    :href="route('dashboard')"
                    class="text-sm text-indigo-600 hover:underline"
                >
                    Panele dön
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-8 sm:px-6 lg:px-8">
                <form
                    class="space-y-4 rounded bg-white p-6 shadow sm:rounded-lg"
                    @submit.prevent="storeLicense"
                >
                    <h3 class="font-semibold text-gray-800">
                        Yeni lisans
                    </h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Anahtar" />
                            <input
                                v-model="createForm.license_key"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                            />
                            <InputError
                                class="mt-1"
                                :message="createForm.errors.license_key"
                            />
                        </div>
                        <div>
                            <InputLabel value="Kurum / Sahip" />
                            <input
                                v-model="createForm.owner_name"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                            />
                            <InputError
                                class="mt-1"
                                :message="createForm.errors.owner_name"
                            />
                        </div>
                        <div>
                            <InputLabel value="Geçerlilik başlangıç" />
                            <input
                                v-model="createForm.valid_from"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            />
                        </div>
                        <div>
                            <InputLabel value="Geçerlilik bitiş" />
                            <input
                                v-model="createForm.valid_until"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            />
                            <InputError
                                class="mt-1"
                                :message="createForm.errors.valid_until"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                id="is_active"
                                v-model="createForm.is_active"
                                type="checkbox"
                            >
                            <InputLabel for="is_active" value="Aktif" />
                        </div>
                    </div>
                    <PrimaryButton :disabled="createForm.processing">
                        Kaydet
                    </PrimaryButton>
                </form>

                <div class="overflow-hidden rounded bg-white shadow sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">
                                    Anahtar
                                </th>
                                <th class="px-4 py-2 text-left">
                                    Sahip
                                </th>
                                <th class="px-4 py-2 text-left">
                                    Bitiş
                                </th>
                                <th class="px-4 py-2 text-left">
                                    Aktif
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in licenses.data"
                                :key="row.id"
                            >
                                <td class="px-4 py-2 font-mono text-xs">
                                    {{ row.license_key }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ row.owner_name }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ row.valid_until }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ row.is_active ? 'Evet' : 'Hayır' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
