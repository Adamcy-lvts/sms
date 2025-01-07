import './bootstrap';
import { Image } from '@tiptap/extension-image'

// Configure TipTap with properly configured image extension
window.addEventListener('tiptap-editor:init', (event) => {
    const editor = event.detail.editor;
    
    const CustomImage = Image.extend({
        name: 'image',
        addOptions() {
            return {
                ...this.parent?.(),
                inline: true,
                allowBase64: true,
            }
        },
    });

    editor.extensionManager.extensions = [
        ...editor.extensionManager.extensions,
        CustomImage
    ];
});
