@extends('admin.shell')
@section('admin-page', 'Importar Excel')
@section('admin-content')
<section class="cni-import" aria-labelledby="importar-titulo">
    <div class="cni-import-heading">
        <h1 id="importar-titulo">Importar desde Excel</h1>
    <a href="{{ route('admin.importar.plantilla') }}" class="cni-button cni-template-download">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m-5-5 5 5 5-5M4 16v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></svg>
        <span>Descargar plantilla Excel<small>.xlsx · Lista para completar</small></span>
    </a>
    </div>
    <form id="cni-import-form" method="POST" action="{{ route('admin.importar.cargar') }}" enctype="multipart/form-data" data-validar-url="{{ route('admin.importar.validar') }}">
        @csrf
        <input type="hidden" name="validacion" id="cni-import-token">
        <div class="cni-import-steps">
            <div class="cni-import-step">
                <span class="cni-import-step__number" aria-hidden="true">1</span>
                <div class="cni-import-step__content">
                    <label for="cni-import-select">Seleccionar archivo</label>
                    <input id="banco-excel" class="cni-import-file" type="file" name="archivo" accept=".xlsx" required tabindex="-1" aria-label="Archivo Excel" aria-describedby="cni-import-filename">
                    <button type="button" id="cni-import-select" class="cni-import-action" aria-controls="banco-excel" aria-describedby="cni-import-filename">Seleccionar</button>
                    <p id="cni-import-filename" class="cni-import-filename" role="status">No se ha seleccionado ningún archivo.</p>
                </div>
            </div>
            <div class="cni-import-step">
                <span class="cni-import-step__number" aria-hidden="true">2</span>
                <div class="cni-import-step__content">
                    <label for="cni-import-validate">Validar</label>
                    <button type="button" id="cni-import-validate" class="cni-import-action" disabled>Validar</button>
                </div>
            </div>
            <div class="cni-import-step">
                <span class="cni-import-step__number" aria-hidden="true">3</span>
                <div class="cni-import-step__content">
                    <label for="cni-import-confirm">Confirmar</label>
                    <button type="submit" id="cni-import-confirm" class="cni-import-action cni-import-action--primary" disabled>Confirmar importación</button>
                </div>
            </div>
        </div>
        <p id="cni-import-message" class="cni-import-message" role="status" aria-live="polite" hidden></p>
        <noscript><p>Activa JavaScript para validar el archivo y confirmar su importación.</p></noscript>
    </form>
</section>
@if(session('importacion'))
<section class="cni-card cni-form-section" aria-labelledby="resultado-importacion">
    <h2 id="resultado-importacion">Resultado de la importación</h2>
    <pre class="cni-bank-resultado">{{ session('importacion') }}</pre>
</section>
@endif
<script>
(() => {
    const form = document.getElementById('cni-import-form');
    const file = document.getElementById('banco-excel');
    const select = document.getElementById('cni-import-select');
    const filename = document.getElementById('cni-import-filename');
    const validate = document.getElementById('cni-import-validate');
    const confirm = document.getElementById('cni-import-confirm');
    const token = document.getElementById('cni-import-token');
    const message = document.getElementById('cni-import-message');
    let busy = false;
    let expires = null;
    function tell(text, error = false) {
        message.textContent = text;
        message.hidden = !text;
        message.classList.toggle('is-error', error);
    }
    select.addEventListener('click', () => { if (!busy) file.click(); });
    file.addEventListener('change', () => {
        filename.textContent = file.files[0]?.name || 'No se ha seleccionado ningún archivo.';
        clearTimeout(expires);
        token.value = '';
        confirm.disabled = true;
        validate.disabled = !file.files.length;
        tell('');
    });
    validate.addEventListener('click', async () => {
        if (busy || !file.files.length) return;
        busy = true;
        clearTimeout(expires);
        validate.disabled = true;
        confirm.disabled = true;
        file.disabled = true;
        select.disabled = true;
        token.value = '';
        tell('Validando archivo…');
        const data = new FormData();
        data.append('archivo', file.files[0]);
        data.append('_token', form.querySelector('[name="_token"]').value);
        try {
            const response = await fetch(form.dataset.validarUrl, {
                method: 'POST', body: data, headers: {'Accept': 'application/json'}, credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.errors?.archivo?.[0] || result.message || 'No se pudo validar el archivo.');
            }
            token.value = result.validacion;
            confirm.disabled = false;
            let text = 'Archivo validado: ' + result.total + ' preguntas. Ya puedes confirmar la importación.';
            if (result.sin_clave || result.incompletas) {
                text += ' Por revisar: ' + result.sin_clave + ' sin clave y ' + result.incompletas + ' con imagen o alternativas incompletas.';
            }
            tell(text);
            expires = setTimeout(() => {
                token.value = '';
                confirm.disabled = true;
                tell('La validación venció. Pulsa Validar otra vez.');
            }, 14 * 60 * 1000);
        } catch (error) {
            tell(error.message || 'No se pudo validar. Inténtalo de nuevo.', true);
        } finally {
            busy = false;
            file.disabled = false;
            select.disabled = false;
            validate.disabled = !file.files.length;
        }
    });
    form.addEventListener('submit', event => {
        if (busy || !token.value) {
            event.preventDefault();
            tell('Valida el archivo antes de confirmar.', true);
            return;
        }
        busy = true;
        validate.disabled = true;
        confirm.disabled = true;
        select.disabled = true;
        confirm.textContent = 'Importando…';
    });
})();
</script>
@endsection
