@extends('layouts.dashboard')

@section('title', __('tickets.show.title'))
@section('page-title', '<span class="text-slate-900">' . e($ticket->subject) . '</span>')

@section('content')
<div class="mx-auto max-w-3xl rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
        <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">{{ __('tickets.status_label', ['status' => $ticket->status]) }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ __('tickets.show.last_activity', ['time' => $ticket->last_message_at ? $ticket->last_message_at->diffForHumans() : $ticket->created_at->diffForHumans()]) }}</p>
    </div>

    @if($ticket->status === 'closed')
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800">
            {{ __('tickets.show.closed_notice') }}
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

    @if($ticket->status !== 'closed')
        <form id="messageForm" method="POST" action="{{ route('tickets.message', $ticket->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
                <textarea id="messageInput" name="message" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white p-3 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="{{ __('tickets.show.message_placeholder') }}"></textarea>
            @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('tickets.create.attachment_label') }}</label>
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <input id="attachmentInput" type="file" name="attachment" accept="image/*" class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:bg-slate-100" />
                <div id="attachmentFileName" class="mt-2 text-sm font-medium text-slate-700">{{ __('tickets.create.no_file_selected') }}</div>
                <div id="attachmentPreviewContainer" class="mt-3 hidden rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('tickets.create.preview') }}</span>
                        <button type="button" id="attachmentRemoveButton" class="rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-red-700">{{ __('common.delete') }}</button>
                    </div>
                    <img id="attachmentPreview" src="" class="max-h-40 w-full rounded object-contain"/>
                </div>
            </div>
            @error('attachment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('tickets.index') }}" class="text-sm text-slate-500">{{ __('tickets.back') }}</a>
            <button id="sendButton" class="rounded-2xl bg-indigo-600 px-4 py-2 text-white transition hover:bg-indigo-700">{{ __('tickets.show.send_button') }}</button>
        </div>
    </form>
    @else
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('tickets.index') }}" class="text-sm text-slate-500">{{ __('tickets.back') }}</a>
        </div>
    @endif

        @if($ticket->status !== 'closed')
            <form method="POST" action="{{ route('tickets.close', $ticket->id) }}" class="mt-4">
                @csrf
                <button class="text-sm text-red-600">{{ __('tickets.show.close_button') }}</button>
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
                const attachmentFileNameEl = document.getElementById('attachmentFileName');
                const attachmentRemoveButtonEl = document.getElementById('attachmentRemoveButton');
                let attachmentPreviewUrl = null;

                function resetAttachmentPreview() {
                    if (attachmentPreviewUrl) {
                        URL.revokeObjectURL(attachmentPreviewUrl);
                    }
                    attachmentPreviewUrl = null;
                    attachmentPreviewEl.src = '';
                    attachmentPreviewContainerEl.classList.add('hidden');
                    attachmentFileNameEl.textContent = '{{ __('tickets.create.no_file_selected') }}';
                    attachmentInputEl.value = '';
                }

                if (attachmentInputEl) {
                    attachmentInputEl.addEventListener('change', function(){
                        const file = this.files && this.files[0];
                        if (file && file.type.startsWith('image/')) {
                            if (attachmentPreviewUrl) {
                                URL.revokeObjectURL(attachmentPreviewUrl);
                            }
                            attachmentPreviewUrl = URL.createObjectURL(file);
                            attachmentPreviewEl.src = attachmentPreviewUrl;
                            attachmentPreviewContainerEl.classList.remove('hidden');
                            attachmentFileNameEl.textContent = file.name;
                        } else {
                            resetAttachmentPreview();
                        }
                    });
                }

                if (attachmentRemoveButtonEl) {
                    attachmentRemoveButtonEl.addEventListener('click', function(){
                        resetAttachmentPreview();
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
                                    attachmentFileNameEl.textContent = '{{ __('tickets.create.no_file_selected') }}';
                                }
                            }
                            fetchMessages();
                        } else {
                            const txt = await res.text();
                            console.error('Send failed', res.status, txt);
                            alert('{{ __('tickets.show.send_failed') }}');
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
