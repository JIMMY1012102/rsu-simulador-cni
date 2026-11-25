<?php

namespace App\\Http\\Controllers\\Alumno;

use Illuminate\\Support\\Facades\\Cache;
use Illuminate\\Support\\Facades\\Http;

class AcademiasController
{
    public function __invoke(string $canal)
    {
        abort_unless(auth()->user()?->rol === 'estudiante', 403);

        $canalesPermitidos = collect(config('panel_alumno.academias', []))
            ->pluck('canal_id')
            ->filter(fn ($id) => is_string($id))
            ->all();

        if (!in_array($canal, $canalesPermitidos, true)) {
            return response()->json([], 404);
        }

        $clave = 'yt_ultimo_video_'.$canal;

        try {
            $resultado = Cache::get($clave);

            if ($resultado !== null) {
                if ($resultado === false) {
                    return response()->json([], 404);
                }

                return is_array($resultado)
                    ? response()->json($resultado, 200)
                    : response()->json([], 404);
            }

            $resultado = $this->consultarUltimoVideo($canal);

            if ($resultado === false) {
                Cache::put($clave, false, now()->addMinutes(10));

                return response()->json([], 404);
            }

            Cache::put($clave, $resultado, now()->addHours(6));

            return response()->json($resultado, 200);
        } catch (\\Throwable $error) {
            try {
                Cache::put($clave, false, now()->addMinutes(10));
            } catch (\\Throwable $ignored) {
                // No exponer fallos de cache al usuario.
            }

            return response()->json([], 404);
        }
    }

    private function consultarUltimoVideo(string $canal): array|false
    {
        try {
            $respuesta = Http::timeout(4)
                ->connectTimeout(3)
                ->get('https://www.youtube.com/feeds/videos.xml', [
                    'channel_id' => $canal,
                ]);

            if (!$respuesta->successful() || $respuesta->status() !== 200) {
                return false;
            }

            $cuerpo = $respuesta->body();
            if (strlen($cuerpo) > 512 * 1024) {
                return false;
            }

            $usoInternoPrevio = libxml_use_internal_errors(true);
            try {
                $xml = simplexml_load_string($cuerpo, \\SimpleXMLElement::class, LIBXML_NONET);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($usoInternoPrevio);
            }

            if ($xml === false) {
                return false;
            }

            $atom = $xml->children('http://www.w3.org/2005/Atom');
            $entrada = $atom->entry[0] ?? null;
            if (!$entrada) {
                return false;
            }

            $youtube = $entrada->children('http://www.youtube.com/xml/schemas/2015');
            $videoId = trim((string) ($youtube->videoId ?? ''));
            if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
                return false;
            }

            $titulo = trim(strip_tags((string) ($entrada->children('http://www.w3.org/2005/Atom')->title ?? '')));
            $titulo = mb_substr($titulo, 0, 120);

            return ['video_id' => $videoId, 'titulo' => $titulo];
        } catch (\\Throwable $error) {
            return false;
        }
    }
}
