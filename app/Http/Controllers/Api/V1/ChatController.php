<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $user_id = Auth::id();

        $chats = Chat::with(['user1' => function ($query) {
                        $query->select('id', 'name', 'logo_path'); // Seleccionar solo las columnas necesarias
                    }, 'user2' => function ($query) {
                        $query->select('id', 'name', 'logo_path'); // Seleccionar solo las columnas necesarias
                    }])
                     ->where('idusuario1', $user_id)
                     ->orWhere('idusuario2', $user_id)
                     ->get();

        return response()->json($chats);
    }

    public function show($id)
    {
        $chat = Chat::with(['user1' => function ($query) {
                        $query->select('id', 'name', 'logo_path'); // Seleccionar solo las columnas necesarias
                    }, 'user2' => function ($query) {
                        $query->select('id', 'name', 'logo_path'); // Seleccionar solo las columnas necesarias
                    }])
                    ->findOrFail($id);

        return response()->json($chat);
    }

    public function store(Request $request)
    {
        $user1_id = Auth::id();
        $user2_id = $request->user2_id;

        // Validar que el usuario no esté intentando chatear consigo mismo
        if ($user1_id === $user2_id) {
            return response()->json(['message' => 'You cannot chat with yourself.'], 422);
        }

        $request->validate([
            'user2_id' => 'required|exists:users,id',
        ]);

        $existingChat = Chat::where('idusuario1', $user1_id)
                            ->where('idusuario2', $user2_id)
                            ->orWhere(function ($query) use ($user1_id, $user2_id) {
                                $query->where('idusuario1', $user2_id)
                                      ->where('idusuario2', $user1_id);
                            })
                            ->first();

        if ($existingChat) {
            return response()->json(['message' => 'A chat already exists between these users.'], 422);
        }

        $chat = Chat::create([
            'idusuario1' => $user1_id,
            'idusuario2' => $user2_id,
        ]);

        return response()->json($chat, 201);
    }

    public function update(Request $request, $id)
    {
        $chat = Chat::findOrFail($id);
        $chat->update($request->all());
        return response()->json($chat, 200);
    }

    public function destroy($id)
    {
        Chat::destroy($id);
        return response()->json(null, 204);
    }
}
