
@extends('layouts.app')

@section('content')
<div class="exam-shell">

    <aside class="exam-sidebar">
        <a class="exam-brand" href="{{ route('panel') }}">
            <div class="exam-logo">CNI</div>
            <div>
                <strong>CNI</strong>
                <small>Portal del alumno</small>
            </div>
        </a>

        <p class="sidebar-label">MI PORTAL</p>

        <nav class="exam-nav">
            <a href="{{ route('panel') }}">⌂ &nbsp; Inicio</a>
            <a href="{{ route('panel') }}">♙ &nbsp; Mi perfil</a>
            <a class="active" href="{{ route('examen.demo') }}">▣ &nbsp; Simulador</a>
            <a href="{{ route('panel') }}">▤ &nbsp; Oferta formativa</a>
            <a href="{{ route('panel') }}">▧ &nbsp; Recursos</a>
            <a href="{{ route('panel') }}">ⓘ &nbsp; Ayuda y contacto</a>
        </nav>

        <div class="sidebar-help">
            <strong>¿Necesitas ayuda?</strong>
            <p>Consulta los recursos del portal o solicita apoyo.</p>
            <a href="mailto:soporte@cni.edu.pe">Contactar con CNI →</a>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar">
                {{ strtoupper(substr(auth('web')->user()->nombres ?? 'A', 0, 1)) }}
            </div>
            <div>
                <strong>{{ auth('web')->user()->nombres ?? 'Alumno' }}</strong>
                <small>Portal del alumno</small>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-logout">↪ Cerrar sesión</button>
        </form>
    </aside>

    <div class="exam-main">
        <header class="exam-topbar">
            <div>Portal del alumno / Simulador / Examen de demostración</div>
            <span>◷ Ayuda</span>
        </header>

        <section class="exam-heading">
            <div>
                <h1>Simulador de examen</h1>
                <p>Simulacro de demostración · Interfaz preliminar</p>
            </div>
            <span class="exam-status">● Maqueta visual</span>
        </section>

        <section class="exam-summary">
            <div class="summary-icon">▤</div>

            <div class="summary-name">
                <strong>Simulacro de admisión CNI</strong>
                <small>40 preguntas · Tiempo referencial: 80 minutos</small>
            </div>

            <div class="summary-progress">
                <div class="progress-labels">
                    <strong>Progreso de demostración</strong>
                    <span id="exam-progress">0 %</span>
                </div>
                <div class="progress-track">
                    <span id="progress-bar"></span>
                </div>
            </div>

            <div class="summary-timer">
                <small>Tiempo de ejemplo</small>
                <strong>00:42:18</strong>
            </div>
        </section>

        <div class="exam-columns">
            <section class="question-card">
                <div class="question-header">
                    <strong>Pregunta <span id="question-number">1</span></strong>
                    <span>de 40</span>
                    <span class="question-tag">▣ Razonamiento matemático</span>
                </div>

                <div class="question-body">
                    <small>Lee con atención y selecciona una sola respuesta.</small>

                    <h2 id="question-title">
                        En una clase de 40 estudiantes, el 60 % son mujeres.
                        Si se incorporan 10 varones, ¿qué porcentaje del total
                        representan ahora las mujeres?
                    </h2>

                    <div class="answer-list" id="answer-list">
                        <button type="button" class="answer-option" data-answer="A">
                            <span class="answer-radio"></span>
                            <span>A.</span>
                            <span>40 %</span>
                            <span class="answer-check"></span>
                        </button>

                        <button type="button" class="answer-option" data-answer="B">
                            <span class="answer-radio"></span>
                            <span>B.</span>
                            <span>48 %</span>
                            <span class="answer-check"></span>
                        </button>

                        <button type="button" class="answer-option" data-answer="C">
                            <span class="answer-radio"></span>
                            <span>C.</span>
                            <span>50 %</span>
                            <span class="answer-check"></span>
                        </button>

                        <button type="button" class="answer-option" data-answer="D">
                            <span class="answer-radio"></span>
                            <span>D.</span>
                            <span>60 %</span>
                            <span class="answer-check"></span>
                        </button>
                    </div>

                    <div class="question-actions">
                        <label class="review-toggle">
                            <input type="checkbox" id="review-check">
                            <span>⚑ Marcar para revisar</span>
                        </label>
                        <span id="save-status">○ Sin selección</span>
                    </div>
                </div>

                <div class="question-footer">
                    <button type="button" class="exam-button" id="previous-question">
                        ← Anterior
                    </button>

                    <div class="question-footer-right">
                        <button type="button" class="text-button" id="skip-question">
                            Omitir pregunta
                        </button>
                        <button type="button" class="exam-button primary-button" id="next-question">
                            Siguiente →
                        </button>
                    </div>
                </div>
            </section>

            <aside class="question-map-card">
                <h3>Mapa de preguntas <span>▦</span></h3>
                <p>Selecciona un número para navegar.</p>

                <div class="question-grid" id="question-grid"></div>

                <div class="map-legend">
                    <span><i class="dot answered"></i> Respondidas</span>
                    <span><i class="dot review"></i> Para revisar</span>
                    <span><i class="dot pending"></i> Sin responder</span>
                </div>

                <button type="button" class="finish-button" id="finish-exam">
                    Finalizar demostración ↗
                </button>

                <small class="map-note">
                    Maqueta visual: las respuestas no se envían al servidor.
                </small>
            </aside>
        </div>

        <footer class="exam-footer">
            <span>CNI · Portal del alumno</span>
            <span>Interfaz preliminar</span>
        </footer>
    </div>
</div>

<div class="demo-modal" id="demo-modal" hidden>
    <div class="demo-modal-content">
        <h2>Fin de la demostración</h2>
        <p>
            Esta pantalla es una maqueta visual. Las respuestas no se han
            guardado en la base de datos ni se ha finalizado un intento real.
        </p>
        <button type="button" class="exam-button primary-button" id="close-modal">
            Volver al examen
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const total = 40;
    let current = 1;

    const answers = {};
    const reviews = new Set();

    const grid = document.getElementById('question-grid');
    const number = document.getElementById('question-number');
    const progress = document.getElementById('exam-progress');
    const progressBar = document.getElementById('progress-bar');
    const reviewCheck = document.getElementById('review-check');
    const saveStatus = document.getElementById('save-status');
    const options = [...document.querySelectorAll('.answer-option')];

    // Crear el mapa de las 40 preguntas.
    for (let i = 1; i <= total; i++) {
        const button = document.createElement('button');

        button.type = 'button';
        button.textContent = String(i).padStart(2, '0');
        button.className = 'question-number';
        button.setAttribute('aria-label', 'Ir a la pregunta ' + i);

        button.addEventListener('click', function () {
            showQuestion(i);
        });

        grid.appendChild(button);
    }

    function renderGrid() {
        [...grid.children].forEach(function (button, index) {
            const n = index + 1;

            button.className = 'question-number';

            if (answers[n]) {
                button.classList.add('is-answered');
            }

            if (reviews.has(n)) {
                button.classList.add('is-review');
            }

            if (n === current) {
                button.classList.add('is-current');
            }
        });

        const count = Object.keys(answers).length;
        const percentage = Math.round((count / total) * 100);

        progress.textContent = percentage + ' %';
        progressBar.style.width = percentage + '%';
    }

    function showQuestion(n) {
        current = Math.max(1, Math.min(total, n));
        number.textContent = current;

        options.forEach(function (option) {
            const selected = answers[current] === option.dataset.answer;

            option.classList.toggle('selected', selected);

            const check = option.querySelector('.answer-check');
            check.textContent = selected ? '✓' : '';
        });

        reviewCheck.checked = reviews.has(current);

        saveStatus.textContent = answers[current]
            ? '○ Selección local de demostración'
            : '○ Sin selección';

        renderGrid();
    }

    // Seleccionar una alternativa.
    options.forEach(function (option) {
        option.addEventListener('click', function () {
            answers[current] = option.dataset.answer;
            showQuestion(current);
        });
    });

    // Marcar o desmarcar para revisar.
    reviewCheck.addEventListener('change', function () {
        if (reviewCheck.checked) {
            reviews.add(current);
        } else {
            reviews.delete(current);
        }

        renderGrid();
    });

    document.getElementById('previous-question').addEventListener('click', function () {
        showQuestion(current - 1);
    });

    document.getElementById('next-question').addEventListener('click', function () {
        showQuestion(current + 1);
    });

    document.getElementById('skip-question').addEventListener('click', function () {
        showQuestion(current + 1);
    });

    document.getElementById('finish-exam').addEventListener('click', function () {
        document.getElementById('demo-modal').hidden = false;
    });

    document.getElementById('close-modal').addEventListener('click', function () {
        document.getElementById('demo-modal').hidden = true;
    });

    showQuestion(current);
});
</script>
@endsection
