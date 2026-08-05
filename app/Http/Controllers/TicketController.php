<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;

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
            'message' => 'required|string',
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
        $msg->save();

        return redirect()->route('tickets.show', $ticket->id)->with('success', 'تیکت شما ارسال شد. تیم پشتیبانی به زودی پاسخ می‌دهد.');
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

    // JSON endpoint for ticket messages (user side)
    public function messages(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        if (!($user->isAdmin() || $ticket->user_id === $user->id || $ticket->merchant_id === $user->id)) {
            abort(403);
        }

        $messages = $ticket->messages()->get()->map(function ($m) {
            return [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender_id ? optional(\App\Models\User::find($m->sender_id))->name : null,
                'body' => $m->body,
                'created_at' => $m->created_at->toDateTimeString(),
            ];
        });

        return response()->json($messages);
    }

    public function postMessage(Request $request, Ticket $ticket)
    {
        $request->validate(['message' => 'required|string']);
        $user = $request->user();

        if (!($user->isAdmin() || $ticket->user_id === $user->id || $ticket->merchant_id === $user->id)) {
            abort(403);
        }

        $msg = new TicketMessage();
        $msg->ticket_id = $ticket->id;
        $msg->sender_type = $user->isAdmin() ? 'admin' : ($user->isMerchant() ? 'merchant' : 'user');
        $msg->sender_id = $user->id;
        $msg->body = $request->input('message');
        $msg->save();

        $ticket->last_message_at = now();
        if ($user->isAdmin()) {
            $ticket->status = 'open';
        }
        $ticket->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_id' => $msg->sender_id,
                'sender_name' => $user->name,
                'body' => $msg->body,
                'created_at' => $msg->created_at->toDateTimeString(),
            ], 201);
        }

        return redirect()->route('tickets.show', $ticket->id)->with('success', 'پیام ذخیره شد');
    }
}
