function validateLogin() {
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    if (!email.value || !password.value) {
        alert('Please fill in email and password.');
        return false;
    }
    return true;
}

function validateRegistration() {
    var fullName = document.getElementById('full_name');
    var email = document.getElementById('email');
    var age = document.getElementById('age');
    var gender = document.getElementById('gender');
    var password = document.getElementById('password');
    var confirm = document.getElementById('confirm_password');
    if (!fullName.value || !email.value || !age.value || !gender.value || !password.value || !confirm.value) {
        alert('Please complete all required fields.');
        return false;
    }
    if (password.value !== confirm.value) {
        alert('Passwords do not match.');
        return false;
    }
    if (password.value.length < 6) {
        alert('Password must be at least 6 characters.');
        return false;
    }
    return true;
}

function validateAppointment() {
    var doctor = document.querySelector('select[name="doctor_id"]');
    var date = document.querySelector('input[name="appointment_date"]');
    var time = document.querySelector('input[name="appointment_time"]');
    var reason = document.querySelector('textarea[name="reason"]');
    if (!doctor.value || !date.value || !time.value || !reason.value.trim()) {
        alert('Please fill out every appointment field.');
        return false;
    }
    return true;
}

function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this record?');
}
