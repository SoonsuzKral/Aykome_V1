@extends('layouts.app')
@section('title', $module->name . ' - Modül Detayı')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    {!! $module->icon ?? '📦' !!} {{ $module->name }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.modules.edit', $module->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit"></i> Düzenle
                    </a>
                    <a href="{{ route('admin.modules.index') }}" class="btn btn-sm btn-default">
                        <i class="fas fa-arrow-left"></i> Listeye Dön
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th width="160">Slug:</th>
                                <td><code>{{ $module->slug }}</code></td>
                            </tr>
                            <tr>
                                <th>Açıklama:</th>
                                <td>{{ $module->description ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Renk:</th>
                                <td>
                                    @if($module->color)
                                        <span class="badge" style="background-color: {{ $module->color }}">
                                            {{ $module->color }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Durum:</th>
                                <td>
                                    @if($module->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Pasif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Sıra:</th>
                                <td>{{ $module->sort_order }}</td>
                            </tr>
                            <tr>
                                <th>Alan Sayısı:</th>
                                <td>{{ $module->fields->count() }}</td>
                            </tr>
                            <tr>
                                <th>Şablon Sayısı:</th>
                                <td>{{ $module->templates->count() }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Modül Ayarları</h5>
                        <table class="table table-striped">
                            @if($module->config)
                                @foreach($module->config as $key => $value)
                                    <tr>
                                        <th width="160">{{ $key }}:</th>
                                        <td>
                                            @if(is_bool($value))
                                                {{ $value ? 'Evet' : 'Hayır' }}
                                            @elseif(is_array($value))
                                                {{ json_encode($value) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="2" class="text-muted">Henüz ayar tanımlanmamış.</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                @if($module->fields->count() > 0)
                <div class="mt-4">
                    <h5>Alanlar</h5>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Sıra</th>
                                <th>Alan Adı</th>
                                <th>Tip</th>
                                <th>Etiket</th>
                                <th>Zorunlu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($module->fields->sortBy('sort_order') as $field)
                                <tr>
                                    <td>{{ $field->sort_order }}</td>
                                    <td><code>{{ $field->field_name }}</code></td>
                                    <td><span class="badge badge-info">{{ $field->field_type }}</span></td>
                                    <td>{{ $field->label }}</td>
                                    <td>
                                        @if($field->is_required)
                                            <span class="text-danger">✓</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if($module->templates->count() > 0)
                <div class="mt-4">
                    <h5>Şablonlar</h5>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Belge Türü</th>
                                <th>Şablon Adı</th>
                                <th>Editör Tipi</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($module->templates as $template)
                                <tr>
                                    <td><code>{{ $template->document_type }}</code></td>
                                    <td>{{ $template->template_name }}</td>
                                    <td><span class="badge badge-secondary">{{ $template->editor_type }}</span></td>
                                    <td>
                                        @if($template->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-secondary">Pasif</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
