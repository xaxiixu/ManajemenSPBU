<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function data()
    {
        $user = auth()->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->limit(10)->get()->map(fn ($n) => [
                'id'     => $n->id,
                'pesan'  => $n->data['pesan'] ?? '',
                'url'    => $n->data['url'] ?? route('dashboard'),
                'dibaca' => $n->read_at !== null,
                'waktu'  => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function buka(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    public function bacaSemua()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }
}
