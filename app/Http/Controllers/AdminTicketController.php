<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        $tickets = Ticket::orderByDesc('last_message_at')->get();
        return view('admin_tickets_index', compact('tickets'));
    }

    public function show(Ticket $ticket)
    {
        $messages = $ticket->messages()->get();
        return view('admin_tickets_show', compact('ticket', 'messages'));
    }

    // JSON endpoint for admin to fetch messages (used by AJAX polling)
    public function messages(Ticket $ticket)
    {
        $messages = $ticket->messages()->get()->map(function ($m) {
            $attachmentUrl = null;
            if ($m->attachment) {
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

    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate(['message' => 'required_without:attachment|string','attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:5120']);

        $msg = new TicketMessage();
        $msg->ticket_id = $ticket->id;
        $msg->sender_type = 'admin';
        $msg->sender_id = $request->user()->id;
        $msg->body = $request->input('message');

        if ($request->hasFile('attachment')) {
            \Illuminate\Support\Facades\Log::info('AdminTicketController::reply - uploading attachment', [
                'original_name' => $request->file('attachment')->getClientOriginalName(),
                'mime' => $request->file('attachment')->getClientMimeType(),
                'size' => $request->file('attachment')->getSize(),
            ]);

            $path = $request->file('attachment')->store('ticket_attachments', 'public');
            $msg->attachment = $path;
        }

        $msg->save();

        $ticket->last_message_at = now();
        $ticket->status = 'open';
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
                'sender_name' => $request->user()->name,
                'body' => $msg->body,
                'attachment' => $attachmentUrl,
                'created_at' => $msg->created_at->toDateTimeString(),
            ], 201);
        }

        return redirect()->route('admin.tickets.show', $ticket->id)->with('success', 'پاسخ ذخیره شد');
    }

    public function close(Ticket $ticket)
    {
        $ticket->status = 'closed';
        $ticket->save();

        return redirect()->route('admin.tickets.show', $ticket->id)->with('success', 'تیکت بسته شد');
    }
}
