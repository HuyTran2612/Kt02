<?php
// --- footer.php ---
?>
<!-- Content ends here -->
</main> <!-- Close main container -->

<footer class="footer mt-auto py-3 bg-light text-center border-top">
    <div class="container">
        <span class="text-muted">© <?php echo date("Y"); ?> - Phát triển bởi [Tên của bạn hoặc nhóm]</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Student Delete Confirmation
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const studentName = this.getAttribute('data-student-name') || 'này';
                if (confirm(`Bạn có chắc chắn muốn xóa sinh viên ${studentName} không?`)) {
                    window.location.href = this.href;
                }
            });
        });

        // Optional: Add confirmation for 'Hủy Đăng Ký' if desired
        const cancelRegButton = document.querySelector('a[href="dangky_xoahet.php"]');
        if(cancelRegButton) {
             cancelRegButton.addEventListener('click', function(event){
                 if (!confirm('Bạn có chắc muốn hủy toàn bộ đăng ký hiện tại (các học phần trong giỏ)?')) {
                    event.preventDefault();
                 }
             });
        }
    });
</script>
</body>
</html>