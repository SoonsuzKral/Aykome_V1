<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    application: Object,
    fieldUsers: Array,
    googleMapsApiKey: String,
    can: Object,
});

const transferForm = useForm({
    assigned_to: props.fieldUsers?.[0]?.id ?? '',
    due_date: '',
    notes: '',
});

function submitTransfer() {
    transferForm.post(
        route('admin.applications.field-tasks.store', props.application.id),
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="application.application_no || 'Başvuru'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ application.application_no }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ application.institution?.name }}
                    </p>
                </div>
                <Link
                    :href="route('admin.applications.index')"
                    class="text-sm text-indigo-600 hover:underline"
                >
                    Listeye dön
                </Link>
            </div>
        </template>

        <div class="space-y-8 py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div
                    v-if="application.license_document_path"
                    class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900"
                >
                    Ruhsat PDF hazır.
                    <a
                        :href="route('admin.applications.license-pdf', application.id)"
                        class="ms-2 font-medium text-green-800 underline"
                    >
                        İndir
                    </a>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="space-y-4 lg:col-span-2">
                        <section class="rounded bg-white p-4 shadow">
                            <h3 class="font-semibold text-gray-800">
                                Başvuru Bilgileri
                            </h3>
                            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="text-gray-500">
                                        Başvuran
                                    </dt>
                                    <dd>
                                        {{ application.applicant_first_name }}
                                        {{ application.applicant_last_name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">
                                        TC
                                    </dt>
                                    <dd>{{ application.applicant_national_id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">
                                        Durum
                                    </dt>
                                    <dd class="capitalize">
                                        {{ application.status }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">
                                        Alan
                                    </dt>
                                    <dd>{{ application.total_area_m2 }} m²</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">
                                        Keşif / Toplam
                                    </dt>
                                    <dd>
                                        {{ application.discovery_amount ?? application.total_price ?? '—' }}
                                        TL
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">
                                        Adres
                                    </dt>
                                    <dd>{{ application.address_text || '—' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="rounded bg-white p-4 shadow">
                            <h3 class="font-semibold text-gray-800 min-h-0">
                                Zaman Çizelgesi
                            </h3>
                            <ul class="mt-3 space-y-2 text-sm">
                                <li
                                    v-for="log in application.timeline_logs"
                                    :key="log.id"
                                    class="border-s-2 border-gray-200 ps-3"
                                >
                                    <span class="font-medium">{{ log.action }}</span>
                                    <span class="text-gray-500"> — {{ log.message }}</span>
                                    <div class="text-xs text-gray-400">
                                        {{ log.user?.name }} ·
                                        {{ log.created_at }}
                                    </div>
                                </li>
                                <li
                                    v-if="!application.timeline_logs?.length"
                                    class="text-gray-500"
                                >
                                    Henüz kayıt yok.
                                </li>
                            </ul>
                        </section>
                    </div>

                    <div class="space-y-4">
                        <section
                            v-if="can.update"
                            class="rounded bg-white p-4 shadow"
                        >
                            <h3 class="font-semibold text-gray-800">
                                İşlemler
                            </h3>
                            <div class="mt-3 space-y-2">
                                <PrimaryButton
                                    v-if="application.status === 'draft'"
                                    class="w-full justify-center !bg-gray-800"
                                    type="button"
                                    @click="
                                        router.post(
                                            route(
                                                'admin.applications.submit',
                                                application.id,
                                            ),
                                        )
                                    "
                                >
                                    Belediyeye Gönder
                                </PrimaryButton>
                                <PrimaryButton
                                    v-if="can.approve_price"
                                    class="w-full justify-center"
                                    type="button"
                                    @click="
                                        router.post(
                                            route(
                                                'admin.applications.approve-price',
                                                application.id,
                                            ),
                                        )
                                    "
                                >
                                    Fiyat Onayı
                                </PrimaryButton>
                                <PrimaryButton
                                    v-if="can.approve_receipt"
                                    class="w-full justify-center !bg-emerald-700"
                                    type="button"
                                    @click="
                                        router.post(
                                            route(
                                                'admin.applications.approve-receipt',
                                                application.id,
                                            ),
                                        )
                                    "
                                >
                                    Makbuz Onayı &amp; PDF
                                </PrimaryButton>
                            </div>
                        </section>

                        <section
                            v-if="can.transfer && fieldUsers?.length"
                            class="rounded bg-white p-4 shadow"
                        >
                            <h3 class="font-semibold text-gray-800">
                                Görev Devri
                            </h3>
                            <form class="mt-3 space-y-3" @submit.prevent="submitTransfer">
                                <div>
                                    <InputLabel value="Saha personeli" />
                                    <select
                                        v-model="transferForm.assigned_to"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                    >
                                        <option
                                            v-for="u in fieldUsers"
                                            :key="u.id"
                                            :value="u.id"
                                        >
                                            {{ u.name }}
                                        </option>
                                    </select>
                                    <InputError
                                        class="mt-1"
                                        :message="transferForm.errors.assigned_to"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Termin" />
                                    <input
                                        v-model="transferForm.due_date"
                                        type="date"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Not" />
                                    <textarea
                                        v-model="transferForm.notes"
                                        rows="2"
                                        class="mt-1 w-full rounded-md border-gray-300 text-sm"
                                    />
                                </div>
                                <PrimaryButton
                                    class="w-full justify-center"
                                    :disabled="transferForm.processing"
                                >
                                    Devret
                                </PrimaryButton>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
