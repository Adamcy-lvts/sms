import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Sms/**/*.php',
        './resources/views/filament/sms/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
