{{-- Shared helper: warns (non-blocking) when a task title already exists for the selected customer.
     Usage: call checkTaskTitleDuplicate(titleInputEl, warnEl, customerId, projectId) on title/customer/project change. --}}
<script>
if (typeof checkTaskTitleDuplicate !== 'function') {
    var _dupCheckTimers = new WeakMap();
    function checkTaskTitleDuplicate(titleEl, warnEl, customerId, projectId) {
        clearTimeout(_dupCheckTimers.get(titleEl));
        const title = titleEl.value.trim();
        if (!title || (!customerId && !projectId)) {
            warnEl.style.display = 'none';
            return;
        }
        const timer = setTimeout(async () => {
            const params = new URLSearchParams({ title });
            if (customerId) params.set('customer_id', customerId);
            if (projectId) params.set('project_id', projectId);
            try {
                const res  = await fetch(`{{ route('admin.tasks.check-duplicate-title') }}?${params}`);
                const data = await res.json();
                if (data.duplicate) {
                    warnEl.textContent = `⚠ A task named "${title}" already exists for this customer (${data.count}${data.count >= 5 ? '+' : ''}).`;
                    warnEl.style.display = 'block';
                } else {
                    warnEl.style.display = 'none';
                }
            } catch (e) {
                warnEl.style.display = 'none';
            }
        }, 450);
        _dupCheckTimers.set(titleEl, timer);
    }
}
</script>
