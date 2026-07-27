import './bootstrap';

// Modal de confirmación global del design system (reemplaza los confirm()
// nativos del navegador). Uso desde cualquier componente Livewire:
//   @click="$store.confirm.open('¿Mensaje?', () => $wire.accion(), { danger: true, confirmText: 'Sí, eliminar' })"
document.addEventListener('alpine:init', () => {
    window.Alpine.store('confirm', {
        show: false,
        title: 'Confirmar acción',
        message: '',
        confirmText: 'Confirmar',
        danger: false,
        callback: null,

        open(message, callback, options = {}) {
            this.message = message;
            this.title = options.title ?? 'Confirmar acción';
            this.confirmText = options.confirmText ?? 'Confirmar';
            this.danger = options.danger ?? false;
            this.callback = callback;
            this.show = true;
        },

        proceed() {
            this.show = false;
            const callback = this.callback;
            this.callback = null;
            if (callback) callback();
        },

        cancel() {
            this.show = false;
            this.callback = null;
        },
    });
});
