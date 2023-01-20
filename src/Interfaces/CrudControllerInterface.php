<?php declare(strict_types = 1);

namespace Thiagoprz\CrudTools\Interfaces;

use Illuminate\Http\Request;

interface CrudControllerInterface
{
    public function getViewPath(bool $forRedirect = false): string;

    public function index(Request $request);

    public function destroy(Request $request, $id);

    public function edit($id);

    public function show(Request $request, $id);

    public function create();

    public function handleFileUploads(Request $request, ValidatesInterface $model = null): void;
}
