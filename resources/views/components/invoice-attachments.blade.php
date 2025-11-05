<div id="attachments-section" class="space-y-4">
    <div class="flex justify-between items-center">
        <label class="block text-sm font-semibold text-navy-900">Attachments (Optional)</label>
        <button type="button" onclick="document.getElementById('attachment-input').click()" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
            + Add Files
        </button>
    </div>
    
    <input type="file" id="attachment-input" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" class="hidden" onchange="displaySelectedFiles()">
    
    <!-- Existing Attachments (for edit mode) -->
    @if(isset($attachments) && $attachments->count() > 0)
        <div class="space-y-2">
            <p class="text-sm font-medium text-gray-700">Existing Attachments:</p>
            @foreach($attachments as $attachment)
                <div class="flex items-center justify-between p-2 border border-gray-300 rounded bg-white">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $attachment->file_name }}</p>
                            <p class="text-xs text-gray-500">{{ $attachment->formatted_size }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ Storage::url($attachment->file_path) }}" target="_blank" class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs">
                            Download
                        </a>
                        <button type="button" onclick="markForDeletion({{ $attachment->id }}, this)" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs">
                            Delete
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    
    <!-- New Files Preview -->
    <div id="new-files-preview" class="space-y-2"></div>
    
    <p class="text-xs text-gray-600 italic">
        Allowed file types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF (Max 10MB per file)
    </p>
</div>

<script>
function displaySelectedFiles() {
    const input = document.getElementById('attachment-input');
    const preview = document.getElementById('new-files-preview');
    preview.innerHTML = '';
    
    if (input.files.length > 0) {
        const title = document.createElement('p');
        title.className = 'text-sm font-medium text-gray-700';
        title.textContent = 'New Files to Upload:';
        preview.appendChild(title);
        
        Array.from(input.files).forEach((file, index) => {
            const fileDiv = document.createElement('div');
            fileDiv.className = 'flex items-center justify-between p-2 border border-blue-300 rounded bg-blue-50';
            
            const fileSize = file.size < 1024 * 1024 
                ? (file.size / 1024).toFixed(2) + ' KB'
                : (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            
            fileDiv.innerHTML = `
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-gray-900">${file.name}</p>
                        <p class="text-xs text-gray-500">${fileSize}</p>
                    </div>
                </div>
                <button type="button" onclick="removeFile(${index})" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs">
                    Remove
                </button>
            `;
            
            preview.appendChild(fileDiv);
        });
    }
}

function removeFile(index) {
    const input = document.getElementById('attachment-input');
    const dt = new DataTransfer();
    
    Array.from(input.files).forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });
    
    input.files = dt.files;
    displaySelectedFiles();
}

function markForDeletion(attachmentId, button) {
    if (confirm('Are you sure you want to delete this attachment?')) {
        // Add hidden input to mark for deletion
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_attachments[]';
        input.value = attachmentId;
        document.querySelector('form').appendChild(input);
        
        // Hide the attachment div
        button.closest('.flex').style.display = 'none';
        
        // Show undo option
        const parentDiv = button.closest('.flex').parentElement;
        const undoDiv = document.createElement('div');
        undoDiv.className = 'p-2 border border-yellow-300 rounded bg-yellow-50 text-sm';
        undoDiv.innerHTML = `
            <span class="text-yellow-800">Marked for deletion</span>
            <button type="button" onclick="undoDeletion(${attachmentId}, this)" class="ml-2 text-blue-600 underline">Undo</button>
        `;
        parentDiv.appendChild(undoDiv);
    }
}

function undoDeletion(attachmentId, button) {
    // Remove the hidden input
    const inputs = document.querySelectorAll(`input[name="delete_attachments[]"][value="${attachmentId}"]`);
    inputs.forEach(input => input.remove());
    
    // Show the attachment div again
    const undoDiv = button.closest('.p-2');
    const parentDiv = undoDiv.parentElement;
    const attachmentDiv = parentDiv.querySelector('.flex[style*="display: none"]');
    if (attachmentDiv) {
        attachmentDiv.style.display = '';
    }
    
    // Remove undo div
    undoDiv.remove();
}
</script>
