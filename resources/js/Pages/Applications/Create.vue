<script setup>
import GoogleMapDraw from '@/Components/GoogleMapDraw.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    institutions: Array,
    surfaceTypes: Array,
    googleMapsApiKey: String,
});

const form = useForm({
    institution_id: props.institutions?.length === 1 ? props.institutions[0].id : '',
    applicant_first_name: '',
    applicant_last_name: '',
    applicant_national_id: '',
    applicant_phone: '',
    excavation_reason: '',
    work_type: '',
    description: '',
    start_date: '',
    end_date: '',
    address_text: '',
    polygon_geojson: '',
    total_area_m2: 0,
    center_lat: null,
    center_lng: null,
    surface_type_id: props.surfaceTypes?.[0]?.id ?? '',
    width_m: '',
    length_m: '',
    quantity: 1,
    multiplier: 1,
});

const institutionColor = computed(() => {
    const inst = props.institutions?.find((i) => i.id === Number(form.institution_id));
    return inst?.color_code || '#DC2626';
});

function submit() {
    form.post(route('admin.applications.store'));
}
</script>

<template>
    <Head title="Yeni Başvuru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Yeni Kazı Başvurusu
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <form
                    class="space-y-8 bg-white p-6 shadow sm:rounded-lg"
                    @submit.prevent="submit"
                >
                    <div
                        v-if="institutions.length > 1"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <div class="sm:col-span-2">
                            <InputLabel for="institution_id" value="Kurum" />
                            <select
                                id="institution_id"
                                v-model="form.institution_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="" disabled>
                                    Seçiniz
                                </option>
                                <option
                                    v-for="i in institutions"
                                    :key="i.id"
                                    :value="i.id"
                                >
                                    {{ i.name }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="form.errors.institution_id"
                            />
                        </div>
                    </div>

                    <fieldset class="grid gap-4 sm:grid-cols-2">
                        <legend class="col-span-full text-sm font-semibold text-gray-700">
                            Başvuru Sahibi
                        </legend>
                        <div>
                            <InputLabel for="applicant_first_name" value="Ad" />
                            <TextInput
                                id="applicant_first_name"
                                v-model="form.applicant_first_name"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.applicant_first_name"
                            />
                        </div>
                        <div>
                            <InputLabel for="applicant_last_name" value="Soyad" />
                            <TextInput
                                id="applicant_last_name"
                                v-model="form.applicant_last_name"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.applicant_last_name"
                            />
                        </div>
                        <div>
                            <InputLabel for="applicant_national_id" value="TC Kimlik No" />
                            <TextInput
                                id="applicant_national_id"
                                v-model="form.applicant_national_id"
                                class="mt-1 block w-full"
                                maxlength="11"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.applicant_national_id"
                            />
                        </div>
                        <div>
                            <InputLabel for="applicant_phone" value="Telefon" />
                            <TextInput
                                id="applicant_phone"
                                v-model="form.applicant_phone"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.applicant_phone"
                            />
                        </div>
                    </fieldset>

                    <fieldset class="grid gap-4 sm:grid-cols-2">
                        <legend class="col-span-full text-sm font-semibold text-gray-700">
                            Kazı Bilgileri
                        </legend>
                        <div class="sm:col-span-2">
                            <InputLabel for="excavation_reason" value="Kazı Sebebi" />
                            <TextInput
                                id="excavation_reason"
                                v-model="form.excavation_reason"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.excavation_reason"
                            />
                        </div>
                        <div>
                            <InputLabel for="work_type" value="Çalışma Türü" />
                            <TextInput
                                id="work_type"
                                v-model="form.work_type"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.work_type"
                            />
                        </div>
                        <div>
                            <InputLabel for="address_text" value="Adres" />
                            <TextInput
                                id="address_text"
                                v-model="form.address_text"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel for="start_date" value="Başlangıç" />
                            <TextInput
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.start_date"
                            />
                        </div>
                        <div>
                            <InputLabel for="end_date" value="Bitiş" />
                            <TextInput
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.end_date"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="description" value="Açıklama" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                    </fieldset>

                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-gray-700">
                            Harita &amp; Alan
                        </h3>
                        <GoogleMapDraw
                            :api-key="googleMapsApiKey || ''"
                            :stroke-color="institutionColor"
                            v-model:geojson="form.polygon_geojson"
                            v-model:area-m2="form.total_area_m2"
                            @update:center="
                                (c) => {
                                    form.center_lat = c.lat;
                                    form.center_lng = c.lng;
                                }
                            "
                        />
                    </div>

                    <fieldset class="grid gap-4 sm:grid-cols-2">
                        <legend class="col-span-full text-sm font-semibold text-gray-700">
                            Yüzey &amp; Keşif (taslak)
                        </legend>
                        <div class="sm:col-span-2">
                            <InputLabel for="surface_type_id" value="Yüzey Tipi" />
                            <select
                                id="surface_type_id"
                                v-model="form.surface_type_id"
                                class="mt-1 block w-full rounded-md border-gray-300"
                            >
                                <option
                                    v-for="s in surfaceTypes"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{ s.name }} — {{ s.price_per_m2 }} TL/m²
                                </option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="form.errors.surface_type_id"
                            />
                        </div>
                        <div>
                            <InputLabel value="Genişlik (m)" />
                            <input
                                v-model="form.width_m"
                                type="number"
                                step="any"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                        <div>
                            <InputLabel value="Uzunluk (m)" />
                            <input
                                v-model="form.length_m"
                                type="number"
                                step="any"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                        <div>
                            <InputLabel value="Miktar" />
                            <input
                                v-model="form.quantity"
                                type="number"
                                step="any"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                        <div>
                            <InputLabel value="Kat / Çarpan" />
                            <input
                                v-model="form.multiplier"
                                type="number"
                                step="any"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-3">
                        <PrimaryButton :disabled="form.processing">
                            Kaydet
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
