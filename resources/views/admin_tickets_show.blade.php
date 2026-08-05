@extends('layouts.admin')

@section('title', 'تیکت (مدیریت)')
@section('page-title', $ticket->subject)

@section('content')
<div class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
    <div class="mb-6">
        <p class="text-xs text-gray-500">درخواست‌دهنده: {{ $ticket->user ? $ticket->user->name : ($ticket->merchant ? $ticket->merchant->name : 'ناشناخته') }}</p>
        <p class="text-xs text-gray-400">وضعیت: {{ $ticket->status }}</p>
    </div>

    <div id="messages" class="space-y-4 mb-6">
        @foreach($messages as $m)
                <div data-message-id="{{ $m->id }}" class="p-4 rounded-lg @if($m->sender_type === 'admin') bg-indigo-50 @else bg-gray-100 @endif">
                <p class="text-xs text-gray-600">{{ ucfirst($m->sender_type) }} @if($m->sender_id) — {{ \App\Models\User::find($m->sender_id)->name ?? '' }} @endif</p>
                <p class="mt-2 text-sm text-gray-800">{{ $m->body }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $m->created_at->diffForHumans() }}</p>
            </div>
        @endforeach
    </div>

        <form id="replyForm" method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}">
        @csrf
        <div class="mb-4">
                <textarea id="replyMessage" name="message" rows="4" class="w-full border p-3 rounded" placeholder="پاسخ خود را بنویسید"></textarea>
            @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-between items-center">
            <a href="{{ route('admin.tickets.index') }}" class="text-sm text-gray-500">بازگشت</a>
            <div class="flex gap-2">
                <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded">ارسال پاسخ</button>
            </div>
        </div>
    </form>

        <script>
            (function(){
                const ticketId = {{ $ticket->id }};
                const messagesEl = document.getElementById('messages');
                let lastMessageId = messagesEl.querySelector('[data-message-id]') ? parseInt(messagesEl.querySelector('[data-message-id]:last-child').getAttribute('data-message-id')) : 0;

                function renderMessages(list) {
                    messagesEl.innerHTML = '';
                    list.forEach(m => {
                        const div = document.createElement('div');
                        div.setAttribute('data-message-id', m.id);
                        div.className = 'p-4 rounded-lg ' + (m.sender_type === 'admin' ? 'bg-indigo-50' : 'bg-gray-100');
                        div.innerHTML = `<p class="text-xs text-gray-600">${m.sender_type}${m.sender_name ? ' — ' + m.sender_name : ''}</p>` +
                                        `<p class="mt-2 text-sm text-gray-800">${m.body}</p>` +
                                        `<p class="text-xs text-gray-400 mt-2">${m.created_at}</p>`;
                        messagesEl.appendChild(div);
                    });
                    // scroll to bottom
                    messagesEl.scrollTop = messagesEl.scrollHeight;
                    const last = messagesEl.querySelector('[data-message-id]:last-child');
                    if (last) lastMessageId = parseInt(last.getAttribute('data-message-id'));
                }

                async function fetchMessages(){
                    try{
                        const res = await fetch(`/admin/tickets/${ticketId}/messages`);
                        if (!res.ok) return;
                        const data = await res.json();
                        renderMessages(data);
                    }catch(e){ console.error(e); }
                }

                // Poll every 3s
                setInterval(fetchMessages, 3000);

                // Submit via AJAX
                const form = document.getElementById('replyForm');
                form.addEventListener('submit', async function(e){
                    e.preventDefault();
                    const body = document.getElementById('replyMessage').value.trim();
                    if (!body) return;
                    try{
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ message: body })
                        });
                        if (res.ok) {
                            document.getElementById('replyMessage').value = '';
                            fetchMessages();
                        } else {
                            console.error('Reply failed');
                        }
                    }catch(e){ console.error(e); }
                });

                // Initial fetch to ensure latest
                fetchMessages();
            })();
        </script>

    <form method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}" class="mt-4">
        @csrf
        <button class="text-sm text-red-600">بستن تیکت</button>
    </form>
</div>
@endsection
