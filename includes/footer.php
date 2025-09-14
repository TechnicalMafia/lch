</div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('toggle-sidebar').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
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

        // Prevent sidebar toggle when clicking on content area
        document.getElementById('content').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Prevent sidebar toggle when clicking buttons
        document.querySelectorAll('button, a').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>