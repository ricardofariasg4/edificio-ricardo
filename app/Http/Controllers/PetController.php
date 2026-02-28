<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PetService;

class PetController extends Controller
{
    protected $petService;

    public function __construct(PetService $petService)
    {
        $this->petService = $petService;
    }

    public function index()
    {
        return $this->petService->getAllPets();
    }

    public function show($id)
    {
        return $this->petService->getPetById($id);
    }

    public function store(Request $request)
    {
        return $this->petService->createPet($request->all());
    }

    public function update(Request $request, $id)
    {
        return $this->petService->updatePet($id, $request->all());
    }

    public function destroy($id)
    {
        return $this->petService->deletePet($id);
    }
}
