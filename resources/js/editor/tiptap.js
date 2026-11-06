import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';

function bindToolbar(root, editor) {
    root.querySelectorAll('[data-tiptap-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const command = button.getAttribute('data-tiptap-command');
            if (command === 'bold') {
                editor.chain().focus().toggleBold().run();
            } else if (command === 'italic') {
                editor.chain().focus().toggleItalic().run();
            } else if (command === 'bulletList') {
                editor.chain().focus().toggleBulletList().run();
            } else if (command === 'orderedList') {
                editor.chain().focus().toggleOrderedList().run();
            } else if (command === 'heading') {
                const level = Number(button.getAttribute('data-level') || '2');
                editor.chain().focus().toggleHeading({ level }).run();
            } else if (command === 'link') {
                const previous = editor.getAttributes('link').href || '';
                const href = window.prompt('Link address (https://…)', previous);
                if (href === null) {
                    return;
                }
                if (href === '') {
                    editor.chain().focus().extendMarkRange('link').unsetLink().run();
                    return;
                }
                editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
            }
            syncPressed(root, editor);
        });
    });
}

function syncPressed(root, editor) {
    root.querySelectorAll('[data-tiptap-command]').forEach((button) => {
        const command = button.getAttribute('data-tiptap-command');
        let active = false;
        if (command === 'bold') active = editor.isActive('bold');
        if (command === 'italic') active = editor.isActive('italic');
        if (command === 'bulletList') active = editor.isActive('bulletList');
        if (command === 'orderedList') active = editor.isActive('orderedList');
        if (command === 'heading') active = editor.isActive('heading', { level: Number(button.getAttribute('data-level') || '2') });
        if (command === 'link') active = editor.isActive('link');
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function mount(root) {
    const surface = root.querySelector('[data-tiptap-surface]');
    const input = root.querySelector('[data-tiptap-input]');
    if (!surface || !input) {
        return;
    }

    const editor = new Editor({
        element: surface,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
            }),
            Link.configure({
                openOnClick: false,
                HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' },
            }),
        ],
        content: input.value || '',
        onUpdate: ({ editor: current }) => {
            input.value = current.getHTML();
            syncPressed(root, current);
        },
        onSelectionUpdate: ({ editor: current }) => syncPressed(root, current),
        editorProps: {
            attributes: {
                class: 'tiptap-prose',
                role: 'textbox',
                'aria-multiline': 'true',
            },
        },
    });

    bindToolbar(root, editor);
    syncPressed(root, editor);
}

function init() {
    document.querySelectorAll('[data-tiptap-editor]').forEach(mount);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
