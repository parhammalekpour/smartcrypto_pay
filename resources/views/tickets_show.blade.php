@extends('layouts.dashboard')

@section('title', 'تیکت')
@section('page-title', $ticket->subject)

@section('content')
<div class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
    <div class="mb-6">
        <p class="text-xs text-gray-500">وضعیت: {{ $ticket->status }}</p>
        <p class="text-sm text-gray-400">آخرین فعالیت: {{ $ticket->last_message_at ? $ticket->last_message_at->diffForHumans() : $ticket->created_at->diffForHumans() }}</p>
    </div>

    @if($ticket->status === 'closed')
        <div class="p-4 mb-6 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800">
            این تیکت بسته شده است و ارسال پیام جدید امکان‌پذیر نیست.
        </div>
    @endif

    <div id="messages" class="space-y-4 mb-6">
        @foreach($messages as $m)
                <div data-message-id="{{ $m->id }}" class="p-4 rounded-lg @if($m->sender_type === 'admin') bg-indigo-50 @else bg-gray-100 @endif">
                <p class="text-xs text-gray-600">{{ ucfirst($m->sender_type) }} @if($m->sender_id) — {{ \App\Models\User::find($m->sender_id)->name ?? '' }} @endif</p>
                <p class="mt-2 text-sm text-gray-800">{{ $m->body }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $m->created_at->diffForHumans() }}</p>
            </div>
        @endforeach
    </div>

    @if($ticket->status !== 'closed')
        <form id="messageForm" method="POST" action="{{ route('tickets.message', $ticket->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
                <textarea id="messageInput" name="message" rows="4" class="w-full border p-3 rounded" placeholder="پیام خود را بنویسید"></textarea>
            @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">ضمیمه (تصویر)</label>
            <div class="flex items-start gap-4">
                <input id="attachmentInput" type="file" name="attachment" accept="image/*" class="w-full max-w-xs" />
                <div id="attachmentPreviewContainer" class="hidden">
                    <img id="attachmentPreview" src="" class="max-w-xs max-h-40 object-contain rounded"/>
                </div>
            </div>
            @error('attachment') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-between items-center">
            <a href="{{ route('tickets.index') }}" class="text-sm text-gray-500">بازگشت</a>
                <button id="sendButton" class="bg-indigo-600 text-white py-2 px-4 rounded">ارسال پیام</button>
        </div>
    </form>
    @else
        <div class="flex justify-between items-center mb-4">
            <a href="{{ route('tickets.index') }}" class="text-sm text-gray-500">بازگشت</a>
        </div>
    @endif

        @if($ticket->status !== 'closed')
            <form method="POST" action="{{ route('tickets.close', $ticket->id) }}" class="mt-4">
                @csrf
                <button class="text-sm text-red-600">بستن تیکت</button>
            </form>
        @endif

            <script>
            (function(){
                const ticketId = {{ $ticket->id }};
                const messagesEl = document.getElementById('messages');

                function renderMessages(list) {
                    messagesEl.innerHTML = '';
                    list.forEach(m => {
                        const div = document.createElement('div');
                        div.setAttribute('data-message-id', m.id);
                        div.className = 'p-4 rounded-lg ' + (m.sender_type === 'admin' ? 'bg-indigo-50' : 'bg-gray-100');
                        div.innerHTML = `<p class="text-xs text-gray-600">${m.sender_type}${m.sender_name ? ' — ' + m.sender_name : ''}</p>` +
                                        `<p class="mt-2 text-sm text-gray-800">${m.body ? m.body : ''}</p>` +
                                        `${m.attachment ? '<div class="mt-2"><img src="'+m.attachment+'" class="max-w-md max-h-60 object-contain rounded"/></div>' : ''}` +
                                        `<p class="text-xs text-gray-400 mt-2">${m.created_at}</p>`;
                        messagesEl.appendChild(div);
                    });
                    messagesEl.scrollTop = messagesEl.scrollHeight;
                }

                async function fetchMessages(){
                    try{
                        const res = await fetch(`/tickets/${ticketId}/messages`);
                        if (!res.ok) return;
                        const data = await res.json();
                        renderMessages(data);
                    }catch(e){ console.error(e); }
                }

                // Poll every 3s
                setInterval(fetchMessages, 3000);

                // Submit via AJAX
                const form = document.getElementById('messageForm');

                // Preview selected attachment
                const attachmentInputEl = document.getElementById('attachmentInput');
                const attachmentPreviewEl = document.getElementById('attachmentPreview');
                const attachmentPreviewContainerEl = document.getElementById('attachmentPreviewContainer');
                if (attachmentInputEl) {
                    attachmentInputEl.addEventListener('change', function(){
                        const file = this.files && this.files[0];
                        if (file && file.type.startsWith('image/')) {
                            const url = URL.createObjectURL(file);
                            attachmentPreviewEl.src = url;
                            attachmentPreviewContainerEl.classList.remove('hidden');
                        } else {
                            attachmentPreviewEl.src = '';
                            attachmentPreviewContainerEl.classList.add('hidden');
                        }
                    });
                }

                if (form) {
                    form.addEventListener('submit', async function(e){
                    e.preventDefault();
                    const bodyEl = document.getElementById('messageInput');
                    const body = bodyEl.value.trim();
                    const fileInput = document.getElementById('attachmentInput');
                    const hasFile = fileInput && fileInput.files.length > 0;
                    // allow sending when either a message or an attachment is present
                    if (!body && !hasFile) return;
                    try{
                        const formData = new FormData();
                        formData.append('message', body);
                        if (hasFile) {
                            formData.append('attachment', fileInput.files[0]);
                        }

                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        });
                        if (res.ok) {
                            bodyEl.value = '';
                            if (hasFile) {
                                fileInput.value = '';
                                if (attachmentPreviewEl) {
                                    attachmentPreviewEl.src = '';
                                    attachmentPreviewContainerEl.classList.add('hidden');
                                }
                            }
                            fetchMessages();
                        } else {
                            const txt = await res.text();
                            console.error('Send failed', res.status, txt);
                            alert('ارسال پیام ناموفق بود. لطفاً دوباره تلاش کنید.');
                        }
                    }catch(e){ console.error(e); }
                });
                }

                // Initial fetch
                fetchMessages();
            })();
        </script>
</div>
@endsection
