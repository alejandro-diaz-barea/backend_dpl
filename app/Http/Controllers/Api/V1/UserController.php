<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class UserController extends Controller
{
    public function __construct()
    {
        // Excluir la ruta store del middleware auth:api
        $this->middleware('auth:api', ['except' => ['store']]);
    }

    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'address' => 'required|string',
                'phone_number' => 'required|string|unique:users,phone_number',
            ]);

            $data['password'] = Hash::make($data['password']);

            // Establecer el valor predeterminado para logo_path si no se proporciona
            $data['logo_path'] = $request->input('logo_path', 'storage/user_photos/default/profile-user.png');

            $user = User::create($data);
            return response()->json(['message' => 'Cuenta creada con éxito', 'user' => $user], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación', 'message' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear la cuenta', 'message' => $e->getMessage()], 500);
        }
    }


    public function uploadPhoto(Request $request)
    {
        try {
            // Validar que se haya enviado una imagen
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Obtener el usuario autenticado
            $user = Auth::user();

            // Obtener la imagen del formulario
            $photo = $request->file('photo');

            // Eliminar la imagen anterior si existe
            try {
                $previousPhotoPath = $user->logo_path;
                if ($previousPhotoPath) {
                    Storage::delete($previousPhotoPath);
                }
            } catch (\Exception $e) {
                // Manejar la excepción
                return response()->json(['error' => 'Error al eliminar la imagen anterior', 'message' => $e->getMessage()], 500);
            }

            // Crear una nueva carpeta para el usuario
            $userFolderPath = 'user_photos/' . $user->id . '_' . Str::slug($user->name);
            Storage::makeDirectory($userFolderPath);

            // Generar un nombre único para la imagen
            $imageName = time() . '_' . $photo->getClientOriginalName();

            // Almacenar la imagen en la carpeta del usuario con un nombre único
            $photoPath = $photo->storeAs($userFolderPath, $imageName, 'public');

            // Actualizar el campo logo_path del usuario con la ruta de la imagen
            $user->logo_path = 'storage/' . $photoPath;
            $user->save();

            return response()->json(['message' => 'Imagen subida correctamente', 'photo_path' => $photoPath, 'user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al subir la imagen', 'message' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request)
    {
        try {
            // Obtener el usuario autenticado
            $user = Auth::user();

            // Validar los datos de entrada
            $data = $request->validate([
                'name' => 'sometimes|required|string',
                'address' => 'sometimes|required|string',
            ]);

            // Actualizar los campos proporcionados
            $user->update($data);

            // Retorna la respuesta con el token incluido en los encabezados
            return response()->json([
                'message' => 'Usuario actualizado con éxito',
                'user' => $user,
                'access_token' => auth()->tokenById($user->id),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación', 'message' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el usuario', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        User::destroy($id);
        return response()->json(null, 204);
    }


    public function indexAdmin()
    {
        // Verifica si el usuario autenticado es superusuario
        $user = Auth::user();
        if (!$user->is_super) {
            return response()->json(['error' => 'No tienes permiso para realizar esta acción'], 403);
        }

        // Obtiene todos los usuarios excepto el usuario autenticado
        $users = User::where('id', '!=', $user->id)->get();

        // Agrega la información sobre si el usuario está baneado o no
        $users->each(function ($user) {
            $user->is_banned = $user->is_banned ? true : false;
        });

        return response()->json($users);
    }





    public function banUser($id)
    {
        $currentUser = Auth::user();
        if (!$currentUser->is_super) {
            return response()->json(['error' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $user = User::findOrFail($id);

        // Si el usuario ya está baneado, lo desbaneamos
        if ($user->is_banned) {
            $user->is_banned = false;
            $user->save();
            return response()->json(['message' => 'Usuario desbaneado con éxito'], 200);
        }

        $user->is_banned = true;
        $user->save();

        return response()->json(['message' => 'Usuario baneado con éxito'], 200);
    }


    public function changeUserRole(Request $request, $id)
{
    try {
        $currentUser = Auth::user();
        if (!$currentUser->is_super) {
            return response()->json(['error' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $request->validate([
            'is_super' => 'required|boolean'
        ]);

        $user = User::findOrFail($id);
        $user->is_super = $request->is_super;
        $user->save();

        return response()->json(['message' => 'Rol de usuario cambiado con éxito'], 200);
    } catch (ValidationException $e) {
        return response()->json(['error' => 'Error de validación', 'message' => $e->validator->errors()], 422);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al cambiar el rol del usuario', 'message' => $e->getMessage()], 500);
    }
}



}
