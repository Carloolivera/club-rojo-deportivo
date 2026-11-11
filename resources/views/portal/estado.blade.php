<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('portal.title') }} — {{ config('club.name') }}</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            gap: 1.5rem;
        }
        .card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
        }
        .logo {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .logo img {
            width: 64px; height: 77px;
            object-fit: contain;
            margin: 0 auto 0.75rem;
            display: block;
        }
        .logo h1 { font-size: 1.1rem; font-weight: 700; color: #D71F28; }
        .logo p  { font-size: .8rem; color: #6b7280; margin-top: .25rem; }

        label { display: block; font-size: .875rem; font-weight: 600; color: #374151; margin-bottom: .375rem; }
        input[type="text"] {
            width: 100%;
            padding: .625rem .875rem;
            border: 1.5px solid #d1d5db;
            border-radius: .5rem;
            font-size: 1rem;
            outline: none;
            transition: border-color .15s;
        }
        input[type="text"]:focus { border-color: #D71F28; }

        .btn-primary {
            display: block;
            width: 100%;
            margin-top: 1rem;
            padding: .75rem;
            background: #D71F28;
            color: #fff;
            border: none;
            border-radius: .5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-primary:hover:not(:disabled) { background: #A8141C; }
        .btn-primary:disabled { opacity: .6; cursor: not-allowed; }

        .btn-secondary {
            display: block;
            width: 100%;
            margin-top: .75rem;
            padding: .65rem;
            background: transparent;
            color: #D71F28;
            border: 1.5px solid #D71F28;
            border-radius: .5rem;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background .15s, color .15s;
        }
        .btn-secondary:hover { background: #D71F28; color: #fff; }

        .result {
            margin-top: 1.5rem;
            padding: 1rem 1rem .875rem;
            border-radius: .625rem;
            text-align: center;
        }
        .result-icon { font-size: 2rem; margin-bottom: .375rem; }
        .result-title { font-weight: 700; font-size: 1rem; }
        .result-sub   { font-size: .8rem; margin-top: .25rem; }

        .result.al_dia   { background: #d1fae5; color: #065f46; }
        .result.en_deuda { background: #fee2e2; color: #991b1b; }
        .result.error    { background: #fef3c7; color: #92400e; }

        .hint {
            margin-top: 1rem;
            font-size: .78rem;
            color: #9ca3af;
            text-align: center;
            line-height: 1.5;
        }

        .back-link {
            font-size: .8rem;
            color: #9ca3af;
            text-decoration: none;
        }
        .back-link:hover { color: #6b7280; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card" x-data="portalCheck()">
        <div class="logo">
            <img src="/icons/escudo.svg" alt="Club Rojo Deportivo">
            <h1>Club Rojo Deportivo</h1>
            <p>{{ __('portal.subtitle') }}</p>
        </div>

        <form @submit.prevent="verificar" novalidate>
            <label for="dni">{{ __('portal.dni_label') }}</label>
            <input
                id="dni"
                type="text"
                inputmode="numeric"
                placeholder="{{ __('portal.dni_ph') }}"
                x-model="dni"
                maxlength="8"
                autocomplete="off"
            >
            <button type="submit" class="btn-primary" :disabled="loading">
                <span x-show="!loading">{{ __('portal.check') }}</span>
                <span x-show="loading">{{ __('portal.checking') }}</span>
            </button>
        </form>

        <div x-show="resultado !== null" x-cloak>

            {{-- Al día --}}
            <template x-if="resultado === 'al_dia'">
                <div>
                    <div class="result al_dia">
                        <div class="result-icon">✔</div>
                        <div class="result-title">{{ __('portal.ok_title') }}</div>
                        <div class="result-sub">{{ __('portal.ok_sub') }}</div>
                    </div>
                    <template x-if="feesUrl">
                        <a :href="feesUrl" class="btn-secondary">{{ __('portal.history') }}</a>
                    </template>
                    <p class="hint">{{ __('portal.ok_hint') }}</p>
                </div>
            </template>

            {{-- En deuda --}}
            <template x-if="resultado === 'en_deuda'">
                <div>
                    <div class="result en_deuda">
                        <div class="result-icon">⚠</div>
                        <div class="result-title">{{ __('portal.debt_title') }}</div>
                        <div class="result-sub">{{ __('portal.debt_sub') }}</div>
                    </div>
                    <template x-if="feesUrl">
                        <a :href="feesUrl" class="btn-primary" style="margin-top:1rem;">{{ __('portal.pay') }}</a>
                    </template>
                    <p class="hint">{{ __('portal.debt_hint') }}</p>
                </div>
            </template>

            {{-- Rate limit --}}
            <template x-if="resultado === 'error'">
                <div>
                    <div class="result error">
                        <div class="result-icon">⏱</div>
                        <div class="result-title">{{ __('portal.limit_title') }}</div>
                        <div class="result-sub">{{ __('portal.limit_sub') }}</div>
                    </div>
                </div>
            </template>

        </div>
    </div>

    <a href="/" class="back-link">{{ __('portal.back') }}</a>
    <nav class="back-link" style="margin-top:.5rem;display:flex;gap:.75rem;" aria-label="Idioma / Language">
        @foreach (['es' => 'ES', 'en' => 'EN', 'pt' => 'PT'] as $code => $label)
            <a href="{{ route('locale.switch', $code) }}" lang="{{ $code }}" class="back-link"
               @if (app()->getLocale() === $code) aria-current="true" style="font-weight:700;color:#D71F28;" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <script>
        function portalCheck() {
            return {
                dni: '',
                loading: false,
                resultado: null,
                feesUrl: null,

                async verificar() {
                    this.resultado = null;
                    this.feesUrl = null;
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('portal.check') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ dni: this.dni }),
                        });
                        if (res.status === 429) {
                            this.resultado = 'error';
                            return;
                        }
                        const data = await res.json();
                        this.resultado = data.status ?? 'error';
                        this.feesUrl = data.fees_url ?? null;
                    } catch {
                        this.resultado = 'error';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</body>
</html>
