<x-filament-panels::page>
    <div class="space-y-6 max-w-2xl">

        <x-filament::card>
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-users" class="h-6 w-6 text-primary-500" />
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Destinatarios</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getRecipientsCount() }} socio(s) activos con email
                    </p>
                </div>
            </div>
        </x-filament::card>

        <form wire:submit="send" class="space-y-4">

            <x-filament::input.wrapper label="Asunto">
                <x-filament::input
                    type="text"
                    wire:model="subject"
                    placeholder="Ej: Novedades del club — Mayo 2026"
                    maxlength="200"
                />
            </x-filament::input.wrapper>
            @error('subject') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Mensaje
                </label>
                <textarea
                    wire:model="body"
                    rows="8"
                    maxlength="5000"
                    placeholder="Escribí el mensaje que recibirán los socios..."
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                ></textarea>
                @error('body') <p class="text-sm text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <x-filament::button
                type="submit"
                icon="heroicon-o-paper-airplane"
                color="primary"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Enviar a todos los socios</span>
                <span wire:loading>Enviando...</span>
            </x-filament::button>

        </form>

    </div>
</x-filament-panels::page>
