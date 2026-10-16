namespace Tests\Feature;

use App\Http\Requests\Resultados\CalificarIntentoRequest;
use App\Models\Usuario;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ResultadosModuleTest extends TestCase
{
    public function test_el_request_rechaza_un_intento_vacio(): void
    {
        $request = new CalificarIntentoRequest();

        $validator = Validator::make(
            ['intento_id' => null],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'intento_id',
            $validator->errors()->toArray()
        );
    }

    public function test_el_request_rechaza_un_id_negativo(): void
    {
        $request = new CalificarIntentoRequest();

        $validator = Validator::make(
            ['intento_id' => -5],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_el_request_rechaza_un_id_no_numerico(): void
    {
        $request = new CalificarIntentoRequest();

        $validator = Validator::make(
            ['intento_id' => 'abc'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_el_request_requiere_autenticacion(): void
    {
        $request = new CalificarIntentoRequest();

        $this->assertFalse($request->authorize());
    }

    public function test_el_request_acepta_un_usuario_autenticado(): void
    {
        $usuario = new Usuario();
        $usuario->id = 1;

        $request = new CalificarIntentoRequest();
        $request->setUserResolver(fn () => $usuario);

        $this->assertTrue($request->authorize());
    }
}
