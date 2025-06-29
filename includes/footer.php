    </div>
    <footer class="bg-dark text-white text-center p-3 mt-5">
        <p>&copy; <?php echo date('Y'); ?> Complaint Management System</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>

    <script>
// Check for new messages every 30 seconds
setInterval(() => {
    fetch('check_messages.php')
        .then(response => response.json())
        .then(data => {
            if (data.unread > 0) {
                document.getElementById('message-notification').innerText = data.unread;
                document.getElementById('message-notification').classList.remove('d-none');
                
                // Play notification sound
                if (data.unread > 0 && typeof Audio !== 'undefined') {
                    new Audio('notification.mp3').play().catch(e => console.log(e));
                }
            } else {
                document.getElementById('message-notification').classList.add('d-none');
            }
        });
}, 30000);
</script>
</body>
</html>