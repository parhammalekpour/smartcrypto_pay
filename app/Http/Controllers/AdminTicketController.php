<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;

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

    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate(['message' => 'required|string']);

        $msg = new TicketMessage();
        $msg->ticket_id = $ticket->id;
        $msg->sender_type = 'admin';
        $msg->sender_id = $request->user()->id;
        $msg->body = $request->input('message');
        $msg->save();

        $ticket->last_message_at = now();
        $ticket->status = 'open';
        $ticket->save();

        return redirect()->route('admin.tickets.show', $ticket->id)->with('success', 'پاسخ ذخیره شد');
    }

    public function close(Ticket $ticket)
    {
        $ticket->status = 'closed';
        $ticket->save();

        return redirect()->route('admin.tickets.show', $ticket->id)->with('success', 'تیکت بسته شد');
    }
}
