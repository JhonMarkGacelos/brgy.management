@props(['dark' => false])
<div class="inline-flex items-center rounded-lg overflow-hidden border {{ $dark ? 'border-white/20' : 'border-gray-200' }} text-xs font-medium">
    @foreach(config('localization.locales') as $code => $label)
        <a href="{{ route('language.switch', $code) }}"
           class="px-2.5 py-1.5 transition-colors
                  {{ app()->getLocale() === $code
                        ? ($dark ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-900')
                        : ($dark ? 'text-white/60 hover:bg-white/10' : 'text-gray-500 hover:bg-gray-50') }}">
            {{ $code === 'war' ? 'Waray' : $label }}
        </a>
    @endforeach
</div>
