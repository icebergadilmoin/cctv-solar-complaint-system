// Handle worker deletion
document.querySelectorAll('.delete-worker').forEach(button => {
    button.addEventListener('click', function() {
        const workerId = this.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this worker?')) {
            fetch('delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${workerId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting worker');
                }
            });
        }
    });
});

// Handle manager deletion
document.querySelectorAll('.delete-manager').forEach(button => {
    button.addEventListener('click', function() {
        const managerId = this.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this manager?')) {
            fetch('delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${managerId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting manager');
                }
            });
        }
    });
});