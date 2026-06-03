<?php
/**
 * BUSure Chat - Admin Footer
 * ✅ Reusable footer for all admin pages
 */
?>
    </div><!-- Close .main-content -->
</div><!-- Close .admin-container -->

<style>
    .admin-footer {
        background: #ffffff;
        border-top: 1px solid #E2E8F0;
        padding: 1rem 1.5rem;
        text-align: center;
        font-size: 0.8rem;
        color: #718096;
        margin-top: auto;
    }

    .admin-footer .footer-links {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-bottom: 0.5rem;
        flex-wrap: wrap;
    }

    .admin-footer .footer-links a {
        color: #718096;
        text-decoration: none;
        transition: color 0.2s;
    }

    .admin-footer .footer-links a:hover {
        color: #128C7E;
    }

    .admin-footer .footer-copyright {
        font-size: 0.75rem;
        opacity: 0.7;
    }

    .admin-footer .footer-copyright span {
        color: #128C7E;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .admin-footer {
            padding: 0.75rem 1rem;
        }

        .admin-footer .footer-links {
            gap: 1rem;
            font-size: 0.75rem;
        }
    }
</style>

<footer class="admin-footer">
    <div class="footer-links">
        <a href="dashboard">Dashboard</a>
        <a href="users">Users</a>
        <a href="settings">Settings</a>
        <a href="../../chat/chats">Chat App</a>
        <a href="../../auth/logout">Logout</a>
    </div>
    <div class="footer-copyright">
        &copy; <?= date('Y') ?> <span>BISureChat</span> — Admin Panel v1.0.0
    </div>
</footer>

<script>
    // Close modals on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                if (modal.style.display === 'flex' || modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
        }
    });

    // Close modals on outside click
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
</script>
</body>
</html>