<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Retorna todas las categorías
    public function index(Request $request)
    {
        $query = $request->input('query');
        $categories = Category::where('categoryname', 'like', "%$query%")->take(5)->get();
        return response()->json($categories);
    }

    // Retorna una categoría específica por su ID
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    // Crea una nueva categoría
    public function store(Request $request)
    {
        $category = Category::create($request->all());
        return response()->json($category, 201);
    }

    // Actualiza una categoría existente por su ID
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->all());
        return response()->json($category, 200);
    }

    // Elimina una categoría existente por su ID
    public function destroy($id)
    {
        Category::destroy($id);
        return response()->json(null, 204);
    }
}
