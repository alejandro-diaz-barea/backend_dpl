<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // Paginación y búsqueda de productos
    public function index(Request $request)
    {
        $search = $request->input('search');
        $orderBy = $request->input('orderby');
        $categories = $request->input('categories', []);

        // Asegurarse de que $categories sea un array
        if (!is_array($categories)) {
            $categories = explode(',', $categories);
        }

        $query = Product::query()->with('categories');

        // Aplicar búsqueda por nombre o descripción
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordenar resultados
        if ($orderBy === 'price') {
            $query->orderBy('price', 'asc');
        } elseif ($orderBy === 'title') {
            $query->orderBy('name', 'asc');
        }

        // Filtrar por categorías
        if (!empty($categories)) {
            $query->whereHas('categories', function ($query) use ($categories) {
                $query->whereIn('categories.id', $categories);
            });
        }

        // Paginar resultados
        $products = $query->paginate(8);

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No se encontraron productos con los criterios de búsqueda especificados.'], 404);
        }

        return response()->json([
            'data' => $products,
            'total_pages' => $products->lastPage()
        ]);
    }

    // Obtener productos de un usuario específico
    public function getUserProducts(Request $request)
    {
        // Obtener el ID del usuario autenticado
        $userId = auth()->id();

        // Obtener los productos del usuario
        $products = Product::where('seller_id', $userId)->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'El usuario no tiene productos asociados.'], 404);
        }

        return response()->json(['data' => $products]);
    }

    // Crear producto
    public function store(Request $request)
    {
        // Obtener el ID del usuario autenticado
        $sellerId = auth()->id();

        // Validar los datos de la solicitud
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'categories' => 'required|array',
        ]);

        // Crear el nuevo producto en la base de datos sin imágenes
        $product = Product::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'seller_id' => $sellerId,
            'date' => DB::raw('CURRENT_TIMESTAMP'),
            'image_path' => '',
        ]);

        $imagePaths = [];

        // Guardar cada imagen en una subcarpeta del producto
        foreach ($request->file('images') as $image) {
            // Crear una subcarpeta descriptiva para cada producto
            $productImageFolder = 'public/product_images/' . $product->id . '_' . Str::slug($product->name);
            $imageName = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs($productImageFolder, $imageName);
            $imagePaths[] = Storage::url($path); // Obtener la URL pública
        }

        // Actualizar el producto con las rutas de las imágenes
        $product->image_path = json_encode($imagePaths); // Guardar las rutas como un JSON
        $product->save();

        // Asociar las categorías al producto
        $categories = $request->input('categories');
        foreach ($categories as $categoryName) {
            // Crear la categoría si no existe
            $category = Category::firstOrCreate(['CategoryName' => $categoryName]);

            // Asociar la categoría al producto
            ProductCategory::create([
                'product_id' => $product->id,
                'category_id' => $category->id,
            ]);
        }

        return response()->json($product, 201);
    }

    // Actualizar producto
    public function update(Request $request, $id)
{
    // Obtener el producto por ID
    $product = Product::findOrFail($id);

    // Verificar si el usuario autenticado es el propietario del producto
    if ($request->user()->id !== $product->seller_id) {
        return response()->json(['error' => 'No tienes permiso para modificar este producto.'], 403);
    }

    // Validar los datos de la solicitud
    $validatedData = $request->validate([
        'name' => 'required|string',
        'description' => 'required|string',
        'price' => 'required|numeric',
        'images' => 'nullable|array',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        'categories' => 'required|array',
    ]);

    // Actualizar el producto con los nuevos datos
    $product->update([
        'name' => $validatedData['name'],
        'description' => $validatedData['description'],
        'price' => $validatedData['price'],
    ]);

    // Actualizar categorías
    $categories = $validatedData['categories'];
    $product->categories()->detach();
    foreach ($categories as $category) {
        $product->categories()->attach($category);
    }

    // Actualizar imágenes
    if ($request->hasFile('images')) {
        $this->updateImages($request->file('images'), $product);
    }

    return response()->json($product, 200);
    }

    // Método para actualizar imágenes
    private function updateImages($images, $product)
{
    // Eliminar imágenes antiguas
    $imagePaths = json_decode($product->image_path, true) ?? [];
    foreach ($imagePaths as $imagePath) {
        $this->deleteImage($imagePath);
    }

    // Agregar nuevas imágenes
    $newImagePaths = [];
    foreach ($images as $image) {
        $newImagePaths[] = $this->storeImage($image, $product);
    }

    // Actualizar las rutas de las imágenes
    $product->image_path = json_encode($newImagePaths);
    $product->save();
}

// Método para eliminar una imagen
private function deleteImage($imagePath)
{
    $imagePath = str_replace('/storage', 'public', $imagePath);
    if (Storage::exists($imagePath)) {
        Storage::delete($imagePath);
    }
}

// Método para guardar una imagen
private function storeImage($image, $product)
{
    $productImageFolder = 'public/product_images/' . $product->id . '_' . Str::slug($product->name);
    $imageName = time() . '_' . $image->getClientOriginalName();
    $path = $image->storeAs($productImageFolder, $imageName);
    return Storage::url($path);
}



    // Mostrar producto
    public function show($id)
    {
        $product = Product::with('categories')->findOrFail($id);
        return response()->json($product);
    }


   // Eliminar producto
   public function destroy(Request $request, $id)
   {
       // Obtener el ID del usuario autenticado
       $userId = auth()->id();
       $user = auth()->user();

       // Verificar si el usuario es propietario del producto o superusuario
       $product = Product::find($id);
       if (!$product) {
           return response()->json(['message' => 'Producto no encontrado.'], 404);
       }

       if ($product->seller_id !== $userId && !$user->is_super) {
           return response()->json(['message' => 'No tienes permiso para eliminar este producto.'], 403);
       }

       // Eliminar las relaciones con las categorías
       $product->categories()->detach();

       // Eliminar las imágenes asociadas al producto
       $imagePaths = json_decode($product->image_path, true);
       foreach ($imagePaths as $imagePath) {
           // Obtener la ruta de la imagen
           $imagePath = str_replace('/storage', 'public', $imagePath);
           // Eliminar la imagen si existe
           if (Storage::exists($imagePath)) {
               Storage::delete($imagePath);
           }
       }

       // Eliminar el producto
       $product->delete();

       return response()->json(null, 204);
   }

    // Cargar imágenes adicionales
    public function uploadImage(Request $request, $id)
    {
        // Validar que el producto exista
        $product = Product::findOrFail($id);

        // Validar que se haya enviado una imagen en la solicitud
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePaths = json_decode($product->image_path, true);

        // Guardar cada imagen en una subcarpeta del producto
        foreach ($request->file('images') as $image) {
            $productImageFolder = 'public/product_images/' . $product->id . '_' . Str::slug($product->name);
            $imageName = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs($productImageFolder, $imageName);
            $imagePaths[] = Storage::url($path);
        }

        // Actualizar el producto con las nuevas rutas de las imágenes
        $product->image_path = json_encode($imagePaths);
        $product->save();

        return response()->json(['message' => 'Imágenes cargadas con éxito']);
    }
}
