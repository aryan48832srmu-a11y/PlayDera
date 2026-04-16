function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function showToast(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };
    const toast = document.createElement('div');
    toast.className = `toast ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg mb-2`;
    toast.textContent = message;
    
    const container = document.getElementById('toast-container');
    if (container) {
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
}

function updateCountdowns() {
    const now = new Date().getTime() / 1000;
    document.querySelectorAll('.countdown').forEach(el => {
        const deadline = new Date(el.dataset.deadline).getTime() / 1000;
        const diff = deadline - now;
        if (diff > 0) {
            const hours = Math.floor(diff / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = Math.floor(diff % 60);
            el.textContent = `${hours}h ${minutes}m ${seconds}s`;
        } else {
            el.textContent = 'Overdue!';
            el.classList.add('text-red-400');
        }
    });
}

if (document.querySelectorAll('.countdown').length > 0) {
    setInterval(updateCountdowns, 1000);
    updateCountdowns();
}

document.querySelectorAll('form[data-ajax]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(() => {
            showToast('An error occurred', 'error');
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});