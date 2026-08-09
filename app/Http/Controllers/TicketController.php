<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isMerchant()) {
            $tickets = Ticket::where('merchant_id', $user->id)->orderByDesc('last_message_at')->get();
        } else {
            $tickets = Ticket::where('user_id', $user->id)->orderByDesc('last_message_at')->get();
        }

        return view('tickets_index', compact('tickets'));
    }

    public function create()
    {
        return view('tickets_create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required_without:attachment|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:5120',
        ]);

        $user = $request->user();

        $ticket = new Ticket();
        $ticket->subject = $request->input('subject');
        if ($user->isMerchant()) {
            $ticket->merchant_id = $user->id;
        } else {
            $ticket->user_id = $user->id;
        }
        $ticket->status = 'open';
        $ticket->last_message_at = now();
        $ticket->save();

        $msg = new TicketMessage();
        $msg->ticket_id = $ticket->id;
        $msg->sender_type = $user->isAdmin() ? 'admin' : ($user->isMerchant() ? 'merchant' : 'user');
        $msg->sender_id = $user->id;
        $msg->body = $request->input('message');

        // Handle optional attachment on initial ticket creation
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('ticket_attachments', 'public');
            $msg->attachment = $path;
        }

        $msg->save();

        return redirect()->route('tickets.show', $ticket->id)->with('success', __('tickets.store.success'));
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Authorization: allow owner merchant/user or admin
        if (!($user->isAdmin() || $ticket->user_id === $user->id || $ticket->merchant_id === $user->id)) {
            abort(403);
        }

        $messages = $ticket->messages()->get();
        return view('tickets_show', compact('ticket', 'messages'));
    }

    public function close(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (!($user->isAdmin() || $ticket->user_id === $user->id || $ticket->merchant_id === $user->id)) {
            abort(403);
        }

        $ticket->status = 'closed';
        $ticket->save();

        return redirect()->route('tickets.show', $ticket->id)->with('success', __('tickets.close.success'));
    }

    // JSON endpoint for ticket messages (user side)
    public function messages(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        if (!($user->isAdmin() || $ticket->user_id === $user->id || $ticket->merchant_id === $user->id)) {
            abort(403);
        }

        $messages = $ticket->messages()->get()->map(function ($m) {
            $attachmentUrl = null;
            if ($m->attachment) {
                // Prefer Storage::url, but fall back to asset() if needed
                try {
                    $attachmentUrl = Storage::url($m->attachment);
                } catch (\Throwable $e) {
                    $attachmentUrl = asset('storage/' . ltrim($m->attachment, '/'));
                }
            }

            return [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender_id ? optional(\App\Models\User::find($m->sender_id))->name : null,
                'body' => $m->body,
                'attachment' => $attachmentUrl,
                'created_at' => $m->created_at->toDateTimeString(),
            ];
        });

        return response()->json($messages);
    }

    public function postMessage(Request $request, Ticket $ticket)
    {
        $request->validate(['message' => 'required_without:attachment|string','attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:5120']);
        $user = $request->user();

        // Debug logging to help diagnose attachment upload issues from user/merchant
        \Illuminate\Support\Facades\Log::info('TicketController::postMessage', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id ?? null,
            'has_file' => $request->hasFile('attachment'),
            'file_keys' => array_keys($request->files->all()),
        ]);

        if (!($user->isAdmin() || $ticket->user_id === $user->id || $ticket->merchant_id === $user->id)) {
            abort(403);
        }

        if ($ticket->status === 'closed' && !$user->isAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => __('tickets.closed_error')], 400);
            }

            return back()->withErrors(['message' => __('tickets.closed_error')])->withInput();
        }

        $msg = new TicketMessage();
        $msg->ticket_id = $ticket->id;
        $msg->sender_type = $user->isAdmin() ? 'admin' : ($user->isMerchant() ? 'merchant' : 'user');
        $msg->sender_id = $user->id;
        $msg->body = $request->input('message');

        // Handle optional attachment for subsequent messages
        if ($request->hasFile('attachment')) {
            // Log file info
            \Illuminate\Support\Facades\Log::info('TicketController::postMessage - uploading attachment', [
                'original_name' => $request->file('attachment')->getClientOriginalName(),
                'mime' => $request->file('attachment')->getClientMimeType(),
                'size' => $request->file('attachment')->getSize(),
            ]);

            $path = $request->file('attachment')->store('ticket_attachments', 'public');
            $msg->attachment = $path;
        }

        $msg->save();

        $ticket->last_message_at = now();
        if ($user->isAdmin()) {
            $ticket->status = 'open';
        }
        $ticket->save();

        if ($request->wantsJson() || $request->ajax()) {
            $attachmentUrl = null;
            if ($msg->attachment) {
                try {
                    $attachmentUrl = Storage::url($msg->attachment);
                } catch (\Throwable $e) {
                    $attachmentUrl = asset('storage/' . ltrim($msg->attachment, '/'));
                }
            }

            return response()->json([
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_id' => $msg->sender_id,
                'sender_name' => $user->name,
                'body' => $msg->body,
                'created_at' => $msg->created_at->toDateTimeString(),
                'attachment' => $attachmentUrl,
            ], 201);
        }

        return redirect()->route('tickets.show', $ticket->id)->with('success', __('tickets.message_saved'));
    }
}
