<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Repositories\UsuarioRepository;
use App\Services\AdminCatalogService;
use App\Services\AuditService;
use App\Services\PasswordSecurityService;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    private UsuarioRepository $usuarioRepo;

    public function __construct(UsuarioRepository $usuarioRepo)
    {
        $this->usuarioRepo = $usuarioRepo;
    }

    public function index()
    {
        return view('admin.usuarios.index', [
            'usuarios' => DB::table('usuario')
                ->leftJoin('programa', 'programa.cod', '=', 'usuario.programa')
                ->where('usuario.tipo', '!=', 1)
                ->orderBy('usuario.id')
                ->select('usuario.*', 'programa.nombre as programa_nombre')
                ->get(),
            'programas' => DB::table('programa')->orderBy('nombre')->get(),
        ]);
    }

    public function store(
        StoreUsuarioRequest $request,
        AdminCatalogService $service,
        PasswordSecurityService $passwords,
        AuditService $auditService
    ) {
        $data = $request->validated();

        $firma = $service->storeSignature($request->file('fi'), $data['pr'], $data['no']);

        $this->usuarioRepo->createWithPassword([
            'id' => $data['no'],
            'tipo' => 2,
            'programa' => $data['pr'],
            'firma' => $firma,
        ], $passwords->make($data['co']));

        $auditService->log('usuario.created', [
            'usuario_id' => $data['no'],
            'programa' => $data['pr'],
            'tipo' => 2,
        ]);

        return back()->with('status', 'Usuario creado correctamente.');
    }
}
