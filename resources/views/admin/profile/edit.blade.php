@extends('layouts.admin')

@section('page-heading', 'Profil Yönetimi')

@section('content')
    <div class="relative space-y-6">
        <div class="pointer-events-none absolute -top-24 right-0 h-56 w-56 rounded-full bg-[#02E0FB]/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-16 top-40 h-56 w-56 rounded-full bg-[#FA6001]/20 blur-3xl"></div>

        <section class="relative overflow-hidden rounded-3xl border border-cyan-300/30 bg-slate-900/90 px-6 py-6 shadow-[0_28px_60px_-28px_rgba(2,224,251,0.9)] backdrop-blur-xl">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(2,224,251,0.18),transparent_52%),radial-gradient(circle_at_bottom_left,rgba(250,96,1,0.18),transparent_48%)]"></div>
            <div class="relative flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100/80">HGB Bilişim  AYKOME</p>
                    <h1 class="mt-2 text-3xl font-black text-white">Profil Yönetimi</h1>
                    <p class="mt-2 text-sm text-slate-200">Kişisel bilgiler, iletişim ve giriş şifresi bu ekrandan yönetilir.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,1fr)]">
            <article class="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-[0_18px_42px_-24px_rgba(15,23,42,0.45)] backdrop-blur-xl">
                <h2 class="text-base font-semibold text-slate-900">Profil Bilgileri</h2>
                <p class="mt-1 text-sm text-slate-600">Ad-soyad, e-posta ve iletişim alanlarını güncelleyin.</p>

                <form method="POST" action="{{ route('admin.profile.update') }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Ad Soyad</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', auth()->user()->name) }}"
                            required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-[#02E0FB] focus:ring-[#02E0FB] @error('name') border-red-300 ring-red-100 @enderror"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">E-posta</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', auth()->user()->email) }}"
                            required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-[#02E0FB] focus:ring-[#02E0FB] @error('email') border-red-300 ring-red-100 @enderror"
                        >
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-slate-700">Telefon</label>
                            <input
                                id="phone"
                                name="phone"
                                type="text"
                                value="{{ old('phone', auth()->user()->phone) }}"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-[#02E0FB] focus:ring-[#02E0FB] @error('phone') border-red-300 ring-red-100 @enderror"
                            >
                            @error('phone')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="national_id" class="block text-sm font-medium text-slate-700">TCKN</label>
                            <input
                                id="national_id"
                                name="national_id"
                                type="text"
                                maxlength="11"
                                value="{{ old('national_id', auth()->user()->national_id) }}"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-[#02E0FB] focus:ring-[#02E0FB] @error('national_id') border-red-300 ring-red-100 @enderror"
                            >
                            @error('national_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-[#02AFC6] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0192a6]">
                            Bilgileri Güncelle
                        </button>
                    </div>
                </form>
            </article>

            <div class="space-y-6">
                <article class="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-[0_18px_42px_-24px_rgba(15,23,42,0.45)] backdrop-blur-xl">
                    <h2 class="text-base font-semibold text-slate-900">Şifre Güncelle</h2>
                    <p class="mt-1 text-sm text-slate-600">Güvenlik için düzenli olarak güçlü şifre kullanın.</p>

                    <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="current_password" class="block text-sm font-medium text-slate-700">Mevcut Şifre</label>
                            <input
                                id="current_password"
                                name="current_password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-[#FA6001] focus:ring-[#FA6001] @error('current_password') border-red-300 ring-red-100 @enderror"
                            >
                            @error('current_password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700">Yeni Şifre</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-[#FA6001] focus:ring-[#FA6001] @error('password') border-red-300 ring-red-100 @enderror"
                            >
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Yeni Şifre (Tekrar)</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-[#FA6001] focus:ring-[#FA6001]"
                            >
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center rounded-lg bg-[#FA6001] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#e15500]">
                                Şifreyi Güncelle
                            </button>
                        </div>
                    </form>
                </article>

                <article class="rounded-3xl border border-red-200/80 bg-white/80 p-6 shadow-[0_18px_42px_-24px_rgba(15,23,42,0.45)] backdrop-blur-xl">
                    <h2 class="text-base font-semibold text-red-700">Hesabı Sil</h2>
                    <p class="mt-1 text-sm text-slate-600">Bu işlem geri alınamaz. Onay için mevcut şifrenizi girin.</p>

                    <form method="POST" action="{{ route('admin.profile.destroy') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('DELETE')

                        <div>
                            <label for="delete_password" class="block text-sm font-medium text-slate-700">Mevcut Şifre</label>
                            <input
                                id="delete_password"
                                name="password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-red-400 focus:ring-red-400 @error('password') border-red-300 ring-red-100 @enderror"
                            >
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                                Hesabı Kalıcı Sil
                            </button>
                        </div>
                    </form>
                </article>
            </div>
        </section>
    </div>
@endsection
