/* Page script for login */
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.password-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i') || this;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye', 'feather-eye');
                icon.classList.add('fa-eye-slash', 'feather-eye-off');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash', 'feather-eye-off');
                icon.classList.add('fa-eye', 'feather-eye');
            }
        });
    });
});
