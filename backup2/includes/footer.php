            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('content').classList.toggle('expanded');
        });
        
        // Toggle submenu
        function toggleSubmenu(id) {
            const submenu = document.getElementById(id);
            submenu.classList.toggle('hidden');
        }
        
        // Auto-hide notifications after 5 seconds
        setTimeout(function() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(function(notification) {
                notification.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>