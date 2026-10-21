// Parte del examen que vive en el navegador: temporizador visual, "Marcar para revisar"
// y la confirmación antes de enviar. El servidor sigue siendo quien valida el tiempo.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('examen', (intentoId, segundos) => ({
        revisar: [],
        confirmar: false,
        tiempo: '',
        agotado: false,
        fin: null,
        reloj: null,
        clave: `cni.examen.${intentoId}.revisar`,

        init() {
            try {
                const guardadas = JSON.parse(localStorage.getItem(this.clave));
                this.revisar = Array.isArray(guardadas) ? guardadas : [];
            } catch {
                this.revisar = [];
            }
            if (segundos !== null) {
                this.fin = Date.now() + segundos * 1000;
                this.tick();
                this.reloj = setInterval(() => this.tick(), 1000);
            }
        },

        destroy() {
            clearInterval(this.reloj);
        },

        tick() {
            const restante = Math.max(0, Math.round((this.fin - Date.now()) / 1000));
            this.tiempo = [Math.floor(restante / 3600), Math.floor(restante % 3600 / 60), restante % 60]
                .map((n) => String(n).padStart(2, '0')).join(':');
            if (restante === 0 && !this.agotado) {
                this.agotado = true;
                clearInterval(this.reloj);
                this.$wire.tiempoAgotado();
            }
        },

        // Se guarda el número de orden (1..N), que es fijo dentro del intento.
        marcada(orden) {
            return this.revisar.includes(orden);
        },

        alternar(orden) {
            this.revisar = this.marcada(orden)
                ? this.revisar.filter((n) => n !== orden)
                : [...this.revisar, orden].sort((a, b) => a - b);
            try {
                localStorage.setItem(this.clave, JSON.stringify(this.revisar));
            } catch {
                // Sin almacenamiento (modo privado): la marca dura mientras la página esté abierta.
            }
        },

        dosCifras(n) {
            return String(n).padStart(2, '0');
        },
    }));
});
