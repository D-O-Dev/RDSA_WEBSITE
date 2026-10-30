<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', Rule::in(['new', 'read', 'done', 'archived'])]]);
        $messages = ContactMessage::query()->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where(fn ($m) => $m->where('name', 'like', '%' . $search . '%')
                ->orWhere('subject', 'like', '%' . $search . '%')->orWhere('reference', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')->orWhere('phone', 'like', '%' . $search . '%')))
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString();
        return response()->view('admin.messages.index', compact('messages'))->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, ContactMessage $message)
    {
        ContactMessage::whereKey($message->id)->where('status', 'new')->update(['status' => 'read']);
        $message->refresh()->load('replies');
        if ($request->expectsJson()) return response()->json([
            'html' => view('messages.thread', compact('message'))->render(),
            'version' => $message->status . ':' . ($message->replies->last()?->id ?? 0),
        ])->header('Cache-Control', 'private, no-store');
        return response()->view('admin.messages.show', compact('message'))->header('Cache-Control', 'private, no-store');
    }

    public function reply(Request $request, ContactMessage $message)
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);
        DB::transaction(function () use ($message, $data, $request) {
            $message = ContactMessage::whereKey($message->id)->lockForUpdate()->firstOrFail();
            $message->replies()->create(['body' => $data['body'], 'author_type' => 'admin', 'user_id' => $request->user()->id]);
            $message->update(['status' => 'done']);
        });
        if ($request->expectsJson()) return response()->json(['message' => 'Réponse publiée dans la conversation privée.']);
        return redirect()->route('admin.messages.show', $message)->with('status', 'Réponse publiée.');
    }

    public function archive(ContactMessage $message)
    {
        $message->update(['status' => 'archived']);
        return redirect()->route('admin.messages.index')->with('status', 'Conversation archivée.');
    }

    public function count()
    {
        return response()->json(['count' => ContactMessage::where('status', 'new')->count()])->header('Cache-Control', 'private, no-store');
    }
}
