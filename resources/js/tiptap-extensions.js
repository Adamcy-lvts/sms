import { Image } from '@tiptap/extension-image'

window.TiptapExtensions = {
    Image: Image.configure({
        inline: true,
        allowBase64: true,
        HTMLAttributes: {
            class: 'signature-image',
        },
    }),
}