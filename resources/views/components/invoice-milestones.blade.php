<div id="milestones-section" class="space-y-4">
    <div class="flex justify-between items-center">
        <label class="block text-sm font-semibold text-navy-900">Invoice Milestones (Optional)</label>
        <button type="button" onclick="addMilestone()" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
            + Add Milestone
        </button>
    </div>
    
    <div id="milestones-container" class="space-y-3">
        @if(isset($milestones) && $milestones->count() > 0)
            @foreach($milestones as $index => $milestone)
                <div class="milestone-item border border-gray-300 rounded p-3 bg-gray-50">
                    <div class="flex gap-3 items-start">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Amount *</label>
                            <input type="number" name="milestones[{{ $index }}][amount]" value="{{ $milestone->amount }}" required step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded milestone-amount" onchange="updateTotalFromMilestones()">
                        </div>
                        <div class="flex-[2]">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" name="milestones[{{ $index }}][description]" value="{{ $milestone->description }}" placeholder="e.g., Initial payment, Milestone 1" class="w-full px-3 py-2 border border-gray-300 rounded">
                        </div>
                        <button type="button" onclick="removeMilestone(this)" class="mt-6 px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                            Remove
                        </button>
                    </div>
                </div>
            @endforeach
        @else
            <!-- Default single milestone -->
            <div class="milestone-item border border-gray-300 rounded p-3 bg-gray-50">
                <div class="flex gap-3 items-start">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Amount *</label>
                        <input type="number" name="milestones[0][amount]" value="{{ old('milestones.0.amount', $invoice->amount ?? '') }}" required step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded milestone-amount" onchange="updateTotalFromMilestones()">
                    </div>
                    <div class="flex-[2]">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                        <input type="text" name="milestones[0][description]" value="{{ old('milestones.0.description') }}" placeholder="e.g., Full payment" class="w-full px-3 py-2 border border-gray-300 rounded">
                    </div>
                    <button type="button" onclick="removeMilestone(this)" class="mt-6 px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                        Remove
                    </button>
                </div>
            </div>
        @endif
    </div>
    
    <p class="text-xs text-gray-600 italic">
        Note: Milestones allow you to break down the invoice into multiple payments with descriptions. The total amount will be automatically calculated from all milestones.
    </p>
</div>

<script>
let milestoneCount = {{ isset($milestones) ? $milestones->count() : 1 }};

function addMilestone() {
    const container = document.getElementById('milestones-container');
    const newMilestone = document.createElement('div');
    newMilestone.className = 'milestone-item border border-gray-300 rounded p-3 bg-gray-50';
    newMilestone.innerHTML = `
        <div class="flex gap-3 items-start">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-700 mb-1">Amount *</label>
                <input type="number" name="milestones[${milestoneCount}][amount]" required step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded milestone-amount" onchange="updateTotalFromMilestones()">
            </div>
            <div class="flex-[2]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="milestones[${milestoneCount}][description]" placeholder="e.g., Milestone ${milestoneCount + 1}" class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <button type="button" onclick="removeMilestone(this)" class="mt-6 px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                Remove
            </button>
        </div>
    `;
    container.appendChild(newMilestone);
    milestoneCount++;
}

function removeMilestone(button) {
    const container = document.getElementById('milestones-container');
    const milestones = container.getElementsByClassName('milestone-item');
    
    // Keep at least one milestone
    if (milestones.length > 1) {
        button.closest('.milestone-item').remove();
        updateTotalFromMilestones();
    } else {
        alert('You must have at least one milestone/amount');
    }
}

function updateTotalFromMilestones() {
    const milestoneAmounts = document.querySelectorAll('.milestone-amount');
    let total = 0;
    
    milestoneAmounts.forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
    });
    
    // Update the main amount field
    const amountField = document.getElementById('amount');
    if (amountField) {
        amountField.value = total.toFixed(2);
        
        // Trigger any existing change handlers
        if (typeof calculateRemainingAmount === 'function') {
            calculateRemainingAmount();
        }
    }
}
</script>
