<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pusher\Pusher;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $user_id = Auth::id();
        $chat_id = $request->input('chat_id');

        $chat = Chat::where('id', $chat_id)
                    ->where(function ($query) use ($user_id) {
                        $query->where('idusuario1', $user_id)
                              ->orWhere('idusuario2', $user_id);
                    })
                    ->first();

        if (!$chat) {
            return response()->json(['error' => 'El chat no existe o no tienes permiso para acceder a él.'], 403);
        }

        $messages = Message::where('IDChat', $chat_id)->get();
        return response()->json($messages);
    }

    public function show($id)
    {
        $message = Message::findOrFail($id);
        return response()->json($message);
    }

    public function store(Request $request)
    {
        $user_id = Auth::id();

        $request->validate([
            'IDChat' => 'required|exists:chats,id',
            'content' => 'required|string|max:255',
        ]);

        // Verificar si el ID del chat pertenece al usuario autenticado
        $chat = Chat::where('id', $request->IDChat)
                    ->where(function ($query) use ($user_id) {
                        $query->where('idusuario1', $user_id)
                              ->orWhere('idusuario2', $user_id);
                    })
                    ->first();

        if (!$chat) {
            return response()->json(['error' => 'No tienes permiso para enviar mensajes en este chat.'], 403);
        }

        $message = Message::create([
            'IDChat' => $request->IDChat,
            'contain' => $request->content,
            'sender_id' => $user_id, // Añadimos el campo sender_id
        ]);

        // Configuración de Pusher usando variables de entorno
        $pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'useTLS' => config('broadcasting.connections.pusher.options.useTLS'),
            ]
        );

        // Enviar evento a Pusher
        $pusher->trigger("chat.{$request->IDChat}", 'message-sent', $message);

        return response()->json($message, 201);
    }


    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        $message->update($request->all());
        return response()->json($message, 200);
    }

    public function destroy($id)
    {
        Message::destroy($id);
        return response()->json(null, 204);
    }
}
