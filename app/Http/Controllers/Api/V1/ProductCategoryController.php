<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    // Retorna todas las relaciones entre productos y categorías
    public function index()
    {
        $productCategories = ProductCategory::all();
        return response()->json($productCategories);
    }

    // Retorna una relación específica entre producto y categoría por su ID
    public function show($id)
    {
        $productCategory = ProductCategory::findOrFail($id);
        return response()->json($productCategory);
    }

    // Crea una nueva relación entre producto y categoría
    public function store(Request $request)
    {
        $productCategory = ProductCategory::create($request->all());
        return response()->json($productCategory, 201);
    }

    // Actualiza una relación entre producto y categoría existente por su ID
    public function update(Request $request, $id)
    {
        $productCategory = ProductCategory::findOrFail($id);
        $productCategory->update($request->all());
        return response()->json($productCategory, 200);
    }

    // Elimina una relación entre producto y categoría existente por su ID
    public function destroy($id)
    {
        ProductCategory::destroy($id);
        return response()->json(null, 204);
    }
}
