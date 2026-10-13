
document.querySelectorAll('form.needs-validation').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    var pw = form.querySelector('[name="password"]');
    var again = form.querySelector('[name="confirm"]');
    if (pw && again) {
      again.setCustomValidity(pw.value === again.value ? '' : 'Passwords do not match');
    }
    if (!form.checkValidity()) {
      event.preventDefault();
    }
    form.classList.add('was-validated');
  });
});

document.querySelectorAll('form.borrow-form').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var card = form.closest('.book-card');
    fetch('books.php', {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'fetch' }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.ok) {
          card.querySelector('.avail').textContent = data.available;
          card.querySelector('.book-action').innerHTML =
            '<span class="badge bg-primary">Borrowed</span> <small class="text-muted">due ' + data.due_on + '</small>';
        } else {
          alert(data.message);
        }
      })
      .catch(function () { alert('Could not borrow the book. Please try again.'); });
  });
});

document.querySelectorAll('form.return-form').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var row = form.closest('tr');
    fetch('loans.php', {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'fetch' }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.ok) {
          row.querySelector('.status-cell').innerHTML = '<span class="badge bg-secondary">Returned</span>';
          row.querySelector('.returned-cell').textContent = data.returned_on;
          form.remove();
        } else {
          alert(data.message);
        }
      })
      .catch(function () { alert('Could not record the return. Please try again.'); });
  });
});

document.querySelectorAll('form.confirm-delete').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    if (!window.confirm(form.dataset.message || 'Are you sure?')) {
      event.preventDefault();
    }
  });
});

document.querySelectorAll('input.phone-input').forEach(function (input) {
  input.addEventListener('input', function () {
    input.value = input.value.replace(/[^0-9]/g, '').slice(0, 11);
  });
});

if (window.bootstrap) {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
  });
}
