@extends('layouts.dashboard')

@section('title', __('tickets.create.title'))
@section('page-title', __('tickets.create.page_title'))

@section('content')
<div class="mx-auto max-w-2xl rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('tickets.create.subject_label') }}</label>
            <input name="subject" class="w-full rounded-2xl border border-slate-300 bg-white p-3 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" value="{{ old('subject', request('subject')) }}" />
            @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('tickets.create.message_label') }}</label>
            <textarea name="message" class="w-full rounded-2xl border border-slate-300 bg-white p-3 text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" rows="6">{{ old('message', request('message')) }}</textarea>
            @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('tickets.create.attachment_label') }}</label>
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <input id="createAttachment" type="file" name="attachment" accept="image/*" class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white hover:bg-slate-100" />
                <div id="createAttachmentFileName" class="mt-2 text-sm font-medium text-slate-700">{{ __('tickets.create.no_file_selected') }}</div>
                <div id="createAttachmentPreviewContainer" class="mt-3 hidden rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('tickets.create.preview') }}</span>
                        <button type="button" id="createAttachmentRemoveButton" class="rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-red-700">{{ __('common.delete') }}</button>
                    </div>
                    <img id="createAttachmentPreview" src="" class="max-h-40 w-full rounded object-contain"/>
                </div>
            </div>
            @error('attachment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <button class="rounded-2xl bg-indigo-600 px-4 py-2 text-white transition hover:bg-indigo-700">{{ __('tickets.create.send_button') }}</button>
        </div>
    </form>
</div>

<script>
    (function(){
        const createInput = document.getElementById('createAttachment');
        const preview = document.getElementById('createAttachmentPreview');
        const previewContainer = document.getElementById('createAttachmentPreviewContainer');
        const fileName = document.getElementById('createAttachmentFileName');
        const removeButton = document.getElementById('createAttachmentRemoveButton');
        let previewUrl = null;

        function resetPreview() {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            previewUrl = null;
            preview.src = '';
            previewContainer.classList.add('hidden');
            fileName.textContent = '{{ __('tickets.create.no_file_selected') }}';
            createInput.value = '';
        }

        if (createInput) {
            createInput.addEventListener('change', function(){
                const file = this.files && this.files[0];
                if (file && file.type.startsWith('image/')) {
                    if (previewUrl) {
                        URL.revokeObjectURL(previewUrl);
                    }
                    previewUrl = URL.createObjectURL(file);
                    preview.src = previewUrl;
                    previewContainer.classList.remove('hidden');
                    fileName.textContent = file.name;
                } else {
                    resetPreview();
                }
            });
        }

        if (removeButton) {
            removeButton.addEventListener('click', function(){
                resetPreview();
            });
        }
    })();
</script>
@endsection
