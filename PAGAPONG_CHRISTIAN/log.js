document.getElementById('loginForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();

    const defaultEmail = "defaultadmin@admin.com";
    const defaultPassword = "123456"; // fixed (6 chars)

    if (!email || !password) {
        alert('Please fill in both fields.');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return;
    }

    if (password.length < 6) {
        alert('Password must be at least 6 characters long.');
        return;
    }

    if (email === defaultEmail && password === defaultPassword) {
        // client-side validation passed — submit form to server-side PHP handler
        document.getElementById('loginForm').submit();
    } else {
        alert('Invalid email or password.');
    }
});
