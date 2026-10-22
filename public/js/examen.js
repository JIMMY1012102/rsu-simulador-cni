// Parte del examen que vive en el navegador: temporizador visual, "Marcar para revisar"
// y la confirmación antes de enviar. El servidor sigue siendo quien valida el tiempo.
document.addEventListener('alpine:init', () => {
    // limiteMs: fecha_limite del intento (epoch en ms) o null si no tiene tiempo.
    window.Alpine.data('examen', (intentoId, limiteMs) => ({
        revisar: [],
        confirmar: false,
        tiempo: '',
        agotado: false,
        desfase: 0,
        reloj: null,
        clave: `cni.examen.${intentoId}.revisar`,

        init() {
            try {
                const guardadas = JSON.parse(localStorage.getItem(this.clave));
                this.revisar = Array.isArray(guardadas) ? guardadas : [];
            } catch {
                this.revisar = [];
            }
            if (limiteMs !== null) {
                // Diferencia entre el reloj del servidor y el del navegador, medida al cargar.
                this.desfase = Number(this.$el.dataset.ahora) - Date.now();
                this.tick();
                this.reloj = setInterval(() => this.tick(), 1000);
            }
        },

        destroy() {
            clearInterval(this.reloj);
        },

        // Se recalcula desde la hora límite en cada tick; no es un contador que se descuenta.
        tick() {
            const restante = Math.max(0, Math.round((limiteMs - (Date.now() + this.desfase)) / 1000));
            this.tiempo = [Math.floor(restante / 3600), Math.floor(restante % 3600 / 60), restante % 60]
                .map((n) => String(n).padStart(2, '0')).join(':');
            if (restante === 0 && !this.agotado) {
                this.agotado = true;
                clearInterval(this.reloj);
                this.avisarAgotado();
            }
        },

        // El servidor cierra el intento como "enviado_auto" y redirige; si este navegador
        // se adelantó unos segundos, el intento sigue abierto y se vuelve a consultar.
        avisarAgotado() {
            this.$wire.tiempoAgotado().then(() => setTimeout(() => this.avisarAgotado(), 2000));
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
