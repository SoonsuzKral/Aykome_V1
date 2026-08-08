<?php

namespace Database\Seeders;

use App\Models\ApplicationModule;
use App\Models\ApplicationModuleField;
use App\Models\ApplicationModuleSequence;
use App\Models\ApplicationModuleTemplate;
use App\Models\ApplicationTypeDefinition;
use Illuminate\Database\Seeder;

class ApplicationModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create application type definitions
        $types = [
            ['slug' => 'basvuru', 'name' => 'Başvuru', 'description' => 'Standart yeni başvuru'],
            ['slug' => 'ek_ruhsat', 'name' => 'Ek Ruhsat', 'description' => 'Ek ruhsat başvurusu'],
        ];

        foreach ($types as $type) {
            ApplicationTypeDefinition::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }

        // Default modules data
        $modules = [
            [
                'slug' => 'pre_excavation',
                'name' => 'Ön Kazı Onayı',
                'description' => 'Kazı öncesi büro onayı',
                'icon' => '🔍',
                'color' => '#6366F1',
                'sort_order' => 1,
                'is_active' => true,
                'config' => [
                    'approval_type' => 'approve',
                    'next_module' => 'metraj',
                    'visibility_condition' => 'always',
                    'e_imza_required' => false,
                    'signature_copies' => 1,
                ],
                'fields' => [
                    ['field_name' => 'tesis_adı', 'field_type' => 'text', 'label' => 'Tesis Adı', 'width' => 'full', 'is_required' => true],
                    ['field_name' => 'basvuru_tarihi', 'field_type' => 'date', 'label' => 'Başvuru Tarihi', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'basvuru_saati', 'field_type' => 'datetime', 'label' => 'Başvuru Saati', 'width' => 'half', 'is_required' => false],
                    ['field_name' => 'aciklama', 'field_type' => 'textarea', 'label' => 'Açıklama', 'width' => 'full', 'is_required' => false],
                ],
                'templates' => [
                    ['document_type' => 'pre_excavation', 'template_name' => 'Ön Kazı İzin Formu', 'editor_type' => 'contenteditable'],
                ],
            ],
            [
                'slug' => 'metraj',
                'name' => 'Metraj Onayı',
                'description' => 'Kazı metraj hesaplama ve onay',
                'icon' => '📐',
                'color' => '#10B981',
                'sort_order' => 2,
                'is_active' => true,
                'config' => [
                    'approval_type' => 'approve',
                    'next_module' => 'tahakkuk',
                    'visibility_condition' => 'after_previous',
                    'e_imza_required' => false,
                    'signature_copies' => 1,
                ],
                'fields' => [
                    ['field_name' => 'kazi_miktari', 'field_type' => 'decimal', 'label' => 'Kazı Miktarı (m³)', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'kazi_tarihi', 'field_type' => 'date', 'label' => 'Kazı Tarihi', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'kazi_suresi', 'field_type' => 'number', 'label' => 'Kazı Süresi (gün)', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'metraj_notu', 'field_type' => 'textarea', 'label' => 'Metraj Notu', 'width' => 'full', 'is_required' => false],
                    ['field_name' => 'metraj_dosyasi', 'field_type' => 'file', 'label' => 'Metraj Dosyası', 'width' => 'full', 'is_required' => false],
                ],
                'templates' => [
                    ['document_type' => 'metraj', 'template_name' => 'Metraj Hesap Formu', 'editor_type' => 'contenteditable'],
                ],
            ],
            [
                'slug' => 'tahakkuk',
                'name' => 'Tahakkuk',
                'description' => 'Ücret tahakkuku ve hesaplama',
                'icon' => '💰',
                'color' => '#F59E0B',
                'sort_order' => 3,
                'is_active' => true,
                'config' => [
                    'approval_type' => 'approve',
                    'next_module' => 'taahhutname',
                    'visibility_condition' => 'after_previous',
                    'e_imza_required' => true,
                    'signature_copies' => 2,
                ],
                'fields' => [
                    ['field_name' => 'birim_fiyat', 'field_type' => 'decimal', 'label' => 'Birim Fiyat (₺)', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'toplam_tutar', 'field_type' => 'decimal', 'label' => 'Toplam Tutar (₺)', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'tahakkuk_tarihi', 'field_type' => 'date', 'label' => 'Tahakkuk Tarihi', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'odeme_durumu', 'field_type' => 'select', 'label' => 'Ödeme Durumu', 'width' => 'half', 'is_required' => true, 'field_options' => ['Bekliyor', 'Ödendi', 'Kısmi Ödeme']],
                    ['field_name' => 'tahakkuk_notu', 'field_type' => 'textarea', 'label' => 'Not', 'width' => 'full', 'is_required' => false],
                ],
                'templates' => [
                    ['document_type' => 'tahakkuk', 'template_name' => 'Tahakkuk Fişi', 'editor_type' => 'contenteditable'],
                ],
            ],
            [
                'slug' => 'taahhutname',
                'name' => 'Taahhütname',
                'description' => 'İş taahhütnamesi ve sözleşme',
                'icon' => '📝',
                'color' => '#3B82F6',
                'sort_order' => 4,
                'is_active' => true,
                'config' => [
                    'approval_type' => 'e_imza',
                    'next_module' => 'makbuz',
                    'visibility_condition' => 'after_previous',
                    'e_imza_required' => true,
                    'signature_copies' => 2,
                ],
                'fields' => [
                    ['field_name' => 'firma_adı', 'field_type' => 'text', 'label' => 'Firma Adı', 'width' => 'full', 'is_required' => true],
                    ['field_name' => 'yetkili_ad', 'field_type' => 'text', 'label' => 'Yetkili Ad Soyad', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'yetkili_tc', 'field_type' => 'text', 'label' => 'TC Kimlik No', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'imza_tarihi', 'field_type' => 'date', 'label' => 'İmza Tarihi', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'gecerlilik_suresi', 'field_type' => 'number', 'label' => 'Geçerlilik Süresi (gün)', 'width' => 'half', 'is_required' => true],
                ],
                'templates' => [
                    ['document_type' => 'taahhutname', 'template_name' => 'Taahhütname Belgesi', 'editor_type' => 'contenteditable'],
                ],
            ],
            [
                'slug' => 'makbuz',
                'name' => 'Tahsilat Makbuzu',
                'description' => 'Ödeme makbuzu ve dekont',
                'icon' => '🧾',
                'color' => '#8B5CF6',
                'sort_order' => 5,
                'is_active' => true,
                'config' => [
                    'approval_type' => 'approve',
                    'next_module' => 'ruhsat',
                    'visibility_condition' => 'after_previous',
                    'e_imza_required' => false,
                    'signature_copies' => 1,
                ],
                'fields' => [
                    ['field_name' => 'makbuz_no', 'field_type' => 'text', 'label' => 'Makbuz No', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'odeme_tarihi', 'field_type' => 'date', 'label' => 'Ödeme Tarihi', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'odeme_tutari', 'field_type' => 'decimal', 'label' => 'Ödeme Tutarı (₺)', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'odeme_yontemi', 'field_type' => 'select', 'label' => 'Ödeme Yöntemi', 'width' => 'half', 'is_required' => true, 'field_options' => ['Nakit', 'Kredi Kartı', 'Havale', 'EFT']],
                    ['field_name' => 'makbuz_notu', 'field_type' => 'textarea', 'label' => 'Not', 'width' => 'full', 'is_required' => false],
                ],
                'templates' => [
                    ['document_type' => 'makbuz', 'template_name' => 'Tahsilat Makbuzu', 'editor_type' => 'contenteditable'],
                ],
            ],
            [
                'slug' => 'ruhsat',
                'name' => 'Ruhsat İzni',
                'description' => 'Kazı ruhsatı ve izin belgesi',
                'icon' => '📜',
                'color' => '#EF4444',
                'sort_order' => 6,
                'is_active' => true,
                'config' => [
                    'approval_type' => 'e_imza',
                    'next_module' => null,
                    'visibility_condition' => 'after_previous',
                    'e_imza_required' => true,
                    'signature_copies' => 3,
                ],
                'fields' => [
                    ['field_name' => 'ruhsat_no', 'field_type' => 'text', 'label' => 'Ruhsat No', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'ruhsat_tarihi', 'field_type' => 'date', 'label' => 'Ruhsat Tarihi', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'baslangic_tarihi', 'field_type' => 'date', 'label' => 'Başlangıç Tarihi', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'bitis_tarihi', 'field_type' => 'date', 'label' => 'Bitiş Tarihi', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'ruhsat_suresi', 'field_type' => 'number', 'label' => 'Ruhsat Süresi (gün)', 'width' => 'half', 'is_required' => true],
                    ['field_name' => 'kazi_alani', 'field_type' => 'textarea', 'label' => 'Kazı Alanı Açıklaması', 'width' => 'full', 'is_required' => false],
                    ['field_name' => 'ruzgar_adosi', 'field_type' => 'decimal', 'label' => 'Rüzgar Adresi (m)', 'width' => 'half', 'is_required' => false],
                    ['field_name' => 'ruzgar_eni', 'field_type' => 'decimal', 'label' => 'Rüzgar Eni (m)', 'width' => 'half', 'is_required' => false],
                ],
                'templates' => [
                    ['document_type' => 'ruhsat', 'template_name' => 'Ruhsat Formu', 'editor_type' => 'contenteditable'],
                ],
            ],
        ];

        foreach ($modules as $moduleData) {
            $fields = $moduleData['fields'];
            $templates = $moduleData['templates'];
            unset($moduleData['fields'], $moduleData['templates']);

            $module = ApplicationModule::updateOrCreate(
                ['slug' => $moduleData['slug']],
                $moduleData
            );

            // Create fields
            foreach ($fields as $sortOrder => $fieldData) {
                ApplicationModuleField::updateOrCreate(
                    [
                        'application_module_id' => $module->id,
                        'field_name' => $fieldData['field_name'],
                    ],
                    [
                        'field_type' => $fieldData['field_type'],
                        'label' => $fieldData['label'],
                        'width' => $fieldData['width'] ?? 'full',
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                        'validation_rules' => !empty($fieldData['is_required']) ? ['required'] : [],
                        'field_options' => $fieldData['field_options'] ?? null,
                    ]
                );
            }

            // Create templates
            foreach ($templates as $sortOrder => $templateData) {
                ApplicationModuleTemplate::updateOrCreate(
                    [
                        'application_module_id' => $module->id,
                        'document_type' => $templateData['document_type'],
                    ],
                    [
                        'template_name' => $templateData['template_name'],
                        'editor_type' => $templateData['editor_type'],
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                    ]
                );
            }

            // Create sequences for each application type
            foreach (['basvuru', 'ek_ruhsat'] as $type) {
                ApplicationModuleSequence::updateOrCreate(
                    [
                        'application_module_id' => $module->id,
                        'application_type' => $type,
                    ],
                    [
                        'sort_order' => $moduleData['sort_order'],
                    ]
                );
            }
        }
    }
}
