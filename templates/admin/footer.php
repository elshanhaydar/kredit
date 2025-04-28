</div><!-- container-fluid end -->

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container-fluid">
            <div class="d-sm-flex justify-content-between align-items-center">
                <div class="text-muted">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Bütün hüquqlar qorunur.
                </div>
                <div class="text-muted">
                    Admin Panel v1.0
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>
    <script src="../assets/js/main.js"></script>

    <!-- DataTables Tərcümə -->
    <script>
        if ($.fn.dataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    url: '../assets/js/datatables-az.json'
                },
                pageLength: 25,
                responsive: true
            });
        }
    </script>

    <!-- Bildiriş yeniləmə -->
    <script>
        // Bildirişləri yoxlamaq
        function checkNotifications() {
            fetch('../api/notifications.php?action=get')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('notificationList');
                    if (container) {
                        container.innerHTML = '';

                        if (data.notifications.length === 0) {
                            container.innerHTML = '<div class="text-center py-3">Bildiriş yoxdur</div>';
                            return;
                        }

                        data.notifications.slice(0, 5).forEach(notification => {
                            container.innerHTML += `
                                <div class="notification-item ${notification.is_read ? '' : 'unread'}">
                                    <div class="notification-content">
                                        ${notification.message}
                                    </div>
                                    <div class="notification-time">
                                        ${new Date(notification.created_at).toLocaleString()}
                                    </div>
                                </div>
                            `;
                        });

                        // Oxunmamış bildiriş sayını yeniləyirik
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            if (data.unread > 0) {
                                badge.textContent = data.unread;
                                badge.style.display = 'inline';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    }
                });
        }

        // Bildirişləri yoxlamağı başladırıq
        if (document.getElementById('notificationList')) {
            checkNotifications();
            // Hər 5 dəqiqədən bir yoxlayırıq
            setInterval(checkNotifications, 300000);
        }
    </script>

</body>
</html>