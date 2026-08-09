@extends('layouts.admin')

@section('title', __('admin_tickets.title'))
@section('page-title', '<span class="text-slate-900">' . e($ticket->subject) . '</span>')

@section('content')
<div class="mx-auto max-w-3xl rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
        <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">{{ __('admin_tickets.requester_label', ['name' => $ticket->user ? $ticket->user->name : ($ticket->merchant ? $ticket->merchant->name : __('admin_tickets.unknown_requester'))]) }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ __('admin_tickets.status_label', ['status' => $ticket->status]) }}</p>
    </div>

    @if($ticket->status === 'closed')
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800">
            {{ __('admin_tickets.closed_notice') }}
        </div>
    @endif

    <div id="messages" class="mb-6 space-y-4">
        @foreach($messages as $m)
            <div data-message-id="{{ $m->id }}" class="rounded-2xl p-4 @if($m->sender_type === 'admin') bg-indigo-50 @else bg-slate-100 @endif">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">{{ ucfirst($m->sender_type) }} @if($m->sender_id) — {{ \App\Models\User::find($m->sender_id)->name ?? '' }} @endif</p>
                <p class="mt-2 text-sm leading-6 text-slate-800">{{ $m->body }}</p>
                <p class="mt-2 text-xs text-slate-400">{{ $m->created_at->diffForHumans() }}</p>
            </div>
        @endforeach
    </div>

        <form id="replyForm" method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
                <textarea id="replyMessage" name="message" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white p-3 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="{{ __('admin_tickets.reply_placeholder') }}"></textarea>
            @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('admin_tickets.attachment_label') }}</label>
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <input id="replyAttachment" type="file" name="attachment" accept="image/*" class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:bg-slate-100" />
                <div id="replyAttachmentFileName" class="mt-2 text-sm font-medium text-slate-700">{{ __('admin_tickets.no_file_selected') }}</div>
                <div id="replyAttachmentPreviewContainer" class="mt-3 hidden rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('admin_tickets.preview') }}</span>
                        <button type="button" id="replyAttachmentRemoveButton" class="rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-red-700">{{ __('common.delete') }}</button>
                    </div>
                    <img id="replyAttachmentPreview" src="" class="max-h-40 w-full rounded object-contain"/>
                </div>
            </div>
            @error('attachment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.tickets.index') }}" class="text-sm text-slate-500">{{ __('admin_tickets.back') }}</a>
            <div class="flex gap-2">
                <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2 text-white transition hover:bg-indigo-700">{{ __('admin_tickets.send_reply') }}</button>
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
                                        `<p class="mt-2 text-sm text-gray-800">${m.body ? m.body : ''}</p>` +
                                        `${m.attachment ? '<div class="mt-2"><img src="'+m.attachment+'" class="max-w-md max-h-60 object-contain rounded"/></div>' : ''}` +
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

                // Preview selected attachment for admin reply
                const replyAttachmentInput = document.getElementById('replyAttachment');
                const replyAttachmentPreview = document.getElementById('replyAttachmentPreview');
                const replyAttachmentPreviewContainer = document.getElementById('replyAttachmentPreviewContainer');
                const replyAttachmentFileName = document.getElementById('replyAttachmentFileName');
                const replyAttachmentRemoveButton = document.getElementById('replyAttachmentRemoveButton');
                let replyAttachmentPreviewUrl = null;

                function resetReplyAttachmentPreview() {
                    if (replyAttachmentPreviewUrl) {
                        URL.revokeObjectURL(replyAttachmentPreviewUrl);
                    }
                    replyAttachmentPreviewUrl = null;
                    replyAttachmentPreview.src = '';
                    replyAttachmentPreviewContainer.classList.add('hidden');
                    replyAttachmentFileName.textContent = '{{ __('admin_tickets.no_file_selected') }}';
                    replyAttachmentInput.value = '';
                }

                if (replyAttachmentInput) {
                    replyAttachmentInput.addEventListener('change', function(){
                        const file = this.files && this.files[0];
                        if (file && file.type.startsWith('image/')) {
                            if (replyAttachmentPreviewUrl) {
                                URL.revokeObjectURL(replyAttachmentPreviewUrl);
                            }
                            replyAttachmentPreviewUrl = URL.createObjectURL(file);
                            replyAttachmentPreview.src = replyAttachmentPreviewUrl;
                            replyAttachmentPreviewContainer.classList.remove('hidden');
                            replyAttachmentFileName.textContent = file.name;
                        } else {
                            resetReplyAttachmentPreview();
                        }
                    });
                }

                if (replyAttachmentRemoveButton) {
                    replyAttachmentRemoveButton.addEventListener('click', function(){
                        resetReplyAttachmentPreview();
                    });
                }

                form.addEventListener('submit', async function(e){
                    e.preventDefault();
                    const bodyEl = document.getElementById('replyMessage');
                    const body = bodyEl.value.trim();
                    const fileInput = document.getElementById('replyAttachment');
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
                                if (replyAttachmentPreview) {
                                    replyAttachmentPreview.src = '';
                                    replyAttachmentPreviewContainer.classList.add('hidden');
                                    replyAttachmentFileName.textContent = '{{ __('admin_tickets.no_file_selected') }}';
                                }
                            }
                            fetchMessages();
                        } else {
                            const txt = await res.text();
                            console.error('Reply failed', res.status, txt);
                            alert('{{ __('admin_tickets.reply_failed') }}');
                        }
                    }catch(e){ console.error(e); }
                });

                // Initial fetch to ensure latest
                fetchMessages();
            })();
        </script>

    @if($ticket->status !== 'closed')
        <form method="POST" action="{{ route('admin.tickets.close', $ticket->id) }}" class="mt-4">
            @csrf
            <button class="text-sm text-red-600">{{ __('admin_tickets.close_ticket') }}</button>
        </form>
    @endif
</div>
@endsection
