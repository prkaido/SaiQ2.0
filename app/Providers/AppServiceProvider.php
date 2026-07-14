<?php

namespace App\Providers;

use App\Observers\AsignaturaObserver;
use App\Observers\ProgramaObserver;
use App\Observers\UsuarioObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerObservers();
    }

    /**
     * Registra los observers para auditoría automática de cambios.
     */
    private function registerObservers(): void
    {
        // Los observers se registran directamente en la BD mediante eventos de DB
        // Alternativamente, si se usan Eloquent Models, registrar aquí:
        // Usuario::observe(UsuarioObserver::class);
        // Programa::observe(ProgramaObserver::class);
        // Asignatura::observe(AsignaturaObserver::class);
    }
}
